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
    $ids = array_values($ids);
    
    if (empty($ids)) {
        $error = "Action not permitted on your own account.";
    } else {
        $ids_placeholder = implode(',', array_fill(0, count($ids), '?'));
        if ($action === 'activate') {
            execute_query("UPDATE users SET is_active = 1 WHERE id IN ($ids_placeholder)", $ids);
            $success = count($ids) . " user(s) activated.";
        } elseif ($action === 'deactivate') {
            execute_query("UPDATE users SET is_active = 0 WHERE id IN ($ids_placeholder)", $ids);
            $success = count($ids) . " user(s) deactivated.";
        } elseif ($action === 'delete') {
            $order_users = fetch_all("SELECT DISTINCT user_id FROM orders WHERE user_id IN ($ids_placeholder)", $ids);
            $order_user_ids = array_column($order_users, 'user_id');
            $deletable_ids = array_values(array_diff($ids, $order_user_ids));
            
            if (!empty($deletable_ids)) {
                $del_placeholders = implode(',', array_fill(0, count($deletable_ids), '?'));
                execute_query("DELETE FROM users WHERE id IN ($del_placeholders)", $deletable_ids);
                $success = count($deletable_ids) . " user(s) deleted.";
                if (count($order_user_ids) > 0) {
                    $error = count($order_user_ids) . " user(s) preserved due to existing order history.";
                }
            } else {
                $error = "No users could be deleted because all selected accounts have order history.";
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

$pagination = get_pagination_data($query, $params, 15);
$users = $pagination['records'];

$total_users = fetch_one("SELECT COUNT(*) as count FROM users")['count'];
$total_admins = fetch_one("SELECT COUNT(*) as count FROM users WHERE is_admin = 1")['count'];
?>

<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-2 border-b border-slate-200">
        <div>
            <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Users & Customers</h1>
            <p class="text-sm text-slate-500 mt-0.5">Manage customer accounts, administration permissions, and spend metrics.</p>
        </div>
        <div class="flex items-center gap-2">
            <a href="create-admin.php" class="btn-admin btn-admin-primary text-xs">
                <i class="fas fa-user-shield"></i> New Administrator
            </a>
        </div>
    </div>

    <?php if ($success): ?>
        <div class="p-3.5 bg-emerald-50 text-emerald-800 rounded-xl border border-emerald-200 text-xs font-medium flex items-center justify-between">
            <div class="flex items-center gap-2">
                <i class="fas fa-check-circle text-emerald-600"></i>
                <span><?php echo htmlspecialchars($success); ?></span>
            </div>
            <button type="button" onclick="this.parentElement.remove()" class="text-emerald-500 hover:text-emerald-700"><i class="fas fa-times text-xs"></i></button>
        </div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="p-3.5 bg-rose-50 text-rose-800 rounded-xl border border-rose-200 text-xs font-medium flex items-center justify-between">
            <div class="flex items-center gap-2">
                <i class="fas fa-exclamation-circle text-rose-600"></i>
                <span><?php echo htmlspecialchars($error); ?></span>
            </div>
            <button type="button" onclick="this.parentElement.remove()" class="text-rose-500 hover:text-rose-700"><i class="fas fa-times text-xs"></i></button>
        </div>
    <?php endif; ?>

    <!-- Search & Filter Card -->
    <div class="admin-card p-4">
        <form method="GET" action="users.php" class="flex flex-col md:flex-row items-stretch md:items-center justify-between gap-3">
            <div class="relative flex-1 max-w-md">
                <i class="fas fa-search absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search by name, email, or phone..." class="admin-input pl-9 text-xs">
            </div>

            <div class="flex items-center gap-2">
                <select name="filter" onchange="this.form.submit()" class="admin-select text-xs w-auto">
                    <option value="all" <?php echo $filter=='all'?'selected':''; ?>>All Accounts</option>
                    <option value="admins" <?php echo $filter=='admins'?'selected':''; ?>>Admins (<?php echo $total_admins; ?>)</option>
                    <option value="customers" <?php echo $filter=='customers'?'selected':''; ?>>Customers</option>
                    <option value="inactive" <?php echo $filter=='inactive'?'selected':''; ?>>Inactive</option>
                </select>
                <?php if ($search || $filter !== 'all'): ?>
                    <a href="users.php" class="btn-admin btn-admin-secondary text-xs" title="Reset Filters">
                        <i class="fas fa-undo"></i>
                    </a>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <!-- Table Form -->
    <form id="bulk-form" method="POST">
        <!-- Floating Bulk Action Dock (Light Themed & Responsive) -->
        <div id="bulk-bar" class="hidden admin-bulk-dock">
            <div class="flex items-center gap-2 pr-3 border-r border-slate-200">
                <span class="bulk-counter-badge"><span id="selected-count">0</span> Selected</span>
            </div>
            <div class="flex items-center gap-2">
                <select name="bulk_action" class="bg-white border border-slate-200 text-slate-800 rounded-lg px-2.5 py-1 text-xs font-semibold outline-none focus:ring-1 focus:ring-emerald-500 cursor-pointer">
                    <option value="">Choose Bulk Action...</option>
                    <option value="activate">Activate Accounts</option>
                    <option value="deactivate">Deactivate Accounts</option>
                    <option value="delete">Delete Forever</option>
                </select>
                <button type="submit" onclick="return confirm('Execute bulk action on selected users?')" class="bulk-btn bulk-btn-primary">
                    Apply
                </button>
            </div>
            <button type="button" onclick="clearSelection()" class="text-slate-400 hover:text-slate-700 p-1 text-xs ml-1" title="Clear Selection">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <!-- Users Table -->
        <div class="admin-card p-0 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th class="w-10 text-center">
                                <input type="checkbox" id="select-all" class="rounded border-slate-300 text-primary focus:ring-primary cursor-pointer">
                            </th>
                            <th>User Profile</th>
                            <th>Role</th>
                            <th class="text-right">Orders / Spend</th>
                            <th>Registered</th>
                            <th>Status</th>
                            <th class="text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($users)): ?>
                            <tr>
                                <td colspan="7" class="p-12 text-center text-slate-400">
                                    <div class="w-12 h-12 rounded-full bg-slate-100 flex items-center justify-center mx-auto mb-3 text-slate-400">
                                        <i class="fas fa-users-slash text-lg"></i>
                                    </div>
                                    <p class="text-sm font-semibold text-slate-700">No users found</p>
                                    <p class="text-xs text-slate-400 mt-0.5">Try refining your search terms or role filters.</p>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($users as $u): ?>
                                <tr class="hover:bg-slate-50/70 transition-colors user-row group">
                                    <td class="text-center">
                                        <?php if ($u['id'] != $_SESSION['user_id']): ?>
                                            <input type="checkbox" name="user_ids[]" value="<?php echo $u['id']; ?>" class="user-checkbox rounded border-slate-300 text-primary focus:ring-primary cursor-pointer">
                                        <?php else: ?>
                                            <span class="w-4 h-4 inline-block opacity-20"><i class="fas fa-lock text-[10px]"></i></span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="flex items-center gap-3">
                                            <div class="w-9 h-9 rounded-full bg-slate-100 border border-slate-200 text-slate-700 font-bold text-xs flex items-center justify-center uppercase">
                                                <?php echo substr($u['name'] ?: 'U', 0, 1); ?>
                                            </div>
                                            <div>
                                                <div class="font-semibold text-slate-900 text-xs flex items-center gap-1.5">
                                                    <span><?php echo htmlspecialchars($u['name']); ?></span>
                                                    <?php if ($u['id'] == $_SESSION['user_id']): ?>
                                                        <span class="px-1.5 py-0.2 rounded bg-indigo-50 text-indigo-700 border border-indigo-200 text-[10px] font-semibold">You</span>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="text-[11px] text-slate-400 font-mono mt-0.5">
                                                    <?php echo htmlspecialchars($u['email']); ?>
                                                    <?php if (!empty($u['phone'])): ?>
                                                        · <?php echo htmlspecialchars($u['phone']); ?>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <?php if ($u['is_admin']): ?>
                                            <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                                <i class="fas fa-shield-alt text-[9px]"></i> Admin
                                            </span>
                                        <?php else: ?>
                                            <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-slate-100 text-slate-600 border border-slate-200">
                                                Customer
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-right">
                                        <div class="text-xs font-semibold text-slate-900">
                                            ₹<?php echo number_format($u['total_spent'] ?? 0, 2); ?>
                                        </div>
                                        <span class="text-[11px] text-slate-400">
                                            <?php echo (int)$u['order_count']; ?> order(s)
                                        </span>
                                    </td>
                                    <td>
                                        <span class="text-xs text-slate-600">
                                            <?php echo date('M d, Y', strtotime($u['created_at'])); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($u['is_active']): ?>
                                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span> Active
                                            </span>
                                        <?php else: ?>
                                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-semibold bg-slate-100 text-slate-600 border border-slate-200">
                                                Inactive
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-right">
                                        <div class="flex items-center justify-end gap-1">
                                            <a href="orders.php?user_id=<?php echo $u['id']; ?>" title="Order History" class="p-1.5 text-slate-400 hover:text-slate-800 rounded hover:bg-slate-100 transition-colors">
                                                <i class="fas fa-receipt text-xs"></i>
                                            </a>
                                            <?php if ($u['id'] != $_SESSION['user_id']): ?>
                                                <a href="?id=<?php echo $u['id']; ?>&action=<?php echo $u['is_active'] ? 'deactivate' : 'activate'; ?>" title="<?php echo $u['is_active'] ? 'Deactivate' : 'Activate'; ?>" class="p-1.5 text-slate-400 hover:text-amber-600 rounded hover:bg-amber-50 transition-colors">
                                                    <i class="fas <?php echo $u['is_active'] ? 'fa-user-slash' : 'fa-user-check'; ?> text-xs"></i>
                                                </a>
                                                <a href="?id=<?php echo $u['id']; ?>&action=delete" onclick="return confirm('Permanently remove this user?')" title="Delete" class="p-1.5 text-slate-400 hover:text-rose-600 rounded hover:bg-rose-50 transition-colors">
                                                    <i class="fas fa-trash-alt text-xs"></i>
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Pagination -->
        <?php echo render_pagination($pagination['total_pages'], $pagination['current_page']); ?>
    </form>
</div>

<script>
const selectAll = document.getElementById('select-all');
const checkboxes = document.querySelectorAll('.user-checkbox');
const bulkBar = document.getElementById('bulk-bar');
const selectedCount = document.getElementById('selected-count');

function updateBulkBar() {
    const checked = document.querySelectorAll('.user-checkbox:checked');
    if (checked.length > 0) {
        bulkBar.classList.remove('hidden');
        bulkBar.classList.add('flex');
        selectedCount.textContent = checked.length;
    } else {
        bulkBar.classList.add('hidden');
        bulkBar.classList.remove('flex');
    }
}

if (selectAll) {
    selectAll.addEventListener('change', () => {
        checkboxes.forEach(cb => cb.checked = selectAll.checked);
        updateBulkBar();
    });
}

checkboxes.forEach(cb => {
    cb.addEventListener('change', updateBulkBar);
});

function clearSelection() {
    if (selectAll) selectAll.checked = false;
    checkboxes.forEach(cb => cb.checked = false);
    updateBulkBar();
}
</script>

<?php include 'includes/footer.php'; ?>
