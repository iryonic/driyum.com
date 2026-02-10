<?php
require_once '../config/database.php';
require_once '../includes/functions.php';

// Handle Action
if (isset($_GET['mark_reminded'])) {
    $id = (int)$_GET['mark_reminded'];
    if (send_abandoned_cart_reminder($id)) {
        execute_query("UPDATE abandoned_carts SET is_reminded = 1 WHERE id = ?", [$id]);
        $_SESSION['success'] = "Reminder sent!";
    } else {
        $_SESSION['error'] = "Failed to send email.";
    }
    header('Location: abandoned_carts.php');
    exit;
}

if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    execute_query("DELETE FROM abandoned_carts WHERE id = ?", [$id]);
    $_SESSION['success'] = "Cart removed.";
    header('Location: abandoned_carts.php');
    exit;
}

// Handle Bulk Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cart_ids']) && is_array($_POST['cart_ids'])) {
    $ids = $_POST['cart_ids'];
    $success_count = 0;
    
    if (isset($_POST['bulk_remind'])) {
        foreach ($ids as $id) {
            $id = (int)$id;
            if (send_abandoned_cart_reminder($id)) {
                execute_query("UPDATE abandoned_carts SET is_reminded = 1 WHERE id = ?", [$id]);
                $success_count++;
            }
        }
        if ($success_count > 0) $_SESSION['success'] = "Sent $success_count reminders!";
    }

    if (isset($_POST['bulk_delete'])) {
        foreach ($ids as $id) {
            $id = (int)$id;
            execute_query("DELETE FROM abandoned_carts WHERE id = ?", [$id]);
            $success_count++;
        }
        $_SESSION['success'] = "Deleted $success_count carts.";
    }

    header('Location: abandoned_carts.php');
    exit;
}

$query = "SELECT ac.*, u.name as user_name, u.email as user_email 
          FROM abandoned_carts ac 
          JOIN users u ON ac.user_id = u.id 
          ORDER BY ac.last_updated DESC";

$pagination = get_pagination_data($query, [], 15);
$carts = $pagination['records'];

include 'includes/header.php';
?>

<div class="mb-8 flex flex-col md:flex-row justify-between items-start md:items-center gap-6 anim-up">
    <div>
        <h1 class="text-3xl font-black text-gray-900 crimson-pro tracking-tight">Abandoned Carts</h1>
        <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mt-1">Found <span class="text-black"><?php echo $pagination['total_records']; ?></span> carts needing recovery</p>
    </div>
</div>

<?php if(isset($_SESSION['success'])): ?>
    <div class="mb-6 p-4 bg-green-50 text-green-700 rounded-2xl border border-green-100 font-bold text-xs anim-up flex items-center gap-3">
        <i class="fas fa-check-circle"></i> <?php echo $_SESSION['success']; ?>
    </div>
    <?php unset($_SESSION['success']); ?>
<?php endif; ?>

<form id="bulkActionForm" method="POST">
    <div class="bg-white rounded-3xl shadow-sm border border-gray-100 overflow-hidden anim-up">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="text-gray-400 text-[8px] uppercase bg-gray-50/50 border-b border-gray-100 font-black tracking-widest">
                        <th class="p-5 w-16 text-center">
                            <input type="checkbox" id="selectAll" class="w-4 h-4 rounded border-gray-200 text-black focus:ring-black cursor-pointer">
                        </th>
                        <th class="p-5 pl-0">Customer</th>
                        <th class="p-5">Total</th>
                        <th class="p-5">Items</th>
                        <th class="p-5">Last Seen</th>
                        <th class="p-5">Status</th>
                        <th class="p-5 text-right">Manage</th>
                    </tr>
                </thead>
                <tbody class="text-xs text-gray-600">
                    <?php if (empty($carts)): ?>
                    <tr>
                        <td colspan="7" class="p-20 text-center text-gray-400">
                            <i class="fas fa-ghost text-4xl mb-4 block opacity-10"></i>
                            <p class="font-bold">No abandoned carts found.</p>
                        </td>
                    </tr>
                    <?php endif; ?>
                    <?php foreach ($carts as $c): 
                        $items = json_decode($c['cart_data'], true);
                        $total_val = 0;
                        $item_count = 0;
                        $parts = [];
                        foreach($items as $pid => $qty) {
                            $p = get_product_by_id($pid);
                            if($p) {
                                $total_val += $p['price'] * $qty;
                                $item_count += $qty;
                                $parts[] = $p['name'] . " ($qty)";
                            }
                        }
                    ?>
                    <tr class="border-b border-gray-50 hover:bg-gray-50/50 transition-all group cart-row">
                        <td class="p-4 text-center">
                            <input type="checkbox" name="cart_ids[]" value="<?php echo $c['id']; ?>" class="cart-checkbox w-4 h-4 rounded border-gray-200 text-black focus:ring-black cursor-pointer">
                        </td>
                        <td class="p-4 pl-0">
                            <div class="font-bold text-gray-900"><?php echo htmlspecialchars($c['user_name']); ?></div>
                            <div class="text-[9px] font-bold text-gray-400"><?php echo htmlspecialchars($c['user_email']); ?></div>
                        </td>
                        <td class="p-4">
                            <div class="font-black text-gray-900">₹<?php echo number_format($total_val); ?></div>
                            <div class="text-[8px] font-bold text-gray-400 uppercase tracking-widest mt-0.5"><?php echo $item_count; ?> Items</div>
                        </td>
                        <td class="p-4 max-w-[200px]">
                            <p class="text-[10px] text-gray-500 truncate" title="<?php echo implode(', ', $parts); ?>">
                                <?php echo implode(', ', $parts); ?>
                            </p>
                        </td>
                        <td class="p-4">
                            <div class="font-bold text-gray-900"><?php echo get_time_ago($c['last_updated']); ?></div>
                            <div class="text-[8px] font-bold text-gray-300 uppercase tracking-widest"><?php echo date('M d', strtotime($c['last_updated'])); ?></div>
                        </td>
                        <td class="p-4">
                            <?php if($c['is_reminded']): ?>
                                <span class="px-2 py-0.5 rounded-lg text-[8px] font-black uppercase tracking-widest border border-blue-100 bg-blue-50 text-blue-500">Sent</span>
                            <?php else: ?>
                                <span class="px-2 py-0.5 rounded-lg text-[8px] font-black uppercase tracking-widest border border-amber-100 bg-amber-50 text-amber-500">Waiting</span>
                            <?php endif; ?>
                        </td>
                        <td class="p-4 text-right">
                            <div class="flex justify-end gap-2 opacity-0 group-hover:opacity-100 transition-all">
                                <?php if(!$c['is_reminded']): ?>
                                    <a href="?mark_reminded=<?php echo $c['id']; ?>" class="w-8 h-8 bg-black text-[#19DC7E] flex items-center justify-center rounded-lg hover:scale-105 transition-all" title="Send Reminder">
                                        <i class="fas fa-bell text-[10px]"></i>
                                    </a>
                                <?php endif; ?>
                                <a href="mailto:<?php echo $c['user_email']; ?>?subject=We fixed your cart!&body=Your snacks are still waiting for you... Come back and complete your order!" class="w-8 h-8 bg-[#19DC7E] text-black flex items-center justify-center rounded-lg hover:scale-105 transition-all" title="Send Email">
                                    <i class="fas fa-paper-plane text-[10px]"></i>
                                </a>
                                <a href="?delete=<?php echo $c['id']; ?>" onclick="return confirm('Delete this cart?')" class="w-8 h-8 bg-red-50 text-red-400 flex items-center justify-center rounded-lg hover:bg-red-500 hover:text-white transition-all">
                                    <i class="fas fa-trash-alt text-[10px]"></i>
                                </a>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Floating Bulk Actions -->
    <style>
        @media (max-width: 768px) {
            #bulkActionBar {
                bottom: 1.5rem !important;
                left: 1rem !important;
                right: 1rem !important;
                width: auto !important;
                transform: none !important;
                flex-direction: column !important;
                align-items: stretch !important;
                padding: 1.25rem !important;
                gap: 1rem !important;
                border-radius: 24px !important;
                background: rgba(0, 0, 0, 0.95);
            }
            #bulkActionBar > div:first-child {
                width: 100%;
                justify-content: space-between;
                border-bottom: 1px solid rgba(255,255,255,0.1);
                padding-bottom: 1rem;
            }
            #bulkActionBar .h-6.w-px { display: none !important; }
            #bulkActionBar > div:nth-child(3) {
                flex-direction: column;
                width: 100%;
                gap: 0.75rem;
            }
            #bulkActionBar button {
                width: 100%;
                justify-content: center;
            }
            #bulkActionBar > button:last-child {
                position: absolute;
                top: 1.25rem;
                right: 1.25rem;
                width: auto;
                margin: 0;
            }
        }
    </style>
    <div id="bulkActionBar" class="hidden fixed bottom-10 left-1/2 -translate-x-1/2 z-50 bg-black text-white px-8 py-5 rounded-[32px] shadow-2xl items-center gap-8 anim-up border border-white/10">
        <div class="flex items-center gap-3">
            <span id="selectedCount" class="w-8 h-8 bg-[#19DC7E] text-black rounded-xl flex items-center justify-center font-black text-xs">0</span>
            <span class="text-[10px] font-black uppercase tracking-widest text-white/60">Carts Selected</span>
        </div>
        <div class="h-6 w-px bg-white/10"></div>
        <div class="flex gap-4">
            <button type="submit" name="bulk_remind" class="bg-[#19DC7E] text-black px-5 py-2.5 rounded-xl text-[10px] font-black uppercase tracking-widest hover:scale-105 transition-all flex items-center justify-center">
                Send Reminders
            </button>
            <button type="submit" name="bulk_delete" onclick="return confirm('Delete selected?')" class="text-red-400 hover:text-red-500 transition-colors uppercase font-black text-[10px] tracking-widest px-2 flex items-center justify-center">
                Delete All
            </button>
        </div>
        <button type="button" onclick="unselectAll()" class="text-white/20 hover:text-white transition-colors ml-4">
            <i class="fas fa-times"></i>
        </button>
    </div>
</form>

<script>
const selectAll = document.getElementById('selectAll');
const checkboxes = document.querySelectorAll('.cart-checkbox');
const bulkBar = document.getElementById('bulkActionBar');
const countLabel = document.getElementById('selectedCount');

function updateBulkUI() {
    const selected = Array.from(checkboxes).filter(cb => cb.checked);
    countLabel.innerText = selected.length;
    
    if (selected.length > 0) {
        bulkBar.classList.remove('hidden');
        bulkBar.classList.add('flex');
    } else {
        bulkBar.classList.add('hidden');
        bulkBar.classList.remove('flex');
    }

    checkboxes.forEach(cb => {
        if (cb.checked) cb.closest('.cart-row').style.background = 'rgba(25, 220, 126, 0.02)';
        else cb.closest('.cart-row').style.background = '';
    });

    selectAll.checked = selected.length === checkboxes.length && checkboxes.length > 0;
}

if(selectAll) {
    selectAll.addEventListener('change', () => {
        checkboxes.forEach(cb => cb.checked = selectAll.checked);
        updateBulkUI();
    });
}

checkboxes.forEach(cb => {
    cb.addEventListener('change', updateBulkUI);
});

function unselectAll() {
    checkboxes.forEach(cb => cb.checked = false);
    if(selectAll) selectAll.checked = false;
    updateBulkUI();
}
</script>

<?php echo render_pagination($pagination['total_pages'], $pagination['current_page']); ?>
<?php include 'includes/footer.php'; ?>


