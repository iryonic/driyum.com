<?php
require_once '../config/database.php';
require_once '../includes/functions.php';

$conn = get_db_connection();

// Handle AJAX Update
if (isset($_POST['action']) && $_POST['action'] === 'update_stock') {
    $pid = (int)$_POST['product_id'];
    $stock = (int)$_POST['stock'];
    
    $stmt = $conn->prepare("UPDATE products SET stock = ? WHERE id = ?");
    $stmt->bind_param("ii", $stock, $pid);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => $conn->error]);
    }
    exit;
}

$search = sanitize_input($_GET['q'] ?? '');
$category_filter = (int)($_GET['category_id'] ?? 0);

// Bulk Action Handler
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['bulk_action'])) {
    $action = $_POST['bulk_action'];
    $ids = $_POST['ids'] ?? [];
    $all_selected = ($_POST['all_selected'] ?? 'false') === 'true';
    
    if ($all_selected) {
        $where = "WHERE 1=1";
        $p = [];
        if ($search) { $where .= " AND (name LIKE ? OR sku LIKE ?)"; $p[] = "%$search%"; $p[] = "%$search%"; }
        if ($category_filter) { $where .= " AND category_id = ?"; $p[] = $category_filter; }
        $res = fetch_all("SELECT id FROM products $where", $p);
        $ids = array_column($res, 'id');
    }
    
    if (!empty($ids)) {
        if ($action === 'set_stock') {
            $val = (int)$_POST['bulk_stock_val'];
            $ids_str = implode(',', array_map('intval', $ids));
            $conn->query("UPDATE products SET stock = $val WHERE id IN ($ids_str)");
            $_SESSION['success'] = "Updated stock for " . count($ids) . " products";
        } elseif ($action === 'delete') {
            $ids_str = implode(',', array_map('intval', $ids));
            $conn->query("UPDATE products SET is_active = 0, stock = 0 WHERE id IN ($ids_str)");
            $_SESSION['success'] = "Archived " . count($ids) . " products safely";
        }
    }
    header("Location: inventory.php");
    exit;
}

include 'includes/header.php';

$where = "WHERE 1=1";
$params = [];
if ($search) {
    $where .= " AND (p.name LIKE ? OR p.sku LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}
if ($category_filter) {
    $where .= " AND p.category_id = ?";
    $params[] = $category_filter;
}

$query = "SELECT p.*, c.name as category_name FROM products p LEFT JOIN categories c ON p.category_id = c.id $where ORDER BY p.stock ASC";
$pagination = get_pagination_data($query, $params, 15);
$products = $pagination['records'];

// Stats
$all_products = fetch_all("SELECT stock FROM products WHERE is_active = 1");
$total_count = count($all_products);
$low_stock_count = count(array_filter($all_products, fn($p) => $p['stock'] > 0 && $p['stock'] < 10));
$out_of_stock_count = count(array_filter($all_products, fn($p) => $p['stock'] <= 0));
?>

<!-- PAGE HEADER -->
<div class="mb-6 flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
    <div>
        <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Inventory & Stock Status</h1>
        <p class="text-xs text-slate-500 mt-0.5">Track warehouse levels, replenish out-of-stock items, and update batch quantities</p>
    </div>
    
    <!-- Quick Stock Stat Badges -->
    <div class="flex items-center gap-2">
        <div class="bg-amber-50 border border-amber-200 px-3 py-1.5 rounded-xl flex items-center gap-2 text-xs font-semibold text-amber-800">
            <span class="w-2 h-2 rounded-full bg-amber-500"></span>
            <span>Low Stock: <?php echo $low_stock_count; ?></span>
        </div>
        <div class="bg-rose-50 border border-rose-200 px-3 py-1.5 rounded-xl flex items-center gap-2 text-xs font-semibold text-rose-800">
            <span class="w-2 h-2 rounded-full bg-rose-500"></span>
            <span>Out of Stock: <?php echo $out_of_stock_count; ?></span>
        </div>
    </div>
</div>

<!-- FILTER BAR -->
<div class="bg-white p-3.5 rounded-xl border border-slate-200 shadow-xs mb-6 flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-3">
    <form method="GET" class="flex flex-col sm:flex-row items-stretch sm:items-center gap-2.5 w-full sm:w-auto flex-1">
        <div class="relative w-full sm:w-64">
            <input type="text" name="q" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search product or SKU..." class="admin-input pl-9 text-xs">
            <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
        </div>
        
        <select name="category_id" onchange="this.form.submit()" class="admin-select py-2 text-xs font-semibold sm:w-48">
            <option value="">All Categories</option>
            <?php 
            $cats = fetch_all("SELECT * FROM categories ORDER BY name ASC");
            foreach($cats as $c): ?>
                <option value="<?php echo $c['id']; ?>" <?php echo $category_filter == $c['id'] ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($c['name']); ?>
                </option>
            <?php endforeach; ?>
        </select>
        
        <?php if($search || $category_filter): ?>
            <a href="inventory.php" class="btn-admin btn-admin-secondary text-xs text-center">Reset</a>
        <?php endif; ?>
    </form>
    
    <span class="text-xs text-slate-500 self-center hidden sm:block">
        Showing <span class="font-semibold text-slate-800"><?php echo count($products); ?></span> of <span class="font-semibold text-slate-800"><?php echo $pagination['total_records']; ?></span>
    </span>
</div>

<!-- FLOATING BULK DOCK -->
<div id="bulk-bar" class="hidden fixed bottom-6 left-1/2 -translate-x-1/2 z-[100] bg-slate-900 text-white px-4 py-2.5 rounded-2xl shadow-2xl border border-slate-700/80 items-center gap-3 text-xs max-w-[95vw] overflow-x-auto anim-fade-in">
    <div class="flex items-center gap-2 border-r border-slate-700 pr-3 shrink-0">
        <span class="w-2 h-2 rounded-full bg-emerald-400"></span>
        <span class="font-bold text-white"><span id="selected-count">0</span> Selected</span>
        <div id="all-pages-notice" class="hidden ml-1">
            <button onclick="selectAllPages()" class="text-emerald-400 hover:underline font-semibold">Select all <?php echo $pagination['total_records']; ?></button>
        </div>
        <span id="all-pages-active" class="hidden text-emerald-400 font-semibold ml-1">(All selected)</span>
    </div>
    
    <form method="POST" class="flex items-center gap-2 shrink-0">
        <input type="hidden" name="bulk_action" id="bulk-action-type">
        <input type="hidden" name="all_selected" id="bulk-all-selected" value="false">
        <div id="bulk-ids-container"></div>
        
        <div class="flex items-center gap-1.5">
            <input type="number" name="bulk_stock_val" placeholder="Qty" class="w-16 bg-slate-800 border border-slate-700 rounded-lg px-2.5 py-1 text-xs font-bold text-white outline-none focus:border-emerald-500">
            <button type="submit" onclick="document.getElementById('bulk-action-type').value='set_stock'" class="btn-admin btn-admin-primary btn-admin-sm">Set Stock</button>
        </div>
        
        <button type="submit" onclick="document.getElementById('bulk-action-type').value='delete'; return confirm('Archive selected products safely?')" class="p-1.5 rounded-lg text-rose-400 hover:bg-rose-500/20 hover:text-rose-300 transition-colors ml-1" title="Archive Products">
            <i class="fas fa-trash-alt text-xs"></i>
        </button>
        
        <button type="button" onclick="resetSelection()" class="text-slate-400 hover:text-white p-1 ml-1" title="Cancel selection">
            <i class="fas fa-times text-xs"></i>
        </button>
    </form>
</div>

<!-- INVENTORY TABLE -->
<div class="admin-table-container">
    <div class="overflow-x-auto">
        <table class="admin-table">
            <thead>
                <tr>
                    <th class="w-10 text-center">
                        <input type="checkbox" id="select-all" class="w-4 h-4 rounded border-slate-300 text-emerald-600 focus:ring-emerald-500 cursor-pointer">
                    </th>
                    <th>Product & SKU</th>
                    <th>Category</th>
                    <th class="text-center w-36">Stock Level</th>
                    <th>Status</th>
                    <th class="text-right w-28">Quick Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if(empty($products)): ?>
                    <tr>
                        <td colspan="6" class="py-12 text-center text-slate-400 text-xs">
                            No inventory items matched your search.
                        </td>
                    </tr>
                <?php else: foreach($products as $p): 
                    $status_text = 'Healthy';
                    $badge_class = 'admin-badge-success';
                    
                    if ($p['stock'] <= 0) {
                        $status_text = 'Out of Stock';
                        $badge_class = 'admin-badge-danger';
                    } elseif ($p['stock'] < 10) {
                        $status_text = 'Low Stock';
                        $badge_class = 'admin-badge-warning';
                    }
                ?>
                <tr class="hover:bg-slate-50 transition-colors group">
                    <td class="text-center">
                        <input type="checkbox" value="<?php echo $p['id']; ?>" class="product-checkbox w-4 h-4 rounded border-slate-300 text-emerald-600 focus:ring-emerald-500 cursor-pointer">
                    </td>
                    <td>
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-lg bg-slate-50 border border-slate-200 p-0.5 shrink-0 overflow-hidden">
                                <img src="../<?php echo $p['image']; ?>" class="w-full h-full object-contain" alt="<?php echo htmlspecialchars($p['name']); ?>">
                            </div>
                            <div class="min-w-0">
                                <p class="font-bold text-slate-900 text-xs truncate max-w-xs sm:max-w-md"><?php echo htmlspecialchars($p['name']); ?></p>
                                <p class="text-[11px] text-slate-400 font-mono">SKU: <?php echo htmlspecialchars($p['sku'] ?: '—'); ?></p>
                            </div>
                        </div>
                    </td>
                    <td class="text-xs text-slate-600 font-medium">
                        <?php echo htmlspecialchars($p['category_name'] ?: 'General'); ?>
                    </td>
                    <td class="text-center">
                        <div class="inline-flex items-center gap-1.5">
                            <input type="number" id="stock-<?php echo $p['id']; ?>" value="<?php echo $p['stock']; ?>" 
                                class="w-20 bg-slate-50 border border-slate-200 rounded-lg px-2.5 py-1 text-center text-xs font-bold text-slate-900 focus:bg-white focus:border-[#004f42] outline-none transition-colors"
                                onkeypress="if(event.key === 'Enter') saveStock(<?php echo $p['id']; ?>)">
                        </div>
                    </td>
                    <td>
                        <span class="admin-badge <?php echo $badge_class; ?>">
                            <span class="w-1.5 h-1.5 rounded-full bg-current"></span>
                            <?php echo $status_text; ?>
                        </span>
                    </td>
                    <td class="text-right">
                        <button onclick="saveStock(<?php echo $p['id']; ?>)" id="btn-<?php echo $p['id']; ?>" class="btn-admin btn-admin-primary btn-admin-sm">
                            Save
                        </button>
                    </td>
                </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- PAGINATION -->
<div class="mt-8">
    <?php echo render_pagination($pagination['total_pages'], $pagination['current_page']); ?>
</div>

<script>
const STORAGE_KEY = 'driyum_inventory_selection';
const selectAll = document.getElementById('select-all');
const productCheckboxes = document.querySelectorAll('.product-checkbox');
const bulkBar = document.getElementById('bulk-bar');
const selectedCountText = document.getElementById('selected-count');
const allPagesNotice = document.getElementById('all-pages-notice');
const allPagesActive = document.getElementById('all-pages-active');

let isAllSelectedAcrossPages = (sessionStorage.getItem('inv_all_pages') === 'true');
const TOTAL_RECORDS = <?php echo (int)$pagination['total_records']; ?>;

function getStored() { return new Set(JSON.parse(sessionStorage.getItem(STORAGE_KEY) || '[]')); }
function syncStored(set) {
    sessionStorage.setItem(STORAGE_KEY, JSON.stringify([...set]));
    updateUI();
}

function selectAllPages() {
    isAllSelectedAcrossPages = true;
    sessionStorage.setItem('inv_all_pages', 'true');
    updateUI();
}

function resetSelection() {
    isAllSelectedAcrossPages = false;
    sessionStorage.removeItem('inv_all_pages');
    sessionStorage.removeItem(STORAGE_KEY);
    if(selectAll) selectAll.checked = false;
    productCheckboxes.forEach(cb => cb.checked = false);
    updateUI();
}

function updateUI() {
    const tracked = getStored();
    const onPage = productCheckboxes.length;
    const checked = Array.from(productCheckboxes).filter(cb => cb.checked).length;
    
    if(selectAll) selectAll.checked = (onPage > 0 && checked === onPage);

    const count = isAllSelectedAcrossPages ? TOTAL_RECORDS : tracked.size;
    if (selectedCountText) selectedCountText.textContent = count;
    
    const allInput = document.getElementById('bulk-all-selected');
    if(allInput) allInput.value = isAllSelectedAcrossPages ? 'true' : 'false';

    const container = document.getElementById('bulk-ids-container');
    if(container) {
        container.innerHTML = '';
        if (!isAllSelectedAcrossPages) {
            tracked.forEach(id => {
                const inp = document.createElement('input');
                inp.type = 'hidden';
                inp.name = 'ids[]';
                inp.value = id;
                container.appendChild(inp);
            });
        }
    }

    if (count > 0) {
        bulkBar.classList.remove('hidden');
        bulkBar.classList.add('flex');
    } else {
        bulkBar.classList.add('hidden');
        bulkBar.classList.remove('flex');
    }

    if (checked === onPage && TOTAL_RECORDS > onPage && !isAllSelectedAcrossPages) {
        if(allPagesNotice) allPagesNotice.classList.remove('hidden');
    } else {
        if(allPagesNotice) allPagesNotice.classList.add('hidden');
    }

    if (isAllSelectedAcrossPages) {
        if(allPagesActive) allPagesActive.classList.remove('hidden');
    } else {
        if(allPagesActive) allPagesActive.classList.add('hidden');
    }
}

if(selectAll) {
    selectAll.addEventListener('change', () => {
        const tracked = getStored();
        productCheckboxes.forEach(cb => {
            cb.checked = selectAll.checked;
            if (selectAll.checked) tracked.add(cb.value);
            else tracked.delete(cb.value);
        });
        syncStored(tracked);
    });
}

productCheckboxes.forEach(cb => {
    cb.addEventListener('change', () => {
        const tracked = getStored();
        if (cb.checked) tracked.add(cb.value);
        else {
            tracked.delete(cb.value);
            isAllSelectedAcrossPages = false;
            sessionStorage.setItem('inv_all_pages', 'false');
        }
        syncStored(tracked);
    });
});

// Restore saved checkbox state on page load
const stored = getStored();
productCheckboxes.forEach(cb => {
    if (stored.has(cb.value)) cb.checked = true;
});
updateUI();

async function saveStock(pid) {
    const input = document.getElementById(`stock-${pid}`);
    const btn = document.getElementById(`btn-${pid}`);
    const stockVal = input.value;
    const originalText = btn.innerHTML;

    btn.innerHTML = '<i class="fas fa-spinner fa-spin text-xs"></i>';
    btn.disabled = true;

    try {
        const formData = new FormData();
        formData.append('action', 'update_stock');
        formData.append('product_id', pid);
        formData.append('stock', stockVal);

        const res = await fetch('inventory.php', { method: 'POST', body: formData });
        const data = await res.json();

        if (data.success) {
            btn.innerHTML = '<i class="fas fa-check text-xs"></i>';
            if(typeof showToast === 'function') showToast(`Stock updated to ${stockVal}`, 'success');
            setTimeout(() => {
                btn.innerHTML = originalText;
                btn.disabled = false;
            }, 1000);
        } else {
            alert('Failed to update stock: ' + (data.message || ''));
            btn.innerHTML = originalText;
            btn.disabled = false;
        }
    } catch(e) {
        alert('Network error while updating stock');
        btn.innerHTML = originalText;
        btn.disabled = false;
    }
}
</script>

<?php include 'includes/footer.php'; ?>
