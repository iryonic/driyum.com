<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

// Strict Admin Check
if (!isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
    header("Location: ../login.php");
    exit;
}

$conn = get_db_connection();

// --- AJAX REORDER HANDLER (Combos, Featured, Shop) ---
if (isset($_GET['action'])) {
    header('Content-Type: application/json');
    $action = $_GET['action'];

    // 1. Reorder Combos
    if ($action === 'reorder_combos') {
        $input = json_decode(file_get_contents('php://input'), true);
        if (!empty($input['order']) && is_array($input['order'])) {
            $stmt = $conn->prepare("UPDATE products SET combo_sort_order = ? WHERE id = ?");
            foreach ($input['order'] as $item) {
                $pid = intval($item['id']);
                $sorder = intval($item['sort_order']);
                $stmt->bind_param("ii", $sorder, $pid);
                $stmt->execute();
            }
            echo json_encode(['success' => true]);
            exit;
        }
        echo json_encode(['success' => false, 'message' => 'Invalid order payload']);
        exit;
    }

    // 2. Reorder Featured ("Our Products")
    if ($action === 'reorder_featured') {
        $input = json_decode(file_get_contents('php://input'), true);
        if (!empty($input['order']) && is_array($input['order'])) {
            $stmt = $conn->prepare("UPDATE products SET featured_sort_order = ? WHERE id = ?");
            foreach ($input['order'] as $item) {
                $pid = intval($item['id']);
                $sorder = intval($item['sort_order']);
                $stmt->bind_param("ii", $sorder, $pid);
                $stmt->execute();
            }
            echo json_encode(['success' => true]);
            exit;
        }
        echo json_encode(['success' => false, 'message' => 'Invalid order payload']);
        exit;
    }

    // 3. Reorder Shop Page Catalog
    if ($action === 'reorder_shop') {
        $input = json_decode(file_get_contents('php://input'), true);
        if (!empty($input['order']) && is_array($input['order'])) {
            $stmt = $conn->prepare("UPDATE products SET sort_order = ? WHERE id = ?");
            foreach ($input['order'] as $item) {
                $pid = intval($item['id']);
                $sorder = intval($item['sort_order']);
                $stmt->bind_param("ii", $sorder, $pid);
                $stmt->execute();
            }
            echo json_encode(['success' => true]);
            exit;
        }
        echo json_encode(['success' => false, 'message' => 'Invalid order payload']);
        exit;
    }

    // 4. Remove / Toggle Combo Status
    if ($action === 'remove_combo' || $action === 'toggle_combo') {
        $id = intval($_GET['id'] ?? 0);
        if ($id > 0) {
            $conn->query("UPDATE products SET is_combo = 0 WHERE id = $id");
            echo json_encode(['success' => true]);
            exit;
        }
        echo json_encode(['success' => false]);
        exit;
    }

    // 5. Remove / Toggle Featured Status
    if ($action === 'remove_featured' || $action === 'toggle_featured') {
        $id = intval($_GET['id'] ?? 0);
        if ($id > 0) {
            $conn->query("UPDATE products SET is_featured = 0 WHERE id = $id");
            echo json_encode(['success' => true]);
            exit;
        }
        echo json_encode(['success' => false]);
        exit;
    }

    // 6. Add to Combo
    if ($action === 'add_combo') {
        $id = intval($_POST['product_id'] ?? 0);
        if ($id > 0) {
            $max = fetch_one("SELECT MAX(combo_sort_order) as m FROM products WHERE is_combo = 1")['m'] ?? 0;
            $next = $max + 1;
            $conn->query("UPDATE products SET is_combo = 1, combo_sort_order = $next WHERE id = $id");
            $_SESSION['msg'] = "Product added to Homepage Combos!";
            header("Location: product_sorting.php?tab=combos");
            exit;
        }
    }

    // 7. Add to Featured
    if ($action === 'add_featured') {
        $id = intval($_POST['product_id'] ?? 0);
        if ($id > 0) {
            $max = fetch_one("SELECT MAX(featured_sort_order) as m FROM products WHERE is_featured = 1")['m'] ?? 0;
            $next = $max + 1;
            $conn->query("UPDATE products SET is_featured = 1, featured_sort_order = $next WHERE id = $id");
            $_SESSION['msg'] = "Product added to Homepage 'Our Products'!";
            header("Location: product_sorting.php?tab=featured");
            exit;
        }
    }
}

// Active Tab determination
$active_tab = $_GET['tab'] ?? 'combos';
if (!in_array($active_tab, ['combos', 'featured', 'shop'])) {
    $active_tab = 'combos';
}

// Fetch Lists
$combo_products = fetch_all("SELECT p.*, c.name as category_name 
                             FROM products p 
                             LEFT JOIN categories c ON p.category_id = c.id 
                             WHERE p.is_combo = 1 AND p.is_active = 1 
                             ORDER BY CASE WHEN p.stock > 0 THEN 0 ELSE 1 END ASC, p.combo_sort_order ASC, p.id DESC");

$featured_products = fetch_all("SELECT p.*, c.name as category_name 
                               FROM products p 
                               LEFT JOIN categories c ON p.category_id = c.id 
                               WHERE p.is_featured = 1 AND p.is_active = 1 
                               ORDER BY CASE WHEN p.stock > 0 THEN 0 ELSE 1 END ASC, p.featured_sort_order ASC, p.id DESC");

$shop_products = fetch_all("SELECT p.*, c.name as category_name 
                           FROM products p 
                           LEFT JOIN categories c ON p.category_id = c.id 
                           WHERE p.is_active = 1 
                           ORDER BY CASE WHEN p.stock > 0 THEN 0 ELSE 1 END ASC, p.sort_order ASC, p.id DESC");

$categories = fetch_all("SELECT * FROM categories WHERE is_active = 1 ORDER BY name ASC");
$all_active_products = fetch_all("SELECT id, name, sku, price FROM products WHERE is_active = 1 ORDER BY name ASC");

$count_combos = count($combo_products);
$count_featured = count($featured_products);
$count_shop = count($shop_products);

require_once 'includes/header.php';
?>

<!-- Sortable.js CDN -->
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>

<style>
.drag-handle {
    touch-action: none;
    -webkit-user-select: none;
    user-select: none;
}
</style>

<div class="space-y-6">

    <!-- Toast Notification -->
    <div id="toast" class="fixed top-20 right-6 z-[9999] hidden items-center gap-2.5 px-4 py-3 rounded-xl text-white font-medium text-xs shadow-xl transition-all duration-200 anim-fade-in"></div>

    <?php if(!empty($_SESSION['msg'])): ?>
        <div class="p-3.5 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs font-medium flex items-center justify-between anim-fade-in">
            <div class="flex items-center gap-2">
                <i class="fas fa-check-circle text-emerald-600 text-sm"></i>
                <span><?php echo htmlspecialchars($_SESSION['msg']); unset($_SESSION['msg']); ?></span>
            </div>
            <button onclick="this.parentElement.remove()" class="text-emerald-500 hover:text-emerald-800 p-1"><i class="fas fa-times"></i></button>
        </div>
    <?php endif; ?>

    <!-- HEADER TITLE -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Product Sorting</h1>
            <p class="text-xs text-slate-500 mt-0.5">Drag and drop products to customize their exact appearance order on the Homepage & Storefront</p>
        </div>
    </div>

    <!-- NAVIGATION TABS -->
    <div class="bg-white p-1.5 rounded-xl border border-slate-200 shadow-xs flex items-center gap-1.5 overflow-x-auto">
        <a href="product_sorting.php?tab=combos" id="tab-btn-combos" class="tab-btn px-4 py-2 rounded-lg font-semibold text-xs transition-colors flex items-center gap-2 shrink-0 <?php echo $active_tab === 'combos' ? 'bg-[#004f42] text-white shadow-xs' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-50'; ?>">
            <i class="fas fa-cubes text-xs"></i>
            <span>1. Combos (<span id="tab-count-combos"><?php echo $count_combos; ?></span>)</span>
        </a>

        <a href="product_sorting.php?tab=featured" id="tab-btn-featured" class="tab-btn px-4 py-2 rounded-lg font-semibold text-xs transition-colors flex items-center gap-2 shrink-0 <?php echo $active_tab === 'featured' ? 'bg-[#004f42] text-white shadow-xs' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-50'; ?>">
            <i class="fas fa-star text-xs"></i>
            <span>2. Our Products (<span id="tab-count-featured"><?php echo $count_featured; ?></span>)</span>
        </a>

        <a href="product_sorting.php?tab=shop" id="tab-btn-shop" class="tab-btn px-4 py-2 rounded-lg font-semibold text-xs transition-colors flex items-center gap-2 shrink-0 <?php echo $active_tab === 'shop' ? 'bg-[#004f42] text-white shadow-xs' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-50'; ?>">
            <i class="fas fa-store text-xs"></i>
            <span>3. Shop Catalog (<span id="tab-count-shop"><?php echo $count_shop; ?></span>)</span>
        </a>
    </div>

    <!-- TAB 1: HOMEPAGE COMBOS SORTING -->
    <div id="tab-content-combos" class="tab-pane <?php echo $active_tab === 'combos' ? '' : 'hidden'; ?> space-y-4">
        <div class="bg-white rounded-xl border border-slate-200 shadow-xs overflow-hidden">
            <div class="p-4 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-3 bg-slate-50/60">
                <div class="flex items-center gap-2.5">
                    <div class="w-8 h-8 rounded-lg bg-amber-50 text-amber-600 flex items-center justify-center text-xs shrink-0">
                        <i class="fas fa-boxes-stacked"></i>
                    </div>
                    <div>
                        <h3 class="text-sm font-bold text-slate-900">Homepage Combos Section</h3>
                        <p class="text-[11px] text-slate-400">Order of combo packages shown in the homepage banner</p>
                    </div>
                </div>
                <button type="button" onclick="openAddModal('combo')" class="btn-admin btn-admin-primary btn-admin-sm">
                    <i class="fas fa-plus text-xs"></i> Add to Combos
                </button>
            </div>

            <div class="overflow-x-auto">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th class="w-16 text-center">Rank</th>
                            <th class="w-16 text-center">Image</th>
                            <th>Product Title</th>
                            <th class="hidden md:table-cell">Category</th>
                            <th class="hidden sm:table-cell">Price</th>
                            <th class="text-center w-28">Stock</th>
                            <th class="text-right w-24">Action</th>
                        </tr>
                    </thead>
                    <tbody id="sortable-combos">
                        <?php if(empty($combo_products)): ?>
                            <tr>
                                <td colspan="7" class="py-12 text-center text-slate-400 text-xs">
                                    <i class="fas fa-boxes text-2xl mb-2 text-slate-300 block"></i>
                                    No combo products found. Click "+ Add to Combos" to feature your first pack.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach($combo_products as $idx => $p): 
                                $img = !empty($p['image']) ? get_url(ltrim($p['image'], './')) : '';
                            ?>
                            <tr class="hover:bg-slate-50 transition-colors group <?php echo $p['stock'] <= 0 ? 'bg-rose-50/20' : ''; ?>" data-id="<?php echo $p['id']; ?>" data-stock="<?php echo (int)$p['stock']; ?>">
                                <td class="text-center">
                                    <div class="drag-handle inline-flex items-center justify-center gap-1.5 cursor-grab active:cursor-grabbing text-slate-400 hover:text-slate-800 transition py-1 px-2 rounded-lg hover:bg-slate-100">
                                        <i class="fas fa-grip-vertical text-xs"></i>
                                        <span class="sort-rank font-bold text-slate-700 text-xs"><?php echo $idx + 1; ?></span>
                                    </div>
                                </td>
                                <td class="text-center">
                                    <div class="w-10 h-10 rounded-lg bg-slate-50 border border-slate-200 overflow-hidden mx-auto shrink-0 p-0.5">
                                        <?php if($img): ?>
                                            <img src="<?php echo $img; ?>" class="w-full h-full object-cover rounded-md" alt="<?php echo htmlspecialchars($p['name']); ?>">
                                        <?php else: ?>
                                            <div class="w-full h-full flex items-center justify-center text-[9px] text-slate-300">No Img</div>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td>
                                    <span class="font-bold text-slate-900 text-xs block line-clamp-1"><?php echo htmlspecialchars($p['name']); ?></span>
                                    <span class="text-[11px] text-slate-400">SKU: <?php echo htmlspecialchars($p['sku'] ?: '—'); ?></span>
                                </td>
                                <td class="hidden md:table-cell text-xs text-slate-600 font-medium">
                                    <?php echo htmlspecialchars($p['category_name'] ?: 'Uncategorized'); ?>
                                </td>
                                <td class="hidden sm:table-cell font-bold text-xs text-slate-900">
                                    ₹<?php echo number_format($p['price'], 2); ?>
                                </td>
                                <td class="text-center">
                                    <?php if($p['stock'] > 0): ?>
                                        <span class="admin-badge admin-badge-success text-[10px]">
                                            In Stock (<?php echo $p['stock']; ?>)
                                        </span>
                                    <?php else: ?>
                                        <span class="admin-badge admin-badge-danger text-[10px]">
                                            Out of Stock
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-right">
                                    <button type="button" onclick="toggleCombo(<?php echo $p['id']; ?>, this)" class="btn-admin btn-admin-danger btn-admin-sm" title="Remove from Combos">
                                        <i class="fas fa-trash-alt text-[10px]"></i> Remove
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- TAB 2: HOMEPAGE "OUR PRODUCTS" SORTING -->
    <div id="tab-content-featured" class="tab-pane <?php echo $active_tab === 'featured' ? '' : 'hidden'; ?> space-y-4">
        <div class="bg-white rounded-xl border border-slate-200 shadow-xs overflow-hidden">
            <div class="p-4 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-3 bg-slate-50/60">
                <div class="flex items-center gap-2.5">
                    <div class="w-8 h-8 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center text-xs shrink-0">
                        <i class="fas fa-star"></i>
                    </div>
                    <div>
                        <h3 class="text-sm font-bold text-slate-900">Featured "Our Products" Section</h3>
                        <p class="text-[11px] text-slate-400">Order of items featured on the homepage showcase</p>
                    </div>
                </div>
                <button type="button" onclick="openAddModal('featured')" class="btn-admin btn-admin-primary btn-admin-sm">
                    <i class="fas fa-plus text-xs"></i> Add to Featured
                </button>
            </div>

            <div class="overflow-x-auto">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th class="w-16 text-center">Rank</th>
                            <th class="w-16 text-center">Image</th>
                            <th>Product Title</th>
                            <th class="hidden md:table-cell">Category</th>
                            <th class="hidden sm:table-cell">Price</th>
                            <th class="text-center w-28">Stock</th>
                            <th class="text-right w-24">Action</th>
                        </tr>
                    </thead>
                    <tbody id="sortable-featured">
                        <?php if(empty($featured_products)): ?>
                            <tr>
                                <td colspan="7" class="py-12 text-center text-slate-400 text-xs">
                                    <i class="fas fa-star text-2xl mb-2 text-slate-300 block"></i>
                                    No featured products selected. Click "+ Add to Featured" to showcase products here.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach($featured_products as $idx => $p): 
                                $img = !empty($p['image']) ? get_url(ltrim($p['image'], './')) : '';
                            ?>
                            <tr class="hover:bg-slate-50 transition-colors group <?php echo $p['stock'] <= 0 ? 'bg-rose-50/20' : ''; ?>" data-id="<?php echo $p['id']; ?>" data-stock="<?php echo (int)$p['stock']; ?>">
                                <td class="text-center">
                                    <div class="drag-handle inline-flex items-center justify-center gap-1.5 cursor-grab active:cursor-grabbing text-slate-400 hover:text-slate-800 transition py-1 px-2 rounded-lg hover:bg-slate-100">
                                        <i class="fas fa-grip-vertical text-xs"></i>
                                        <span class="sort-rank font-bold text-slate-700 text-xs"><?php echo $idx + 1; ?></span>
                                    </div>
                                </td>
                                <td class="text-center">
                                    <div class="w-10 h-10 rounded-lg bg-slate-50 border border-slate-200 overflow-hidden mx-auto shrink-0 p-0.5">
                                        <?php if($img): ?>
                                            <img src="<?php echo $img; ?>" class="w-full h-full object-cover rounded-md" alt="<?php echo htmlspecialchars($p['name']); ?>">
                                        <?php else: ?>
                                            <div class="w-full h-full flex items-center justify-center text-[9px] text-slate-300">No Img</div>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td>
                                    <span class="font-bold text-slate-900 text-xs block line-clamp-1"><?php echo htmlspecialchars($p['name']); ?></span>
                                    <span class="text-[11px] text-slate-400">SKU: <?php echo htmlspecialchars($p['sku'] ?: '—'); ?></span>
                                </td>
                                <td class="hidden md:table-cell text-xs text-slate-600 font-medium">
                                    <?php echo htmlspecialchars($p['category_name'] ?: 'Uncategorized'); ?>
                                </td>
                                <td class="hidden sm:table-cell font-bold text-xs text-slate-900">
                                    ₹<?php echo number_format($p['price'], 2); ?>
                                </td>
                                <td class="text-center">
                                    <?php if($p['stock'] > 0): ?>
                                        <span class="admin-badge admin-badge-success text-[10px]">
                                            In Stock (<?php echo $p['stock']; ?>)
                                        </span>
                                    <?php else: ?>
                                        <span class="admin-badge admin-badge-danger text-[10px]">
                                            Out of Stock
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-right">
                                    <button type="button" onclick="toggleFeatured(<?php echo $p['id']; ?>, this)" class="btn-admin btn-admin-danger btn-admin-sm" title="Remove from Featured">
                                        <i class="fas fa-trash-alt text-[10px]"></i> Remove
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- TAB 3: SHOP PAGE CATALOG DEFAULT SORTING -->
    <div id="tab-content-shop" class="tab-pane <?php echo $active_tab === 'shop' ? '' : 'hidden'; ?> space-y-4">
        <div class="bg-white rounded-xl border border-slate-200 shadow-xs overflow-hidden">
            <div class="p-4 border-b border-slate-100 flex flex-col md:flex-row md:items-center justify-between gap-3 bg-slate-50/60">
                <div class="flex items-center gap-2.5">
                    <div class="w-8 h-8 rounded-lg bg-sky-50 text-sky-600 flex items-center justify-center text-xs shrink-0">
                        <i class="fas fa-store"></i>
                    </div>
                    <div>
                        <h3 class="text-sm font-bold text-slate-900">Shop Catalog Default Sequence</h3>
                        <p class="text-[11px] text-slate-400">Default presentation sequence on the shop collection page</p>
                    </div>
                </div>

                <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-2 w-full md:w-auto">
                    <!-- Live Search Input -->
                    <div class="relative w-full sm:w-56">
                        <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                        <input type="text" id="shop-search" oninput="filterShopTable()" placeholder="Filter items..." class="admin-input pl-8 py-1.5 text-xs">
                    </div>

                    <!-- Category Selector -->
                    <select id="shop-category-filter" onchange="filterShopTable()" class="admin-select py-1.5 text-xs font-semibold">
                        <option value="">All Categories</option>
                        <?php foreach($categories as $cat): ?>
                            <option value="<?php echo htmlspecialchars($cat['name']); ?>"><?php echo htmlspecialchars($cat['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th class="w-16 text-center">Rank</th>
                            <th class="w-16 text-center">Image</th>
                            <th>Product Title</th>
                            <th class="hidden md:table-cell">Category</th>
                            <th class="hidden sm:table-cell">Price</th>
                            <th class="text-center w-28">Stock</th>
                            <th class="hidden sm:table-cell text-right w-24">Item ID</th>
                        </tr>
                    </thead>
                    <tbody id="sortable-shop">
                        <?php foreach($shop_products as $idx => $p): 
                            $img = !empty($p['image']) ? get_url(ltrim($p['image'], './')) : '';
                        ?>
                        <tr class="hover:bg-slate-50 transition-colors group shop-row <?php echo $p['stock'] <= 0 ? 'bg-rose-50/20' : ''; ?>" data-id="<?php echo $p['id']; ?>" data-stock="<?php echo (int)$p['stock']; ?>" data-category="<?php echo htmlspecialchars($p['category_name'] ?: 'Uncategorized'); ?>" data-name="<?php echo htmlspecialchars(strtolower($p['name'])); ?>">
                            <td class="text-center">
                                <div class="drag-handle inline-flex items-center justify-center gap-1.5 cursor-grab active:cursor-grabbing text-slate-400 hover:text-slate-800 transition py-1 px-2 rounded-lg hover:bg-slate-100">
                                    <i class="fas fa-grip-vertical text-xs"></i>
                                    <span class="sort-rank font-bold text-slate-700 text-xs"><?php echo $idx + 1; ?></span>
                                </div>
                            </td>
                            <td class="text-center">
                                <div class="w-10 h-10 rounded-lg bg-slate-50 border border-slate-200 overflow-hidden mx-auto shrink-0 p-0.5">
                                    <?php if($img): ?>
                                        <img src="<?php echo $img; ?>" class="w-full h-full object-cover rounded-md" alt="<?php echo htmlspecialchars($p['name']); ?>">
                                    <?php else: ?>
                                        <div class="w-full h-full flex items-center justify-center text-[9px] text-slate-300">No Img</div>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td>
                                <span class="font-bold text-slate-900 text-xs block line-clamp-1"><?php echo htmlspecialchars($p['name']); ?></span>
                                <span class="text-[11px] text-slate-400">SKU: <?php echo htmlspecialchars($p['sku'] ?: '—'); ?></span>
                            </td>
                            <td class="hidden md:table-cell text-xs text-slate-600 font-medium">
                                <?php echo htmlspecialchars($p['category_name'] ?: 'Uncategorized'); ?>
                            </td>
                            <td class="hidden sm:table-cell font-bold text-xs text-slate-900">
                                ₹<?php echo number_format($p['price'], 2); ?>
                            </td>
                            <td class="text-center">
                                <?php if($p['stock'] > 0): ?>
                                    <span class="admin-badge admin-badge-success text-[10px]">
                                        In Stock (<?php echo $p['stock']; ?>)
                                    </span>
                                <?php else: ?>
                                    <span class="admin-badge admin-badge-danger text-[10px]">
                                        Out of Stock
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td class="hidden sm:table-cell text-right text-slate-400 font-mono text-xs">
                                #<?php echo $p['id']; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>

<!-- ADD PRODUCT MODAL (For Combos / Featured) -->
<div id="add-modal" class="fixed inset-0 z-[999] hidden flex items-center justify-center p-4">
    <div class="absolute inset-0 bg-slate-900/40 backdrop-blur-xs" onclick="closeAddModal()"></div>
    <div class="bg-white w-full max-w-md rounded-2xl shadow-2xl border border-slate-200 relative z-10 anim-fade-in overflow-hidden">
        <div class="bg-[#004f42] px-5 py-4 flex items-center justify-between text-white">
            <h3 id="modal-heading" class="text-sm font-bold tracking-tight">Add Product</h3>
            <button type="button" onclick="closeAddModal()" class="w-7 h-7 rounded-lg bg-white/10 hover:bg-white/20 text-white flex items-center justify-center text-xs transition">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <form id="add-form" method="POST" class="p-5 space-y-4">
            <input type="hidden" name="action" id="modal-action" value="">
            <div>
                <label class="admin-label">Select Store Product</label>
                <select name="product_id" id="modal-product-select" required class="admin-select">
                    <option value="">-- Choose a Product --</option>
                    <?php foreach($all_active_products as $prod): ?>
                        <option value="<?php echo $prod['id']; ?>">
                            <?php echo htmlspecialchars($prod['name']); ?> (₹<?php echo number_format($prod['price']); ?><?php echo !empty($prod['sku']) ? ' | ' . $prod['sku'] : ''; ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="pt-2 flex gap-2">
                <button type="button" onclick="closeAddModal()" class="flex-1 btn-admin btn-admin-secondary text-xs">Cancel</button>
                <button type="submit" class="flex-1 btn-admin btn-admin-primary text-xs">
                    Confirm & Add
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function switchTab(tab) {
    window.location.href = 'product_sorting.php?tab=' + encodeURIComponent(tab);
}

function showToast(message, type = 'success') {
    const toast = document.getElementById('toast');
    toast.className = `fixed top-20 right-6 z-[9999] flex items-center gap-2 px-4 py-3 rounded-xl text-white font-medium text-xs shadow-xl transition-all duration-200 anim-fade-in ${
        type === 'success' ? 'bg-[#004f42] border border-emerald-400/30' : 'bg-rose-600'
    }`;
    toast.innerHTML = `<i class="fas ${type === 'success' ? 'fa-check-circle text-emerald-400' : 'fa-exclamation-circle'} text-sm shrink-0"></i><span>${message}</span>`;
    toast.classList.remove('hidden');
    setTimeout(() => {
        toast.classList.add('hidden');
    }, 2500);
}

document.addEventListener('DOMContentLoaded', () => {
    const sortableOptions = {
        handle: '.drag-handle',
        animation: 180,
        ghostClass: 'bg-emerald-50/70',
        delay: 100,
        delayOnTouchOnly: true,
        touchStartThreshold: 5,
        direction: 'vertical'
    };

    const comboEl = document.getElementById('sortable-combos');
    if(comboEl) {
        new Sortable(comboEl, {
            ...sortableOptions,
            onEnd: function() {
                saveOrder('sortable-combos', 'reorder_combos');
            }
        });
    }

    const featuredEl = document.getElementById('sortable-featured');
    if(featuredEl) {
        new Sortable(featuredEl, {
            ...sortableOptions,
            onEnd: function() {
                saveOrder('sortable-featured', 'reorder_featured');
            }
        });
    }

    const shopEl = document.getElementById('sortable-shop');
    if(shopEl) {
        new Sortable(shopEl, {
            ...sortableOptions,
            onEnd: function() {
                saveOrder('sortable-shop', 'reorder_shop');
            }
        });
    }
});

function partitionOutOfStock(containerId) {
    const container = document.getElementById(containerId);
    if (!container) return;
    const rows = Array.from(container.querySelectorAll('tr[data-id]'));
    
    const inStock = [];
    const outOfStock = [];
    
    rows.forEach(row => {
        const stock = parseInt(row.getAttribute('data-stock') || '0', 10);
        if (stock > 0) inStock.push(row);
        else outOfStock.push(row);
    });

    let misplaced = false;
    let seenOutOfStock = false;
    for (const row of rows) {
        const stock = parseInt(row.getAttribute('data-stock') || '0', 10);
        if (stock <= 0) seenOutOfStock = true;
        else if (seenOutOfStock) {
            misplaced = true;
            break;
        }
    }

    if (misplaced) {
        inStock.forEach(r => container.appendChild(r));
        outOfStock.forEach(r => container.appendChild(r));
        showToast('Out-of-stock items automatically placed at the end.', 'info');
    }

    const allRows = container.querySelectorAll('tr[data-id]');
    allRows.forEach((row, index) => {
        const rankSpan = row.querySelector('.sort-rank');
        if (rankSpan) rankSpan.textContent = index + 1;
    });
}

function saveOrder(containerId, actionName) {
    partitionOutOfStock(containerId);

    const container = document.getElementById(containerId);
    const rows = container.querySelectorAll('tr[data-id]');
    const orderPayload = [];

    rows.forEach((row, index) => {
        const id = row.getAttribute('data-id');
        orderPayload.push({
            id: id,
            sort_order: index + 1
        });
    });

    fetch(`product_sorting.php?action=${actionName}`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ order: orderPayload })
    })
    .then(r => r.json())
    .then(data => {
        if(data.success) {
            showToast('Sequence updated and saved in real-time!');
        } else {
            showToast(data.message || 'Error updating sequence', 'error');
        }
    })
    .catch(() => {
        showToast('Network error updating sequence', 'error');
    });
}

async function toggleCombo(id, btn) {
    const confirmed = typeof window.showConfirm === 'function'
        ? await window.showConfirm('Remove this product from Homepage Combos?', {
            title: 'Remove from Combos',
            type: 'danger',
            confirmText: 'Remove'
        })
        : confirm('Remove this product from Homepage Combos?');
    if(!confirmed) return;
    if(btn) {
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin text-[10px]"></i>';
    }
    fetch(`product_sorting.php?action=remove_combo&id=${id}`)
    .then(r => r.json())
    .then(data => {
        if(data && data.success) {
            const row = document.querySelector(`#sortable-combos tr[data-id="${id}"]`);
            if(row) {
                row.remove();
                const tbody = document.getElementById('sortable-combos');
                if(tbody) {
                    tbody.querySelectorAll('tr[data-id]').forEach((r, idx) => {
                        const badge = r.querySelector('.sort-rank');
                        if(badge) badge.textContent = idx + 1;
                    });
                    const remaining = tbody.querySelectorAll('tr[data-id]').length;
                    const countEl = document.getElementById('tab-count-combos');
                    if(countEl) countEl.textContent = remaining;
                }
            }
            showToast('Product removed from Combos!', 'success');
        } else {
            showToast(data.message || 'Error removing product', 'error');
            if(btn) btn.disabled = false;
        }
    })
    .catch(() => {
        showToast('Network error while removing product', 'error');
        if(btn) btn.disabled = false;
    });
}

async function toggleFeatured(id, btn) {
    const confirmed = typeof window.showConfirm === 'function'
        ? await window.showConfirm('Remove this product from Featured section?', {
            title: 'Remove from Featured',
            type: 'danger',
            confirmText: 'Remove'
        })
        : confirm('Remove this product from Featured section?');
    if(!confirmed) return;
    if(btn) {
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin text-[10px]"></i>';
    }
    fetch(`product_sorting.php?action=remove_featured&id=${id}`)
    .then(r => r.json())
    .then(data => {
        if(data && data.success) {
            const row = document.querySelector(`#sortable-featured tr[data-id="${id}"]`);
            if(row) {
                row.remove();
                const tbody = document.getElementById('sortable-featured');
                if(tbody) {
                    tbody.querySelectorAll('tr[data-id]').forEach((r, idx) => {
                        const badge = r.querySelector('.sort-rank');
                        if(badge) badge.textContent = idx + 1;
                    });
                    const remaining = tbody.querySelectorAll('tr[data-id]').length;
                    const countEl = document.getElementById('tab-count-featured');
                    if(countEl) countEl.textContent = remaining;
                }
            }
            showToast('Product removed from Featured!', 'success');
        } else {
            showToast(data.message || 'Error removing product', 'error');
            if(btn) btn.disabled = false;
        }
    })
    .catch(() => {
        showToast('Network error while removing product', 'error');
        if(btn) btn.disabled = false;
    });
}

function openAddModal(type) {
    const modal = document.getElementById('add-modal');
    const form = document.getElementById('add-form');
    const heading = document.getElementById('modal-heading');
    
    if(type === 'combo') {
        heading.textContent = 'Add Product to Homepage Combos';
        form.action = 'product_sorting.php?action=add_combo';
    } else {
        heading.textContent = 'Add Product to Our Products';
        form.action = 'product_sorting.php?action=add_featured';
    }
    
    modal.classList.remove('hidden');
}

function closeAddModal() {
    document.getElementById('add-modal').classList.add('hidden');
}

function filterShopTable() {
    const query = document.getElementById('shop-search').value.toLowerCase().trim();
    const category = document.getElementById('shop-category-filter').value.toLowerCase().trim();
    const rows = document.querySelectorAll('.shop-row');

    rows.forEach(row => {
        const name = row.getAttribute('data-name');
        const cat = row.getAttribute('data-category').toLowerCase();
        
        const matchName = !query || name.includes(query);
        const matchCat = !category || cat === category;

        if(matchName && matchCat) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });
}
</script>

<?php include 'includes/footer.php'; ?>
