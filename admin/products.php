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

// AJAX Bulk Actions (Status, Weight, Delete, Featured)
if (isset($_POST['ajax_action']) && in_array($_POST['ajax_action'], ['bulk_delete', 'bulk_status', 'bulk_weight', 'bulk_featured'])) {
    $ids = $_POST['ids'] ?? [];
    if (empty($ids)) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'No IDs provided']);
        exit;
    }
    
    $conn = get_db_connection();
    $ids_str = implode(',', array_map('intval', $ids));
    $action = $_POST['ajax_action'];

    if ($action === 'bulk_delete') {
        // Fetch paths to delete featured images
        $res = $conn->query("SELECT image FROM products WHERE id IN ($ids_str)");
        while ($row = $res->fetch_assoc()) {
            if ($row['image']) {
                $full_path = '../' . $row['image'];
                if (file_exists($full_path)) @unlink($full_path);
            }
        }
        
        // Delete gallery images records and files
        $g_res = $conn->query("SELECT image_path FROM product_images WHERE product_id IN ($ids_str)");
        while ($g_row = $g_res->fetch_assoc()) {
            $full_path = '../' . $g_row['image_path'];
            if (file_exists($full_path)) @unlink($full_path);
        }
        $conn->query("DELETE FROM product_images WHERE product_id IN ($ids_str)");
        $conn->query("DELETE FROM products WHERE id IN ($ids_str)");
    } 
    elseif ($action === 'bulk_status') {
        $status = (int)$_POST['status'];
        $conn->query("UPDATE products SET is_active = $status WHERE id IN ($ids_str)");
    }
    elseif ($action === 'bulk_weight') {
        $weight = $conn->real_escape_string($_POST['weight']);
        $conn->query("UPDATE products SET weight = '$weight' WHERE id IN ($ids_str)");
    }
    elseif ($action === 'bulk_featured') {
        $feat = (int)$_POST['featured'];
        if ($feat === 1) {
            $sql = "UPDATE products SET is_featured = 1, is_active = 1 WHERE id IN ($ids_str)";
        } else {
            $sql = "UPDATE products SET is_featured = 0 WHERE id IN ($ids_str)";
        }
        
        if (!$conn->query($sql)) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => $conn->error]);
            exit;
        }
    }

    header('Content-Type: application/json');
    echo json_encode(['success' => true]);
    exit;
}

// AJAX Status Toggle (Single)
if (isset($_POST['ajax_action']) && in_array($_POST['ajax_action'], ['toggle_status', 'toggle_featured'])) {
    $id = (int)$_POST['id'];
    $action = $_POST['ajax_action'];
    
    if ($action === 'toggle_status') {
        $current = fetch_one("SELECT is_active FROM products WHERE id = ?", [$id]);
        if ($current) {
            $new_status = $current['is_active'] ? 0 : 1;
            execute_query("UPDATE products SET is_active = ? WHERE id = ?", [$new_status, $id]);
            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'new_status' => $new_status, 'label' => $new_status ? 'Active' : 'Inactive']);
            exit;
        }
    } elseif ($action === 'toggle_featured') {
        $current = fetch_one("SELECT is_featured FROM products WHERE id = ?", [$id]);
        if ($current) {
            $new_val = $current['is_featured'] ? 0 : 1;
            execute_query("UPDATE products SET is_featured = ? WHERE id = ?", [$new_val, $id]);
            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'is_featured' => $new_val]);
            exit;
        }
    }
}

// Handle Delete (GET fallback)
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    get_db_connection()->query("DELETE FROM products WHERE id = $id");
    header("Location: products.php");
    exit;
}

include 'includes/header.php';

// Search & Filter Logic
$search = sanitize_input($_GET['search'] ?? '');
$category_filter = (int)($_GET['category'] ?? 0);
$stock_filter = sanitize_input($_GET['stock'] ?? '');

$params = [];
$where = "WHERE 1=1";

if ($search) {
    $where .= " AND (p.name LIKE ? OR c.name LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if ($category_filter > 0) {
    $where .= " AND p.category_id = ?";
    $params[] = $category_filter;
}

if ($stock_filter === 'low') {
    $where .= " AND p.stock > 0 AND p.stock < 10";
} elseif ($stock_filter === 'out') {
    $where .= " AND p.stock <= 0";
} elseif ($stock_filter === 'active') {
    $where .= " AND p.is_active = 1";
} elseif ($stock_filter === 'draft') {
    $where .= " AND p.is_active = 0";
}

$query = "SELECT p.*, c.name as cat_name FROM products p LEFT JOIN categories c ON p.category_id = c.id $where ORDER BY p.is_active DESC, p.id DESC";
$pagination = get_pagination_data($query, $params, 12);
$products = $pagination['records'];

$categories = fetch_all("SELECT * FROM categories ORDER BY name ASC");

// Summary counts for quick status tabs
$stat_total = fetch_one("SELECT COUNT(*) as c FROM products")['c'] ?? 0;
$stat_active = fetch_one("SELECT COUNT(*) as c FROM products WHERE is_active = 1")['c'] ?? 0;
$stat_low = fetch_one("SELECT COUNT(*) as c FROM products WHERE stock > 0 AND stock < 10 AND is_active = 1")['c'] ?? 0;
$stat_out = fetch_one("SELECT COUNT(*) as c FROM products WHERE stock <= 0")['c'] ?? 0;
?>

<!-- PAGE HEADER -->
<div class="mb-5 flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
    <div>
       
        <h1 class="text-2xl font-bold text-slate-900 tracking-tight flex items-center gap-2.5">
            <i class="fas fa-layer-group text-emerald-700 text-xl"></i>
            Product Catalog
        </h1>
        <p class="text-xs text-slate-500 mt-0.5">Manage product inventory, pricing, weights, storefront visibility, and merchandising tags.</p>
    </div>
    
    <div class="flex items-center gap-2.5 flex-wrap self-stretch sm:self-auto">
        <!-- View Mode Switcher -->
        <div class="inline-flex items-center p-1 bg-slate-100 border border-slate-200 rounded-xl" id="viewToggleGroup">
            <button type="button" onclick="switchProductView('grid')" id="viewBtnGrid" class="px-2.5 py-1 text-xs font-semibold rounded-lg transition-all bg-white text-slate-900 shadow-2xs" title="Grid View">
                <i class="fas fa-th-large mr-1 text-[11px]"></i> Grid
            </button>
            <button type="button" onclick="switchProductView('table')" id="viewBtnTable" class="px-2.5 py-1 text-xs font-semibold rounded-lg transition-all text-slate-500 hover:text-slate-900" title="Table View">
                <i class="fas fa-list mr-1 text-[11px]"></i> Table
            </button>
        </div>

        <a href="product_sorting.php" class="btn-admin btn-admin-secondary text-xs" title="Drag & drop custom sorting">
            <i class="fas fa-arrows-alt text-slate-400"></i> Sort Sequence
        </a>

        <a href="product_form.php" class="btn-admin btn-admin-primary text-xs">
            <i class="fas fa-plus"></i> New Product
        </a>
    </div>
</div>

<!-- FILTER & SEARCH TOOLBAR -->
<div class="admin-card p-3 mb-6 bg-white space-y-3">
    <!-- Top row: Status Tabs -->
    <div class="flex items-center justify-between flex-wrap gap-2 pb-3 border-b border-slate-100">
        <div class="flex items-center gap-1.5 flex-wrap text-xs">
            <?php
            $make_tab_url = function($filter_val) use ($search, $category_filter) {
                $p = [];
                if ($search) $p['search'] = $search;
                if ($category_filter) $p['category'] = $category_filter;
                if ($filter_val) $p['stock'] = $filter_val;
                return 'products.php' . (!empty($p) ? '?' . http_build_query($p) : '');
            };
            ?>
            <a href="<?php echo $make_tab_url(''); ?>" class="px-3 py-1.5 rounded-lg font-semibold transition-colors <?php echo empty($stock_filter) ? 'bg-[#004f42] text-white shadow-2xs' : 'text-slate-600 hover:bg-slate-100'; ?>">
                All Products <span class="ml-1 opacity-80">(<?php echo $stat_total; ?>)</span>
            </a>
            <a href="<?php echo $make_tab_url('active'); ?>" class="px-3 py-1.5 rounded-lg font-semibold transition-colors <?php echo $stock_filter === 'active' ? 'bg-[#004f42] text-white shadow-2xs' : 'text-slate-600 hover:bg-slate-100'; ?>">
                Active <span class="ml-1 opacity-80">(<?php echo $stat_active; ?>)</span>
            </a>
            <a href="<?php echo $make_tab_url('low'); ?>" class="px-3 py-1.5 rounded-lg font-semibold transition-colors <?php echo $stock_filter === 'low' ? 'bg-[#004f42] text-white shadow-2xs' : 'text-amber-700 hover:bg-amber-50'; ?>">
                <i class="fas fa-exclamation-triangle text-[10px] mr-1"></i> Low Stock <span class="ml-1 opacity-80">(<?php echo $stat_low; ?>)</span>
            </a>
            <a href="<?php echo $make_tab_url('out'); ?>" class="px-3 py-1.5 rounded-lg font-semibold transition-colors <?php echo $stock_filter === 'out' ? 'bg-[#004f42] text-white shadow-2xs' : 'text-rose-700 hover:bg-rose-50'; ?>">
                Out of Stock <span class="ml-1 opacity-80">(<?php echo $stat_out; ?>)</span>
            </a>
        </div>

        <div class="text-xs text-slate-500 font-medium">
            Found <strong class="text-slate-800"><?php echo (int)$pagination['total_records']; ?></strong> items
        </div>
    </div>

    <!-- Bottom row: Search + Category Filter -->
    <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-2.5">
        <form method="GET" class="relative flex-1">
            <?php if($category_filter): ?><input type="hidden" name="category" value="<?php echo $category_filter; ?>"><?php endif; ?>
            <?php if($stock_filter): ?><input type="hidden" name="stock" value="<?php echo $stock_filter; ?>"><?php endif; ?>
            <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search products by title or category..." class="admin-input pl-9 pr-8 py-2 text-xs">
            <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
            <?php if($search): ?>
                <a href="<?php echo $make_tab_url($stock_filter); ?>" class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-700 text-xs" title="Clear search">
                    <i class="fas fa-times"></i>
                </a>
            <?php endif; ?>
        </form>

        <form method="GET" class="sm:w-56 shrink-0">
            <?php if($search): ?><input type="hidden" name="search" value="<?php echo htmlspecialchars($search); ?>"><?php endif; ?>
            <?php if($stock_filter): ?><input type="hidden" name="stock" value="<?php echo $stock_filter; ?>"><?php endif; ?>
            <select name="category" onchange="this.form.submit()" class="admin-select py-2 text-xs">
                <option value="">All Categories (<?php echo count($categories); ?>)</option>
                <?php foreach ($categories as $cat): ?>
                    <option value="<?php echo $cat['id']; ?>" <?php echo $category_filter == $cat['id'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($cat['name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </form>

        <?php if($search || $category_filter || $stock_filter): ?>
            <a href="products.php" class="btn-admin btn-admin-secondary text-xs px-3 py-2 shrink-0 text-slate-500 hover:text-rose-600" title="Reset all filters">
                <i class="fas fa-redo-alt text-[10px]"></i> Reset
            </a>
        <?php endif; ?>
    </div>
</div>

<!-- FLOATING BULK ACTIONS DOCK (LIGHT THEMED & RESPONSIVE) -->
<div id="bulk-action-bar" class="hidden admin-bulk-dock">
    <div class="flex items-center gap-2 border-r border-slate-200 pr-3 shrink-0">
        <span class="bulk-counter-badge"><span id="selected-count">0</span> Selected</span>
        <button type="button" onclick="document.querySelectorAll('.product-checkbox').forEach(cb => {cb.checked = false; updateBulkBar();})" class="text-slate-400 hover:text-slate-700 ml-1" title="Clear selection">
            <i class="fas fa-times text-xs"></i>
        </button>
    </div>

    <!-- Status Toggles -->
    <div class="flex items-center gap-1 shrink-0">
        <button type="button" onclick="bulkUpdateAction('bulk_status', {status: 1})" class="bulk-btn hover:text-emerald-700 hover:border-emerald-300">
            <i class="fas fa-eye text-emerald-600 text-[11px]"></i> Active
        </button>
        <button type="button" onclick="bulkUpdateAction('bulk_status', {status: 0})" class="bulk-btn hover:text-slate-700 hover:border-slate-300">
            <i class="fas fa-eye-slash text-slate-400 text-[11px]"></i> Inactive
        </button>
    </div>

    <!-- Homepage Feature -->
    <div class="flex items-center gap-1 shrink-0">
        <button type="button" onclick="bulkUpdateAction('bulk_featured', {featured: 1})" class="bulk-btn bulk-btn-amber" title="Feature on homepage">
            <i class="fas fa-star text-[11px]"></i> Feature
        </button>
        <button type="button" onclick="bulkUpdateAction('bulk_featured', {featured: 0})" class="bulk-btn" title="Remove from homepage">
            <i class="far fa-star text-slate-400 text-[11px]"></i> Unfeature
        </button>
    </div>

    <!-- Weight Quick Set -->
    <div class="flex items-center gap-1 bg-slate-50 border border-slate-200 px-2 py-1 rounded-lg shrink-0">
        <span class="text-[11px] text-slate-500 font-medium">Kg:</span>
        <input type="number" step="0.001" id="bulk-weight-input" placeholder="0.5" class="w-14 bg-white border border-slate-200 text-xs font-bold text-slate-800 rounded px-1.5 py-0.5 outline-none focus:border-emerald-500">
        <button type="button" onclick="bulkUpdateAction('bulk_weight', {weight: document.getElementById('bulk-weight-input').value})" class="w-6 h-6 rounded bg-[#004f42] hover:bg-[#00382f] text-white flex items-center justify-center transition-colors">
            <i class="fas fa-check text-[10px]"></i>
        </button>
    </div>

    <!-- Delete Action -->
    <button type="button" onclick="bulkDeleteProducts()" class="bulk-btn bulk-btn-danger shrink-0" title="Delete selected products">
        <i class="fas fa-trash-alt text-xs"></i> Delete
    </button>
</div>

<!-- ZERO RESULTS STATE -->
<?php if(empty($products)): ?>
    <div class="bg-white rounded-2xl border border-slate-200 p-12 text-center">
        <div class="w-14 h-14 rounded-2xl bg-slate-100 text-slate-400 flex items-center justify-center mx-auto mb-3 text-2xl">
            <i class="fas fa-apple-alt"></i>
        </div>
        <h3 class="text-base font-bold text-slate-800">No products match your criteria</h3>
        <p class="text-xs text-slate-500 mt-1 max-w-sm mx-auto">Try clearing your search keyword, switching category, or resetting stock filters.</p>
        <a href="products.php" class="btn-admin btn-admin-secondary text-xs mt-4">Reset Filters</a>
    </div>
<?php else: ?>

    <!-- 1. COMPACT & BALANCED GRID VIEW -->
    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 xl:grid-cols-4 gap-4" id="productsGridView">
        <?php foreach ($products as $p): 
            $img_src = !empty($p['image']) ? '../' . ltrim($p['image'], '/') : '../assets/images/placeholder.png';
            $prod_url = !empty($p['slug']) ? '../product/' . $p['slug'] : '../product.php?id=' . $p['id'];
        ?>
        <div class="product-card bg-white rounded-xl border border-slate-200/90 hover:border-slate-300 hover:shadow-md transition-all duration-200 flex flex-col group overflow-hidden">
            
            <!-- Fixed Proportion Image Container (No dead whitespace!) -->
            <div class="h-44 sm:h-48 relative overflow-hidden flex items-center justify-center p-3 bg-slate-50/60 border-b border-slate-100" style="<?php echo !empty($p['bg_color']) ? 'background-color:' . htmlspecialchars($p['bg_color']) . ';' : ''; ?>">
                <img src="<?php echo htmlspecialchars($img_src); ?>" class="max-h-full max-w-full object-contain group-hover:scale-105 transition-transform duration-300" alt="<?php echo htmlspecialchars($p['name']); ?>" loading="lazy">
                
                <!-- Top-Left: Featured Star Button -->
                <button type="button" onclick="toggleFeatured(<?php echo $p['id']; ?>, this)" class="featured-toggle absolute top-2.5 left-2.5 w-7 h-7 rounded-lg bg-white/90 backdrop-blur-xs border border-slate-200/80 shadow-2xs flex items-center justify-center text-xs transition-transform hover:scale-110 <?php echo $p['is_featured'] ? 'text-amber-400' : 'text-slate-300 hover:text-slate-400'; ?>" title="Toggle featured on homepage">
                    <i class="fas fa-star"></i>
                </button>

                <!-- Top-Right: Bulk Selection Checkbox -->
                <div class="absolute top-2.5 right-2.5">
                    <label class="w-7 h-7 rounded-lg bg-white/90 backdrop-blur-xs border border-slate-200/80 shadow-2xs flex items-center justify-center cursor-pointer hover:bg-white transition-colors">
                        <input type="checkbox" class="product-checkbox w-4 h-4 rounded border-slate-300 text-emerald-600 focus:ring-emerald-500 cursor-pointer" value="<?php echo $p['id']; ?>" onchange="updateBulkBar()">
                    </label>
                </div>
                
                <!-- Bottom-Left: Stock Level Pill -->
                <?php if($p['stock'] <= 0): ?>
                    <span class="absolute bottom-2.5 left-2.5 bg-slate-900/90 backdrop-blur-xs text-white text-[10px] font-bold px-2 py-0.5 rounded-md shadow-2xs">
                        Out of Stock
                    </span>
                <?php elseif($p['stock'] <= 5): ?>
                    <span class="absolute bottom-2.5 left-2.5 bg-rose-600 text-white text-[10px] font-bold px-2 py-0.5 rounded-md shadow-2xs">
                        Only <?php echo $p['stock']; ?> left
                    </span>
                <?php endif; ?>

                <!-- Bottom-Right: Active / Inactive Toggle Badge -->
                <button type="button" onclick="toggleProductStatus(<?php echo $p['id']; ?>, this)" class="absolute bottom-2.5 right-2.5 text-[10px] font-bold uppercase tracking-wider px-2 py-0.5 rounded-md transition-all shadow-2xs <?php echo $p['is_active'] ? 'bg-emerald-50 text-emerald-700 border border-emerald-200 hover:bg-emerald-100' : 'bg-slate-100 text-slate-500 border border-slate-200 hover:bg-slate-200'; ?>" title="Click to toggle visibility">
                    <?php echo $p['is_active'] ? 'Active' : 'Inactive'; ?>
                </button>
            </div>

            <!-- Content Area (Clean, Compact, Well-Proportioned) -->
            <div class="p-3.5 flex flex-col justify-between flex-1 space-y-3">
                <div>
                    <!-- Category & Meta Row -->
                    <div class="flex items-center justify-between gap-2 mb-1.5">
                        <span class="text-[10px] font-bold uppercase tracking-wider text-emerald-800 bg-emerald-50 border border-emerald-100 px-2 py-0.5 rounded-md truncate max-w-[150px]">
                            <?php echo htmlspecialchars($p['cat_name'] ?? 'General'); ?>
                        </span>
                        <?php if(!empty($p['weight'])): ?>
                            <span class="text-[10px] font-semibold text-slate-400 font-mono">
                                <?php echo htmlspecialchars($p['weight']); ?>
                            </span>
                        <?php endif; ?>
                    </div>

                    <!-- Title -->
                    <h3 class="font-bold text-sm text-slate-900 leading-snug line-clamp-2 group-hover:text-[#004f42] transition-colors" title="<?php echo htmlspecialchars($p['name']); ?>">
                        <?php echo htmlspecialchars($p['name']); ?>
                    </h3>
                </div>

                <!-- Price & Stock Row -->
                <div>
                    <div class="flex items-center justify-between pt-2 border-t border-slate-100 mb-2.5">
                        <div class="flex items-baseline gap-1.5">
                            <span class="font-extrabold text-base text-slate-900">₹<?php echo number_format($p['price']); ?></span>
                            <?php if(!empty($p['original_price']) && $p['original_price'] > $p['price']): ?>
                                <span class="text-xs text-slate-400 line-through">₹<?php echo number_format($p['original_price']); ?></span>
                            <?php endif; ?>
                        </div>
                        <div class="text-right">
                            <span class="text-xs font-semibold flex items-center gap-1 <?php echo $p['stock'] <= 5 ? 'text-rose-600' : 'text-slate-600'; ?>">
                                <span class="w-1.5 h-1.5 rounded-full <?php echo $p['stock'] <= 0 ? 'bg-rose-500' : ($p['stock'] <= 5 ? 'bg-amber-500' : 'bg-emerald-500'); ?>"></span>
                                <?php echo $p['stock']; ?> units
                            </span>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="flex items-center gap-1.5">
                        <a href="product_form.php?id=<?php echo $p['id']; ?>" class="btn-admin btn-admin-secondary text-xs flex-1 justify-center py-1.5">
                            <i class="fas fa-pen text-[10px] text-slate-400"></i> Edit
                        </a>
                        <a href="<?php echo htmlspecialchars($prod_url); ?>" target="_blank" class="btn-admin btn-admin-secondary text-xs px-2.5 py-1.5 text-slate-400 hover:text-slate-800" title="View product in storefront">
                            <i class="fas fa-external-link-alt text-[10px]"></i>
                        </a>
                        <button type="button" onclick="if(confirm('Delete product <?php echo addslashes($p['name']); ?>?')) window.location='?delete=<?php echo $p['id']; ?>'" class="btn-admin btn-admin-danger text-xs px-2.5 py-1.5" title="Delete product">
                            <i class="fas fa-trash-alt text-[10px]"></i>
                        </button>
                    </div>
                </div>

            </div>

        </div>
        <?php endforeach; ?>
    </div>

    <!-- 2. HIGH-DENSITY TABLE VIEW (Alternative for Fast Scanning) -->
    <div class="admin-card overflow-hidden hidden" id="productsTableView">
        <div class="overflow-x-auto">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th class="w-10 text-center">
                            <input type="checkbox" id="selectAllTable" class="rounded border-slate-300 text-emerald-600 focus:ring-emerald-500 cursor-pointer" onchange="toggleSelectAllTable(this)">
                        </th>
                        <th class="min-w-[260px]">Product</th>
                        <th class="min-w-[120px]">Category</th>
                        <th class="min-w-[110px]">Price</th>
                        <th class="min-w-[100px]">Inventory</th>
                        <th class="w-20 text-center">Featured</th>
                        <th class="w-24 text-center">Status</th>
                        <th class="w-28 text-right pr-4">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php foreach ($products as $p): 
                        $img_src = !empty($p['image']) ? '../' . ltrim($p['image'], '/') : '../assets/images/placeholder.png';
                        $prod_url = !empty($p['slug']) ? '../product/' . $p['slug'] : '../product.php?id=' . $p['id'];
                    ?>
                    <tr class="hover:bg-slate-50/70 transition-colors">
                        <!-- Checkbox -->
                        <td class="text-center">
                            <input type="checkbox" class="product-checkbox rounded border-slate-300 text-emerald-600 focus:ring-emerald-500 cursor-pointer" value="<?php echo $p['id']; ?>" onchange="updateBulkBar()">
                        </td>

                        <!-- Product Thumbnail + Name + Weight -->
                        <td>
                            <div class="flex items-center gap-3">
                                <div class="w-11 h-11 rounded-lg bg-slate-50 border border-slate-200 p-1 shrink-0 flex items-center justify-center">
                                    <img src="<?php echo htmlspecialchars($img_src); ?>" class="max-w-full max-h-full object-contain" alt="">
                                </div>
                                <div class="min-w-0">
                                    <a href="product_form.php?id=<?php echo $p['id']; ?>" class="font-bold text-slate-800 hover:text-emerald-700 transition-colors text-xs truncate block max-w-xs">
                                        <?php echo htmlspecialchars($p['name']); ?>
                                    </a>
                                    <div class="text-[10px] text-slate-400 flex items-center gap-1.5 mt-0.5">
                                        <span>ID: #<?php echo $p['id']; ?></span>
                                        <?php if(!empty($p['weight'])): ?>
                                            <span>• <?php echo htmlspecialchars($p['weight']); ?></span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </td>

                        <!-- Category -->
                        <td>
                            <span class="text-[11px] font-semibold text-slate-600 bg-slate-100 border border-slate-200/80 px-2 py-0.5 rounded-md">
                                <?php echo htmlspecialchars($p['cat_name'] ?? 'General'); ?>
                            </span>
                        </td>

                        <!-- Price -->
                        <td>
                            <div class="font-bold text-xs text-slate-900">
                                ₹<?php echo number_format($p['price']); ?>
                                <?php if(!empty($p['original_price']) && $p['original_price'] > $p['price']): ?>
                                    <span class="text-[10px] text-slate-400 line-through font-normal block">₹<?php echo number_format($p['original_price']); ?></span>
                                <?php endif; ?>
                            </div>
                        </td>

                        <!-- Stock -->
                        <td>
                            <div class="flex items-center gap-1.5">
                                <span class="w-2 h-2 rounded-full <?php echo $p['stock'] <= 0 ? 'bg-rose-500' : ($p['stock'] <= 5 ? 'bg-amber-500' : 'bg-emerald-500'); ?>"></span>
                                <span class="text-xs font-semibold <?php echo $p['stock'] <= 5 ? 'text-rose-600' : 'text-slate-700'; ?>">
                                    <?php echo $p['stock']; ?>
                                </span>
                            </div>
                        </td>

                        <!-- Featured Toggle -->
                        <td class="text-center">
                            <button type="button" onclick="toggleFeatured(<?php echo $p['id']; ?>, this)" class="featured-toggle p-1 text-xs transition-transform hover:scale-110 <?php echo $p['is_featured'] ? 'text-amber-400' : 'text-slate-300 hover:text-slate-400'; ?>" title="Toggle featured on homepage">
                                <i class="fas fa-star"></i>
                            </button>
                        </td>

                        <!-- Status Toggle -->
                        <td class="text-center">
                            <button type="button" onclick="toggleProductStatus(<?php echo $p['id']; ?>, this)" class="text-[10px] font-bold uppercase tracking-wider px-2 py-0.5 rounded-md transition-colors <?php echo $p['is_active'] ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-slate-100 text-slate-500 border border-slate-200'; ?>">
                                <?php echo $p['is_active'] ? 'Active' : 'Inactive'; ?>
                            </button>
                        </td>

                        <!-- Actions -->
                        <td class="text-right pr-4">
                            <div class="flex items-center justify-end gap-1.5">
                                <a href="<?php echo htmlspecialchars($prod_url); ?>" target="_blank" class="btn-admin btn-admin-secondary text-xs p-1.5 text-slate-400 hover:text-slate-800" title="View in store">
                                    <i class="fas fa-external-link-alt text-[10px]"></i>
                                </a>
                                <a href="product_form.php?id=<?php echo $p['id']; ?>" class="btn-admin btn-admin-secondary text-xs p-1.5 text-slate-600" title="Edit">
                                    <i class="fas fa-pen text-[10px]"></i>
                                </a>
                                <button type="button" onclick="if(confirm('Delete product <?php echo addslashes($p['name']); ?>?')) window.location='?delete=<?php echo $p['id']; ?>'" class="btn-admin btn-admin-danger text-xs p-1.5" title="Delete">
                                    <i class="fas fa-trash-alt text-[10px]"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

<?php endif; ?>

<!-- RESPONSIVE PAGINATION -->
<div class="mt-6">
    <?php echo render_pagination($pagination); ?>
</div>

<script>
async function toggleFeatured(id, btn) {
    const icon = btn.querySelector('i');
    btn.style.pointerEvents = 'none';
    icon.classList.remove('fa-star');
    icon.classList.add('fa-spinner', 'fa-spin');

    try {
        const formData = new FormData();
        formData.append('ajax_action', 'toggle_featured');
        formData.append('id', id);

        const response = await fetch('products.php', {
            method: 'POST',
            body: formData
        });

        const data = await response.json();
        if (data.success) {
            if (data.is_featured) {
                btn.className = 'featured-toggle p-0.5 text-xs transition-transform hover:scale-110 text-amber-400';
            } else {
                btn.className = 'featured-toggle p-0.5 text-xs transition-transform hover:scale-110 text-slate-300 hover:text-slate-400';
            }
        }
    } catch (e) {
        console.error(e);
    } finally {
        icon.classList.remove('fa-spinner', 'fa-spin');
        icon.classList.add('fa-star');
        btn.style.pointerEvents = 'auto';
    }
}

async function toggleProductStatus(id, btn) {
    const originalContent = btn.innerHTML;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin text-[10px]"></i>';
    btn.disabled = true;

    try {
        const formData = new FormData();
        formData.append('ajax_action', 'toggle_status');
        formData.append('id', id);

        const response = await fetch('products.php', {
            method: 'POST',
            body: formData
        });

        const data = await response.json();

        if (data.success) {
            btn.innerHTML = data.label;
            if (data.new_status) {
                btn.className = 'text-[10px] font-bold uppercase tracking-wider px-2 py-0.5 rounded-md transition-colors bg-emerald-50 text-emerald-700 border border-emerald-200';
            } else {
                btn.className = 'text-[10px] font-bold uppercase tracking-wider px-2 py-0.5 rounded-md transition-colors bg-slate-100 text-slate-500 border border-slate-200';
            }
        } else {
            alert('Failed to update status');
            btn.innerHTML = originalContent;
        }
    } catch (error) {
        console.error('Error:', error);
        alert('An error occurred');
        btn.innerHTML = originalContent;
    } finally {
        btn.disabled = false;
    }
}

function updateBulkBar() {
    const checked = document.querySelectorAll('.product-checkbox:checked');
    const bar = document.getElementById('bulk-action-bar');
    const count = document.getElementById('selected-count');
    
    if (checked.length > 0) {
        bar.classList.remove('hidden');
        bar.classList.add('flex');
        count.innerText = checked.length;
    } else {
        bar.classList.add('hidden');
        bar.classList.remove('flex');
    }
}

async function bulkUpdateAction(action, extraData = {}) {
    const checked = document.querySelectorAll('.product-checkbox:checked');
    if (checked.length === 0) return;
    
    if (action === 'bulk_weight' && !extraData.weight) {
        alert('Please enter a weight value');
        return;
    }

    const ids = Array.from(checked).map(cb => cb.value);
    const bar = document.getElementById('bulk-action-bar');
    const originalBar = bar.innerHTML;
    bar.innerHTML = '<div class="flex items-center gap-2 px-6 py-1"><i class="fas fa-spinner fa-spin text-emerald-400"></i> <span class="text-xs font-semibold text-slate-300">Applying changes...</span></div>';

    try {
        const formData = new FormData();
        formData.append('ajax_action', action);
        ids.forEach(id => formData.append('ids[]', id));
        
        for (const [key, value] of Object.entries(extraData)) {
            formData.append(key, value);
        }

        const response = await fetch('products.php', {
            method: 'POST',
            body: formData
        });

        const data = await response.json();
        if (data.success) {
            window.location.reload(); 
        } else {
            alert('Failed to update products: ' + (data.error || 'Unknown error'));
            bar.innerHTML = originalBar;
        }
    } catch (error) {
        console.error('Error:', error);
        alert('An error occurred');
        bar.innerHTML = originalBar;
    }
}

async function bulkDeleteProducts() {
    const checked = document.querySelectorAll('.product-checkbox:checked');
    if (checked.length === 0) return;
    const ok = typeof window.showConfirm === 'function'
        ? await window.showConfirm(`Are you sure you want to delete ${checked.length} selected products? This will also delete their gallery photos.`, {
            title: 'Delete Products',
            type: 'danger',
            confirmText: 'Delete'
        })
        : confirm(`Are you sure you want to delete ${checked.length} selected products?`);
    if (!ok) return;

    await bulkUpdateAction('bulk_delete');
}

// View Mode Switching (Grid vs Table)
function switchProductView(mode) {
    const gridView = document.getElementById('productsGridView');
    const tableView = document.getElementById('productsTableView');
    const gridBtn = document.getElementById('viewBtnGrid');
    const tableBtn = document.getElementById('viewBtnTable');
    
    if (!gridView || !tableView) return;

    if (mode === 'table') {
        gridView.classList.add('hidden');
        tableView.classList.remove('hidden');
        if (tableBtn) {
            tableBtn.className = 'px-2.5 py-1 text-xs font-semibold rounded-lg transition-all bg-white text-slate-900 shadow-2xs';
        }
        if (gridBtn) {
            gridBtn.className = 'px-2.5 py-1 text-xs font-semibold rounded-lg transition-all text-slate-500 hover:text-slate-900';
        }
        localStorage.setItem('driyum_product_view', 'table');
    } else {
        tableView.classList.add('hidden');
        gridView.classList.remove('hidden');
        if (gridBtn) {
            gridBtn.className = 'px-2.5 py-1 text-xs font-semibold rounded-lg transition-all bg-white text-slate-900 shadow-2xs';
        }
        if (tableBtn) {
            tableBtn.className = 'px-2.5 py-1 text-xs font-semibold rounded-lg transition-all text-slate-500 hover:text-slate-900';
        }
        localStorage.setItem('driyum_product_view', 'grid');
    }
}

function toggleSelectAllTable(masterCb) {
    const tableCheckboxes = document.querySelectorAll('#productsTableView .product-checkbox');
    tableCheckboxes.forEach(cb => {
        cb.checked = masterCb.checked;
    });
    updateBulkBar();
}

// Restore user view preference
document.addEventListener('DOMContentLoaded', () => {
    const saved = localStorage.getItem('driyum_product_view');
    if (saved === 'table') {
        switchProductView('table');
    }
});
</script>
<?php include 'includes/footer.php'; ?>
