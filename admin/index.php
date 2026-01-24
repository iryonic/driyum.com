<?php include 'includes/header.php'; ?>

<?php
// Stats
$orders_count = fetch_one("SELECT COUNT(*) as c FROM orders")['c'];
$products_count = fetch_one("SELECT COUNT(*) as c FROM products WHERE is_active=1")['c'];
$revenue = fetch_one("SELECT SUM(total) as t FROM orders WHERE order_status != 'cancelled'")['t'] ?? 0;
$pending_orders = fetch_one("SELECT COUNT(*) as c FROM orders WHERE order_status = 'pending'")['c'];
$subscribers_count = fetch_one("SELECT COUNT(*) as c FROM newsletter_subscribers WHERE is_active=1")['c'];

// Recent Orders
$recent_orders = fetch_all("SELECT * FROM orders ORDER BY created_at DESC LIMIT 5");

// Activity Pulse Data
$low_stock = fetch_all("SELECT name, stock FROM products WHERE stock <= 5 AND is_active = 1 LIMIT 3");
$recent_reviews = fetch_all("SELECT r.*, u.name as user_name, p.name as prod_name FROM reviews r JOIN users u ON r.user_id = u.id JOIN products p ON r.product_id = p.id ORDER BY r.created_at DESC LIMIT 3");
?>

<div class="mb-8 flex justify-between items-center">
    <div>
        <h1 class="text-3xl font-['Fredoka'] font-bold text-gray-900">Dashboard</h1>
        <p class="text-gray-500 text-sm">Welcome back, Admin!</p>
    </div>
    <div class="text-right">
        <span class="text-xs font-bold text-gray-400 uppercase">Current Time</span>
        <div id="admin-clock" class="font-mono text-lg font-bold text-gray-800"><?php echo date('H:i:s'); ?></div>
    </div>
</div>

<script>
function updateClock() {
    const clock = document.getElementById('admin-clock');
    if (!clock) return;
    const now = new Date();
    const h = String(now.getHours()).padStart(2, '0');
    const m = String(now.getMinutes()).padStart(2, '0');
    const s = String(now.getSeconds()).padStart(2, '0');
    clock.textContent = `${h}:${m}:${s}`;
}
setInterval(updateClock, 1000);
updateClock();
</script>

<!-- STATS GRID -->
<div class="grid grid-cols-1 md:grid-cols-3 xl:grid-cols-5 gap-6 mb-12">
    <!-- Revenue -->
    <div class="bg-white p-8 rounded-[35px] shadow-sm border border-gray-100 group hover:scale-[1.02] transition-all duration-300">
        <div class="w-16 h-16 rounded-[22px] bg-green-500/10 flex items-center justify-center text-[#19DC7E] text-3xl mb-4 group-hover:rotate-12 transition-transform shadow-inner">
            <i class="fas fa-rupee-sign"></i>
        </div>
        <div>
            <div class="text-[10px] text-gray-400 font-black uppercase tracking-widest mb-1">Total Revenue</div>
            <div class="text-3xl font-black text-gray-900 fredoka">₹<?php echo number_format($revenue); ?></div>
        </div>
    </div>

    <!-- Orders -->
    <div class="bg-white p-8 rounded-[35px] shadow-sm border border-gray-100 group hover:scale-[1.02] transition-all duration-300">
        <div class="w-16 h-16 rounded-[22px] bg-blue-500/10 flex items-center justify-center text-blue-500 text-3xl mb-4 group-hover:rotate-12 transition-transform shadow-inner">
            <i class="fas fa-shopping-bag"></i>
        </div>
        <div>
            <div class="text-[10px] text-gray-400 font-black uppercase tracking-widest mb-1">Lifetime Orders</div>
            <div class="text-3xl font-black text-gray-900 fredoka"><?php echo $orders_count; ?></div>
        </div>
    </div>

    <!-- Pending -->
    <div class="bg-white p-8 rounded-[35px] shadow-sm border border-gray-100 group hover:scale-[1.02] transition-all duration-300 relative overflow-hidden">
        <div class="w-16 h-16 rounded-[22px] bg-yellow-500/10 flex items-center justify-center text-yellow-500 text-3xl mb-4 group-hover:rotate-12 transition-transform shadow-inner">
            <i class="fas fa-clock"></i>
        </div>
        <div>
            <div class="text-[10px] text-gray-400 font-black uppercase tracking-widest mb-1">Processing Pending Orders</div>
            <div class="text-3xl font-black text-gray-900 fredoka"><?php echo $pending_orders; ?></div>
        </div>
        <?php if($pending_orders > 0): ?>
            <div class="absolute right-0 top-0 h-full w-2 bg-yellow-400 animate-pulse"></div>
        <?php endif; ?>
    </div>

    <!-- Products -->
    <div class="bg-white p-8 rounded-[35px] shadow-sm border border-gray-100 group hover:scale-[1.02] transition-all duration-300">
        <div class="w-16 h-16 rounded-[22px] bg-purple-500/10 flex items-center justify-center text-purple-500 text-3xl mb-4 group-hover:rotate-12 transition-transform shadow-inner">
            <i class="fas fa-boxes"></i>
        </div>
        <div>
            <div class="text-[10px] text-gray-400 font-black uppercase tracking-widest mb-1">Active Products</div>
            <div class="text-3xl font-black text-gray-900 fredoka"><?php echo $products_count; ?></div>
        </div>
    </div>

    <!-- Subscribers -->
    <div class="bg-white p-8 rounded-[35px] shadow-sm border border-gray-100 group hover:scale-[1.02] transition-all duration-300">
        <div class="w-16 h-16 rounded-[22px] bg-pink-500/10 flex items-center justify-center text-pink-500 text-3xl mb-4 group-hover:rotate-12 transition-transform shadow-inner">
            <i class="fas fa-envelope-open-text"></i>
        </div>
        <div>
            <div class="text-[10px] text-gray-400 font-black uppercase tracking-widest mb-1">Subscribers</div>
            <div class="text-3xl font-black text-gray-900 fredoka"><?php echo $subscribers_count; ?></div>
        </div>
    </div>
</div>

<!-- MAIN DASHBOARD CONTENT -->
<div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
    
    <!-- LEFT: RECENT ORDERS -->
    <div class="lg:col-span-2 space-y-8">
        <div class="bg-white rounded-[35px] shadow-sm border border-gray-100 overflow-hidden anim-up">
            <div class="p-8 border-b border-gray-100 flex justify-between items-center">
                <h3 class="font-bold text-2xl font-['Fredoka'] text-gray-900">Recent Orders</h3>
                <a href="orders.php" class="btn-chunky bg-gray-50 text-gray-500 px-4 py-2 rounded-xl text-xs">View All</a>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="text-gray-400 text-[10px] font-black uppercase tracking-widest bg-gray-50/50 border-b border-gray-50">
                            <th class="p-6">Order ID</th>
                            <th class="p-6">Customer</th>
                            <th class="p-6">Total</th>
                            <th class="p-6">Status</th>
                            <th class="p-6 text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody class="text-sm text-gray-600 font-['Outfit']">
                        <?php foreach ($recent_orders as $o): ?>
                        <tr class="border-b border-gray-50 hover:bg-gray-50 transition group">
                            <td class="p-6">
                                <span class="font-black text-gray-900">#<?php echo $o['order_number'] ?: $o['id']; ?></span>
                            </td>
                            <td class="p-6 font-bold text-gray-500">
                                <?php 
                                    $addr = json_decode($o['shipping_address'] ?? '{}', true);
                                    echo $addr['name'] ?? 'Guest';
                                ?>
                            </td>
                            <td class="p-6">
                                <span class="font-black text-gray-900">₹<?php echo number_format($o['total']); ?></span>
                            </td>
                            <td class="p-6">
                                <a href="orders.php?id=<?php echo $o['id']; ?>" class="px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-widest hover:scale-105 transition-transform inline-block <?php echo get_status_color($o['order_status']); ?>">
                                    <?php echo $o['order_status']; ?>
                                </a>
                            </td>
                            <td class="p-6 text-right">
                                <a href="orders.php?id=<?php echo $o['id']; ?>" class="w-10 h-10 inline-flex items-center justify-center bg-gray-50 text-gray-400 rounded-xl group-hover:bg-black group-hover:text-white transition shadow-sm"><i class="fas fa-eye text-xs"></i></a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if(empty($recent_orders)): ?>
                            <tr><td colspan="5" class="p-20 text-center text-gray-400 font-medium italic">No orders yet. Start your marketing engine! 🚀</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- RIGHT: ACTIVITY PULSE & NOTIFICATIONS -->
    <div class="space-y-8">
        
        <!-- Live Notifications -->
        <div class="bg-white p-8 rounded-[35px] shadow-sm border border-gray-100 anim-up" style="animation-delay: 100ms">
            <h3 class="font-bold text-xl font-['Fredoka'] text-gray-900 mb-6 flex items-center gap-2">
                <span class="relative flex h-3 w-3">
                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-red-400 opacity-75"></span>
                    <span class="relative inline-flex rounded-full h-3 w-3 bg-red-500"></span>
                </span>
                Activity Pulse
            </h3>

            <div class="space-y-6">
                <!-- New Orders Alert -->
                <?php if($pending_orders > 0): ?>
                <div class="p-4 bg-yellow-50 rounded-2xl border border-yellow-100 flex gap-4">
                    <div class="w-10 h-10 rounded-xl bg-yellow-400 text-white flex items-center justify-center shrink-0 shadow-sm animate-bounce">
                        <i class="fas fa-shopping-basket"></i>
                    </div>
                    <div>
                        <div class="text-xs font-black text-yellow-800 uppercase tracking-widest">Action Required</div>
                        <p class="text-sm font-bold text-yellow-900"><?php echo $pending_orders; ?> pending orders need dispatching.</p>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Low Stock Alert -->
                <?php foreach($low_stock as $ls): ?>
                <div class="p-4 bg-red-50 rounded-2xl border border-red-100 flex gap-4 transition hover:translate-x-1 duration-300">
                    <div class="w-10 h-10 rounded-xl bg-red-500 text-white flex items-center justify-center shrink-0 shadow-sm">
                        <i class="fas fa-exclamation-triangle"></i>
                    </div>
                    <div>
                        <div class="text-[10px] font-black text-red-800 uppercase tracking-widest">Critical Stock</div>
                        <p class="text-sm font-bold text-red-950"><?php echo $ls['name']; ?></p>
                        <span class="text-[10px] font-bold text-red-500 bg-white px-2 py-0.5 rounded-full mt-1 inline-block"><?php echo $ls['stock']; ?> left</span>
                    </div>
                </div>
                <?php endforeach; ?>

                <!-- Recent Reviews -->
                <?php foreach($recent_reviews as $rr): ?>
                <div class="p-4 bg-blue-50/50 rounded-2xl border border-blue-100 flex gap-4 hover:bg-white transition duration-300 border-dashed">
                    <div class="w-10 h-10 rounded-xl bg-blue-100 text-blue-600 flex items-center justify-center shrink-0">
                        <i class="fas fa-star text-xs"></i>
                    </div>
                    <div class="flex-1">
                        <div class="flex justify-between items-start">
                            <span class="text-[10px] font-black text-blue-800 uppercase tracking-widest">New Review</span>
                            <span class="text-[9px] text-gray-400 font-bold"><?php echo get_time_ago($rr['created_at']); ?></span>
                        </div>
                        <p class="text-[11px] font-bold text-gray-900 leading-tight mt-0.5">"<?php echo substr($rr['comment'], 0, 40); ?>..."</p>
                        <p class="text-[9px] font-medium text-gray-400 mt-1"><?php echo $rr['user_name']; ?> on <span class="text-gray-600 italic"><?php echo $rr['prod_name']; ?></span></p>
                    </div>
                </div>
                <?php endforeach; ?>

                <?php if(empty($low_stock) && empty($recent_reviews) && $pending_orders == 0): ?>
                    <div class="text-center py-10">
                        <div class="text-4xl mb-4">✨</div>
                        <p class="text-gray-400 font-bold text-sm">Everything is chilling.</p>
                        <p class="text-[10px] text-gray-300 uppercase mt-1">No urgent alerts found.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Quick Links Card -->
        <div class="bg-gradient-to-br from-[#19DC7E] to-[#0ea5e9] p-8 rounded-[35px] text-white shadow-xl shadow-green-200 anim-up" style="animation-delay: 200ms">
            <h4 class="font-black fredoka text-2xl mb-4">Grow Driyum.</h4>
            <p class="text-white/80 text-sm font-medium mb-8 leading-relaxed">Launch a new snack drop or update your regional rates to keep customers happy.</p>
            <div class="space-y-3">
                <a href="product_form.php" class="flex items-center justify-between bg-white text-black p-4 rounded-2xl font-black text-xs uppercase tracking-widest hover:scale-105 transition active:scale-95 shadow-lg"> New Snack <i class="fas fa-plus"></i></a>
                <a href="shipping.php" class="flex items-center justify-between bg-black/20 text-white p-4 rounded-2xl font-black text-xs uppercase tracking-widest hover:bg-black/30 transition shadow-sm"> Manage Rates <i class="fas fa-truck"></i></a>
            </div>
        </div>

    </div>
</div>

</body>
</html>
