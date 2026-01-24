<?php include 'includes/header.php'; ?>
<?php
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$msg = '';
$err = '';

// DELETE GALLERY IMAGE
if (isset($_GET['del_img'])) {
    $img_id = (int)$_GET['del_img'];
    $conn = get_db_connection();
    $conn->query("DELETE FROM product_images WHERE id = $img_id");
    echo "<script>window.location='product_form.php?id=$id';</script>";
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
    $bg_color = $_POST['bg_color'] ?? '';
    $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $name)));

    // 1. Featured Image
    $image_path = $_POST['current_image'] ?? '';
    if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
        $upload_dir = '../assets/images/products/';
        if (!file_exists($upload_dir)) mkdir($upload_dir, 0777, true);
        
        $ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
        $filename = uniqid('prod_') . '.' . $ext;
        
        if (move_uploaded_file($_FILES['image']['tmp_name'], $upload_dir . $filename)) {
            $image_path = 'assets/images/products/' . $filename;
        }
    }

    $ingredients = $_POST['ingredients'];
    $nutrition = $_POST['nutritional_info'];
    $weight = $_POST['weight'] ?? '0.500';
    
    // SAVE PRODUCT META
    if ($id > 0) {
        $sql = "UPDATE products SET category_id=?, name=?, description=?, ingredients=?, nutritional_info=?, price=?, original_price=?, stock=?, is_active=?, image=?, bg_color=?, weight=? WHERE id=?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("issssddiisssi", $cat_id, $name, $desc, $ingredients, $nutrition, $price, $orig_price, $stock, $active, $image_path, $bg_color, $weight, $id);
    } else {
        $sql = "INSERT INTO products (category_id, name, slug, description, ingredients, nutritional_info, price, original_price, stock, is_active, image, bg_color, weight) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("isssssddiisss", $cat_id, $name, $slug, $desc, $ingredients, $nutrition, $price, $orig_price, $stock, $active, $image_path, $bg_color, $weight);
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
    } else {
        $err = "Database Error: " . $stmt->error;
    }
}

// Fetch Product & Data
$product = [
    'name' => '', 'category_id' => 1, 'price' => '', 'original_price' => '', 
    'description' => '', 'ingredients' => '', 'nutritional_info' => '', 
    'image' => '', 'stock' => 10, 'is_active' => 1, 'bg_color' => '', 'weight' => '0.500'
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
             <h1 class="text-3xl font-['Fredoka'] font-bold text-gray-900"><?php echo $id ? 'Edit Snack' : 'New Snack'; ?></h1>
             <a href="products.php" class="text-gray-500 hover:text-black mt-1 inline-block"><i class="fas fa-arrow-left"></i> Back to List</a>
        </div>
        <div class="flex gap-2">
             <?php if($msg): ?>
                <div class="bg-green-100 text-green-700 px-4 py-2 rounded-lg font-bold animate-pulse"><?php echo $msg; ?></div>
             <?php endif; ?>
             <button type="submit" form="pform" class="btn-chunky bg-[#19DC7E] text-black px-8 py-3 hover:scale-105 border-none shadow-xl transform transition">
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
                        <input type="text" name="name" value="<?php echo htmlspecialchars($product['name']); ?>" required class="w-full text-xl font-bold border-b-2 border-gray-100 focus:border-[#19DC7E] outline-none py-2" placeholder="e.g. Kashmiri Apple Rings">
                    </div>
                    <div>
                        <label class="block text-xs font-bold uppercase text-gray-400 mb-1">Description</label>
                        <textarea name="description" rows="5" class="w-full bg-gray-50 rounded-xl p-4 text-sm focus:outline-none focus:ring-2 focus:ring-[#19DC7E]"><?php echo htmlspecialchars($product['description']); ?></textarea>
                    </div>
                </div>
            </div>

            <!-- INGREDIENTS & NUTRITION -->
             <div class="bg-white p-6 rounded-[20px] shadow-sm border border-gray-100 grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-xs font-bold uppercase text-gray-400 mb-2">Ingredients</label>
                    <div id="ingredient-manager" class="space-y-3">
                        <div class="flex gap-2">
                            <input type="text" id="ing-input" placeholder="Add ingredient..." class="flex-1 bg-gray-50 rounded-xl px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-[#19DC7E]">
                            <button type="button" onclick="addIngredient()" class="bg-black text-white px-4 py-3 rounded-xl transition hover:bg-[#19DC7E] hover:text-black">
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
                    <div id="nutrition-manager" class="space-y-3">
                        <div class="flex gap-2">
                            <input type="text" id="nut-label" placeholder="Label (e.g. Protein)" class="w-1/2 bg-gray-50 rounded-xl px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-[#19DC7E]">
                            <input type="text" id="nut-value" placeholder="Value (e.g. 5g)" class="w-1/2 bg-gray-50 rounded-xl px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-[#19DC7E]">
                            <button type="button" onclick="addNutrition()" class="bg-black text-white px-4 py-3 rounded-xl transition hover:bg-[#19DC7E] hover:text-black">
                                <i class="fas fa-plus"></i>
                            </button>
                        </div>
                        <div id="nut-list" class="space-y-2 max-h-[150px] overflow-y-auto p-2 bg-gray-50/50 rounded-xl border-2 border-dashed border-gray-100 custom-scroll">
                            <!-- Items will appear here -->
                        </div>
                        <input type="hidden" name="nutritional_info" id="nutrition-json" value="<?php echo htmlspecialchars($product['nutritional_info']); ?>">
                    </div>

                    <script>
                        let nutrition = {};
                        try {
                            const initial = document.getElementById('nutrition-json').value;
                            if (initial && initial.startsWith('{')) {
                                nutrition = JSON.parse(initial);
                            } else if (initial) {
                                // Fallback for old line-separated text (e.g. "Protein: 5g\nCarbs: 10g")
                                initial.split('\n').forEach(line => {
                                    const parts = line.split(':');
                                    if(parts.length === 2) {
                                        nutrition[parts[0].trim()] = parts[1].trim();
                                    }
                                });
                            }
                        } catch (e) {
                            console.error("Error parsing nutrition", e);
                        }

                        function renderNutrition() {
                            const list = document.getElementById('nut-list');
                            list.innerHTML = '';
                            Object.entries(nutrition).forEach(([label, value], index) => {
                                const el = document.createElement('div');
                                el.className = 'bg-white px-4 py-3 rounded-xl border border-gray-100 shadow-sm flex items-center justify-between anim-up';
                                el.innerHTML = `
                                    <div class="flex flex-col">
                                        <span class="text-[9px] font-black uppercase tracking-widest text-gray-400">${label}</span>
                                        <span class="text-sm font-bold text-gray-900">${value}</span>
                                    </div>
                                    <button type="button" onclick="removeNutrition('${label}')" class="text-gray-300 hover:text-red-500 transition-colors">
                                        <i class="fas fa-times-circle text-lg"></i>
                                    </button>
                                `;
                                list.appendChild(el);
                            });
                            document.getElementById('nutrition-json').value = JSON.stringify(nutrition);
                        }

                        function addNutrition() {
                            const labelInput = document.getElementById('nut-label');
                            const valueInput = document.getElementById('nut-value');
                            const label = labelInput.value.trim();
                            const value = valueInput.value.trim();
                            
                            if (label && value) {
                                nutrition[label] = value;
                                labelInput.value = '';
                                valueInput.value = '';
                                labelInput.focus();
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
                    <!-- Existing Gallery -->
                    <?php if(!empty($gallery_images)): ?>
                        <div class="grid grid-cols-4 gap-4 mb-6">
                            <?php foreach($gallery_images as $img): ?>
                                <div class="relative group aspect-square">
                                    <img src="../<?php echo $img['image_path']; ?>" class="w-full h-full object-cover rounded-xl border border-gray-100 shadow-sm">
                                    <a href="?id=<?php echo $id; ?>&del_img=<?php echo $img['id']; ?>" class="absolute top-1 right-1 bg-red-500 text-white w-6 h-6 rounded-full flex items-center justify-center text-xs opacity-0 group-hover:opacity-100 transition shadow-lg" onclick="return confirm('Delete image?')"><i class="fas fa-times"></i></a>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <!-- Upload New -->
                    <div class="border-2 border-dashed border-gray-200 rounded-2xl p-8 hover:bg-gray-50 hover:border-[#19DC7E] transition cursor-pointer relative overflow-hidden group text-center">
                        <i class="fas fa-images text-4xl text-gray-300 mb-2 group-hover:text-[#19DC7E] transition"></i>
                        <p class="text-xs font-bold text-gray-400 uppercase tracking-widest">Add Gallery Images</p>
                        <input type="file" name="gallery[]" multiple class="absolute inset-0 opacity-0 cursor-pointer" onchange="previewMultipleImages(this, 'gallery-container')">
                    </div>
                </div>
            </div>

            <div class="bg-white p-6 rounded-[20px] shadow-sm border border-gray-100 grid grid-cols-1 md:grid-cols-3 gap-6">
                 <div>
                    <label class="block text-xs font-bold uppercase text-gray-400 mb-1">Selling Price (₹)</label>
                    <input type="number" step="1" name="price" value="<?php echo $product['price']; ?>" required class="w-full bg-gray-50 rounded-xl px-4 py-3 font-black text-xl focus:ring-2 focus:ring-[#19DC7E] outline-none">
                </div>
                <div>
                    <label class="block text-xs font-bold uppercase text-gray-400 mb-1">Original Price (₹)</label>
                    <input type="number" step="1" name="original_price" value="<?php echo $product['original_price']; ?>" class="w-full bg-gray-50 rounded-xl px-4 py-3 text-gray-400 font-bold focus:ring-2 focus:ring-[#19DC7E] outline-none">
                </div>
                <div>
                    <label class="block text-xs font-bold uppercase text-gray-400 mb-1">Weight (KG)</label>
                    <input type="number" step="0.001" name="weight" value="<?php echo $product['weight'] ?: '0.500'; ?>" required class="w-full bg-gray-50 rounded-xl px-4 py-3 font-bold focus:ring-2 focus:ring-[#19DC7E] outline-none" placeholder="e.g. 0.250">
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
                        <select name="is_active" class="w-full bg-gray-50 rounded-xl px-4 py-3 font-bold focus:ring-2 focus:ring-[#19DC7E] outline-none appearance-none">
                            <option value="1" <?php echo $product['is_active']?'selected':''; ?>>Active (Visible)</option>
                            <option value="0" <?php echo !$product['is_active']?'selected':''; ?>>Draft (Hidden)</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-[10px] font-black uppercase text-gray-300 mb-1">Stock Level</label>
                        <input type="number" name="stock" value="<?php echo $product['stock']; ?>" class="w-full bg-gray-50 rounded-xl px-4 py-3 font-bold focus:ring-2 focus:ring-[#19DC7E] outline-none">
                    </div>
                </div>
            </div>

            <div class="bg-white p-6 rounded-[30px] shadow-sm border border-gray-100">
                <label class="block text-xs font-bold uppercase text-gray-400 mb-4 tracking-widest text-center">Classification</label>
                <div class="space-y-2 max-h-60 overflow-y-auto pr-2 custom-scroll">
                    <?php foreach ($cats as $c): ?>
                    <label class="flex items-center gap-3 cursor-pointer p-3 hover:bg-gray-50 rounded-xl group transition">
                        <input type="radio" name="category_id" value="<?php echo $c['id']; ?>" <?php echo $product['category_id']==$c['id']?'checked':''; ?> class="w-5 h-5 text-[#19DC7E] focus:ring-[#19DC7E]">
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
                        $colors = ['#FFFBEB', '#F0FDFA', '#FEF2F2', '#F5F3FF', '#ECFDF5', '#FFF7ED', '#FDF2F8'];
                        foreach($colors as $c): ?>
                            <button type="button" onclick="document.getElementById('bg_color_input').value='<?php echo $c; ?>'" 
                                    class="w-8 h-8 rounded-full border-2 border-white shadow-sm hover:scale-110 transition" 
                                    style="background-color: <?php echo $c; ?>"></button>
                        <?php endforeach; ?>
                    </div>
                    <input type="text" name="bg_color" id="bg_color_input" value="<?php echo htmlspecialchars($product['bg_color']); ?>" 
                           placeholder="Hex Code (e.g. #FFFBEB)" 
                           class="w-full bg-gray-50 rounded-xl px-4 py-3 font-bold focus:ring-2 focus:ring-[#19DC7E] outline-none">
                    <p class="text-[9px] text-gray-400 mt-2 font-medium">Leave empty for a random delightful color.</p>
                </div>
            </div>

            <div class="bg-white p-8 rounded-[30px] shadow-sm border border-gray-100 text-center relative group overflow-hidden">
                <label class="block text-xs font-bold uppercase text-gray-400 mb-6 tracking-widest">Featured Display</label>
                <div class="relative aspect-square bg-gray-50 rounded-3xl border-2 border-dashed border-gray-100 group-hover:border-[#19DC7E] transition-all flex items-center justify-center overflow-hidden">
                    <img id="featured-preview" src="<?php echo $product['image'] ? '../'.$product['image'] : ''; ?>" class="w-full h-full object-contain p-4 <?php echo $product['image'] ? '' : 'hidden'; ?>">
                    
                    <div id="featured-placeholder" class="<?php echo $product['image'] ? 'hidden' : ''; ?> text-gray-300">
                        <i class="fas fa-cloud-upload-alt text-5xl mb-3 group-hover:scale-110 transition-transform"></i>
                        <p class="text-[10px] font-black uppercase tracking-widest">Main Product Art</p>
                    </div>
                    
                    <input type="file" name="image" class="absolute inset-0 opacity-0 cursor-pointer z-30" onchange="previewImage(this, 'featured-preview'); document.getElementById('featured-placeholder').classList.add('hidden'); document.getElementById('featured-preview').classList.remove('hidden');">
                </div>
                <p class="mt-4 text-[10px] font-bold text-gray-300 uppercase tracking-widest">Click to update</p>
            </div>
        </div>

    </form>
</div>
</body>
</html>
