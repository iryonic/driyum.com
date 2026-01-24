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
            $success = " User account activated.";
        } elseif ($action == 'deactivate') {
            execute_query("UPDATE users SET is_active = 0 WHERE id = ?", [$id]);
            $success = " User account deactivated.";
        } elseif ($action == 'delete') {
            // Check if user has orders
            $order_check = fetch_one("SELECT COUNT(*) as count FROM orders WHERE user_id = ?", [$id]);
            if ($order_check['count'] > 0) {
                $error = "History preserved: This user has orders and cannot be purged. Deactivate them instead.";
            } else {
                execute_query("DELETE FROM users WHERE id = ?", [$id]);
                $success = "User deleted ";
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
$active_vibe = fetch_one("SELECT COUNT(*) as count FROM users WHERE is_active = 1")['count'];
?>

<div class="mb-12 flex flex-col xl:flex-row justify-between items-start xl:items-end gap-8 anim-up">
    <div class="flex-1">
        <h1 class="text-5xl font-black text-gray-900 fredoka mb-4 tracking-tight">ALL USERS .</h1>
        <div class="flex flex-wrap items-center gap-6">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-[#19DC7E] text-black rounded-2xl flex items-center justify-center font-bold shadow-lg shadow-green-500/20"><?php echo $total_users; ?></div>
                <span class="text-[10px] font-black uppercase tracking-widest text-gray-400">Total Users</span>
            </div>
            <div class="h-6 w-px bg-gray-200 hidden md:block"></div>
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-black text-[#19DC7E] rounded-2xl flex items-center justify-center font-bold shadow-lg shadow-black/20"><?php echo $total_admins; ?></div>
                <span class="text-[10px] font-black uppercase tracking-widest text-gray-400">Admins</span>
            </div>
            <div class="h-6 w-px bg-gray-200 hidden md:block"></div>
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-white border-2 border-dashed border-[#19DC7E] text-[#19DC7E] rounded-2xl flex items-center justify-center font-bold"><?php echo $active_vibe; ?></div>
                <span class="text-[10px] font-black uppercase tracking-widest text-gray-400">Active Highs</span>
            </div>
        </div>
    </div>
    
    <div class="flex flex-col sm:flex-row w-full xl:w-auto gap-4">
        <form class="flex flex-1 gap-2">
            <div class="relative flex-1">
                <i class="fas fa-search absolute left-6 top-1/2 -translate-y-1/2 text-gray-300"></i>
                <input type="text" name="search" value="<?php echo $search; ?>" placeholder="Summon by name or email..." class="bg-white border-3 border-transparent rounded-[24px] pl-14 pr-8 py-4 outline-none focus:border-[#19DC7E] shadow-xl transition-all font-bold text-sm min-w-[280px]">
            </div>
            <select name="filter" onchange="this.form.submit()" class="bg-white border-3 border-transparent rounded-[24px] px-6 py-4 outline-none focus:border-[#19DC7E] shadow-xl transition-all font-bold text-sm appearance-none cursor-pointer">
                <option value="all" <?php echo $filter=='all'?'selected':''; ?>>All Users</option>
                <option value="admins" <?php echo $filter=='admins'?'selected':''; ?>>Admins Only</option>
                <option value="customers" <?php echo $filter=='customers'?'selected':''; ?>>Snackers Only</option>
                <option value="inactive" <?php echo $filter=='inactive'?'selected':''; ?>>Inactive Users</option>
            </select>
            <button type="submit" class="w-14 h-14 bg-black text-[#19DC7E] rounded-full shadow-2xl hover:scale-110 active:scale-90 transition-all flex items-center justify-center border-none">
                <i class="fas fa-bolt"></i>
            </button>
        </form>

        <a href="create-admin.php" class="btn-chunky bg-[#19DC7E] text-black px-8 h-14 rounded-[24px] shadow-2xl hover:scale-105 transition flex items-center justify-center gap-3 font-black uppercase tracking-widest text-xs whitespace-nowrap">
            <i class="fas fa-plus"></i> New Admin
        </a>
    </div>
</div>

<?php if($success): ?>
    <div class="mb-10 p-6 bg-[#19DC7E] text-black rounded-[30px] shadow-xl shadow-green-500/20 font-black text-sm anim-up flex items-center gap-5">
        <div class="w-12 h-12 bg-black/10 rounded-2xl flex items-center justify-center text-xl"><i class="fas fa-magic"></i></div>
        <?php echo $success; ?>
    </div>
<?php endif; ?>

<?php if($error): ?>
    <div class="mb-10 p-6 bg-red-500 text-white rounded-[30px] shadow-xl shadow-red-500/20 font-black text-sm anim-up flex items-center gap-5">
        <div class="w-12 h-12 bg-white/20 rounded-2xl flex items-center justify-center text-xl"><i class="fas fa-ghost"></i></div>
        <?php echo $error; ?>
    </div>
<?php endif; ?>

<div class="bg-white rounded-[50px] shadow-2xl border border-gray-100 overflow-hidden anim-up">
    <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="text-gray-400 text-[10px] uppercase bg-gray-50/20 border-b border-gray-100 font-['Outfit']">
                    <th class="p-10 font-black tracking-[0.2em] opacity-50">Heart & Soul</th>
                    <th class="p-10 font-black tracking-[0.2em] opacity-50">Impact Score</th>
                    <th class="p-10 font-black tracking-[0.2em] opacity-50">Journey Start</th>
                    <th class="p-10 font-black tracking-[0.2em] opacity-50">Frequency</th>
                    <th class="p-10 font-black tracking-[0.2em] opacity-50 text-right">Moderation</th>
                </tr>
            </thead>
            <tbody class="text-sm font-['Outfit'] text-gray-600">
                <?php foreach ($users as $u): ?>
                <tr class="border-b border-gray-50 hover:bg-gray-50/80 transition-all duration-500 group">
                    <td class="p-10">
                        <div class="flex items-center gap-8">
                            <div class="relative">
                                <div class="w-20 h-20 rounded-[30px] bg-gray-100 flex items-center justify-center text-gray-400 font-black text-2xl uppercase shadow-inner border-2 border-white group-hover:bg-[#19DC7E] group-hover:text-black group-hover:rotate-6 transition-all duration-500 overflow-hidden">
                                    <?php echo substr($u['name'], 0, 1); ?>
                                </div>
                                <?php if($u['is_admin']): ?>
                                    <div class="absolute -top-3 -right-3 w-10 h-10 bg-black text-[#19DC7E] rounded-full flex items-center justify-center text-sm shadow-xl border-4 border-white">
                                        <i class="fas fa-crown"></i>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div>
                                <div class="font-black text-2xl text-gray-900 flex items-center gap-4 mb-2">
                                    <?php echo $u['name']; ?>
                                    <?php if($u['id'] == $_SESSION['user_id']): ?>
                                        <span class="bg-indigo-100 text-indigo-600 px-3 py-1 rounded-full text-[8px] uppercase tracking-widest font-black">Universe Self</span>
                                    <?php endif; ?>
                                </div>
                                <div class="flex flex-col gap-1">
                                    <div class="flex items-center gap-2 text-xs font-bold text-gray-400">
                                        <i class="fas fa-envelope-open opacity-30"></i>
                                        <?php echo $u['email']; ?>
                                    </div>
                                    <?php if(!empty($u['phone'])): ?>
                                        <div class="flex items-center gap-2 text-[10px] font-black text-[#19DC7E] uppercase tracking-widest">
                                            <i class="fas fa-phone opacity-30"></i>
                                            <?php echo $u['phone']; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </td>
                    <td class="p-10">
                        <div class="space-y-3">
                            <div class="flex items-end gap-2">
                                <span class="font-black text-2xl text-gray-900 leading-none"><?php echo $u['order_count']; ?></span>
                                <span class="text-[10px] font-black tracking-[0.15em] text-gray-400 uppercase pb-1">Snack Orders</span>
                            </div>
                            <div class="inline-flex items-center gap-2 px-4 py-2 bg-black text-[#19DC7E] rounded-2xl text-[10px] font-black uppercase tracking-[0.2em] shadow-lg shadow-black/10">
                                <i class="fas fa-coins text-[8px]"></i> LTV: ₹<?php echo number_format($u['total_spent'] ?? 0, 0); ?>
                            </div>
                        </div>
                    </td>
                    <td class="p-10">
                        <div class="font-black text-gray-800 text-xl mb-1"><?php echo date('M d, Y', strtotime($u['created_at'])); ?></div>
                        <div class="text-[10px] text-gray-300 uppercase font-black tracking-[0.2em] bg-gray-50 px-3 py-1 rounded-full inline-block">
                            <?php 
                                $days = round((time() - strtotime($u['created_at'])) / 86400);
                                echo $days == 0 ? "Born Today" : "$days Days in Orbit";
                            ?>
                        </div>
                    </td>
                    <td class="p-10">
                        <?php if($u['is_active']): ?>
                            <div class="inline-flex items-center gap-4 px-5 py-3 bg-[#19DC7E] text-black rounded-3xl shadow-xl shadow-green-500/20 group-hover:scale-110 transition-transform duration-500">
                                <div class="w-2.5 h-2.5 rounded-full bg-black animate-pulse"></div>
                                <span class="text-[10px] font-black uppercase tracking-[0.2em]">Active</span>
                            </div>
                        <?php else: ?>
                            <div class="inline-flex items-center gap-4 px-5 py-3 bg-gray-100 text-gray-400 rounded-3xl">
                                <div class="w-2.5 h-2.5 rounded-full bg-gray-300"></div>
                                <span class="text-[10px] font-black uppercase tracking-[0.2em]">Deactive</span>
                            </div>
                        <?php endif; ?>
                    </td>
                    <td class="p-10 text-right">
                        <div class="flex justify-end items-center gap-3 translate-x-10 opacity-0 group-hover:opacity-100 group-hover:translate-x-0 transition-all duration-700">
                            <?php if($u['is_active']): ?>
                                <a href="?id=<?php echo $u['id']; ?>&action=deactivate" title="Suspend" class="w-14 h-14 bg-red-50 text-red-500 hover:bg-red-500 hover:text-white rounded-[24px] flex items-center justify-center transition-all shadow-xl shadow-red-500/5 active:scale-90">
                                    <i class="fas fa-ban"></i>
                                </a>
                            <?php else: ?>
                                <a href="?id=<?php echo $u['id']; ?>&action=activate" title="Restore" class="w-14 h-14 bg-green-50 text-green-500 hover:bg-green-500 hover:text-white rounded-[24px] flex items-center justify-center transition-all shadow-xl shadow-green-500/5 active:scale-90">
                                    <i class="fas fa-undo"></i>
                                </a>
                            <?php endif; ?>

                            <a href="orders.php?user_id=<?php echo $u['id']; ?>" title="Order History" class="w-14 h-14 bg-indigo-50 text-indigo-500 hover:bg-indigo-500 hover:text-white rounded-[24px] flex items-center justify-center transition-all shadow-xl shadow-indigo-500/5 active:scale-90">
                                <i class="fas fa-fingerprint"></i>
                            </a>

                            <div class="h-10 w-px bg-gray-100 mx-2"></div>

                            <a href="?id=<?php echo $u['id']; ?>&action=delete" onclick="return confirm('Purge this identity? This cannot be undone.')" title="Purge" class="w-14 h-14 bg-black text-red-500 hover:bg-red-600 hover:text-white rounded-[24px] flex items-center justify-center transition-all shadow-2xl active:scale-90 border-none">
                                <i class="fas fa-trash-alt"></i>
                            </a>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if(empty($users)): ?>
                <tr>
                    <td colspan="5" class="p-32 text-center">
                        <div class="relative inline-block mb-10">
                            <div class="w-40 h-40 bg-gray-50 rounded-[60px] flex items-center justify-center shadow-inner border-2 border-white">
                                <i class="fas fa-user-ninja text-gray-200 text-6xl"></i>
                            </div>
                            <div class="absolute -bottom-4 -right-4 w-16 h-16 bg-white rounded-full flex items-center justify-center shadow-xl text-3xl">
                                🔍
                            </div>
                        </div>
                        <h3 class="text-4xl font-black text-gray-900 fredoka mb-4">The void stares back.</h3>
                        <p class="text-gray-400 font-medium font-['Outfit'] max-w-md mx-auto">No registered Users match your summons. Try expanding your search horizons.</p>
                        <a href="users.php" class="inline-block mt-10 text-[#19DC7E] font-black uppercase tracking-widest border-b-4 border-[#19DC7E]/30 hover:border-[#19DC7E] transition-all">Clear Universe Filters</a>
                    </td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php echo render_pagination($pagination['total_pages'], $pagination['current_page']); ?>
</body>
</html>
