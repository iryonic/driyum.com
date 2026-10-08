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
            $_SESSION['success'] = "Archived/Deactivated " . count($ids) . " products safely";
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
$all_products = fetch_all("SELECT stock FROM products");
$total_count = count($all_products);
$low_stock_count = count(array_filter($all_products, fn($p) => $p['stock'] > 0 && $p['stock'] < 10));
$out_of_stock_count = count(array_filter($all_products, fn($p) => $p['stock'] <= 0));
?>

<div class="mb-8 flex flex-col md:flex-row justify-between items-start md:items-center gap-6 anim-up">
    <div>
        <h1 class="text-3xl font-black text-gray-900 crimson-pro tracking-tight">Inventory</h1>
        <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mt-1">Managing <span class="text-black"><?php echo $pagination['total_records']; ?></span> products</p>
    </div>
    <div class="flex flex-col sm:flex-row items-center gap-4 w-full md:w-auto">
        <form class="flex flex-col sm:flex-row gap-3 w-full">
            <div class="relative group flex-1 md:w-64">
                <input type="text" name="q" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search products..." class="w-full bg-white border border-gray-100 focus:border-black rounded-2xl pl-10 pr-4 py-2 text-xs font-bold transition-all outline-none shadow-sm">
                <i class="fas fa-search absolute left-4 top-1/2 -translate-y-1/2 text-gray-300 group-focus-within:text-black transition-colors text-[10px]"></i>
            </div>
            <select name="category_id" onchange="this.form.submit()" class="bg-white border border-gray-100 rounded-2xl px-4 py-2 text-[10px] font-black uppercase tracking-widest outline-none focus:border-black shadow-sm">
                <option value="">All Categories</option>
                <?php 
                $cats = fetch_all("SELECT * FROM categories ORDER BY name ASC");
                foreach($cats as $c): ?>
                    <option value="<?php echo $c['id']; ?>" <?php echo $category_filter == $c['id'] ? 'selected' : ''; ?>><?php echo $c['name']; ?></option>
                <?php endforeach; ?>
            </select>
        </form>
        <div class="flex gap-2">
            <div class="bg-amber-50 px-4 py-2 rounded-2xl border border-amber-100 flex items-center gap-2">
                <span class="text-[9px] font-black uppercase text-amber-600">Low: <?php echo $low_stock_count; ?></span>
            </div>
            <div class="bg-red-50 px-4 py-2 rounded-2xl border border-red-100 flex items-center gap-2">
                <span class="text-[9px] font-black uppercase text-red-600">Out: <?php echo $out_of_stock_count; ?></span>
            </div>
        </div>
    </div>
</div>

<!-- Bulk Action Bar -->
<style>
    @media (max-width: 768px) {
        #bulk-bar {
            left: 1rem;
            right: 1rem;
            bottom: 1.5rem;
            transform: none !important;
            flex-direction: column;
            align-items: stretch;
            padding: 1.25rem;
            gap: 1rem;
            width: auto;
            border-radius: 24px;
            background: rgba(0, 0, 0, 0.95);
            backdrop-filter: blur(16px);
        }
        #bulk-bar > div:first-child {
            border-right: none;
            padding-right: 0;
            justify-content: space-between;
            width: 100%;
        }
        #bulk-bar form {
            flex-wrap: wrap;
            gap: 0.75rem;
            width: 100%;
            justify-content: space-between;
        }
        #bulk-bar .w-px.h-6 {
            display: none;
        }
        #bulk-bar input[name="bulk_stock_val"] {
            width: 80px;
        }
    }
</style>
<div id="bulk-bar" class="hidden fixed bottom-10 z-50 bg-black text-white px-8 py-5 rounded-[32px] shadow-2xl items-center gap-8 anim-up border border-white/10">
    <div class="flex items-center gap-4 pr-8 border-r border-white/10">
        <div class="w-10 h-10 rounded-2xl bg-[#24B25D] flex items-center justify-center text-black">
            <i class="fas fa-boxes text-lg"></i>
        </div>
        <div>
            <p class="text-[10px] font-black text-[#24B25D] uppercase tracking-widest"><span id="selected-count">0</span> Selected</p>
            <div id="all-pages-notice" class="hidden">
                <button onclick="selectAllPages()" class="text-[9px] font-bold text-white hover:underline">Select all matching <?php echo $pagination['total_records']; ?></button>
            </div>
            <p id="all-pages-active" class="hidden text-[9px] font-bold text-white">All matching records selected</p>
        </div>
    </div>
    
    <form method="POST" class="flex items-center gap-4">
        <input type="hidden" name="bulk_action" id="bulk-action-type">
        <input type="hidden" name="all_selected" id="bulk-all-selected" value="false">
        <div id="bulk-ids-container"></div>
        
        <div class="flex items-center gap-3">
            <input type="number" name="bulk_stock_val" placeholder="Stock" class="w-24 bg-white/10 border border-white/10 rounded-xl px-4 py-2 text-xs font-bold text-white outline-none focus:border-[#24B25D]">
            <button type="submit" onclick="document.getElementById('bulk-action-type').value='set_stock'" class="bg-[#24B25D] text-black px-5 py-2.5 rounded-xl text-[10px] font-black uppercase tracking-widest hover:scale-105 transition-all">Set Stock</button>
        </div>
        
        <div class="w-px h-6 bg-white/10 mx-2"></div>
        
        <button type="submit" onclick="document.getElementById('bulk-action-type').value='delete'; return confirm('Delete selected products?')" class="text-red-400 hover:text-red-500 transition-colors">
            <i class="fas fa-trash-alt"></i>
        </button>
        
        <button type="button" onclick="resetSelection()" class="text-[9px] font-black uppercase tracking-widest text-white/40 hover:text-white transition-colors ml-4">Cancel</button>
    </form>
</div>

<div class="bg-white rounded-3xl shadow-sm border border-gray-100 overflow-hidden anim-up">
    <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="text-gray-400 text-[8px] uppercase bg-gray-50/50 border-b border-gray-100 font-black tracking-widest">
                    <th class="p-5 w-16 text-center">
                        <input type="checkbox" id="select-all" class="w-4 h-4 rounded border-gray-200 text-black focus:ring-black cursor-pointer">
                    </th>
                    <th class="p-5">Product</th>
                    <th class="p-5 font-black">Category</th>
                    <th class="p-5">Stock</th>
                    <th class="p-5">Status</th>
                    <th class="p-5 text-right">Action</th>
                </tr>
            </thead>
            <tbody class="text-xs text-gray-600">
                <?php foreach($products as $p): 
                    $status_text = 'Healthy';
                    $color_class = 'bg-green-50 text-green-600 border-green-100';
                    $dot_color = 'bg-green-500';
                    
                    if ($p['stock'] <= 0) {
                        $status_text = 'Out of Stock';
                        $color_class = 'bg-red-50 text-red-600 border-red-100';
                        $dot_color = 'bg-red-500';
                    } elseif ($p['stock'] < 10) {
                        $status_text = 'Low Stock';
                        $color_class = 'bg-amber-50 text-amber-600 border-amber-100';
                        $dot_color = 'bg-amber-500';
                    }
                ?>
                <tr class="border-b border-gray-50 hover:bg-gray-50/50 transition-all group">
                    <td class="p-4 text-center">
                        <input type="checkbox" value="<?php echo $p['id']; ?>" class="product-checkbox w-4 h-4 rounded border-gray-200 text-[#24B25D] focus:ring-[#24B25D] cursor-pointer">
                    </td>
                    <td class="p-4">
                        <div class="flex items-center gap-4">
                            <div class="w-10 h-10 rounded-xl bg-white border border-gray-100 p-1">
                                <img src="../<?php echo $p['image']; ?>" class="w-full h-full object-contain">
                            </div>
                            <div>
                                <p class="font-bold text-gray-900"><?php echo $p['name']; ?></p>
                                <p class="text-[9px] text-gray-400 font-bold uppercase tracking-widest">SKU: <?php echo $p['sku']; ?></p>
                            </div>
                        </div>
                    </td>
                    <td class="p-4">
                        <span class="text-[9px] font-black uppercase tracking-widest text-gray-400"><?php echo $p['category_name']; ?></span>
                    </td>
                    <td class="p-4">
                        <input type="number" id="stock-<?php echo $p['id']; ?>" value="<?php echo $p['stock']; ?>" 
                            class="w-20 bg-gray-50 border-none rounded-xl px-3 py-2 text-center text-xs font-black focus:ring-1 focus:ring-black outline-none transition uppercase">
                    </td>
                    <td class="p-4">
                        <span class="px-2 py-0.5 rounded-lg text-[8px] font-black uppercase tracking-widest border <?php echo $color_class; ?> flex items-center gap-1.5 w-fit">
                            <span class="w-1 h-1 rounded-full <?php echo $dot_color; ?>"></span>
                            <?php echo $status_text; ?>
                        </span>
                    </td>
                    <td class="p-4 text-right">
                        <button onclick="saveStock(<?php echo $p['id']; ?>)" id="btn-<?php echo $p['id']; ?>" class="bg-black text-[#24B25D] px-4 py-2 rounded-xl text-[9px] font-black uppercase tracking-widest hover:scale-105 active:scale-95 transition-all shadow-sm">
                            Update
                        </button>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<div id="toast" class="fixed bottom-10 right-10 z-[200] translate-y-20 opacity-0 transition-all duration-500 pointer-events-none">
    <div class="bg-black text-white px-6 py-3 rounded-2xl shadow-2xl font-black text-[10px] uppercase tracking-widest flex items-center gap-3">
        <i class="fas fa-check-circle text-[#24B25D]"></i>
        <span id="toast-text">Updated!</span>
    </div>
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

    if (tracked.size > 0 || isAllSelectedAcrossPages) {
        bulkBar.classList.remove('hidden');
        bulkBar.classList.add('flex');
        
        if (isAllSelectedAcrossPages) {
            selectedCountText.textContent = TOTAL_RECORDS;
            allPagesNotice.classList.add('hidden');
            allPagesActive.classList.remove('hidden');
        } else {
            selectedCountText.textContent = tracked.size;
            if (checked === onPage && TOTAL_RECORDS > onPage) allPagesNotice.classList.remove('hidden');
            else allPagesNotice.classList.add('hidden');
            allPagesActive.classList.add('hidden');
        }
    } else {
        bulkBar.classList.add('hidden');
        bulkBar.classList.remove('flex');
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

document.querySelector('form[method="POST"]')?.addEventListener('submit', function(e) {
    if(this.id === 'bulkEmailForm') return; // skip for broadcast
    const tracked = getStored();
    document.getElementById('bulk-all-selected').value = isAllSelectedAcrossPages;
    const container = document.getElementById('bulk-ids-container');
    container.innerHTML = '';
    if(!isAllSelectedAcrossPages) {
        tracked.forEach(id => {
            const input = document.createElement('input');
            input.type = 'hidden'; input.name = 'ids[]'; input.value = id;
            container.appendChild(input);
        });
    }
});

function init() {
    const tracked = getStored();
    productCheckboxes.forEach(cb => { if (tracked.has(cb.value)) cb.checked = true; });
    updateUI();
}
init();

function showToast(msg) {
    const toast = document.getElementById('toast');
    document.getElementById('toast-text').innerText = msg;
    toast.classList.remove('translate-y-20', 'opacity-0');
    setTimeout(() => toast.classList.add('translate-y-20', 'opacity-0'), 2000);
}

async function saveStock(pid) {
    const stock = document.getElementById('stock-' + pid).value;
    const btn = document.getElementById('btn-' + pid);
    const originalText = btn.innerHTML;
    
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
    btn.disabled = true;

    const formData = new FormData();
    formData.append('action', 'update_stock');
    formData.append('product_id', pid);
    formData.append('stock', stock);

    try {
        const res = await fetch('inventory.php', { method: 'POST', body: formData });
        const data = await res.json();
        if (data.success) {
            showToast("STOCK UPDATED");
        }
    } catch (e) { console.error(e); }
    finally {
        btn.innerHTML = originalText;
        btn.disabled = false;
    }
}
</script>

<?php echo render_pagination($pagination['total_pages'], $pagination['current_page']); ?>

<?php include 'includes/footer.php'; ?>


