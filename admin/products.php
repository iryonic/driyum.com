<?php
require_once '../config/database.php';
require_once '../includes/functions.php';

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
                if (file_exists($full_path)) unlink($full_path);
            }
        }
        
        // Delete gallery images records and files
        $g_res = $conn->query("SELECT image_path FROM product_images WHERE product_id IN ($ids_str)");
        while ($g_row = $g_res->fetch_assoc()) {
            $full_path = '../' . $g_row['image_path'];
            if (file_exists($full_path)) unlink($full_path);
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
        // If showing on home, we should also make sure it's active
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
            echo json_encode(['success' => true, 'new_status' => $new_status, 'label' => $new_status ? 'Active' : 'Offline']);
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
?>
<?php include 'includes/header.php'; ?>
<?php
// Handle Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'save') {
        // Save/Update logic would go here (Simplified for demo)
        $msg = "Product saved successfully!";
    }
}

// Handle Delete
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    get_db_connection()->query("DELETE FROM products WHERE id = $id");
    echo "<script>window.location='products.php';</script>";
}

// Search Logic
$search = sanitize_input($_GET['search'] ?? '');
$params = [];
$where = "WHERE 1=1";
if ($search) {
    $where .= " AND (p.name LIKE ? OR c.name LIKE ?)";
    $params = ["%$search%", "%$search%"];
}

// Fetch Products
$query = "SELECT p.*, c.name as cat_name FROM products p LEFT JOIN categories c ON p.category_id = c.id $where ORDER BY p.is_active DESC, p.id DESC";
$pagination = get_pagination_data($query, $params, 12);
$products = $pagination['records'];
?>
<style>
    /* Premium Responsive Bulk Dock */
    #bulk-action-bar {
        transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
    }
    
    @media (max-width: 768px) {
        #bulk-action-bar {
            bottom: 1rem !important;
            left: 1rem !important;
            right: 1rem !important;
            margin: 0 !important;
            transform: translateY(0) scale(1) !important;
            border-radius: 20px;
            padding: 0.4rem;
            gap: 0.4rem;
            overflow-x: auto;
            justify-content: flex-start;
            max-width: none !important;
            width: auto;
            /* Hide scrollbar but keep functionality */
            -ms-overflow-style: none;
            scrollbar-width: none;
        }
        #bulk-action-bar::-webkit-scrollbar {
            display: none;
        }
        
        #bulk-action-bar > div, 
        #bulk-action-bar > button {
            flex-shrink: 0;
        }

        #bulk-action-bar .px-5 {
            padding-left: 0.5rem;
            padding-right: 0.5rem;
        }

        #bulk-action-bar .px-4 {
            padding-left: 0.4rem;
            padding-right: 0.4rem;
        }

        /* More aggressive text hiding on mobile */
        #bulk-action-bar span.sm\:inline {
            display: none !important;
        }
    }
</style>

<div class="mb-8 flex flex-col md:flex-row justify-between items-start md:items-center gap-6 anim-up">
    <div>
        <h1 class="text-3xl font-black text-gray-900 crimson-pro tracking-tight">Snack Vault</h1>
        <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mt-1">Found <span class="text-black"><?php echo $pagination['total_records']; ?></span> items in inventory</p>
    </div>
    
    <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3 w-full md:w-auto">
        <form class="relative group flex-1 sm:w-64">
            <input type="text" name="search" value="<?php echo $search; ?>" placeholder="Search snacks..." class="w-full bg-white border border-gray-100 focus:border-[#24B25D] rounded-2xl pl-10 pr-4 py-2.5 text-xs font-bold transition-all outline-none shadow-sm">
            <i class="fas fa-search absolute left-4 top-1/2 -translate-y-1/2 text-gray-300 group-focus-within:text-[#24B25D] transition-colors text-[10px]"></i>
        </form>
        <a href="product_form.php" class="bg-[#24B25D] text-black font-black px-6 py-2.5 rounded-2xl text-[10px] uppercase tracking-widest shadow-lg shadow-emerald-100 hover:scale-105 active:scale-95 transition-all text-center">
            <i class="fas fa-plus mr-1"></i> New Snack
        </a>
    </div>
</div>

<!-- PREMIUM BULK ACTION DOCK -->
<div id="bulk-action-bar" class="hidden fixed bottom-8   z-[100] bg-[#111] backdrop-blur-2xl border border-white/10 px-3 py-3 rounded-[28px] shadow-[0_30px_60px_-15px_rgba(0,0,0,0.5)] items-center gap-2 anim-up border-b-4 border-b-[#24B25D]/20 max-w-[95vw] sm:max-w-none">
    
    <!-- Selection Info -->
    <div class="bg-white/5 rounded-2xl px-5 py-3 flex items-center gap-4 mr-1 border border-white/5">
        <div class="flex flex-col">
            <span class="text-[8px] font-black text-gray-500 uppercase tracking-[0.2em] leading-none mb-1 hidden sm:block">Editing</span>
            <span class="text-xs font-black text-[#24B25D] leading-none"><span id="selected-count">0</span><span class="hidden sm:inline ml-1">Items</span></span>
        </div>
        <button onclick="document.querySelectorAll('.product-checkbox').forEach(cb => {cb.checked = false; updateBulkBar();})" class="w-7 h-7 rounded-xl bg-white/5 hover:bg-white/10 flex items-center justify-center transition-all text-gray-400 hover:text-white">
            <i class="fas fa-times text-[10px]"></i>
        </button>
    </div>

    <!-- Group: Status -->
    <div class="flex items-center p-0.5 bg-white/5 rounded-2xl border border-white/5">
        <button onclick="bulkUpdateAction('bulk_status', {status: 1})" class="flex items-center gap-2 px-3 sm:px-4 py-2.5 rounded-xl hover:bg-white/5 text-white transition-all group">
            <i class="fas fa-eye text-[10px] text-[#24B25D] group-hover:scale-110 transition-transform"></i>
            <span class="text-[10px] font-black uppercase tracking-widest hidden sm:inline">Active</span>
        </button>
        <button onclick="bulkUpdateAction('bulk_status', {status: 0})" class="flex items-center gap-2 px-3 sm:px-4 py-2.5 rounded-xl hover:bg-white/5 text-white transition-all group">
            <i class="fas fa-eye-slash text-[10px] text-gray-400 group-hover:scale-110 transition-transform"></i>
            <span class="text-[10px] font-black uppercase tracking-widest hidden sm:inline">Inactive</span>
        </button>
    </div>

    <!-- Group: Home Show -->
    <div class="flex items-center p-0.5 bg-white/5 rounded-2xl border border-white/5">
        <button onclick="bulkUpdateAction('bulk_featured', {featured: 1})" class="flex items-center gap-2 px-3 sm:px-4 py-2.5 rounded-xl hover:bg-white/5 text-white transition-all group" title="Show on Homepage">
            <i class="fas fa-home text-[10px] text-[#24B25D] group-hover:scale-110 transition-transform"></i>
            <span class="text-[10px] font-black uppercase tracking-widest hidden sm:inline">Show on Home</span>
        </button>
        <button onclick="bulkUpdateAction('bulk_featured', {featured: 0})" class="flex items-center gap-2 px-3 sm:px-4 py-2.5 rounded-xl hover:bg-white/5 text-white transition-all group" title="Hide from Homepage">
            <i class="fas fa-eye-slash text-[10px] text-gray-500 group-hover:scale-110 transition-transform"></i>
            <span class="text-[10px] font-black uppercase tracking-widest hidden sm:inline">Hide from Home</span>
        </button>
    </div>

    <!-- Group: Weight -->
    <div class="flex items-center pl-3 sm:pl-4 pr-1 py-1 bg-white/10 rounded-2xl border border-white/10 ring-2 ring-transparent focus-within:ring-[#24B25D]/30 transition-all">
        <i class="fas fa-weight-hanging text-[10px] text-gray-400 mr-2 sm:mr-3"></i>
        <input type="number" step="0.001" id="bulk-weight-input" placeholder="0.5" class="w-10 sm:w-16 bg-transparent border-none p-0 text-xs font-black text-white outline-none placeholder:text-white/20">
        <button onclick="bulkUpdateAction('bulk_weight', {weight: document.getElementById('bulk-weight-input').value})" class="bg-[#24B25D] text-black w-8 h-8 rounded-xl flex items-center justify-center hover:scale-105 active:scale-95 transition-all ml-1 sm:ml-2 shadow-lg shadow-[#24B25D]/20">
            <i class="fas fa-check text-[10px]"></i>
        </button>
    </div>

    <div class="w-px h-8 bg-white/10 mx-0.5 hidden sm:block"></div>

    <!-- Delete -->
    <button onclick="bulkDeleteProducts()" class="w-11 h-11 rounded-2xl flex items-center justify-center text-red-400 hover:bg-red-500/10 hover:text-red-500 transition-all hover:rotate-6">
        <i class="fas fa-trash-alt text-sm"></i>
    </button>
</div>

<!-- Products Grid -->
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 2xl:grid-cols-5 gap-6" id="productGrid">
    <?php foreach ($products as $p): ?>
    <div class="product-card bg-white rounded-3xl p-4 shadow-sm border border-gray-100 hover:shadow-xl hover:-translate-y-1 transition-all duration-300 group flex flex-col anim-up">
        
        <!-- Image Area -->
        <div class="rounded-2xl aspect-square mb-4 relative overflow-hidden flex items-center justify-center p-4" style="background-color: <?php echo $p['bg_color'] ?: '#F9FAFB'; ?>">
            <img src="../<?php echo $p['image']; ?>" class="w-full h-full object-contain group-hover:scale-110 transition duration-500 drop-shadow-xl">
            
            <!-- Badges Over Image -->
            <div class="absolute top-3 right-3 flex flex-col gap-2 scale-90 origin-top-right">
                <input type="checkbox" class="product-checkbox w-5 h-5 rounded-lg border-2 border-white/50 text-[#24B25D] focus:ring-[#24B25D] cursor-pointer shadow-lg" value="<?php echo $p['id']; ?>" onchange="updateBulkBar()">
            </div>
            
            <?php if($p['stock'] <= 5): ?>
                <div class="absolute bottom-3 left-3 bg-red-500 text-white text-[8px] font-black uppercase tracking-widest px-2 py-1 rounded-lg shadow-lg">Low Stock</div>
            <?php endif; ?>
        </div>

        <!-- Info -->
        <div class="flex-1 flex flex-col">
            <div class="flex items-center justify-between mb-1">
                <div class="flex items-center gap-2">
                    <span class="text-[9px] font-bold text-gray-400 uppercase tracking-widest"><?php echo $p['cat_name']; ?></span>
                    <button onclick="toggleFeatured(<?php echo $p['id']; ?>, this)" class="featured-toggle transition-all hover:scale-125 <?php echo $p['is_featured'] ? 'text-yellow-400' : 'text-gray-200'; ?>">
                        <i class="fas fa-star text-[10px]"></i>
                    </button>
                </div>
                <button onclick="toggleProductStatus(<?php echo $p['id']; ?>, this)" class="text-[8px] font-black uppercase tracking-widest status-badge <?php echo $p['is_active'] ? 'text-[#24B25D]' : 'text-gray-300'; ?>">
                    <?php echo $p['is_active'] ? 'Active' : 'Offline'; ?>
                </button>
            </div>
            
            <h3 class="font-bold text-sm crimson-pro text-gray-900 leading-tight mb-3"><?php echo $p['name']; ?></h3>
            
            <div class="mt-auto flex items-center justify-between pt-2 border-t border-gray-50">
                <div>
                    <p class="text-[8px] font-black text-gray-400 uppercase tracking-tighter">Price</p>
                    <p class="font-black text-gray-900">₹<?php echo number_format($p['price']); ?></p>
                </div>
                <div class="text-right">
                    <p class="text-[8px] font-black text-gray-400 uppercase tracking-tighter">Stock</p>
                    <p class="font-bold <?php echo $p['stock'] < 10 ? 'text-red-500' : 'text-gray-900'; ?>"><?php echo $p['stock']; ?></p>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-4 gap-2 mt-4">
            <a href="product_form.php?id=<?php echo $p['id']; ?>" class="col-span-3 bg-gray-50 text-gray-900 py-2.5 rounded-xl text-center text-[10px] font-black uppercase tracking-widest hover:bg-black hover:text-white transition-all">Manage</a>
            <button onclick="if(confirm('Delete snack?')) window.location='?delete=<?php echo $p['id']; ?>'" class="bg-gray-50 text-gray-300 hover:text-red-500 rounded-xl transition-colors"><i class="fas fa-trash-alt text-[10px]"></i></button>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<?php echo render_pagination($pagination['total_pages'], $pagination['current_page']); ?>

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
                btn.classList.add('text-yellow-400');
                btn.classList.remove('text-gray-200');
            } else {
                btn.classList.remove('text-yellow-400');
                btn.classList.add('text-gray-200');
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
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
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
                btn.classList.remove('bg-gray-200', 'text-gray-500');
                btn.classList.add('bg-[#24B25D]', 'text-black');
            } else {
                btn.classList.remove('bg-[#24B25D]', 'text-black');
                btn.classList.add('bg-gray-200', 'text-gray-500');
            }
            // Success animation
            btn.classList.add('scale-110');
            setTimeout(() => btn.classList.remove('scale-110'), 200);
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
    bar.innerHTML = '<div class="flex items-center gap-3 px-10"><i class="fas fa-spinner fa-spin text-[#24B25D] text-xl"></i> <span class="text-xs font-black text-gray-400 uppercase tracking-widest">Applying Changes...</span></div>';

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
        ? await window.showConfirm(`Danger! You are about to delete ${checked.length} products. This will also delete their gallery images and cannot be undone. Proceed?`, {
            title: 'Delete Products Forever',
            type: 'danger',
            confirmText: 'Delete Products'
        })
        : confirm(`Danger! You are about to delete ${checked.length} products. This will also delete their gallery images and cannot be undone. Proceed?`);
    if (!ok) return;

    await bulkUpdateAction('bulk_delete');
}
</script>
<?php include 'includes/footer.php'; ?>


