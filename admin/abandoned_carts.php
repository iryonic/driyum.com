<?php
require_once '../config/database.php';
require_once '../includes/functions.php';

// Security Check
if (!is_admin()) {
    header("Location: ../login.php");
    exit;
}

// Handle Action
if (isset($_GET['mark_reminded'])) {
    $id = (int)$_GET['mark_reminded'];
    if (send_abandoned_cart_reminder($id)) {
        execute_query("UPDATE abandoned_carts SET is_reminded = 1 WHERE id = ?", [$id]);
        $_SESSION['success'] = "Reminder sent successfully!";
    } else {
        $_SESSION['error'] = "Failed to send reminder. Check email logs.";
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
        $processed_ids = [];
        foreach ($ids as $id) {
            $id = (int)$id;
            if (queue_abandoned_cart_reminder($id)) {
                $processed_ids[] = $id;
                $success_count++;
            }
        }
        if (!empty($processed_ids)) {
            $id_list = implode(',', $processed_ids);
            execute_query("UPDATE abandoned_carts SET is_reminded = 1 WHERE id IN ($id_list)");
            process_email_queue(5);
            $_SESSION['success'] = "Queued $success_count reminders. Sending started in background!";
        }
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
          LEFT JOIN users u ON ac.user_id = u.id 
          ORDER BY ac.last_updated DESC";

$pagination = get_pagination_data($query, [], 15);
$carts = $pagination['records'];

include 'includes/header.php';
?>

<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-2 border-b border-slate-200">
        <div>
            <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Abandoned Carts</h1>
            <p class="text-sm text-slate-500 mt-0.5">Track drop-offs, recover lost revenue, and dispatch automated or manual cart reminders.</p>
        </div>
        <div class="flex items-center gap-2">
            <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-amber-50 text-amber-700 text-xs font-semibold border border-amber-200">
                <i class="fas fa-shopping-cart text-[11px]"></i>
                <span><?php echo (int)$pagination['total_records']; ?> Unfinished Carts</span>
            </span>
        </div>
    </div>

    <form id="bulkActionForm" method="POST">
        <!-- Main Table Card -->
        <div class="admin-card p-0 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th class="w-10 text-center">
                                <input type="checkbox" id="selectAll" class="rounded border-slate-300 text-primary focus:ring-primary cursor-pointer">
                            </th>
                            <th>Customer</th>
                            <th class="text-right">Value</th>
                            <th>Items</th>
                            <th>Last Active</th>
                            <th>Recovery Status</th>
                            <th class="text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($carts)): ?>
                            <tr>
                                <td colspan="7" class="p-12 text-center text-slate-400">
                                    <div class="w-12 h-12 rounded-full bg-slate-100 flex items-center justify-center mx-auto mb-3 text-slate-400">
                                        <i class="fas fa-ghost text-lg"></i>
                                    </div>
                                    <p class="text-sm font-semibold text-slate-700">No abandoned carts</p>
                                    <p class="text-xs text-slate-400 mt-0.5">All customer carts have been converted or cleared.</p>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($carts as $c): 
                                $items = json_decode($c['cart_data'] ?? '', true);
                                if (!is_array($items)) $items = [];
                                $total_val = 0;
                                $item_count = 0;
                                $parts = [];
                                foreach($items as $pid => $qty) {
                                    $p = get_product_by_id($pid);
                                    if($p) {
                                        $total_val += $p['price'] * $qty;
                                        $item_count += $qty;
                                        $parts[] = $p['name'] . " (" . $qty . ")";
                                    }
                                }
                            ?>
                                <tr class="hover:bg-slate-50/70 transition-colors cart-row group">
                                    <td class="text-center">
                                        <input type="checkbox" name="cart_ids[]" value="<?php echo $c['id']; ?>" class="cart-checkbox rounded border-slate-300 text-primary focus:ring-primary cursor-pointer">
                                    </td>
                                    <td>
                                        <div class="text-xs font-semibold text-slate-900">
                                            <?php echo htmlspecialchars($c['user_name'] ?? 'Guest Customer'); ?>
                                        </div>
                                        <div class="text-[11px] text-slate-400 font-mono mt-0.5">
                                            <?php echo htmlspecialchars($c['user_email'] ?? 'No email registered'); ?>
                                        </div>
                                    </td>
                                    <td class="text-right">
                                        <div class="font-semibold text-slate-900 text-xs">₹<?php echo number_format($total_val, 2); ?></div>
                                        <span class="text-[10px] text-slate-400"><?php echo $item_count; ?> item(s)</span>
                                    </td>
                                    <td class="max-w-[240px]">
                                        <p class="text-xs text-slate-600 truncate" title="<?php echo htmlspecialchars(implode(', ', $parts)); ?>">
                                            <?php echo !empty($parts) ? htmlspecialchars(implode(', ', $parts)) : '<span class="text-slate-400 italic">Empty</span>'; ?>
                                        </p>
                                    </td>
                                    <td>
                                        <div class="text-xs font-medium text-slate-800"><?php echo get_time_ago($c['last_updated']); ?></div>
                                        <div class="text-[10px] text-slate-400"><?php echo date('M d, Y · h:i A', strtotime($c['last_updated'])); ?></div>
                                    </td>
                                    <td>
                                        <?php if ($c['is_reminded']): ?>
                                            <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-sky-50 text-sky-700 border border-sky-200">
                                                <i class="fas fa-check text-[9px]"></i> Reminded
                                            </span>
                                        <?php else: ?>
                                            <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-amber-50 text-amber-700 border border-amber-200">
                                                <i class="fas fa-clock text-[9px]"></i> Waiting
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-right">
                                        <div class="flex items-center justify-end gap-1">
                                            <?php if (!$c['is_reminded']): ?>
                                                <a href="?mark_reminded=<?php echo $c['id']; ?>" class="p-1.5 text-slate-400 hover:text-emerald-600 rounded hover:bg-emerald-50 transition-colors" title="Send Email Reminder">
                                                    <i class="fas fa-bell text-xs"></i>
                                                </a>
                                            <?php endif; ?>
                                            <?php if (!empty($c['user_email'])): ?>
                                                <a href="mailto:<?php echo htmlspecialchars($c['user_email']); ?>?subject=<?php echo rawurlencode('Your DRIYUM cart is waiting for you!'); ?>&body=<?php echo rawurlencode('Hello! We noticed you left some delicious natural treats in your DRIYUM cart. Complete your order today: https://driyum.com/cart'); ?>" class="p-1.5 text-slate-400 hover:text-sky-600 rounded hover:bg-sky-50 transition-colors" title="Email Direct">
                                                    <i class="fas fa-paper-plane text-xs"></i>
                                                </a>
                                            <?php endif; ?>
                                            <a href="?delete=<?php echo $c['id']; ?>" onclick="return confirm('Remove this abandoned cart?')" class="p-1.5 text-slate-400 hover:text-rose-600 rounded hover:bg-rose-50 transition-colors" title="Delete Cart">
                                                <i class="fas fa-trash-alt text-xs"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Floating Bulk Action Dock -->
        <div id="bulkActionBar" class="hidden fixed bottom-6 left-1/2 -translate-x-1/2 z-50 bg-slate-900 text-white px-5 py-3 rounded-2xl shadow-2xl border border-slate-700 items-center gap-4 transition-all">
            <div class="flex items-center gap-2.5 pr-4 border-r border-slate-700">
                <span id="selectedCount" class="w-6 h-6 rounded-full bg-emerald-500 text-slate-950 font-bold text-xs flex items-center justify-center">0</span>
                <span class="text-xs font-medium text-slate-200">Selected</span>
            </div>
            <div class="flex items-center gap-2">
                <button type="submit" name="bulk_remind" onclick="this.innerHTML='<i class=\'fas fa-spinner fa-spin mr-1\'></i> Queueing...'; this.classList.add('opacity-70', 'pointer-events-none');" class="btn-admin btn-admin-primary text-xs py-1.5 px-3">
                    <i class="fas fa-envelope mr-1"></i> Send Reminders
                </button>
                <button type="submit" name="bulk_delete" onclick="return confirm('Permanently delete selected carts?')" class="btn-admin btn-admin-danger text-xs py-1.5 px-3">
                    <i class="fas fa-trash-alt mr-1"></i> Delete Selected
                </button>
            </div>
            <button type="button" onclick="unselectAll()" class="text-slate-400 hover:text-white p-1 text-xs ml-2" title="Clear Selection">
                <i class="fas fa-times"></i>
            </button>
        </div>
    </form>

    <!-- Pagination -->
    <?php echo render_pagination($pagination['total_pages'], $pagination['current_page']); ?>
</div>

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
        const row = cb.closest('.cart-row');
        if (cb.checked) row.classList.add('bg-emerald-50/40');
        else row.classList.remove('bg-emerald-50/40');
    });

    if (selectAll) {
        selectAll.checked = selected.length === checkboxes.length && checkboxes.length > 0;
    }
}

if (selectAll) {
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

<?php include 'includes/footer.php'; ?>
