<?php include 'includes/header.php'; ?>
<?php
$conn = get_db_connection();
$success = "";
$error = "";

// Handle Actions
if (isset($_GET['action']) && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $action = $_GET['action'];
    
    // Prevent self-modifying or deleting if currently logged in
    if ($id == $_SESSION['user_id'] && ($action == 'deactivate' || $action == 'delete')) {
        $error = "You cannot deactivate or delete your own account.";
    } else {
        if ($action == 'activate') {
            execute_query("UPDATE users SET is_active = 1 WHERE id = ?", [$id]);
            $success = "User account activated.";
        } elseif ($action == 'deactivate') {
            execute_query("UPDATE users SET is_active = 0 WHERE id = ?", [$id]);
            $success = "User account deactivated.";
        } elseif ($action == 'delete') {
            // Check if user has orders
            $order_check = fetch_one("SELECT COUNT(*) as count FROM orders WHERE user_id = ?", [$id]);
            if ($order_check['count'] > 0) {
                $error = "History preserved: This user has orders and cannot be purged. Deactivate them instead.";
            } else {
                execute_query("DELETE FROM users WHERE id = ?", [$id]);
                $success = "User deleted successfully.";
            }
        }
    }
}

// Handle Bulk Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['bulk_action']) && !empty($_POST['user_ids'])) {
    $action = $_POST['bulk_action'];
    $ids = array_map('intval', $_POST['user_ids']);
    
    // Remove current user from bulk actions to prevent self-deletion
    $ids = array_filter($ids, function($id) { return $id != $_SESSION['user_id']; });
    $ids = array_values($ids); // Reset keys and ensure it's a simple list
    
    if (empty($ids)) {
        $error = "Action not permitted on your own account.";
    } else {
        $ids_placeholder = implode(',', array_fill(0, count($ids), '?'));
        if ($action === 'activate') {
            execute_query("UPDATE users SET is_active = 1 WHERE id IN ($ids_placeholder)", $ids);
            $success = count($ids) . " Users activated.";
        } elseif ($action === 'deactivate') {
            execute_query("UPDATE users SET is_active = 0 WHERE id IN ($ids_placeholder)", $ids);
            $success = count($ids) . " Users deactivated.";
        } elseif ($action === 'delete') {
            // Check for orders before bulk delete
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            $order_users = fetch_all("SELECT DISTINCT user_id FROM orders WHERE user_id IN ($placeholders)", $ids);
            $order_user_ids = array_column($order_users, 'user_id');
            
            $deletable_ids = array_values(array_diff($ids, $order_user_ids));
            
            if (!empty($deletable_ids)) {
                $del_placeholders = implode(',', array_fill(0, count($deletable_ids), '?'));
                execute_query("DELETE FROM users WHERE id IN ($del_placeholders)", $deletable_ids);
                $success = count($deletable_ids) . " Users deleted.";
                if (count($order_user_ids) > 0) {
                    $error = count($order_user_ids) . " Users kept due to order history.";
                }
            } else {
                $error = "No users could be deleted (they all have order history).";
            }
        }
    }
}

// Search & Filter Logic
$search = sanitize_input($_GET['search'] ?? '');
$filter = $_GET['filter'] ?? 'all';

$where_clauses = [];
$params = [];

if ($search) {
    $where_clauses[] = "(name LIKE ? OR email LIKE ? OR phone LIKE ?)";
    $term = "%$search%";
    $params[] = $term; $params[] = $term; $params[] = $term;
}

if ($filter === 'admins') {
    $where_clauses[] = "is_admin = 1";
} elseif ($filter === 'customers') {
    $where_clauses[] = "is_admin = 0";
} elseif ($filter === 'inactive') {
    $where_clauses[] = "is_active = 0";
}

$where_sql = !empty($where_clauses) ? "WHERE " . implode(" AND ", $where_clauses) : "";

$query = "SELECT u.*, 
          (SELECT COUNT(*) FROM orders WHERE user_id = u.id) as order_count,
          (SELECT SUM(total) FROM orders WHERE user_id = u.id AND order_status != 'cancelled') as total_spent
          FROM users u 
          $where_sql 
          ORDER BY u.created_at DESC";

$pagination = get_pagination_data($query, $params, 10);
$users = $pagination['records'];

// Stats for Header
$total_users = fetch_one("SELECT COUNT(*) as count FROM users")['count'];
$total_admins = fetch_one("SELECT COUNT(*) as count FROM users WHERE is_admin = 1")['count'];
?>

<div class="mb-8 flex flex-col xl:flex-row justify-between items-start xl:items-center gap-6 anim-up">
    <div>
        <h1 class="text-3xl font-black text-gray-900 crimson-pro tracking-tight">User Base</h1>
        <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mt-1">Managing <span class="text-black"><?php echo $total_users; ?></span> profiles in the system</p>
    </div>
    
    <div class="flex flex-col sm:flex-row w-full xl:w-auto gap-3 items-stretch sm:items-center">
        <form class="flex flex-col sm:flex-row flex-1 gap-2 items-stretch sm:items-center">
            <div class="relative group flex-1">
                <i class="fas fa-search absolute left-4 top-1/2 -translate-y-1/2 text-gray-300 group-focus-within:text-black transition-colors text-[10px]"></i>
                <input type="text" name="search" value="<?php echo $search; ?>" placeholder="Name or email..." class="w-full bg-white border border-gray-100 rounded-2xl pl-10 pr-4 py-2 text-xs font-bold outline-none focus:border-black shadow-sm transition-all min-w-0 sm:min-w-[200px]">
            </div>
            <div class="relative">
                <select name="filter" onchange="this.form.submit()" class="w-full bg-white border border-gray-100 rounded-2xl pl-4 pr-10 py-2 text-xs font-bold outline-none focus:border-black shadow-sm cursor-pointer appearance-none transition-all">
                    <option value="all" <?php echo $filter=='all'?'selected':''; ?>>All Roles</option>
                    <option value="admins" <?php echo $filter=='admins'?'selected':''; ?>>Admins Only</option>
                    <option value="customers" <?php echo $filter=='customers'?'selected':''; ?>>Customers Only</option>
                    <option value="inactive" <?php echo $filter=='inactive'?'selected':''; ?>>Inactive</option>
                </select>
                <i class="fas fa-chevron-down absolute right-4 top-1/2 -translate-y-1/2 text-gray-300 pointer-events-none text-[8px]"></i>
            </div>
        </form>

        <a href="create-admin.php" class="bg-black text-[#19DC7E] px-5 py-2.5 rounded-2xl text-[10px] font-black uppercase tracking-widest hover:scale-105 active:scale-95 transition-all flex items-center justify-center gap-2 shadow-lg shadow-black/5 whitespace-nowrap">
            <i class="fas fa-plus"></i> New Admin
        </a>
    </div>
</div>

<?php if($success): ?>
    <div class="mb-6 p-4 bg-green-50 text-green-700 rounded-2xl border border-green-100 font-bold text-xs anim-up flex items-center gap-3">
        <i class="fas fa-check-circle"></i>
        <?php echo $success; ?>
    </div>
<?php endif; ?>

<?php if($error): ?>
    <div class="mb-6 p-4 bg-red-50 text-red-700 rounded-2xl border border-red-100 font-bold text-xs anim-up flex items-center gap-3">
        <i class="fas fa-exclamation-circle"></i>
        <?php echo $error; ?>
    </div>
<?php endif; ?>

<form id="bulk-form" method="POST">
    <!-- Bulk Action Bar -->
    <style>
        @media (max-width: 768px) {
            #bulk-bar {
                bottom: 1.5rem;
                left: 1rem;
                right: 1rem;
                width: auto;
                transform: none !important;
            }
            #bulk-bar > div {
                flex-direction: column;
                align-items: stretch;
                gap: 1rem;
                padding: 1.25rem;
                border-radius: 24px;
            }
            #bulk-bar .h-6.w-px {
                display: none;
            }
            #bulk-bar > div > div:first-child {
                width: 100%;
                justify-content: space-between;
            }
            #bulk-bar > div > div:nth-child(3) {
                width: 100%;
                gap: 0.5rem;
            }
            #bulk-bar select {
                flex: 1;
            }
        }
    </style>
    <div id="bulk-bar" class="hidden fixed bottom-8  z-50 anim-up-static">
        <div class="bg-black text-white px-6 py-3 rounded-[30px] shadow-2xl flex items-center gap-6 border border-white/10 backdrop-blur-xl">
            <div class="flex items-center gap-3">
                <span id="selected-count" class="w-8 h-8 bg-[#19DC7E] text-black rounded-xl flex items-center justify-center font-black text-xs">0</span>
                <span class="text-[9px] font-black uppercase tracking-widest opacity-60">Selected</span>
            </div>
            <div class="h-6 w-px bg-white/10"></div>
            <div class="flex items-center gap-3">
                <select name="bulk_action" class="bg-white/5 border border-white/10 rounded-xl px-4 py-2 text-[10px] font-black outline-none focus:border-[#19DC7E] transition-all cursor-pointer">
                    <option value="" class="bg-black">Choose Action</option>
                    <option value="activate" class="bg-black">Activate</option>
                    <option value="deactivate" class="bg-black">Deactivate</option>
                    <option value="delete" class="bg-black text-red-400">Delete</option>
                </select>
                <button type="submit" onclick="return confirm('Execute bulk action?')" class="bg-[#19DC7E] text-black px-5 py-2 rounded-xl font-black text-[9px] uppercase tracking-widest hover:brightness-110 active:scale-95 transition-all">Apply</button>
            </div>
            <button type="button" onclick="clearSelection()" class="text-[9px] font-black uppercase tracking-widest opacity-40 hover:opacity-100 transition-opacity">Cancel</button>
        </div>
    </div>

    <div class="bg-white rounded-3xl shadow-sm border border-gray-100 overflow-hidden anim-up">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="text-gray-400 text-[8px] uppercase bg-gray-50/50 border-b border-gray-100 font-black tracking-widest">
                        <th class="p-5 w-16 text-center">
                            <input type="checkbox" id="select-all" class="w-4 h-4 rounded border-gray-200 text-black focus:ring-black cursor-pointer">
                        </th>
                        <th class="p-5 pl-0">User Identity</th>
                        <th class="p-5 hidden md:table-cell">Metrics</th>
                        <th class="p-5 hidden sm:table-cell">Joined</th>
                        <th class="p-5">Status</th>
                        <th class="p-5 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="text-xs text-gray-600">
                    <?php foreach ($users as $u): ?>
                    <tr class="border-b border-gray-50 hover:bg-gray-50/50 transition-all group user-row">
                        <td class="p-4 text-center">
                            <input type="checkbox" name="user_ids[]" value="<?php echo $u['id']; ?>" class="user-checkbox w-4 h-4 rounded border-gray-200 text-[#19DC7E] focus:ring-[#19DC7E] cursor-pointer">
                        </td>
                        <td class="p-4 pl-0">
                            <div class="flex items-center gap-4">
                                <div class="w-10 h-10 rounded-xl bg-gray-50 flex items-center justify-center text-gray-900 font-black text-xs border border-gray-100 group-hover:bg-black group-hover:text-[#19DC7E] transition-colors uppercase">
                                    <?php echo substr($u['name'], 0, 1); ?>
                                </div>
                                <div>
                                    <div class="font-bold text-gray-900 flex items-center gap-2">
                                        <?php echo $u['name']; ?>
                                        <?php if($u['id'] == $_SESSION['user_id']): ?>
                                            <span class="bg-indigo-50 text-indigo-500 px-1.5 py-0.5 rounded text-[7px] font-black uppercase tracking-tighter">You</span>
                                        <?php endif; ?>
                                        <?php if($u['is_admin']): ?>
                                            <span class="bg-black text-[#19DC7E] px-1.5 py-0.5 rounded text-[7px] font-black uppercase tracking-tighter">Admin</span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="text-[10px] text-gray-400 font-medium"><?php echo $u['email']; ?></div>
                                </div>
                            </div>
                        </td>
                        <td class="p-4 hidden md:table-cell">
                            <div class="font-bold text-gray-900"><?php echo $u['order_count']; ?> <span class="text-[9px] text-gray-400 font-medium ml-1">Orders</span></div>
                            <div class="text-[9px] font-bold text-[#19DC7E]">₹<?php echo number_format($u['total_spent'] ?? 0, 0); ?> <span class="text-[8px] text-gray-400 font-medium">Spent</span></div>
                        </td>
                        <td class="p-4 text-[10px] font-bold text-gray-400 hidden sm:table-cell">
                            <?php echo date('M d, Y', strtotime($u['created_at'])); ?>
                        </td>
                        <td class="p-4">
                            <?php if($u['is_active']): ?>
                                <span class="px-2 py-1 bg-green-50 text-green-600 rounded-lg text-[8px] font-black uppercase tracking-widest border border-green-100">Active</span>
                            <?php else: ?>
                                <span class="px-2 py-1 bg-gray-50 text-gray-400 rounded-lg text-[8px] font-black uppercase tracking-widest border border-gray-100">Inactive</span>
                            <?php endif; ?>
                        </td>
                        <td class="p-4 text-right">
                            <div class="flex justify-end gap-2 opacity-0 group-hover:opacity-100 transition-all">
                                <a href="orders.php?user_id=<?php echo $u['id']; ?>" title="History" class="w-8 h-8 bg-gray-50 text-gray-400 hover:bg-black hover:text-[#19DC7E] rounded-lg flex items-center justify-center transition-all"><i class="fas fa-receipt text-[10px]"></i></a>
                                <?php if($u['id'] != $_SESSION['user_id']): ?>
                                    <a href="?id=<?php echo $u['id']; ?>&action=<?php echo $u['is_active'] ? 'deactivate' : 'activate'; ?>" title="Toggle Status" class="w-8 h-8 bg-gray-50 text-gray-400 hover:bg-black hover:text-[#19DC7E] rounded-lg flex items-center justify-center transition-all">
                                        <i class="fas <?php echo $u['is_active'] ? 'fa-user-slash' : 'fa-user-check'; ?> text-[10px]"></i>
                                    </a>
                                    <a href="?id=<?php echo $u['id']; ?>&action=delete" onclick="return confirm('Delete user?')" title="Delete" class="w-8 h-8 bg-red-50 text-red-400 hover:bg-red-500 hover:text-white rounded-lg flex items-center justify-center transition-all">
                                        <i class="fas fa-trash-alt text-[10px]"></i>
                                    </a>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php echo render_pagination($pagination['total_pages'], $pagination['current_page']); ?>
</form>

<script>
const selectAll = document.getElementById('select-all');
const checkboxes = document.querySelectorAll('.user-checkbox');
const bulkBar = document.getElementById('bulk-bar');
const selectedCount = document.getElementById('selected-count');

function updateBulkBar() {
    const checked = document.querySelectorAll('.user-checkbox:checked');
    if (checked.length > 0) {
        bulkBar.classList.remove('hidden');
        selectedCount.textContent = checked.length;
    } else {
        bulkBar.classList.add('hidden');
    }
}

if(selectAll) {
    selectAll.addEventListener('change', () => {
        checkboxes.forEach(cb => cb.checked = selectAll.checked);
        updateBulkBar();
    });
}

checkboxes.forEach(cb => {
    cb.addEventListener('change', updateBulkBar);
});

function clearSelection() {
    selectAll.checked = false;
    checkboxes.forEach(cb => cb.checked = false);
    updateBulkBar();
}
</script>

<?php include 'includes/footer.php'; ?>


