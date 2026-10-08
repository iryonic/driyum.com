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
            // Find current min combo_sort_order to place at top or max to place at bottom
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
/* Custom Responsive & Touch Styles */
.no-scrollbar::-webkit-scrollbar { display: none; }
.no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
.drag-handle {
    touch-action: none;
    -webkit-user-select: none;
    user-select: none;
}
</style>

<div class="max-w-100 mx-auto space-y-4 sm:space-y-6">

    <!-- Toast Notification -->
    <div id="toast" class="fixed top-4 left-4 right-4 sm:left-auto sm:right-6 sm:top-6 z-[9999] hidden flex items-center gap-3 px-4 sm:px-5 py-3 sm:py-3.5 rounded-2xl text-white font-bold text-xs shadow-2xl transition-all duration-300"></div>

    <?php if(!empty($_SESSION['msg'])): ?>
        <div class="p-3.5 sm:p-4 rounded-2xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs font-bold flex items-center justify-between anim-up">
            <div class="flex items-center gap-2">
                <i class="fas fa-check-circle text-emerald-600 text-base shrink-0"></i>
                <span><?php echo htmlspecialchars($_SESSION['msg']); unset($_SESSION['msg']); ?></span>
            </div>
            <button onclick="this.parentElement.remove()" class="text-emerald-400 hover:text-emerald-800 p-1"><i class="fas fa-times"></i></button>
        </div>
    <?php endif; ?>

    <!-- HEADER TITLE -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 sm:gap-4">
        <div>
            <div class="flex items-center gap-2 text-[11px] sm:text-xs font-black text-[#24B25D] uppercase tracking-wider mb-1">
                <i class="fas fa-arrows-alt"></i>
                <span>Visual Sequence Engine</span>
            </div>
            <h1 class="text-2xl sm:text-3xl font-black text-gray-900 font-heading tracking-tight">Product Sorting</h1>
            <p class="text-xs font-medium text-gray-500 mt-0.5 sm:mt-1">Drag and drop products to customize their exact appearance order on the Homepage & Shop</p>
        </div>

    </div>

 
    <!-- NAVIGATION TABS (Mobile horizontally scrollable pills) -->
    <div class="bg-white p-1.5 sm:p-2 rounded-2xl border border-gray-100 shadow-sm flex items-center overflow-x-auto no-scrollbar sm:flex-wrap gap-1.5 sm:gap-2">
        <button type="button" onclick="switchTab('combos')" id="tab-btn-combos" class="tab-btn shrink-0 whitespace-nowrap px-4 py-2.5 sm:px-6 sm:py-3 rounded-xl font-black text-xs uppercase tracking-wider transition-all flex items-center gap-2 <?php echo $active_tab === 'combos' ? 'bg-[#24B25D] text-white shadow-md' : 'text-gray-500 hover:text-gray-900 hover:bg-gray-50'; ?>">
            <i class="fas fa-cubes text-sm"></i>
            <span>1. Combos (<span id="tab-count-combos"><?php echo $count_combos; ?></span>)</span>
        </button>

        <button type="button" onclick="switchTab('featured')" id="tab-btn-featured" class="tab-btn shrink-0 whitespace-nowrap px-4 py-2.5 sm:px-6 sm:py-3 rounded-xl font-black text-xs uppercase tracking-wider transition-all flex items-center gap-2 <?php echo $active_tab === 'featured' ? 'bg-[#24B25D] text-white shadow-md' : 'text-gray-500 hover:text-gray-900 hover:bg-gray-50'; ?>">
            <i class="fas fa-star text-sm"></i>
            <span>2. Our Products (<span id="tab-count-featured"><?php echo $count_featured; ?></span>)</span>
        </button>

        <button type="button" onclick="switchTab('shop')" id="tab-btn-shop" class="tab-btn shrink-0 whitespace-nowrap px-4 py-2.5 sm:px-6 sm:py-3 rounded-xl font-black text-xs uppercase tracking-wider transition-all flex items-center gap-2 <?php echo $active_tab === 'shop' ? 'bg-[#24B25D] text-white shadow-md' : 'text-gray-500 hover:text-gray-900 hover:bg-gray-50'; ?>">
            <i class="fas fa-shopping-bag text-sm"></i>
            <span>3. Shop Catalog (<span id="tab-count-shop"><?php echo $count_shop; ?></span>)</span>
        </button>
    </div>

    <!-- ========================================== -->
    <!-- TAB 1: HOMEPAGE COMBOS SORTING -->
    <!-- ========================================== -->
    <div id="tab-content-combos" class="tab-pane <?php echo $active_tab === 'combos' ? '' : 'hidden'; ?> space-y-4">
        <div class="bg-white rounded-2xl sm:rounded-3xl border border-gray-100 shadow-sm overflow-hidden">
            <!-- Header Bar -->
            <div class="px-4 py-4 sm:px-6 sm:py-5 border-b border-gray-100 flex flex-col sm:flex-row sm:items-center justify-between gap-3 sm:gap-4 bg-gray-50/50">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-xl bg-amber-500/10 text-amber-600 flex items-center justify-center text-sm shrink-0">
                        <i class="fas fa-boxes"></i>
                    </div>
                    <div>
                        <h3 class="text-sm font-black text-gray-900 font-heading">Our Combos</h3>
                        </div>
                </div>
                <button type="button" onclick="openAddModal('combo')" class="inline-flex items-center justify-center gap-2 bg-[#24B25D] hover:bg-[#004F42] text-white px-4 sm:px-5 py-2.5 rounded-xl font-bold text-xs transition shadow-sm active:scale-95 w-full sm:w-auto">
                    <i class="fas fa-plus text-xs"></i>
                    <span>Add Product to Combos</span>
                </button>
            </div>

            <!-- Table -->
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="border-b border-gray-100 text-[10px] font-black text-gray-400 uppercase tracking-wider bg-gray-50/20">
                            <th class="py-3 px-2 sm:py-4 sm:px-4 w-12 sm:w-16 text-center">Sort</th>
                            <th class="py-3 px-2 sm:py-4 sm:px-4 w-14 sm:w-20 text-center">Image</th>
                            <th class="py-3 px-2 sm:py-4 sm:px-4 min-w-[160px] sm:min-w-[240px]">Product Title</th>
                            <th class="hidden md:table-cell py-3 px-4 sm:py-4 sm:px-4 min-w-[130px]">Category</th>
                            <th class="hidden sm:table-cell py-3 px-4 sm:py-4 sm:px-4 min-w-[90px]">Price</th>
                            <th class="py-3 px-2 sm:py-4 sm:px-4 text-center w-24 sm:w-32">Stock</th>
                            <th class="py-3 pr-4 pl-2 sm:py-4 sm:pr-6 sm:pl-4 text-right w-20 sm:w-28">Action</th>
                        </tr>
                    </thead>
                    <tbody id="sortable-combos" class="divide-y divide-gray-50 text-xs">
                        <?php if(empty($combo_products)): ?>
                            <tr>
                                <td colspan="7" class="py-12 text-center text-gray-400 px-4">
                                    <i class="fas fa-boxes text-3xl mb-2 text-gray-300 block"></i>
                                    No combo products found. Click "+ Add Product to Combos" to feature your first pack.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach($combo_products as $idx => $p): 
                                $img = !empty($p['image']) ? get_url(ltrim($p['image'], './')) : '';
                            ?>
                            <tr class="hover:bg-gray-50/70 transition-colors group cursor-default <?php echo $p['stock'] <= 0 ? 'bg-rose-50/25' : ''; ?>" data-id="<?php echo $p['id']; ?>" data-stock="<?php echo (int)$p['stock']; ?>">
                                <td class="py-3 px-2 sm:py-4 sm:px-4 text-center">
                                    <div class="drag-handle inline-flex items-center justify-center gap-1 cursor-grab active:cursor-grabbing text-gray-400 hover:text-gray-800 transition p-2 sm:py-1 sm:px-2 rounded-xl sm:rounded-lg hover:bg-gray-100 active:bg-emerald-50 active:text-emerald-700 min-w-[36px] min-h-[36px]">
                                        <i class="fas fa-grip-vertical text-xs"></i>
                                        <span class="sort-rank font-black text-gray-700 text-xs"><?php echo $idx + 1; ?></span>
                                    </div>
                                </td>
                                <td class="py-3 px-2 sm:py-4 sm:px-4 text-center">
                                    <div class="w-10 h-10 sm:w-12 sm:h-12 rounded-xl bg-gray-50 border border-gray-100 overflow-hidden mx-auto shadow-sm shrink-0">
                                        <?php if($img): ?>
                                            <img src="<?php echo $img; ?>" class="w-full h-full object-cover" alt="<?php echo htmlspecialchars($p['name']); ?>">
                                        <?php else: ?>
                                            <div class="w-full h-full flex items-center justify-center text-[8px] text-gray-300">No Img</div>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td class="py-3 px-2 sm:py-4 sm:px-4">
                                    <div class="font-bold text-gray-900 text-xs sm:text-sm line-clamp-2 hover:text-[#24B25D] transition"><?php echo htmlspecialchars($p['name']); ?></div>
                                    <div class="flex flex-wrap items-center gap-1.5 mt-0.5">
                                        <span class="text-[10px] text-gray-400">SKU: <?php echo htmlspecialchars($p['sku'] ?: '—'); ?></span>
                                        <span class="md:hidden inline-flex text-[9px] font-bold px-1.5 py-0.5 rounded bg-gray-100 text-gray-600">
                                            <?php echo htmlspecialchars($p['category_name'] ?: 'Uncategorized'); ?>
                                        </span>
                                        <span class="sm:hidden font-mono font-bold text-[11px] text-[#004F42]">
                                            ₹<?php echo number_format($p['price'], 2); ?>
                                        </span>
                                    </div>
                                </td>
                                <td class="hidden md:table-cell py-3 px-4 sm:py-4 sm:px-4 font-medium text-gray-600">
                                    <?php echo htmlspecialchars($p['category_name'] ?: 'Uncategorized'); ?>
                                </td>
                                <td class="hidden sm:table-cell py-3 px-4 sm:py-4 sm:px-4 font-black text-gray-900 font-mono">
                                    ₹<?php echo number_format($p['price'], 2); ?>
                                </td>
                                <td class="py-3 px-2 sm:py-4 sm:px-4 text-center">
                                    <?php if($p['stock'] > 0): ?>
                                        <span class="inline-flex px-2 py-0.5 sm:px-2.5 sm:py-1 rounded-full text-[9px] sm:text-[10px] font-black bg-emerald-50 text-emerald-700 border border-emerald-100 whitespace-nowrap">
                                            In Stock <span class="hidden sm:inline">(<?php echo $p['stock']; ?>)</span>
                                        </span>
                                    <?php else: ?>
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 sm:px-2.5 sm:py-1 rounded-full text-[9px] sm:text-[10px] font-black bg-rose-50 text-rose-700 border border-rose-100 whitespace-nowrap" title="Out-of-stock items are automatically positioned at the end">
                                            <i class="fas fa-arrow-down text-[8px]"></i> Out of Stock (Last)
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="py-3 pr-4 pl-2 sm:py-4 sm:pr-6 sm:pl-4 text-right">
                                    <button type="button" onclick="toggleCombo(<?php echo $p['id']; ?>, this)" title="Remove from Combos" class="inline-flex items-center gap-1 px-2.5 py-1.5 sm:px-3 sm:py-1.5 rounded-lg bg-gray-50 text-gray-400 hover:text-red-600 hover:bg-red-50 text-xs font-bold transition">
                                        <i class="fas fa-trash-alt text-[11px]"></i>
                                        <span class="hidden sm:inline">Remove</span>
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

    <!-- ========================================== -->
    <!-- TAB 2: HOMEPAGE "OUR PRODUCTS" SORTING -->
    <!-- ========================================== -->
    <div id="tab-content-featured" class="tab-pane <?php echo $active_tab === 'featured' ? '' : 'hidden'; ?> space-y-4">
        <div class="bg-white rounded-2xl sm:rounded-3xl border border-gray-100 shadow-sm overflow-hidden">
            <!-- Header Bar -->
            <div class="px-4 py-4 sm:px-6 sm:py-5 border-b border-gray-100 flex flex-col sm:flex-row sm:items-center justify-between gap-3 sm:gap-4 bg-gray-50/50">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-xl bg-emerald-500/10 text-emerald-600 flex items-center justify-center text-sm shrink-0">
                        <i class="fas fa-star"></i>
                    </div>
                    <div>
                        <h3 class="text-sm font-black text-gray-900 font-heading">Our Products</h3>
                    </div>
                </div>
                <button type="button" onclick="openAddModal('featured')" class="inline-flex items-center justify-center gap-2 bg-[#24B25D] hover:bg-[#004F42] text-white px-4 sm:px-5 py-2.5 rounded-xl font-bold text-xs transition shadow-sm active:scale-95 w-full sm:w-auto">
                    <i class="fas fa-plus text-xs"></i>
                    <span>Add Product to Featured</span>
                </button>
            </div>

            <!-- Stock Priority & Mobile Drag Hint -->
            <div class="px-4 py-2.5 bg-emerald-50/80 border-b border-emerald-100/80 text-[11px] font-medium text-emerald-800 flex flex-wrap items-center justify-between gap-2">
                <div class="flex items-center gap-2">
                    <i class="fas fa-check-circle text-emerald-600 shrink-0"></i>
                    <span><strong>Stock Priority Engine:</strong> In-stock products rank first. Out-of-stock items automatically stay at the bottom.</span>
                </div>
                <div class="sm:hidden text-[10px] text-amber-800 font-bold flex items-center gap-1">
                    <i class="fas fa-hand-pointer text-amber-600"></i> Hold grip (<i class="fas fa-grip-vertical"></i>) to reorder
                </div>
            </div>

            <!-- Table -->
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="border-b border-gray-100 text-[10px] font-black text-gray-400 uppercase tracking-wider bg-gray-50/20">
                            <th class="py-3 px-2 sm:py-4 sm:px-4 w-12 sm:w-16 text-center">Sort</th>
                            <th class="py-3 px-2 sm:py-4 sm:px-4 w-14 sm:w-20 text-center">Image</th>
                            <th class="py-3 px-2 sm:py-4 sm:px-4 min-w-[160px] sm:min-w-[240px]">Product Title</th>
                            <th class="hidden md:table-cell py-3 px-4 sm:py-4 sm:px-4 min-w-[130px]">Category</th>
                            <th class="hidden sm:table-cell py-3 px-4 sm:py-4 sm:px-4 min-w-[90px]">Price</th>
                            <th class="py-3 px-2 sm:py-4 sm:px-4 text-center w-24 sm:w-32">Stock</th>
                            <th class="py-3 pr-4 pl-2 sm:py-4 sm:pr-6 sm:pl-4 text-right w-20 sm:w-28">Action</th>
                        </tr>
                    </thead>
                    <tbody id="sortable-featured" class="divide-y divide-gray-50 text-xs">
                        <?php if(empty($featured_products)): ?>
                            <tr>
                                <td colspan="7" class="py-12 text-center text-gray-400 px-4">
                                    <i class="fas fa-star text-3xl mb-2 text-gray-300 block"></i>
                                    No featured products selected. Click "+ Add Product to Featured" to showcase products here.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach($featured_products as $idx => $p): 
                                $img = !empty($p['image']) ? get_url(ltrim($p['image'], './')) : '';
                            ?>
                            <tr class="hover:bg-gray-50/70 transition-colors group cursor-default <?php echo $p['stock'] <= 0 ? 'bg-rose-50/25' : ''; ?>" data-id="<?php echo $p['id']; ?>" data-stock="<?php echo (int)$p['stock']; ?>">
                                <td class="py-3 px-2 sm:py-4 sm:px-4 text-center">
                                    <div class="drag-handle inline-flex items-center justify-center gap-1 cursor-grab active:cursor-grabbing text-gray-400 hover:text-gray-800 transition p-2 sm:py-1 sm:px-2 rounded-xl sm:rounded-lg hover:bg-gray-100 active:bg-emerald-50 active:text-emerald-700 min-w-[36px] min-h-[36px]">
                                        <i class="fas fa-grip-vertical text-xs"></i>
                                        <span class="sort-rank font-black text-gray-700 text-xs"><?php echo $idx + 1; ?></span>
                                    </div>
                                </td>
                                <td class="py-3 px-2 sm:py-4 sm:px-4 text-center">
                                    <div class="w-10 h-10 sm:w-12 sm:h-12 rounded-xl bg-gray-50 border border-gray-100 overflow-hidden mx-auto shadow-sm shrink-0">
                                        <?php if($img): ?>
                                            <img src="<?php echo $img; ?>" class="w-full h-full object-cover" alt="<?php echo htmlspecialchars($p['name']); ?>">
                                        <?php else: ?>
                                            <div class="w-full h-full flex items-center justify-center text-[8px] text-gray-300">No Img</div>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td class="py-3 px-2 sm:py-4 sm:px-4">
                                    <div class="font-bold text-gray-900 text-xs sm:text-sm line-clamp-2 hover:text-[#24B25D] transition"><?php echo htmlspecialchars($p['name']); ?></div>
                                    <div class="flex flex-wrap items-center gap-1.5 mt-0.5">
                                        <span class="text-[10px] text-gray-400">SKU: <?php echo htmlspecialchars($p['sku'] ?: '—'); ?></span>
                                        <span class="md:hidden inline-flex text-[9px] font-bold px-1.5 py-0.5 rounded bg-gray-100 text-gray-600">
                                            <?php echo htmlspecialchars($p['category_name'] ?: 'Uncategorized'); ?>
                                        </span>
                                        <span class="sm:hidden font-mono font-bold text-[11px] text-[#004F42]">
                                            ₹<?php echo number_format($p['price'], 2); ?>
                                        </span>
                                    </div>
                                </td>
                                <td class="hidden md:table-cell py-3 px-4 sm:py-4 sm:px-4 font-medium text-gray-600">
                                    <?php echo htmlspecialchars($p['category_name'] ?: 'Uncategorized'); ?>
                                </td>
                                <td class="hidden sm:table-cell py-3 px-4 sm:py-4 sm:px-4 font-black text-gray-900 font-mono">
                                    ₹<?php echo number_format($p['price'], 2); ?>
                                </td>
                                <td class="py-3 px-2 sm:py-4 sm:px-4 text-center">
                                    <?php if($p['stock'] > 0): ?>
                                        <span class="inline-flex px-2 py-0.5 sm:px-2.5 sm:py-1 rounded-full text-[9px] sm:text-[10px] font-black bg-emerald-50 text-emerald-700 border border-emerald-100 whitespace-nowrap">
                                            In Stock <span class="hidden sm:inline">(<?php echo $p['stock']; ?>)</span>
                                        </span>
                                    <?php else: ?>
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 sm:px-2.5 sm:py-1 rounded-full text-[9px] sm:text-[10px] font-black bg-rose-50 text-rose-700 border border-rose-100 whitespace-nowrap" title="Out-of-stock items are automatically positioned at the end">
                                            <i class="fas fa-arrow-down text-[8px]"></i> Out of Stock (Last)
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="py-3 pr-4 pl-2 sm:py-4 sm:pr-6 sm:pl-4 text-right">
                                    <button type="button" onclick="toggleFeatured(<?php echo $p['id']; ?>, this)" title="Remove from Featured" class="inline-flex items-center gap-1 px-2.5 py-1.5 sm:px-3 sm:py-1.5 rounded-lg bg-gray-50 text-gray-400 hover:text-red-600 hover:bg-red-50 text-xs font-bold transition">
                                        <i class="fas fa-trash-alt text-[11px]"></i>
                                        <span class="hidden sm:inline">Remove</span>
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

    <!-- ========================================== -->
    <!-- TAB 3: SHOP PAGE CATALOG DEFAULT SORTING -->
    <!-- ========================================== -->
    <div id="tab-content-shop" class="tab-pane <?php echo $active_tab === 'shop' ? '' : 'hidden'; ?> space-y-4">
        <div class="bg-white rounded-2xl sm:rounded-3xl border border-gray-100 shadow-sm overflow-hidden">
            <!-- Header Bar with Filter & Search -->
            <div class="px-4 py-4 sm:px-6 sm:py-5 border-b border-gray-100 flex flex-col md:flex-row md:items-center justify-between gap-3 sm:gap-4 bg-gray-50/50">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-xl bg-sky-500/10 text-sky-600 flex items-center justify-center text-sm shrink-0">
                        <i class="fas fa-store"></i>
                    </div>
                    <div>
                        <h3 class="text-sm font-black text-gray-900 font-heading">Shop Page Order</h3>
                    </div>
                </div>

                <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-2 sm:gap-3 w-full md:w-auto">
                    <!-- Live Search Input -->
                    <div class="relative w-full sm:w-60 md:w-64">
                        <i class="fas fa-search absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-400 text-xs"></i>
                        <input type="text" id="shop-search" oninput="filterShopTable()" placeholder="Filter by product..." class="w-full pl-9 pr-4 py-2 rounded-xl border border-gray-200 text-xs font-medium text-gray-800 focus:outline-none focus:border-[#24B25D] bg-white transition">
                    </div>

                    <!-- Category Selector -->
                    <select id="shop-category-filter" onchange="filterShopTable()" class="w-full sm:w-auto px-3 py-2 rounded-xl border border-gray-200 text-xs font-bold text-gray-700 bg-white focus:outline-none focus:border-[#24B25D] transition">
                        <option value="">All Categories</option>
                        <?php foreach($categories as $cat): ?>
                            <option value="<?php echo htmlspecialchars($cat['name']); ?>"><?php echo htmlspecialchars($cat['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <!-- Stock Priority & Mobile Drag Hint -->
            <div class="px-4 py-2.5 bg-emerald-50/80 border-b border-emerald-100/80 text-[11px] font-medium text-emerald-800 flex flex-wrap items-center justify-between gap-2">
                <div class="flex items-center gap-2">
                    <i class="fas fa-check-circle text-emerald-600 shrink-0"></i>
                    <span><strong>Stock Priority Engine:</strong> In-stock products rank first. Out-of-stock items automatically stay at the bottom of /shop.</span>
                </div>
                <div class="sm:hidden text-[10px] text-amber-800 font-bold flex items-center gap-1">
                    <i class="fas fa-hand-pointer text-amber-600"></i> Hold grip (<i class="fas fa-grip-vertical"></i>) to reorder
                </div>
            </div>

            <!-- Table -->
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="border-b border-gray-100 text-[10px] font-black text-gray-400 uppercase tracking-wider bg-gray-50/20">
                            <th class="py-3 px-2 sm:py-4 sm:px-4 w-12 sm:w-16 text-center">Sort</th>
                            <th class="py-3 px-2 sm:py-4 sm:px-4 w-14 sm:w-20 text-center">Image</th>
                            <th class="py-3 px-2 sm:py-4 sm:px-4 min-w-[160px] sm:min-w-[240px]">Product Title</th>
                            <th class="hidden md:table-cell py-3 px-4 sm:py-4 sm:px-4 min-w-[130px]">Category</th>
                            <th class="hidden sm:table-cell py-3 px-4 sm:py-4 sm:px-4 min-w-[90px]">Price</th>
                            <th class="py-3 px-2 sm:py-4 sm:px-4 text-center w-24 sm:w-32">Stock</th>
                            <th class="hidden sm:table-cell py-3 pr-4 pl-2 sm:py-4 sm:pr-6 sm:pl-4 text-right w-20 sm:w-24">Order ID</th>
                        </tr>
                    </thead>
                    <tbody id="sortable-shop" class="divide-y divide-gray-50 text-xs">
                        <?php foreach($shop_products as $idx => $p): 
                            $img = !empty($p['image']) ? get_url(ltrim($p['image'], './')) : '';
                        ?>
                        <tr class="hover:bg-gray-50/70 transition-colors group cursor-default shop-row <?php echo $p['stock'] <= 0 ? 'bg-rose-50/25' : ''; ?>" data-id="<?php echo $p['id']; ?>" data-stock="<?php echo (int)$p['stock']; ?>" data-category="<?php echo htmlspecialchars($p['category_name'] ?: 'Uncategorized'); ?>" data-name="<?php echo htmlspecialchars(strtolower($p['name'])); ?>">
                            <td class="py-3 px-2 sm:py-4 sm:px-4 text-center">
                                <div class="drag-handle inline-flex items-center justify-center gap-1 cursor-grab active:cursor-grabbing text-gray-400 hover:text-gray-800 transition p-2 sm:py-1 sm:px-2 rounded-xl sm:rounded-lg hover:bg-gray-100 active:bg-emerald-50 active:text-emerald-700 min-w-[36px] min-h-[36px]">
                                    <i class="fas fa-grip-vertical text-xs"></i>
                                    <span class="sort-rank font-black text-gray-700 text-xs"><?php echo $idx + 1; ?></span>
                                </div>
                            </td>
                            <td class="py-3 px-2 sm:py-4 sm:px-4 text-center">
                                <div class="w-10 h-10 sm:w-12 sm:h-12 rounded-xl bg-gray-50 border border-gray-100 overflow-hidden mx-auto shadow-sm shrink-0">
                                    <?php if($img): ?>
                                        <img src="<?php echo $img; ?>" class="w-full h-full object-cover" alt="<?php echo htmlspecialchars($p['name']); ?>">
                                    <?php else: ?>
                                        <div class="w-full h-full flex items-center justify-center text-[8px] text-gray-300">No Img</div>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td class="py-3 px-2 sm:py-4 sm:px-4">
                                <div class="font-bold text-gray-900 text-xs sm:text-sm line-clamp-2 hover:text-[#24B25D] transition"><?php echo htmlspecialchars($p['name']); ?></div>
                                <div class="flex flex-wrap items-center gap-1.5 mt-0.5">
                                    <span class="text-[10px] text-gray-400">SKU: <?php echo htmlspecialchars($p['sku'] ?: '—'); ?></span>
                                    <span class="md:hidden inline-flex text-[9px] font-bold px-1.5 py-0.5 rounded bg-gray-100 text-gray-600">
                                        <?php echo htmlspecialchars($p['category_name'] ?: 'Uncategorized'); ?>
                                    </span>
                                    <span class="sm:hidden font-mono font-bold text-[11px] text-[#004F42]">
                                        ₹<?php echo number_format($p['price'], 2); ?>
                                    </span>
                                </div>
                            </td>
                            <td class="hidden md:table-cell py-3 px-4 sm:py-4 sm:px-4 font-medium text-gray-600">
                                <?php echo htmlspecialchars($p['category_name'] ?: 'Uncategorized'); ?>
                            </td>
                            <td class="hidden sm:table-cell py-3 px-4 sm:py-4 sm:px-4 font-black text-gray-900 font-mono">
                                ₹<?php echo number_format($p['price'], 2); ?>
                            </td>
                            <td class="py-3 px-2 sm:py-4 sm:px-4 text-center">
                                <?php if($p['stock'] > 0): ?>
                                    <span class="inline-flex px-2 py-0.5 sm:px-2.5 sm:py-1 rounded-full text-[9px] sm:text-[10px] font-black bg-emerald-50 text-emerald-700 border border-emerald-100 whitespace-nowrap">
                                        In Stock <span class="hidden sm:inline">(<?php echo $p['stock']; ?>)</span>
                                    </span>
                                <?php else: ?>
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 sm:px-2.5 sm:py-1 rounded-full text-[9px] sm:text-[10px] font-black bg-rose-50 text-rose-700 border border-rose-100 whitespace-nowrap" title="Out-of-stock items are automatically positioned at the end">
                                        <i class="fas fa-arrow-down text-[8px]"></i> Out of Stock (Last)
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td class="hidden sm:table-cell py-3 pr-4 pl-2 sm:py-4 sm:pr-6 sm:pl-4 text-right text-gray-400 font-mono font-bold text-[11px]">
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
<div id="add-modal" class="fixed inset-0 z-[999] hidden flex items-center justify-center p-3 sm:p-4">
    <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" onclick="closeAddModal()"></div>
    <div class="bg-white w-full max-w-md rounded-2xl sm:rounded-3xl shadow-2xl overflow-hidden border border-gray-100 relative z-10 anim-up my-auto">
        <div class="bg-[#004F42] px-5 py-4 sm:px-6 sm:py-4.5 flex items-center justify-between text-white">
            <h3 id="modal-heading" class="text-sm sm:text-base font-black font-heading tracking-tight">Add Product</h3>
            <button type="button" onclick="closeAddModal()" class="w-8 h-8 rounded-full bg-white/10 hover:bg-white/20 text-white flex items-center justify-center text-xs transition">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <form id="add-form" method="POST" class="p-4 sm:p-6 space-y-4">
            <input type="hidden" name="action" id="modal-action" value="">
            <div>
                <label class="block text-xs font-black text-gray-800 mb-1.5">Select Store Product</label>
                <select name="product_id" id="modal-product-select" required class="w-full rounded-xl border border-gray-200 px-3.5 py-3 text-xs font-bold text-gray-800 focus:outline-none focus:border-[#24B25D] bg-white transition">
                    <option value="">-- Choose a Product --</option>
                    <?php foreach($all_active_products as $prod): ?>
                        <option value="<?php echo $prod['id']; ?>">
                            <?php echo htmlspecialchars($prod['name']); ?> (₹<?php echo number_format($prod['price']); ?><?php echo !empty($prod['sku']) ? ' | ' . $prod['sku'] : ''; ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="pt-2">
                <button type="submit" class="w-full py-3 sm:py-3.5 rounded-xl bg-[#24B25D] hover:bg-[#004F42] text-white font-black text-xs uppercase tracking-wider shadow-lg shadow-[#24B25D]/20 active:scale-95 transition">
                    Confirm & Add to Section
                </button>
            </div>
        </form>
    </div>
</div>

<script>
// --- TAB SWITCHER ---
function switchTab(tab) {
    document.querySelectorAll('.tab-pane').forEach(el => el.classList.add('hidden'));
    document.querySelectorAll('.tab-btn').forEach(btn => {
        btn.classList.remove('bg-[#24B25D]', 'text-white', 'shadow-md');
        btn.classList.add('text-gray-500', 'hover:text-gray-900', 'hover:bg-gray-50');
    });

    const activePane = document.getElementById('tab-content-' + tab);
    const activeBtn = document.getElementById('tab-btn-' + tab);
    if(activePane) activePane.classList.remove('hidden');
    if(activeBtn) {
        activeBtn.classList.remove('text-gray-500', 'hover:text-gray-900', 'hover:bg-gray-50');
        activeBtn.classList.add('bg-[#24B25D]', 'text-white', 'shadow-md');
    }

    // Sync metric card highlights
    document.querySelectorAll('[data-metric-tab]').forEach(card => {
        if(card.getAttribute('data-metric-tab') === tab) {
            card.classList.add('border-[#24B25D]', 'ring-2', 'ring-[#24B25D]/20');
            card.classList.remove('border-gray-100');
        } else {
            card.classList.remove('border-[#24B25D]', 'ring-2', 'ring-[#24B25D]/20');
            card.classList.add('border-gray-100');
        }
    });

    // Update URL query without reload
    const url = new URL(window.location);
    url.searchParams.set('tab', tab);
    window.history.replaceState({}, '', url);
}

// --- TOAST NOTIFICATION ---
function showToast(message, type = 'success') {
    const toast = document.getElementById('toast');
    toast.className = `fixed top-4 left-4 right-4 sm:left-auto sm:right-6 sm:top-6 z-[9999] flex items-center gap-3 px-4 sm:px-5 py-3 sm:py-3.5 rounded-2xl text-white font-bold text-xs shadow-2xl transition-all duration-300 ${
        type === 'success' ? 'bg-[#004F42] border border-emerald-400/30' : 'bg-red-600'
    }`;
    toast.innerHTML = `<i class="fas ${type === 'success' ? 'fa-check-circle text-emerald-400' : 'fa-exclamation-circle'} text-base shrink-0"></i><span>${message}</span>`;
    toast.classList.remove('hidden');
    setTimeout(() => {
        toast.classList.add('hidden');
    }, 2800);
}

// --- SORTABLE INITIALIZATION WITH TOUCH & MOBILE SUPPORT ---
document.addEventListener('DOMContentLoaded', () => {
    const sortableOptions = {
        handle: '.drag-handle',
        animation: 200,
        ghostClass: 'bg-emerald-50/80',
        delay: 120, // allows natural scroll on touchscreens unless user holds the handle
        delayOnTouchOnly: true,
        touchStartThreshold: 5,
        direction: 'vertical'
    };

    // 1. Sortable Combos
    const comboEl = document.getElementById('sortable-combos');
    if(comboEl) {
        new Sortable(comboEl, {
            ...sortableOptions,
            onEnd: function() {
                saveOrder('sortable-combos', 'reorder_combos');
            }
        });
    }

    // 2. Sortable Featured
    const featuredEl = document.getElementById('sortable-featured');
    if(featuredEl) {
        new Sortable(featuredEl, {
            ...sortableOptions,
            onEnd: function() {
                saveOrder('sortable-featured', 'reorder_featured');
            }
        });
    }

    // 3. Sortable Shop
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

// Partition out-of-stock items so they are always at the bottom
function partitionOutOfStock(containerId) {
    const container = document.getElementById(containerId);
    if (!container) return;
    const rows = Array.from(container.querySelectorAll('tr[data-id]'));
    
    const inStock = [];
    const outOfStock = [];
    
    rows.forEach(row => {
        const stock = parseInt(row.getAttribute('data-stock') || '0', 10);
        if (stock > 0) {
            inStock.push(row);
        } else {
            outOfStock.push(row);
        }
    });

    let misplaced = false;
    let seenOutOfStock = false;
    for (const row of rows) {
        const stock = parseInt(row.getAttribute('data-stock') || '0', 10);
        if (stock <= 0) {
            seenOutOfStock = true;
        } else if (seenOutOfStock) {
            misplaced = true;
            break;
        }
    }

    if (misplaced) {
        inStock.forEach(r => container.appendChild(r));
        outOfStock.forEach(r => container.appendChild(r));
        showToast('Out-of-stock items automatically placed at the end.', 'info');
    }

    // Update ranks
    const allRows = container.querySelectorAll('tr[data-id]');
    allRows.forEach((row, index) => {
        const rankSpan = row.querySelector('.sort-rank');
        if (rankSpan) rankSpan.textContent = index + 1;
    });
}

// Generic Save Order Function
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
            showToast('Sequence updated & synced in real time!');
        } else {
            showToast(data.message || 'Error updating sequence', 'error');
        }
    })
    .catch(() => {
        showToast('Network error updating sequence', 'error');
    });
}

// --- TOGGLE / REMOVE ACTIONS ---
function toggleCombo(id, btn) {
    if(!confirm('Remove this product from the Homepage Combos section?')) return;
    if(btn) {
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin text-[11px]"></i>';
    }
    fetch(`product_sorting.php?action=remove_combo&id=${id}`)
    .then(r => r.json())
    .then(data => {
        if(data && data.success) {
            const row = document.querySelector(`#sortable-combos tr[data-id="${id}"]`);
            if(row) {
                row.style.transition = 'all 0.3s ease';
                row.style.opacity = '0';
                row.style.transform = 'translateX(20px)';
                setTimeout(() => {
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
                        if(remaining === 0) {
                            window.location.reload();
                        }
                    }
                }, 300);
            } else {
                window.location.href = 'product_sorting.php?tab=combos';
            }
            showToast('Product successfully removed from Combos!', 'success');
        } else {
            showToast(data.message || 'Error removing product', 'error');
            if(btn) {
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-trash-alt text-[11px]"></i> <span class="hidden sm:inline">Remove</span>';
            }
        }
    })
    .catch(() => {
        showToast('Network error while removing product', 'error');
        if(btn) {
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-trash-alt text-[11px]"></i> <span class="hidden sm:inline">Remove</span>';
        }
    });
}

function toggleFeatured(id, btn) {
    if(!confirm('Remove this product from Our Products?')) return;
    if(btn) {
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin text-[11px]"></i>';
    }
    fetch(`product_sorting.php?action=remove_featured&id=${id}`)
    .then(r => r.json())
    .then(data => {
        if(data && data.success) {
            const row = document.querySelector(`#sortable-featured tr[data-id="${id}"]`);
            if(row) {
                row.style.transition = 'all 0.3s ease';
                row.style.opacity = '0';
                row.style.transform = 'translateX(20px)';
                setTimeout(() => {
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
                        if(remaining === 0) {
                            window.location.reload();
                        }
                    }
                }, 300);
            } else {
                window.location.href = 'product_sorting.php?tab=featured';
            }
            showToast('Product successfully removed from Featured!', 'success');
        } else {
            showToast(data.message || 'Error removing product', 'error');
            if(btn) {
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-trash-alt text-[11px]"></i> <span class="hidden sm:inline">Remove</span>';
            }
        }
    })
    .catch(() => {
        showToast('Network error while removing product', 'error');
        if(btn) {
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-trash-alt text-[11px]"></i> <span class="hidden sm:inline">Remove</span>';
        }
    });
}

// --- MODAL HELPERS ---
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

// --- FILTER SHOP TABLE ---
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

