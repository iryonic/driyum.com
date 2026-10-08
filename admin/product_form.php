<?php
require_once '../config/database.php';
require_once '../includes/functions.php';

// AJAX DELETE GALLERY IMAGES
if (isset($_POST['ajax_action']) && $_POST['ajax_action'] === 'delete_gallery') {
    $ids = $_POST['ids'] ?? [];
    if (!empty($ids)) {
        $conn = get_db_connection();
        $ids_str = implode(',', array_map('intval', $ids));
        
        // Fetch paths to delete files
        $res = $conn->query("SELECT image_path FROM product_images WHERE id IN ($ids_str)");
        while ($row = $res->fetch_assoc()) {
            $full_path = '../' . $row['image_path'];
            if (file_exists($full_path)) unlink($full_path);
        }

        $conn->query("DELETE FROM product_images WHERE id IN ($ids_str)");
        header('Content-Type: application/json');
        echo json_encode(['success' => true]);
        exit;
    }
}
?>
<?php include 'includes/header.php'; ?>
<?php
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$msg = '';
$err = '';

// DELETE GALLERY IMAGE (Fallback / Legacy - technically not needed if AJAX is used)
if (isset($_GET['del_img'])) {
    $img_id = (int)$_GET['del_img'];
    $conn = get_db_connection();
    $conn->query("DELETE FROM product_images WHERE id = $img_id");
    echo "<script>window.location='product_form.php?id=$id';</script>";
    exit;
}

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $conn = get_db_connection();
    
    $name = $_POST['name'];
    $desc = $_POST['description'];
    $price = (float)$_POST['price'];
    $orig_price = (float)$_POST['original_price'];
    $stock = (int)$_POST['stock'];
    $cat_id = (int)$_POST['category_id'];
    $active = (int)$_POST['is_active'];
    $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $name)));
    $is_featured = isset($_POST['is_featured']) ? (int)$_POST['is_featured'] : 0;
    $is_combo = isset($_POST['is_combo']) ? (int)$_POST['is_combo'] : ($cat_id === 3 ? 1 : 0);
    if ($cat_id === 3 && !isset($_POST['is_combo'])) $is_combo = 1;
    $bg_color = $_POST['bg_color'] ?? '';


    // 1. Featured Image
    $image_path = $_POST['current_image'] ?? '';
    if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
        $upload_dir = '../assets/images/products/';
        if (!file_exists($upload_dir)) mkdir($upload_dir, 0777, true);
        
        $ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
        $filename = uniqid('prod_') . '.' . $ext;
        
        if (move_uploaded_file($_FILES['image']['tmp_name'], $upload_dir . $filename)) {
            $image_path = 'assets/images/products/' . $filename;
            // Optimize the uploaded image
            if (function_exists('optimize_image')) {
                $optimized = optimize_image($upload_dir . $filename, $upload_dir . $filename);
                if ($optimized) $image_path = $optimized;
            }
        }
    }

    $ingredients = $_POST['ingredients'];
    $nutrition = $_POST['nutritional_info'];
    $weight = $_POST['weight'] ?? '0.500';
    
    // SAVE PRODUCT META
    if ($id > 0) {
        $sql = "UPDATE products SET category_id=?, name=?, description=?, ingredients=?, nutritional_info=?, price=?, original_price=?, stock=?, is_active=?, is_featured=?, is_combo=?, image=?, bg_color=?, weight=? WHERE id=?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("issssddiiiisssi", $cat_id, $name, $desc, $ingredients, $nutrition, $price, $orig_price, $stock, $active, $is_featured, $is_combo, $image_path, $bg_color, $weight, $id);
    } else {
        $sql = "INSERT INTO products (category_id, name, slug, description, ingredients, nutritional_info, price, original_price, stock, is_active, is_featured, is_combo, image, bg_color, weight) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("isssssddiiiisss", $cat_id, $name, $slug, $desc, $ingredients, $nutrition, $price, $orig_price, $stock, $active, $is_featured, $is_combo, $image_path, $bg_color, $weight);
    }

    if ($stmt->execute()) {
        if ($id == 0) $id = $stmt->insert_id;
        $msg = "Product saved successfully!";

        // 2. Handle Gallery Images (Multi-Upload)
        if (isset($_FILES['gallery']) && !empty($_FILES['gallery']['name'][0])) {
            $gallery_dir = '../assets/images/products/';
            if (!file_exists($gallery_dir)) mkdir($gallery_dir, 0777, true);
            
            foreach ($_FILES['gallery']['name'] as $key => $name) {
                if ($_FILES['gallery']['error'][$key] === 0) {
                    $ext = pathinfo($name, PATHINFO_EXTENSION);
                    $new_name = uniqid('g_') . '.' . $ext;
                    $target = $gallery_dir . $new_name;
                    
                    if (move_uploaded_file($_FILES['gallery']['tmp_name'][$key], $target)) {
                        $g_path = 'assets/images/products/' . $new_name;
                        if (!$conn->query("INSERT INTO product_images (product_id, image_path) VALUES ($id, '$g_path')")) {
                            $msg .= " | DB Error for $name: " . $conn->error;
                        }
                    } else {
                        $msg .= " | Failed to move $name";
                    }
                } elseif ($_FILES['gallery']['error'][$key] !== UPLOAD_ERR_NO_FILE) {
                     $msg .= " | Upload Error Code: " . $_FILES['gallery']['error'][$key];
                }
            }
        }
        
        // Clear Frontend Cache
        if (function_exists('clear_all_cache')) clear_all_cache();

        // Redirect to products list
        echo "<script>window.location='products.php';</script>";
        exit;
    } else {
        $err = "Database Error: " . $stmt->error;
    }
}

// Fetch Product & Data
$product = [
    'name' => '', 'category_id' => 1, 'price' => '', 'original_price' => '', 
    'description' => '', 'ingredients' => '', 'nutritional_info' => '', 
    'image' => '', 'stock' => 10, 'is_active' => 1, 'is_featured' => 0, 'is_combo' => 0, 'bg_color' => '', 'weight' => '0.500'
];
$gallery_images = [];

if ($id) {
    $res = get_db_connection()->query("SELECT * FROM products WHERE id = $id");
    if($res) $product = $res->fetch_assoc();
    
    // Fetch Gallery
    $g_res = get_db_connection()->query("SELECT * FROM product_images WHERE product_id = $id ORDER BY sort_order");
    while($row = $g_res->fetch_assoc()) $gallery_images[] = $row;
}

$cats_res = get_db_connection()->query("SELECT * FROM categories");
$cats = [];
while($row = $cats_res->fetch_assoc()) $cats[] = $row;
?>

<div class="max-w-6xl mx-auto pb-20">
    <div class="mb-8 flex justify-between items-center">
        <div>
             <h1 class="text-3xl font-heading font-bold text-gray-900"><?php echo $id ? 'Edit Snack' : 'New Snack'; ?></h1>
             <a href="products.php" class="text-gray-500 hover:text-black mt-1 inline-block"><i class="fas fa-arrow-left"></i> Back to List</a>
        </div>
        <div class="flex gap-2">
             <?php if($msg): ?>
                <div class="bg-green-100 text-green-700 px-4 py-2 rounded-lg font-bold animate-pulse"><?php echo $msg; ?></div>
             <?php endif; ?>
             <button type="submit" form="pform" class="btn-chunky bg-[#24B25D] text-black px-8 py-3 hover:scale-105 border-none shadow-xl transform transition">
                  <i class="fas fa-save mr-2"></i> Save Product
             </button>
        </div>
    </div>

    <form id="pform" method="POST" enctype="multipart/form-data" class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <input type="hidden" name="current_image" value="<?php echo $product['image']; ?>">
        
        <!-- MAIN INFO -->
        <div class="lg:col-span-2 space-y-6">
            <div class="bg-white p-6 rounded-[20px] shadow-sm border border-gray-100">
                <div class="space-y-4">
                    <div>
                        <label class="block text-xs font-bold uppercase text-gray-400 mb-1">Product Name</label>
                        <input type="text" name="name" value="<?php echo htmlspecialchars($product['name']); ?>" required class="w-full text-xl font-bold border-b-2 border-gray-100 focus:border-[#24B25D] outline-none py-2" placeholder="e.g. Kashmiri Apple Rings">
                    </div>
                    <div>
                        <label class="block text-xs font-bold uppercase text-gray-400 mb-1">Description</label>
                        <textarea name="description" rows="5" class="w-full bg-gray-50 rounded-xl p-4 text-sm focus:outline-none focus:ring-2 focus:ring-[#24B25D]"><?php echo htmlspecialchars($product['description']); ?></textarea>
                    </div>
                </div>
            </div>

            <!-- INGREDIENTS & NUTRITION -->
             <div class="bg-white p-6 rounded-[20px] shadow-sm border border-gray-100 grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-xs font-bold uppercase text-gray-400 mb-2">Ingredients</label>
                    <div id="ingredient-manager" class="space-y-3">
                        <div class="flex gap-2">
                            <input type="text" id="ing-input" placeholder="Add ingredient..." class="flex-1 bg-gray-50 rounded-xl px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-[#24B25D]">
                            <button type="button" onclick="addIngredient()" class="bg-black text-white px-4 py-3 rounded-xl transition hover:bg-[#24B25D] hover:text-black">
                                <i class="fas fa-plus"></i>
                            </button>
                        </div>
                        <div id="ing-list" class="flex flex-wrap gap-2 min-h-[50px] p-2 bg-gray-50/50 rounded-xl border-2 border-dashed border-gray-100">
                            <!-- Items will appear here -->
                        </div>
                        <input type="hidden" name="ingredients" id="ingredients-json" value="<?php echo htmlspecialchars($product['ingredients']); ?>">
                    </div>

                    <script>
                        let ingredients = [];
                        try {
                            const initial = document.getElementById('ingredients-json').value;
                            if (initial) {
                                ingredients = JSON.parse(initial);
                            }
                        } catch (e) {
                            // Fallback for old comma-separated or plain text
                            const val = document.getElementById('ingredients-json').value;
                            if (val) ingredients = val.split(',').map(s => s.trim()).filter(s => s !== '');
                        }

                        function renderIngredients() {
                            const list = document.getElementById('ing-list');
                            list.innerHTML = '';
                            ingredients.forEach((ing, index) => {
                                const el = document.createElement('div');
                                el.className = 'bg-white px-4 py-2 rounded-full border border-gray-100 shadow-sm flex items-center gap-3 anim-up';
                                el.innerHTML = `
                                    <span class="text-xs font-bold text-gray-700">${ing}</span>
                                    <button type="button" onclick="removeIngredient(${index})" class="text-gray-300 hover:text-red-500 transition-colors">
                                        <i class="fas fa-times-circle"></i>
                                    </button>
                                `;
                                list.appendChild(el);
                            });
                            document.getElementById('ingredients-json').value = JSON.stringify(ingredients);
                        }

                        function addIngredient() {
                            const input = document.getElementById('ing-input');
                            const val = input.value.trim();
                            if (val && !ingredients.includes(val)) {
                                ingredients.push(val);
                                input.value = '';
                                renderIngredients();
                            }
                        }

                        function removeIngredient(index) {
                            ingredients.splice(index, 1);
                            renderIngredients();
                        }

                        // Handle Enter key on input
                        document.getElementById('ing-input').addEventListener('keypress', function(e) {
                            if (e.key === 'Enter') {
                                e.preventDefault();
                                addIngredient();
                            }
                        });

                        // Initial render
                        renderIngredients();
                    </script>
                </div>
                <div>
                    <label class="block text-xs font-bold uppercase text-gray-400 mb-2">Nutritional Info</label>
                    <div id="nutrition-manager" class="space-y-6">
                        <div class="flex flex-col gap-3 bg-gray-50/50 p-4 rounded-[28px] border border-gray-100">
                            <div class="flex-1 relative">
                                <i class="fas fa-tag absolute left-4 top-1/2 -translate-y-1/2 text-gray-300 text-xs"></i>
                                <input type="text" id="nut-label" placeholder="Nutrient" class="w-full bg-white rounded-2xl pl-10 pr-4 py-3 text-sm font-bold border-2 border-transparent focus:border-[#24B25D] outline-none transition-all shadow-sm">
                            </div>
                            <div class="flex-1 relative">
                                <i class="fas fa-flask absolute left-4 top-1/2 -translate-y-1/2 text-gray-300 text-xs"></i>
                                <input type="text" id="nut-value" placeholder="Value" class="w-full bg-white rounded-2xl pl-10 pr-4 py-3 text-sm font-bold border-2 border-transparent focus:border-[#24B25D] outline-none transition-all shadow-sm">
                            </div>
                            <button type="button" onclick="addNutrition()" class="bg-black text-[#24B25D] px-6 py-3 rounded-2xl font-black text-[10px] uppercase tracking-widest hover:scale-105 active:scale-95 transition-all shadow-lg flex items-center justify-center gap-2">
                                <i class="fas fa-plus"></i> Add
                            </button>
                        </div>
                        <div id="nut-list" class="grid grid-cols-1 gap-3 max-h-[400px] overflow-y-auto p-1 custom-scroll">
                            <!-- Items will appear here -->
                        </div>
                        <input type="hidden" name="nutritional_info" id="nutrition-json" value="<?php echo htmlspecialchars($product['nutritional_info']); ?>">
                    </div>

                    <script>
                        let nutrition = {};
                        const defaultLabels = [
                            "Energy", "Protein", "Carbohydrates", "Total Sugars", 
                            "Total Fat", "Dietary Fiber", "Sodium"
                        ];

                        try {
                            const initial = document.getElementById('nutrition-json').value;
                            if (initial && initial.startsWith('{')) {
                                nutrition = JSON.parse(initial);
                            } else if (initial) {
                                // Fallback for old line-separated text
                                initial.split('\n').forEach(line => {
                                    const parts = line.split(':');
                                    if(parts.length === 2) {
                                        nutrition[parts[0].trim()] = parts[1].trim();
                                    }
                                });
                            }

                            // If new product or empty nutrition, add defaults
                            if (Object.keys(nutrition).length === 0) {
                                defaultLabels.forEach(label => {
                                    nutrition[label] = "";
                                });
                            }
                        } catch (e) {
                            console.error("Error parsing nutrition", e);
                        }

                        function renderNutrition() {
                            const list = document.getElementById('nut-list');
                            list.innerHTML = '';
                            
                            const getIcon = (label) => {
                                const l = label.toLowerCase();
                                if (l.includes('energy')) return 'fa-bolt text-yellow-500';
                                if (l.includes('protein')) return 'fa-dumbbell text-blue-500';
                                if (l.includes('carb')) return 'fa-wheat-awn text-orange-500';
                                if (l.includes('sugar')) return 'fa-cubes text-pink-400';
                                if (l.includes('fat')) return 'fa-droplet text-amber-500';
                                if (l.includes('fiber')) return 'fa-leaf text-green-500';
                                if (l.includes('sodium') || l.includes('salt')) return 'fa-circle-dot text-gray-400';
                                return 'fa-info-circle text-indigo-400';
                            };

                            Object.entries(nutrition).forEach(([label, value]) => {
                                const el = document.createElement('div');
                                el.className = 'group bg-white p-4 rounded-[24px] border border-gray-100 hover:border-[#24B25D] hover:shadow-[0_20px_40px_-20px_rgba(25,220,126,0.15)] transition-all duration-300 flex items-center gap-4 anim-up';
                                el.innerHTML = `
                                    <div class="w-10 h-10 bg-gray-50 rounded-2xl flex items-center justify-center shrink-0 group-hover:bg-[#24B25D]/10 transition-colors">
                                        <i class="fas ${getIcon(label)} text-xs transition-transform group-hover:scale-110"></i>
                                    </div>
                                    <div class="flex-1">
                                        <span class="text-[9px] font-black uppercase tracking-[0.15em] text-gray-300 block mb-0.5">${label}</span>
                                        <input type="text" value="${value}" onchange="updateNutritionValue('${label}', this.value)" 
                                               class="text-sm font-bold text-gray-900 bg-transparent border-none outline-none p-0 focus:ring-0 w-full placeholder-gray-200" 
                                               placeholder="Set value (e.g. 10g)">
                                    </div>
                                    <button type="button" onclick="removeNutrition('${label}')" class="w-10 h-10 rounded-xl text-gray-200 hover:bg-red-50 hover:text-red-500 transition-all opacity-0 group-hover:opacity-100">
                                        <i class="fas fa-trash-alt text-xs"></i>
                                    </button>
                                `;
                                list.appendChild(el);
                            });
                            syncNutrition();
                        }

                        function updateNutritionValue(label, value) {
                            nutrition[label] = value;
                            syncNutrition();
                        }

                        function syncNutrition() {
                            document.getElementById('nutrition-json').value = JSON.stringify(nutrition);
                        }

                        function addNutrition() {
                            const labelInput = document.getElementById('nut-label');
                            const valueInput = document.getElementById('nut-value');
                            const label = labelInput.value.trim();
                            const value = valueInput.value.trim();
                            
                            if (label) {
                                nutrition[label] = value || "";
                                labelInput.value = '';
                                valueInput.value = '';
                                renderNutrition();
                            }
                        }

                        function removeNutrition(label) {
                            delete nutrition[label];
                            renderNutrition();
                        }

                        // Handle Enter key on value input
                        document.getElementById('nut-value').addEventListener('keypress', function(e) {
                            if (e.key === 'Enter') {
                                e.preventDefault();
                                addNutrition();
                            }
                        });

                        // Initial render
                        renderNutrition();
                    </script>
                </div>
            </div>

            <!-- GALLERY SECTION -->
            <div class="bg-white p-6 rounded-[20px] shadow-sm border border-gray-100">
                <label class="block text-xs font-bold uppercase text-gray-400 mb-4">Gallery Images</label>
                
                <div id="gallery-container" class="space-y-4">
                    <!-- Bulk Action Bar -->
                    <div id="bulk-action-bar" class="hidden flex items-center justify-between bg-red-50 p-4 rounded-2xl border border-red-100 mb-4 anim-up">
                        <span class="text-xs font-bold text-red-600 uppercase tracking-widest"><span id="selected-count">0</span> Images Selected</span>
                        <button type="button" onclick="bulkDeleteImages()" class="bg-red-500 text-white px-4 py-2 rounded-xl text-[10px] font-black uppercase tracking-widest hover:bg-red-600 transition shadow-lg">
                            <i class="fas fa-trash-alt mr-2"></i> Delete Selected
                        </button>
                    </div>

                    <!-- Existing Gallery -->
                    <?php if(!empty($gallery_images)): ?>
                        <div class="grid grid-cols-4 gap-4 mb-6" id="existing-gallery">
                            <?php foreach($gallery_images as $img): ?>
                                <div class="relative group aspect-square gallery-item" id="gallery-item-<?php echo $img['id']; ?>">
                                    <img src="../<?php echo $img['image_path']; ?>" class="w-full h-full object-cover rounded-xl border border-gray-100 shadow-sm transition-all group-hover:brightness-75">
                                    
                                    <!-- Selection Overlay -->
                                    <div class="absolute inset-0 bg-black/20 opacity-0 group-hover:opacity-100 transition-opacity rounded-xl flex items-center justify-center">
                                        <input type="checkbox" class="gallery-checkbox w-6 h-6 rounded-lg text-[#24B25D] focus:ring-[#24B25D] cursor-pointer" value="<?php echo $img['id']; ?>" onchange="updateBulkBar()">
                                    </div>

                                    <!-- Quick Delete -->
                                    <button type="button" onclick="deleteGalleryImage(<?php echo $img['id']; ?>)" class="absolute top-2 right-2 bg-white/90 text-red-500 w-8 h-8 rounded-xl flex items-center justify-center text-xs opacity-0 group-hover:opacity-100 transition shadow-lg hover:bg-red-500 hover:text-white">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <!-- Local Preview for Newly Selected Files -->
                    <div id="new-gallery-preview-grid" class="grid grid-cols-4 gap-4 mb-4 hidden"></div>

                    <!-- Upload New -->
                    <div class="border-2 border-dashed border-gray-200 rounded-2xl p-8 hover:bg-gray-50 hover:border-[#24B25D] transition cursor-pointer relative overflow-hidden group text-center">
                        <i class="fas fa-images text-4xl text-gray-300 mb-2 group-hover:text-[#24B25D] transition"></i>
                        <p class="text-xs font-bold text-gray-400 uppercase tracking-widest">Add Gallery Images</p>
                        <input type="file" name="gallery[]" multiple class="absolute inset-0 opacity-0 cursor-pointer" onchange="previewMultipleImages(this)">
                    </div>
                </div>
            </div>

            <div class="bg-white p-6 rounded-[20px] shadow-sm border border-gray-100 grid grid-cols-1 md:grid-cols-3 gap-6">
                 <div>
                    <label class="block text-xs font-bold uppercase text-gray-400 mb-1">Selling Price (₹)</label>
                    <input type="number" step="1" name="price" value="<?php echo $product['price']; ?>" required class="w-full bg-gray-50 rounded-xl px-4 py-3 font-black text-xl focus:ring-2 focus:ring-[#24B25D] outline-none">
                </div>
                <div>
                    <label class="block text-xs font-bold uppercase text-gray-400 mb-1">Original Price (₹)</label>
                    <input type="number" step="1" name="original_price" value="<?php echo $product['original_price']; ?>" class="w-full bg-gray-50 rounded-xl px-4 py-3 text-gray-400 font-bold focus:ring-2 focus:ring-[#24B25D] outline-none">
                </div>
                <div>
                    <label class="block text-xs font-bold uppercase text-gray-400 mb-1">Weight (KG)</label>
                    <input type="number" step="0.001" name="weight" value="<?php echo $product['weight'] ?: '0.500'; ?>" required class="w-full bg-gray-50 rounded-xl px-4 py-3 font-bold focus:ring-2 focus:ring-[#24B25D] outline-none" placeholder="e.g. 0.250">
                    <p class="text-[9px] text-gray-400 mt-1 italic">Used for dynamic shipping rates.</p>
                </div>
            </div>
        </div>

        <!-- SIDEBAR -->
        <div class="space-y-6">
            <div class="bg-white p-6 rounded-[30px] shadow-sm border border-gray-100">
                <label class="block text-xs font-bold uppercase text-gray-400 mb-4 tracking-widest text-center">Visibility & Stock</label>
                <div class="space-y-4">
                    <div>
                        <label class="block text-[10px] font-black uppercase text-gray-300 mb-1">Status</label>
                        <select name="is_active" class="w-full bg-gray-50 rounded-xl px-4 py-3 font-bold focus:ring-2 focus:ring-[#24B25D] outline-none appearance-none">
                            <option value="1" <?php echo $product['is_active']?'selected':''; ?>>Active (Visible)</option>
                            <option value="0" <?php echo !$product['is_active']?'selected':''; ?>>Draft (Hidden)</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-[10px] font-black uppercase text-gray-300 mb-1">Stock Quantity</label>
                        <input type="number" name="stock" value="<?php echo $product['stock']; ?>" required class="w-full bg-gray-50 rounded-xl px-4 py-3 font-bold focus:ring-2 focus:ring-[#24B25D] outline-none">
                    </div>
                    <div>
                        <label class="block text-[10px] font-black uppercase text-gray-300 mb-1">Featured Product</label>
                        <select name="is_featured" class="w-full bg-gray-50 rounded-xl px-4 py-3 font-bold focus:ring-2 focus:ring-[#24B25D] outline-none appearance-none">
                            <option value="0" <?php echo !$product['is_featured']?'selected':''; ?>>No (Normal)</option>
                            <option value="1" <?php echo $product['is_featured']?'selected':''; ?>>Yes (Show Featured)</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-[10px] font-black uppercase text-gray-300 mb-1">Mark as Combo</label>
                        <select name="is_combo" class="w-full bg-gray-50 rounded-xl px-4 py-3 font-bold focus:ring-2 focus:ring-[#24B25D] outline-none appearance-none">
                            <option value="0" <?php echo !$product['is_combo']?'selected':''; ?>>No (Individual)</option>
                            <option value="1" <?php echo $product['is_combo']?'selected':''; ?>>Yes (Show in Combo Bar)</option>
                        </select>
                    </div>
                </div>
            </div>

            <div class="bg-white p-6 rounded-[30px] shadow-sm border border-gray-100">
                <label class="block text-xs font-bold uppercase text-gray-400 mb-4 tracking-widest text-center">Classification</label>
                <div class="space-y-2 max-h-60 overflow-y-auto pr-2 custom-scroll">
                    <?php foreach ($cats as $c): ?>
                    <label class="flex items-center gap-3 cursor-pointer p-3 hover:bg-gray-50 rounded-xl group transition">
                        <input type="radio" name="category_id" value="<?php echo $c['id']; ?>" <?php echo $product['category_id']==$c['id']?'checked':''; ?> class="w-5 h-5 text-[#24B25D] focus:ring-[#24B25D]">
                        <span class="font-bold text-sm text-gray-600 group-hover:text-black transition"><?php echo $c['name']; ?></span>
                    </label>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Product Aesthetics -->
            <div class="bg-white p-6 rounded-[30px] shadow-sm border border-gray-100">
                <label class="block text-xs font-bold uppercase text-gray-400 mb-4 tracking-widest text-center">Product Aesthetic</label>
                <div>
                    <label class="block text-[10px] font-black uppercase text-gray-300 mb-2">Background Color</label>
                    <div class="flex flex-wrap gap-2 mb-4">
                        <?php 
                        $colors = ['#FFFEDC', '#F0FDFA', '#FEF2F2', '#F5F3FF', '#ECFDF5', '#FFF7ED', '#FDF2F8'];
                        foreach($colors as $c): ?>
                            <button type="button" onclick="document.getElementById('bg_color_input').value='<?php echo $c; ?>'" 
                                    class="w-8 h-8 rounded-full border-2 border-white shadow-sm hover:scale-110 transition" 
                                    style="background-color: <?php echo $c; ?>"></button>
                        <?php endforeach; ?>
                    </div>
                    <input type="text" name="bg_color" id="bg_color_input" value="<?php echo htmlspecialchars($product['bg_color']); ?>" 
                           placeholder="Hex Code (e.g. #FFFEDC)" 
                           class="w-full bg-gray-50 rounded-xl px-4 py-3 font-bold focus:ring-2 focus:ring-[#24B25D] outline-none">
                    <p class="text-[9px] text-gray-400 mt-2 font-medium">Leave empty for a random delightful color.</p>
                </div>
            </div>

            <div class="bg-white p-8 rounded-[30px] shadow-sm border border-gray-100 text-center relative group overflow-hidden">
                <label class="block text-xs font-bold uppercase text-gray-400 mb-6 tracking-widest">Featured Display</label>
                <div class="relative aspect-square bg-gray-50 rounded-3xl border-2 border-dashed border-gray-100 group-hover:border-[#24B25D] transition-all flex items-center justify-center overflow-hidden">
                    <img id="featured-preview" src="<?php echo $product['image'] ? '../'.$product['image'] : ''; ?>" class="w-full h-full object-contain p-4 <?php echo $product['image'] ? '' : 'hidden'; ?>">
                    
                    <div id="featured-placeholder" class="<?php echo $product['image'] ? 'hidden' : ''; ?> text-gray-300">
                        <i class="fas fa-cloud-upload-alt text-5xl mb-3 group-hover:scale-110 transition-transform"></i>
                        <p class="text-[10px] font-black uppercase tracking-widest">Main Product Art</p>
                    </div>
                    
                    <input type="file" name="image" class="absolute inset-0 opacity-0 cursor-pointer z-30" onchange="previewImage(this, 'featured-preview')">
                </div>
                <p class="mt-4 text-[10px] font-bold text-gray-300 uppercase tracking-widest">Click to update</p>
            </div>
        </div>

    </form>
</div>

<script>
function previewImage(input, previewId) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            const preview = document.getElementById(previewId);
            preview.src = e.target.result;
            preview.classList.remove('hidden');
            
            // Hide placeholder if it exists
            const placeholder = preview.nextElementSibling;
            if (placeholder && placeholder.id.includes('placeholder')) {
                placeholder.classList.add('hidden');
            }
        }
        reader.readAsDataURL(input.files[0]);
    }
}

function previewMultipleImages(input) {
    const previewContainer = document.getElementById('new-gallery-preview-grid');
    if (!previewContainer) return;
    
    previewContainer.innerHTML = ''; // Clear previous selections
    
    if (input.files.length > 0) {
        previewContainer.classList.remove('hidden');
        Array.from(input.files).forEach(file => {
            const reader = new FileReader();
            reader.onload = function(e) {
                const div = document.createElement('div');
                div.className = 'relative aspect-square anim-up';
                div.innerHTML = `
                    <img src="${e.target.result}" class="w-full h-full object-cover rounded-xl border-2 border-[#24B25D] shadow-lg">
                    <div class="absolute -top-2 -right-2 bg-[#24B25D] text-black w-6 h-6 rounded-full flex items-center justify-center text-[10px] shadow-xl border-2 border-white font-black">
                        NEW
                    </div>
                `;
                previewContainer.appendChild(div);
            }
            reader.readAsDataURL(file);
        });
    } else {
        previewContainer.classList.add('hidden');
    }
}

function updateBulkBar() {
    const checked = document.querySelectorAll('.gallery-checkbox:checked');
    const bar = document.getElementById('bulk-action-bar');
    const count = document.getElementById('selected-count');
    
    if (checked.length > 0) {
        bar.classList.remove('hidden');
        count.innerText = checked.length;
    } else {
        bar.classList.add('hidden');
    }
}

async function deleteGalleryImage(id) {
    const ok = await showConfirm('Are you sure you want to delete this image?', {
        title: 'Delete Image',
        type: 'danger',
        confirmText: 'Delete'
    });
    if (!ok) return;
    
    const item = document.getElementById(`gallery-item-${id}`);
    item.style.opacity = '0.5';
    item.style.pointerEvents = 'none';

    try {
        const formData = new FormData();
        formData.append('ajax_action', 'delete_gallery');
        formData.append('ids[]', id);

        const response = await fetch('product_form.php?id=<?php echo $id; ?>', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        if (data.success) {
            item.remove();
            updateBulkBar();
            if (document.querySelectorAll('.gallery-item').length === 0) {
                document.getElementById('existing-gallery')?.remove();
            }
        }
    } catch (e) {
        console.error(e);
        alert('Failed to delete image');
        item.style.opacity = '1';
        item.style.pointerEvents = 'auto';
    }
}

async function bulkDeleteImages() {
    const checked = document.querySelectorAll('.gallery-checkbox:checked');
    if (checked.length === 0) return;
    const ok = await showConfirm(`Are you sure you want to delete ${checked.length} selected images?`, {
        title: 'Delete Selected Images',
        type: 'danger',
        confirmText: 'Delete'
    });
    if (!ok) return;

    const ids = Array.from(checked).map(cb => cb.value);
    const bar = document.getElementById('bulk-action-bar');
    bar.innerHTML = '<div class="flex items-center gap-3"><i class="fas fa-spinner fa-spin text-red-500"></i> <span class="text-xs font-bold text-red-600">Deleting...</span></div>';

    try {
        const formData = new FormData();
        formData.append('ajax_action', 'delete_gallery');
        ids.forEach(id => formData.append('ids[]', id));

        const response = await fetch('product_form.php?id=<?php echo $id; ?>', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        if (data.success) {
            ids.forEach(id => {
                document.getElementById(`gallery-item-${id}`)?.remove();
            });
            updateBulkBar();
            if (document.querySelectorAll('.gallery-item').length === 0) {
                document.getElementById('existing-gallery')?.remove();
            }
        }
    } catch (e) {
        console.error(e);
        alert('Failed to delete images');
    } finally {
        updateBulkBar();
        bar.innerHTML = `
            <span class="text-xs font-bold text-red-600 uppercase tracking-widest"><span id="selected-count">0</span> Images Selected</span>
            <button type="button" onclick="bulkDeleteImages()" class="bg-red-500 text-white px-4 py-2 rounded-xl text-[10px] font-black uppercase tracking-widest hover:bg-red-600 transition shadow-lg">
                <i class="fas fa-trash-alt mr-2"></i> Delete Selected
            </button>
        `;
    }
}
</script>
</body>
</html>


