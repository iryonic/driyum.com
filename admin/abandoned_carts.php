<?php
require_once '../config/database.php';
require_once '../includes/functions.php';

// Handle Action
if (isset($_GET['mark_reminded'])) {
    $id = (int)$_GET['mark_reminded'];
    if (send_abandoned_cart_reminder($id)) {
        execute_query("UPDATE abandoned_carts SET is_reminded = 1 WHERE id = ?", [$id]);
        $_SESSION['success'] = "Recovery email sent and status updated!";
    } else {
        $_SESSION['error'] = "Failed to send recovery email. Please check your SMTP settings.";
    }
    header('Location: abandoned_carts.php');
    exit;
}

if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    execute_query("DELETE FROM abandoned_carts WHERE id = ?", [$id]);
    $_SESSION['success'] = "Abandoned cart removed.";
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

<div class="mb-12 flex flex-col md:flex-row justify-between items-start md:items-end gap-6 anim-up">
    <div>
        <h1 class="text-4xl font-black text-gray-900 fredoka mb-2">Abandoned Carts.</h1>
        <div class="flex items-center gap-4">
            <p class="text-gray-500 font-medium font-['Outfit'] italic">Recover lost sales and remind snackers.</p>
            <div class="h-4 w-px bg-gray-200"></div>
            <span class="text-[10px] font-black uppercase tracking-widest text-orange-500 bg-orange-50 px-3 py-1 rounded-full"><?php echo $pagination['total_records']; ?> Ghost Carts</span>
        </div>
    </div>
</div>

<div class="bg-white rounded-[50px] shadow-2xl border border-gray-100 overflow-hidden anim-up">
    <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="text-gray-400 text-[10px] uppercase bg-gray-50/30 border-b border-gray-100 font-['Outfit']">
                    <th class="p-10 font-black tracking-[0.2em] opacity-40">User</th>
                    <th class="p-10 font-black tracking-[0.2em] opacity-40">Cart Value</th>
                    <th class="p-10 font-black tracking-[0.2em] opacity-40">Items</th>
                    <th class="p-10 font-black tracking-[0.2em] opacity-40">Last Active</th>
                    <th class="p-10 font-black tracking-[0.2em] opacity-40">Status</th>
                    <th class="p-10 font-black tracking-[0.2em] opacity-40 text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="text-sm font-['Outfit'] text-gray-600">
                <?php if (empty($carts)): ?>
                <tr>
                    <td colspan="6" class="p-24 text-center">
                        <div class="w-24 h-24 bg-gray-50 rounded-[35px] flex items-center justify-center mx-auto mb-8 shadow-inner border border-gray-100">
                            <i class="fas fa-ghost text-gray-200 text-4xl"></i>
                        </div>
                        <h3 class="text-2xl font-black text-gray-900 fredoka mb-2">No Ghosts in the Machine.</h3>
                        <p class="text-gray-400 font-medium font-['Outfit'] text-sm">Every cart has found its home... for now.</p>
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
                <tr class="border-b border-gray-50 hover:bg-gray-50/50 transition-all duration-300 group">
                    <td class="p-10">
                        <div class="flex items-center gap-4">
                            <div class="w-12 h-12 bg-gray-100 rounded-2xl flex items-center justify-center text-gray-400 font-black">
                                <?php echo strtoupper(substr($c['user_name'], 0, 1)); ?>
                            </div>
                            <div>
                                <div class="font-black text-lg text-gray-900 leading-tight"><?php echo $c['user_name']; ?></div>
                                <div class="text-[10px] font-black text-[#19DC7E] uppercase tracking-widest"><?php echo $c['user_email']; ?></div>
                            </div>
                        </div>
                    </td>
                    <td class="p-10">
                        <div class="font-black text-xl text-gray-900">₹<?php echo number_format($total_val); ?></div>
                        <div class="text-[10px] font-black text-gray-400 uppercase tracking-widest mt-1"><?php echo $item_count; ?> Items Total</div>
                    </td>
                    <td class="p-10">
                        <div class="max-w-xs overflow-hidden">
                            <p class="text-[11px] font-bold text-gray-500 leading-relaxed truncate" title="<?php echo implode(', ', $parts); ?>">
                                <?php echo implode(', ', $parts); ?>
                            </p>
                        </div>
                    </td>
                    <td class="p-10">
                        <div class="text-gray-900 font-bold"><?php echo get_time_ago($c['last_updated']); ?></div>
                        <div class="text-[10px] font-black text-gray-300 uppercase tracking-widest mt-1"><?php echo date('M d, H:i', strtotime($c['last_updated'])); ?></div>
                    </td>
                    <td class="p-10">
                        <?php if($c['is_reminded']): ?>
                            <span class="text-[9px] font-black bg-blue-50 text-blue-500 px-3 py-1.5 rounded-full uppercase tracking-widest">Reminded</span>
                        <?php else: ?>
                            <span class="text-[9px] font-black bg-orange-50 text-orange-500 px-3 py-1.5 rounded-full uppercase tracking-widest">Awaiting Follow</span>
                        <?php endif; ?>
                    </td>
                    <td class="p-10 text-right">
                        <div class="flex justify-end gap-3 translate-x-4 opacity-0 group-hover:opacity-100 group-hover:translate-x-0 transition-all duration-500">
                            <?php if(!$c['is_reminded']): ?>
                                <a href="?mark_reminded=<?php echo $c['id']; ?>" class="w-14 h-14 bg-black text-[#19DC7E] hover:scale-110 flex items-center justify-center rounded-[20px] transition-all shadow-lg active:scale-95" title="Mark as Reminded">
                                    <i class="fas fa-bell text-lg"></i>
                                </a>
                            <?php endif; ?>
                            <a href="mailto:<?php echo $c['user_email']; ?>?subject=We fixed your cart!&body=Your snacks are still waiting for you... Come back and complete your order!" class="w-14 h-14 bg-[#19DC7E] text-black hover:scale-110 flex items-center justify-center rounded-[20px] transition-all shadow-md active:scale-95" title="Manual Email Recovery">
                                <i class="fas fa-paper-plane text-lg"></i>
                            </a>
                            <a href="?delete=<?php echo $c['id']; ?>" onclick="return confirm('Erase this ghost cart?')" class="w-14 h-14 bg-red-50 text-red-500 hover:bg-red-600 hover:text-white flex items-center justify-center rounded-[20px] transition-all shadow-sm active:scale-95" title="Delete Permanentely">
                                <i class="fas fa-trash-alt text-lg"></i>
                            </a>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php echo render_pagination($pagination['total_pages'], $pagination['current_page']); ?>

<?php include 'includes/footer.php'; ?>
