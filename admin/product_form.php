<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

// Strict Admin Check
if (!isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
    if (isset($_POST['ajax_action'])) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'Unauthorized']);
        exit;
    }
    header("Location: ../login.php");
    exit;
}

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
            if (file_exists($full_path)) @unlink($full_path);
        }

        $conn->query("DELETE FROM product_images WHERE id IN ($ids_str)");
        header('Content-Type: application/json');
        echo json_encode(['success' => true]);
        exit;
    }
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$msg = '';
$err = '';

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['ajax_action'])) {
    $conn = get_db_connection();
    
    $name = sanitize_input($_POST['name'] ?? '');
    $desc = $_POST['description'] ?? '';
    $price = (float)($_POST['price'] ?? 0);
    $orig_price = (float)($_POST['original_price'] ?? 0);
    $stock = (int)($_POST['stock'] ?? 0);
    $cat_id = (int)($_POST['category_id'] ?? 1);
    $active = (int)($_POST['is_active'] ?? 1);
    $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $name)));
    $is_featured = isset($_POST['is_featured']) ? (int)$_POST['is_featured'] : 0;
    $is_combo = isset($_POST['is_combo']) ? (int)$_POST['is_combo'] : ($cat_id === 3 ? 1 : 0);
    if ($cat_id === 3 && !isset($_POST['is_combo'])) $is_combo = 1;
    $bg_color = sanitize_input($_POST['bg_color'] ?? '');

    // 1. Featured Image
    $image_path = $_POST['current_image'] ?? '';
    $allowed_exts = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
    if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
        $upload_dir = '../assets/images/products/';
        if (!file_exists($upload_dir)) mkdir($upload_dir, 0777, true);
        
        $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
        if (in_array($ext, $allowed_exts)) {
            $filename = uniqid('prod_') . '.' . $ext;
            
            if (move_uploaded_file($_FILES['image']['tmp_name'], $upload_dir . $filename)) {
                $image_path = 'assets/images/products/' . $filename;
                if (function_exists('optimize_image')) {
                    $optimized = optimize_image($upload_dir . $filename, $upload_dir . $filename);
                    if ($optimized) $image_path = $optimized;
                }
            }
        } else {
            $err = "Invalid file type for product image";
        }
    }

    $ingredients = $_POST['ingredients'] ?? '';
    $nutrition = $_POST['nutritional_info'] ?? '';
    $weight = sanitize_input($_POST['weight'] ?? '0.500');
    
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
            
            $stmt_gallery = $conn->prepare("INSERT INTO product_images (product_id, image_path) VALUES (?, ?)");
            foreach ($_FILES['gallery']['name'] as $key => $gname) {
                if ($_FILES['gallery']['error'][$key] === 0) {
                    $ext = strtolower(pathinfo($gname, PATHINFO_EXTENSION));
                    if (in_array($ext, $allowed_exts)) {
                        $filename = uniqid('gallery_') . '.' . $ext;
                        if (move_uploaded_file($_FILES['gallery']['tmp_name'][$key], $gallery_dir . $filename)) {
                            $g_path = 'assets/images/products/' . $filename;
                            if (function_exists('optimize_image')) {
                                $optimized = optimize_image($gallery_dir . $filename, $gallery_dir . $filename);
                                if ($optimized) $g_path = $optimized;
                            }
                            $stmt_gallery->bind_param("is", $id, $g_path);
                            $stmt_gallery->execute();
                        }
                    }
                }
            }
        }

        $_SESSION['success'] = "Product updated successfully!";
        header("Location: product_form.php?id=$id");
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
    $g_res = get_db_connection()->query("SELECT * FROM product_images WHERE product_id = $id ORDER BY sort_order ASC, id ASC");
    while($row = $g_res->fetch_assoc()) $gallery_images[] = $row;
}

$cats_res = get_db_connection()->query("SELECT * FROM categories ORDER BY sort_order ASC, name ASC");
$cats = [];
while($row = $cats_res->fetch_assoc()) $cats[] = $row;

include 'includes/header.php';
?>

<div class="max-w-6xl mx-auto pb-12">
    
    <!-- Top Action Bar -->
    <div class="mb-6 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div class="flex items-center gap-3">
            <a href="products.php" class="w-9 h-9 rounded-xl border border-slate-200 bg-white hover:bg-slate-50 flex items-center justify-center text-slate-500 hover:text-slate-900 transition-colors" title="Back to catalog">
                <i class="fas fa-arrow-left text-xs"></i>
            </a>
            <div>
                <h1 class="text-2xl font-bold text-slate-900 tracking-tight">
                    <?php echo $id ? 'Edit Product' : 'Create New Product'; ?>
                </h1>
                <p class="text-xs text-slate-500 mt-0.5">
                    <?php echo $id ? 'Managing inventory specifications for ' . htmlspecialchars($product['name']) : 'Add an artisan Kashmiri snack to your store'; ?>
                </p>
            </div>
        </div>

        <div class="flex items-center gap-2.5">
            <a href="products.php" class="btn-admin btn-admin-secondary text-xs">Cancel</a>
            <button type="submit" form="product-form" class="btn-admin btn-admin-primary text-xs">
                <i class="fas fa-save text-xs"></i> Save Product
            </button>
        </div>
    </div>

    <?php if($err): ?>
        <div class="mb-6 p-3.5 bg-rose-50 border border-rose-200 text-rose-800 rounded-xl text-xs font-medium flex items-center gap-2">
            <i class="fas fa-exclamation-circle text-rose-600"></i>
            <span><?php echo $err; ?></span>
        </div>
    <?php endif; ?>

    <form id="product-form" method="POST" enctype="multipart/form-data" class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <input type="hidden" name="current_image" value="<?php echo htmlspecialchars($product['image']); ?>">

        <!-- MAIN COLUMN (2 cols) -->
        <div class="lg:col-span-2 space-y-6">
            
            <!-- Basic Information -->
            <div class="bg-white rounded-xl border border-slate-200 p-5 shadow-xs space-y-4">
                <h3 class="text-sm font-bold text-slate-900 pb-2 border-b border-slate-100 flex items-center gap-2">
                    <i class="fas fa-info-circle text-[#004f42] text-xs"></i> General Information
                </h3>

                <div>
                    <label class="admin-label">Product Name <span class="text-rose-500">*</span></label>
                    <input type="text" name="name" value="<?php echo htmlspecialchars($product['name']); ?>" required class="admin-input font-semibold text-sm" placeholder="e.g. Crisp Kashmiri Apple Rings">
                </div>

                <div>
                    <label class="admin-label">Product Description</label>
                    <textarea name="description" rows="5" class="admin-textarea text-xs" placeholder="Describe the snack aroma, texture, sourcing story, and flavour profile..."><?php echo htmlspecialchars($product['description']); ?></textarea>
                </div>
            </div>

            <!-- Pricing & Inventory -->
            <div class="bg-white rounded-xl border border-slate-200 p-5 shadow-xs space-y-4">
                <h3 class="text-sm font-bold text-slate-900 pb-2 border-b border-slate-100 flex items-center gap-2">
                    <i class="fas fa-coins text-[#004f42] text-xs"></i> Pricing & Inventory
                </h3>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div>
                        <label class="admin-label">Selling Price (₹) <span class="text-rose-500">*</span></label>
                        <input type="number" step="1" name="price" value="<?php echo $product['price']; ?>" required class="admin-input text-base font-bold text-slate-900" placeholder="299">
                    </div>
                    <div>
                        <label class="admin-label">Original Price (₹)</label>
                        <input type="number" step="1" name="original_price" value="<?php echo $product['original_price']; ?>" class="admin-input text-base font-semibold text-slate-500" placeholder="399">
                    </div>
                    <div>
                        <label class="admin-label">Package Weight (KG) <span class="text-rose-500">*</span></label>
                        <input type="number" step="0.001" name="weight" value="<?php echo $product['weight'] ?: '0.500'; ?>" required class="admin-input text-base font-semibold" placeholder="0.250">
                        <span class="text-[11px] text-slate-400 mt-0.5 block">Used for shipping weight brackets</span>
                    </div>
                </div>

                <div class="pt-2">
                    <label class="admin-label">Initial Stock Quantity <span class="text-rose-500">*</span></label>
                    <input type="number" name="stock" value="<?php echo $product['stock']; ?>" required class="admin-input sm:max-w-xs text-base font-bold text-slate-900" placeholder="50">
                </div>
            </div>

            <!-- Ingredients & Nutrition Details -->
            <div class="bg-white rounded-xl border border-slate-200 p-5 shadow-xs space-y-6">
                <h3 class="text-sm font-bold text-slate-900 pb-2 border-b border-slate-100 flex items-center gap-2">
                    <i class="fas fa-leaf text-[#004f42] text-xs"></i> Ingredients & Nutrition
                </h3>

                <!-- Ingredients Tag Builder -->
                <div>
                    <label class="admin-label">Ingredients List</label>
                    <div class="space-y-2">
                        <div class="flex gap-2">
                            <input type="text" id="ing-input" placeholder="Type an ingredient and press Enter..." class="admin-input">
                            <button type="button" onclick="addIngredient()" class="btn-admin btn-admin-secondary shrink-0">
                                <i class="fas fa-plus text-xs"></i> Add
                            </button>
                        </div>
                        <div id="ing-list" class="flex flex-wrap gap-2 min-h-[44px] p-2 bg-slate-50 rounded-xl border border-slate-200/80">
                            <!-- Tags populated by JS -->
                        </div>
                        <input type="hidden" name="ingredients" id="ingredients-json" value="<?php echo htmlspecialchars($product['ingredients']); ?>">
                    </div>
                </div>

                <!-- Nutrition Matrix -->
                <div>
                    <label class="admin-label">Nutritional Information</label>
                    <div class="space-y-3">
                        <div class="flex flex-col sm:flex-row gap-2 bg-slate-50 p-2.5 rounded-xl border border-slate-200/80">
                            <input type="text" id="nut-label" placeholder="Nutrient (e.g. Protein)" class="admin-input flex-1">
                            <input type="text" id="nut-value" placeholder="Value (e.g. 4.2g)" class="admin-input flex-1">
                            <button type="button" onclick="addNutrition()" class="btn-admin btn-admin-primary shrink-0">
                                <i class="fas fa-plus text-xs"></i> Add
                            </button>
                        </div>
                        <div id="nut-list" class="grid grid-cols-1 sm:grid-cols-2 gap-2 max-h-72 overflow-y-auto p-1">
                            <!-- Items populated by JS -->
                        </div>
                        <input type="hidden" name="nutritional_info" id="nutrition-json" value="<?php echo htmlspecialchars($product['nutritional_info']); ?>">
                    </div>
                </div>
            </div>

            <!-- Gallery Images -->
            <div class="bg-white rounded-xl border border-slate-200 p-5 shadow-xs space-y-4">
                <div class="flex items-center justify-between pb-2 border-b border-slate-100">
                    <h3 class="text-sm font-bold text-slate-900 flex items-center gap-2">
                        <i class="fas fa-images text-[#004f42] text-xs"></i> Product Gallery
                    </h3>
                    <span class="text-xs text-slate-400">Multiple photos</span>
                </div>

                <!-- Bulk Gallery Delete Banner -->
                <div id="bulk-action-bar" class="hidden flex items-center justify-between bg-rose-50 p-3 rounded-xl border border-rose-200 anim-fade-in">
                    <span class="text-xs font-semibold text-rose-700"><span id="selected-count">0</span> images selected</span>
                    <button type="button" onclick="bulkDeleteImages()" class="btn-admin btn-admin-danger btn-admin-sm">
                        <i class="fas fa-trash-alt text-[10px]"></i> Delete Selected
                    </button>
                </div>

                <!-- Existing Gallery Grid -->
                <?php if(!empty($gallery_images)): ?>
                    <div class="grid grid-cols-3 sm:grid-cols-4 md:grid-cols-5 gap-3" id="existing-gallery">
                        <?php foreach($gallery_images as $img): ?>
                            <div class="relative group aspect-square rounded-xl overflow-hidden border border-slate-200 bg-slate-50 gallery-item" id="gallery-item-<?php echo $img['id']; ?>">
                                <img src="../<?php echo $img['image_path']; ?>" class="w-full h-full object-cover">
                                
                                <div class="absolute inset-0 bg-slate-900/40 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center gap-2">
                                    <input type="checkbox" class="gallery-checkbox w-4 h-4 rounded text-emerald-600 focus:ring-emerald-500 cursor-pointer" value="<?php echo $img['id']; ?>" onchange="updateBulkBar()">
                                    <button type="button" onclick="deleteGalleryImage(<?php echo $img['id']; ?>)" class="w-7 h-7 rounded-lg bg-rose-600 text-white flex items-center justify-center hover:bg-rose-700 transition-colors" title="Delete">
                                        <i class="fas fa-times text-xs"></i>
                                    </button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <!-- New Upload Preview Grid -->
                <div id="new-gallery-preview-grid" class="grid grid-cols-3 sm:grid-cols-4 md:grid-cols-5 gap-3 hidden"></div>

                <!-- Upload Drag-Drop Box -->
                <div class="border-2 border-dashed border-slate-300 hover:border-[#004f42] rounded-xl p-6 text-center hover:bg-slate-50/50 transition-colors relative cursor-pointer group">
                    <i class="fas fa-cloud-arrow-up text-3xl text-slate-300 group-hover:text-[#004f42] transition-colors mb-2"></i>
                    <p class="text-xs font-semibold text-slate-700">Drop additional gallery photos here or click to browse</p>
                    <p class="text-[11px] text-slate-400 mt-0.5">Supports PNG, JPG, WEBP</p>
                    <input type="file" name="gallery[]" multiple class="absolute inset-0 opacity-0 cursor-pointer" onchange="previewMultipleImages(this)">
                </div>
            </div>

        </div>

        <!-- SIDEBAR COLUMN (1 col) -->
        <div class="space-y-6">
            
            <!-- Visibility & Status -->
            <div class="bg-white rounded-xl border border-slate-200 p-5 shadow-xs space-y-4">
                <h3 class="text-sm font-bold text-slate-900 pb-2 border-b border-slate-100">
                    Publishing
                </h3>

                <div>
                    <label class="admin-label">Visibility Status</label>
                    <select name="is_active" class="admin-select font-semibold">
                        <option value="1" <?php echo $product['is_active'] ? 'selected' : ''; ?>>Active (Visible on Store)</option>
                        <option value="0" <?php echo !$product['is_active'] ? 'selected' : ''; ?>>Draft (Hidden)</option>
                    </select>
                </div>

                <div>
                    <label class="admin-label">Featured on Home</label>
                    <select name="is_featured" class="admin-select font-semibold">
                        <option value="0" <?php echo !$product['is_featured'] ? 'selected' : ''; ?>>No (Normal)</option>
                        <option value="1" <?php echo $product['is_featured'] ? 'selected' : ''; ?>>Yes (Featured in hero sections)</option>
                    </select>
                </div>

                <div>
                    <label class="admin-label">Combo Offer</label>
                    <select name="is_combo" class="admin-select font-semibold">
                        <option value="0" <?php echo !$product['is_combo'] ? 'selected' : ''; ?>>No (Individual snack)</option>
                        <option value="1" <?php echo $product['is_combo'] ? 'selected' : ''; ?>>Yes (Show in combo bar)</option>
                    </select>
                </div>
            </div>

            <!-- Category Radio Selector -->
            <div class="bg-white rounded-xl border border-slate-200 p-5 shadow-xs space-y-3">
                <h3 class="text-sm font-bold text-slate-900 pb-2 border-b border-slate-100">
                    Category Classification
                </h3>

                <div class="space-y-1.5 max-h-56 overflow-y-auto">
                    <?php foreach ($cats as $c): ?>
                    <label class="flex items-center gap-2.5 p-2 rounded-lg hover:bg-slate-50 cursor-pointer transition-colors">
                        <input type="radio" name="category_id" value="<?php echo $c['id']; ?>" <?php echo $product['category_id'] == $c['id'] ? 'checked' : ''; ?> class="w-4 h-4 text-emerald-600 focus:ring-emerald-500">
                        <span class="text-xs font-semibold text-slate-700"><?php echo htmlspecialchars($c['name']); ?></span>
                    </label>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Featured Image Art -->
            <div class="bg-white rounded-xl border border-slate-200 p-5 shadow-xs space-y-4">
                <h3 class="text-sm font-bold text-slate-900 pb-2 border-b border-slate-100">
                    Primary Product Image
                </h3>

                <div class="relative aspect-square bg-slate-50 rounded-xl border-2 border-dashed border-slate-200 hover:border-[#004f42] transition-colors flex items-center justify-center overflow-hidden group">
                    <img id="featured-preview" src="<?php echo $product['image'] ? '../' . $product['image'] : ''; ?>" class="w-full h-full object-contain p-3 <?php echo $product['image'] ? '' : 'hidden'; ?>">
                    
                    <div id="featured-placeholder" class="<?php echo $product['image'] ? 'hidden' : ''; ?> text-center p-4 text-slate-400">
                        <i class="fas fa-image text-3xl mb-1.5 text-slate-300"></i>
                        <p class="text-xs font-semibold text-slate-600">Select main image</p>
                        <p class="text-[11px]">PNG with transparency looks best</p>
                    </div>

                    <input type="file" name="image" class="absolute inset-0 opacity-0 cursor-pointer z-20" onchange="previewImage(this, 'featured-preview')">
                </div>

                <!-- Product Card Background Color -->
                <div>
                    <label class="admin-label">Card Background Tint</label>
                    <div class="flex items-center gap-1.5 mb-2">
                        <?php 
                        $colors = ['#FFFEDC', '#F0FDFA', '#FEF2F2', '#F5F3FF', '#ECFDF5', '#FFF7ED', '#FDF2F8', '#FFFFFF'];
                        foreach($colors as $c): ?>
                            <button type="button" onclick="document.getElementById('bg_color_input').value='<?php echo $c; ?>'" 
                                    class="w-6 h-6 rounded-md border border-slate-300 shadow-2xs hover:scale-110 transition-transform" 
                                    style="background-color: <?php echo $c; ?>"></button>
                        <?php endforeach; ?>
                    </div>
                    <input type="text" name="bg_color" id="bg_color_input" value="<?php echo htmlspecialchars($product['bg_color']); ?>" 
                           placeholder="#FFFEDC" 
                           class="admin-input text-xs font-mono">
                </div>
            </div>

        </div>

    </form>
</div>

<script>
// --- INGREDIENTS LOGIC ---
let ingredients = [];
try {
    const initial = document.getElementById('ingredients-json').value;
    if (initial) {
        ingredients = JSON.parse(initial);
    }
} catch (e) {
    const val = document.getElementById('ingredients-json').value;
    if (val) ingredients = val.split(',').map(s => s.trim()).filter(s => s !== '');
}

function renderIngredients() {
    const list = document.getElementById('ing-list');
    list.innerHTML = '';
    if (ingredients.length === 0) {
        list.innerHTML = '<span class="text-xs text-slate-400 py-1 px-2">No ingredients added yet.</span>';
    } else {
        ingredients.forEach((ing, index) => {
            const el = document.createElement('div');
            el.className = 'bg-white px-2.5 py-1 rounded-lg border border-slate-200 text-xs font-medium text-slate-800 shadow-2xs flex items-center gap-2';
            el.innerHTML = `
                <span>${ing}</span>
                <button type="button" onclick="removeIngredient(${index})" class="text-slate-400 hover:text-rose-500 transition-colors">
                    <i class="fas fa-times text-[10px]"></i>
                </button>
            `;
            list.appendChild(el);
        });
    }
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

document.getElementById('ing-input').addEventListener('keypress', function(e) {
    if (e.key === 'Enter') {
        e.preventDefault();
        addIngredient();
    }
});
renderIngredients();

// --- NUTRITIONAL INFO LOGIC ---
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
        initial.split('\n').forEach(line => {
            const parts = line.split(':');
            if(parts.length === 2) {
                nutrition[parts[0].trim()] = parts[1].trim();
            }
        });
    }

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
    
    Object.entries(nutrition).forEach(([label, value]) => {
        const el = document.createElement('div');
        el.className = 'bg-white p-2.5 rounded-lg border border-slate-200 shadow-2xs flex items-center justify-between gap-2';
        el.innerHTML = `
            <div class="flex-1 min-w-0">
                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">${label}</span>
                <input type="text" value="${value}" onchange="updateNutritionValue('${label}', this.value)" 
                       class="text-xs font-semibold text-slate-800 bg-transparent border-none outline-none p-0 focus:ring-0 w-full placeholder-slate-300" 
                       placeholder="e.g. 10g">
            </div>
            <button type="button" onclick="removeNutrition('${label}')" class="text-slate-300 hover:text-rose-500 p-1 transition-colors">
                <i class="fas fa-trash-alt text-[10px]"></i>
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

document.getElementById('nut-value').addEventListener('keypress', function(e) {
    if (e.key === 'Enter') {
        e.preventDefault();
        addNutrition();
    }
});
renderNutrition();

// --- IMAGE PREVIEW LOGIC ---
function previewImage(input, previewId) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            const preview = document.getElementById(previewId);
            preview.src = e.target.result;
            preview.classList.remove('hidden');
            
            const placeholder = document.getElementById('featured-placeholder');
            if (placeholder) placeholder.classList.add('hidden');
        }
        reader.readAsDataURL(input.files[0]);
    }
}

function previewMultipleImages(input) {
    const previewContainer = document.getElementById('new-gallery-preview-grid');
    if (!previewContainer) return;
    
    previewContainer.innerHTML = '';
    
    if (input.files.length > 0) {
        previewContainer.classList.remove('hidden');
        Array.from(input.files).forEach(file => {
            const reader = new FileReader();
            reader.onload = function(e) {
                const div = document.createElement('div');
                div.className = 'relative aspect-square rounded-xl overflow-hidden border border-emerald-400 bg-emerald-50/20 shadow-2xs';
                div.innerHTML = `
                    <img src="${e.target.result}" class="w-full h-full object-cover">
                    <span class="absolute top-1.5 right-1.5 bg-emerald-600 text-white text-[9px] font-bold px-1.5 py-0.5 rounded shadow">
                        NEW
                    </span>
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
    const ok = typeof window.showConfirm === 'function'
        ? await window.showConfirm('Delete this gallery photo?', {
            title: 'Delete Photo',
            type: 'danger',
            confirmText: 'Delete'
        })
        : confirm('Delete this gallery photo?');
    if (!ok) return;
    
    const item = document.getElementById(`gallery-item-${id}`);
    item.style.opacity = '0.5';

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
        alert('Failed to delete image');
        item.style.opacity = '1';
    }
}

async function bulkDeleteImages() {
    const checked = document.querySelectorAll('.gallery-checkbox:checked');
    if (checked.length === 0) return;
    const ok = typeof window.showConfirm === 'function'
        ? await window.showConfirm(`Delete ${checked.length} selected photos?`, {
            title: 'Delete Photos',
            type: 'danger',
            confirmText: 'Delete'
        })
        : confirm(`Delete ${checked.length} selected photos?`);
    if (!ok) return;

    const ids = Array.from(checked).map(cb => cb.value);
    const bar = document.getElementById('bulk-action-bar');
    const originalHtml = bar.innerHTML;
    bar.innerHTML = '<span class="text-xs font-semibold text-rose-700"><i class="fas fa-spinner fa-spin mr-2"></i>Deleting...</span>';

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
        alert('Failed to delete images');
    } finally {
        bar.innerHTML = originalHtml;
        updateBulkBar();
    }
}
</script>

<?php include 'includes/footer.php'; ?>
