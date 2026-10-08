<?php include 'includes/header.php'; 

// Chart Range Configuration
$days = isset($_GET['range']) ? (int)$_GET['range'] : 7;
if(!in_array($days, [7, 15, 30])) $days = 7;

// Stats
$excluded_statuses = "'cancelled', 'pending_payment'";
$orders_count = fetch_one("SELECT COUNT(*) as c FROM orders WHERE order_status NOT IN ($excluded_statuses)")['c'];
$products_count = fetch_one("SELECT COUNT(*) as c FROM products WHERE is_active=1")['c'];
$revenue = fetch_one("SELECT SUM(total) as t FROM orders WHERE order_status NOT IN ($excluded_statuses)")['t'] ?? 0;
$pending_orders = fetch_one("SELECT COUNT(*) as c FROM orders WHERE order_status = 'pending'")['c'];
$subscribers_count = fetch_one("SELECT COUNT(*) as c FROM newsletter_subscribers WHERE is_active=1")['c'];
$live_users_count = get_live_user_count(5);

// Recent Orders
$recent_orders = fetch_all("SELECT * FROM orders ORDER BY created_at DESC LIMIT 5");

// Activity Pulse Data
$low_stock = fetch_all("SELECT name, stock, image FROM products WHERE stock <= 5 AND is_active = 1 LIMIT 3");
$recent_reviews = fetch_all("SELECT r.*, u.name as user_name, p.name as prod_name FROM reviews r JOIN users u ON r.user_id = u.id JOIN products p ON r.product_id = p.id ORDER BY r.created_at DESC LIMIT 3");

// Business Intelligence Stats
$sales_today = fetch_one("SELECT SUM(total) as t FROM orders WHERE DATE(created_at) = CURDATE() AND order_status NOT IN ($excluded_statuses)")['t'] ?? 0;
$top_selling = fetch_all("SELECT p.name, SUM(oi.quantity) as total_sold, p.image, p.price FROM order_items oi JOIN products p ON oi.product_id = p.id JOIN orders o ON oi.order_id = o.id WHERE o.order_status NOT IN ($excluded_statuses) GROUP BY p.id ORDER BY total_sold DESC LIMIT 3");

// Chart Data: dynamic range
$sales_data = [];
for($i = $days - 1; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    if ($days > 7) {
        $label = date('d M', strtotime($date));
    } else {
        $label = date('D', strtotime($date));
    }
    $val = fetch_one("SELECT SUM(total) as t FROM orders WHERE DATE(created_at) = ? AND order_status NOT IN ($excluded_statuses)", [$date])['t'] ?? 0;
    $sales_data[] = ['label' => $label, 'value' => (float)$val];
}

// Category Distribution (Count)
$cat_data = fetch_all("SELECT c.name, COUNT(p.id) as count FROM categories c LEFT JOIN products p ON c.id = p.category_id GROUP BY c.id");

// Advanced Stats
$aov = $orders_count > 0 ? $revenue / $orders_count : 0;
$abandoned_count = fetch_one("SELECT COUNT(*) as c FROM abandoned_carts")['c'];
$repeat_customers = fetch_one("SELECT COUNT(*) as c FROM (SELECT user_id FROM orders WHERE user_id IS NOT NULL AND order_status NOT IN ($excluded_statuses) GROUP BY user_id HAVING COUNT(id) > 1) as t")['c'];
$repeat_rate = $orders_count > 0 ? ($repeat_customers / $orders_count) * 100 : 0;

// Affiliate & Marketing
$affiliate_revenue = fetch_one("SELECT SUM(total) as t FROM orders WHERE affiliate_id IS NOT NULL AND order_status NOT IN ($excluded_statuses)")['t'] ?? 0;
$coupon_savings = fetch_one("SELECT SUM(discount) as t FROM orders WHERE discount > 0 AND order_status NOT IN ($excluded_statuses)")['t'] ?? 0;

// Category Revenue Distribution
$cat_revenue_data = fetch_all("SELECT c.name, SUM(oi.subtotal) as revenue FROM categories c JOIN products p ON c.id = p.category_id JOIN order_items oi ON p.id = oi.product_id JOIN orders o ON oi.order_id = o.id WHERE o.order_status NOT IN ($excluded_statuses) GROUP BY c.id");

// Top Customers Intelligence
$top_customers = fetch_all("SELECT u.name, u.email, COUNT(o.id) as order_count, SUM(o.total) as total_spent FROM orders o JOIN users u ON o.user_id = u.id WHERE o.order_status NOT IN ($excluded_statuses) GROUP BY o.user_id ORDER BY total_spent DESC LIMIT 3");
?>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<div class="mb-8 flex flex-col md:flex-row justify-between items-start md:items-center gap-4 anim-up">
    <div>
        <h1 class="text-3xl font-black text-gray-900 crimson-pro tracking-tight">Dashboard Overview</h1>
        <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mt-1">Live store performance and insights</p>
    </div>
    <div class="flex items-center gap-3 bg-white px-4 py-2 rounded-2xl border border-gray-100 shadow-sm">
        <span class="w-2 h-2 rounded-full bg-[#24B25D] animate-pulse"></span>
        <span class="text-[9px] font-black uppercase tracking-widest text-gray-500">System Ready</span>
    </div>
</div>

<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6 gap-6 mb-10">
    <!-- Live Users -->
    <div class="bg-black p-6 rounded-3xl shadow-xl group hover:scale-105 transition-all anim-up relative overflow-hidden">
        <div class="flex items-center gap-4 relative z-10">
            <div class="w-12 h-12 rounded-2xl bg-[#24B25D]/20 text-[#24B25D] flex items-center justify-center text-xl relative">
                <i class="fas fa-users"></i>
                <span class="absolute -top-1 -right-1 flex h-3 w-3">
                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-[#24B25D] opacity-75"></span>
                    <span class="relative inline-flex rounded-full h-3 w-3 bg-[#24B25D]"></span>
                </span>
            </div>
            <div>
                <div class="text-[9px] text-gray-400 font-bold uppercase tracking-widest mb-0.5">Live Users</div>
                <div class="text-xl font-black text-white crimson-pro" id="live-users-count"><?php echo $live_users_count; ?></div>
            </div>
        </div>
        <div class="absolute -right-4 -bottom-4 opacity-5 pointer-events-none">
            <i class="fas fa-signal text-9xl"></i>
        </div>
    </div>

    <!-- Revenue -->
    <div class="bg-white p-6 rounded-3xl shadow-sm border border-gray-100 group hover:shadow-lg transition-all anim-up" style="animation-delay: 50ms">
        <div class="flex items-center gap-4">
            <div class="w-12 h-12 rounded-2xl bg-green-50 text-[#24B25D] flex items-center justify-center text-xl group-hover:scale-110 transition-transform">
                <i class="fas fa-indian-rupee-sign"></i>
            </div>
            <div>
                <div class="text-[9px] text-gray-400 font-bold uppercase tracking-widest mb-0.5">Revenue</div>
                <div class="text-xl font-black text-gray-900 crimson-pro">₹<?php echo number_format($revenue, 0); ?></div>
            </div>
        </div>
    </div>

    <!-- Orders -->
    <div class="bg-white p-6 rounded-3xl shadow-sm border border-gray-100 group hover:shadow-lg transition-all anim-up" style="animation-delay: 100ms">
        <div class="flex items-center gap-4">
            <div class="w-12 h-12 rounded-2xl bg-blue-50 text-blue-500 flex items-center justify-center text-xl group-hover:scale-110 transition-transform">
                <i class="fas fa-shopping-bag"></i>
            </div>
            <div>
                <div class="text-[9px] text-gray-400 font-bold uppercase tracking-widest mb-0.5">Orders</div>
                <div class="text-xl font-black text-gray-900 crimson-pro"><?php echo $orders_count; ?></div>
            </div>
        </div>
    </div>

    <!-- Pending -->
    <div class="bg-white p-6 rounded-3xl shadow-sm border border-gray-100 group hover:shadow-lg transition-all anim-up relative overflow-hidden" style="animation-delay: 150ms">
        <div class="flex items-center gap-4">
            <div class="w-12 h-12 rounded-2xl bg-orange-50 text-orange-500 flex items-center justify-center text-xl group-hover:scale-110 transition-transform">
                <i class="fas fa-clock"></i>
            </div>
            <div>
                <div class="text-[9px] text-gray-400 font-bold uppercase tracking-widest mb-0.5">Pending</div>
                <div class="text-xl font-black text-gray-900 crimson-pro" id="pending-orders-count"><?php echo $pending_orders; ?></div>
            </div>
        </div>
        <?php if($pending_orders > 0): ?>
            <div class="absolute right-0 top-0 bottom-0 w-1 bg-orange-400"></div>
        <?php endif; ?>
    </div>

    <!-- Products -->
    <div class="bg-white p-6 rounded-3xl shadow-sm border border-gray-100 group hover:shadow-lg transition-all anim-up" style="animation-delay: 200ms">
        <div class="flex items-center gap-4">
            <div class="w-12 h-12 rounded-2xl bg-indigo-50 text-indigo-500 flex items-center justify-center text-xl group-hover:scale-110 transition-transform">
                <i class="fas fa-box"></i>
            </div>
            <div>
                <div class="text-[9px] text-gray-400 font-bold uppercase tracking-widest mb-0.5">Snacks</div>
                <div class="text-xl font-black text-gray-900 crimson-pro"><?php echo $products_count; ?></div>
            </div>
        </div>
    </div>

    <!-- Subscribers -->
    <div class="bg-white p-6 rounded-3xl shadow-sm border border-gray-100 group hover:shadow-lg transition-all anim-up" style="animation-delay: 250ms">
        <div class="flex items-center gap-4">
            <div class="w-12 h-12 rounded-2xl bg-pink-50 text-pink-500 flex items-center justify-center text-xl group-hover:scale-110 transition-transform">
                <i class="fas fa-envelope-open-text"></i>
            </div>
            <div>
                <div class="text-[9px] text-gray-400 font-bold uppercase tracking-widest mb-0.5">Subscribers</div>
                <div class="text-xl font-black text-gray-900 crimson-pro"><?php echo $subscribers_count; ?></div>
            </div>
        </div>
    </div>
</div>

<div class="grid grid-cols-1 xl:grid-cols-3 gap-8 mb-10">
    <!-- Sales Chart -->
    <div class="xl:col-span-2 bg-white p-8 rounded-3xl shadow-sm border border-gray-100 anim-up">
        <div class="flex justify-between items-center mb-6">
            <div>
                <h3 class="font-black text-xl crimson-pro text-gray-900">Revenue Stream</h3>
                <p class="text-[9px] text-gray-400 font-black uppercase tracking-widest">Performance over the last <?php echo $days; ?> days</p>
            </div>
            <div class="flex gap-2">
                <select onchange="window.location.href='index.php?range=' + this.value" class="bg-gray-50 border border-gray-100 rounded-xl px-3 py-1.5 text-[10px] font-black uppercase tracking-widest outline-none focus:border-[#24B25D] shadow-sm">
                    <option value="7" <?php echo $days == 7 ? 'selected' : ''; ?>>7 Days</option>
                    <option value="15" <?php echo $days == 15 ? 'selected' : ''; ?>>15 Days</option>
                    <option value="30" <?php echo $days == 30 ? 'selected' : ''; ?>>Month</option>
                </select>
            </div>
        </div>
        <div class="h-64 relative">
            <canvas id="salesChart"></canvas>
        </div>
    </div>

    <!-- Category Performance -->
    <div class="bg-white p-8 rounded-3xl shadow-sm border border-gray-100 anim-up flex flex-col" style="animation-delay: 100ms">
        <div class="flex justify-between items-start mb-6">
            <div>
                <h3 class="font-black text-xl crimson-pro text-gray-900 mb-1">Financial Split</h3>
                <p class="text-[9px] text-gray-400 font-black uppercase tracking-widest">Revenue by Category</p>
            </div>
            <div id="chart-toggle" class="flex bg-gray-50 p-1 rounded-xl">
                <button onclick="toggleCatChart('revenue')" id="btn-cat-rev" class="px-3 py-1 text-[8px] font-black uppercase tracking-widest rounded-lg bg-white shadow-sm transition-all">Rev</button>
                <button onclick="toggleCatChart('count')" id="btn-cat-count" class="px-3 py-1 text-[8px] font-black uppercase tracking-widest rounded-lg text-gray-400 hover:text-gray-600 transition-all">Qty</button>
            </div>
        </div>
        <div class="h-56 relative flex-1">
            <canvas id="categoryChart"></canvas>
        </div>
    </div>
</div>

<!-- INTELLIGENCE INSIGHTS -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-10">
    <!-- AOV -->
    <div class="bg-white p-6 rounded-3xl border border-gray-100 shadow-sm hover:shadow-md transition-all anim-up">
        <div class="flex items-center gap-4 mb-4">
            <div class="w-10 h-10 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center">
                <i class="fas fa-calculator"></i>
            </div>
            <div>
                <div class="text-[8px] font-black text-gray-400 uppercase tracking-widest">Avg Order Value</div>
                <div class="text-lg font-black text-gray-900 crimson-pro">₹<?php echo number_format($aov, 0); ?></div>
            </div>
        </div>
        <div class="text-[9px] text-gray-500 font-medium">Based on <span class="text-black font-bold"><?php echo $orders_count; ?></span> successful orders.</div>
    </div>

    <!-- Repeat Rate -->
    <div class="bg-white p-6 rounded-3xl border border-gray-100 shadow-sm hover:shadow-md transition-all anim-up" style="animation-delay: 50ms">
        <div class="flex items-center gap-4 mb-4">
            <div class="w-10 h-10 rounded-xl bg-orange-50 text-orange-600 flex items-center justify-center">
                <i class="fas fa-redo"></i>
            </div>
            <div>
                <div class="text-[8px] font-black text-gray-400 uppercase tracking-widest">Repeat Cust. Rate</div>
                <div class="text-lg font-black text-gray-900 crimson-pro"><?php echo number_format($repeat_rate, 1); ?>%</div>
            </div>
        </div>
        <div class="text-[9px] text-gray-500 font-medium"><span class="text-black font-bold"><?php echo $repeat_customers; ?></span> users order more than once.</div>
    </div>

    <!-- Abandoned Carts -->
    <div class="bg-white p-6 rounded-3xl border border-gray-100 shadow-sm hover:shadow-md transition-all anim-up" style="animation-delay: 100ms">
        <div class="flex items-center gap-4 mb-4">
            <div class="w-10 h-10 rounded-xl bg-red-50 text-red-600 flex items-center justify-center">
                <i class="fas fa-shopping-basket"></i>
            </div>
            <div>
                <div class="text-[8px] font-black text-gray-400 uppercase tracking-widest">Abandoned Carts</div>
                <div class="text-lg font-black text-gray-900 crimson-pro"><?php echo $abandoned_count; ?></div>
            </div>
        </div>
        <div class="text-[9px] text-gray-500 font-medium">Potential revenue waiting to be recovered.</div>
    </div>

    <!-- Affiliate Revenue -->
    <div class="bg-white p-6 rounded-3xl border border-gray-100 shadow-sm hover:shadow-md transition-all anim-up" style="animation-delay: 150ms">
        <div class="flex items-center gap-4 mb-4">
            <div class="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center">
                <i class="fas fa-handshake"></i>
            </div>
            <div>
                <div class="text-[8px] font-black text-gray-400 uppercase tracking-widest">Affiliate Revenue</div>
                <div class="text-lg font-black text-gray-900 crimson-pro">₹<?php echo number_format($affiliate_revenue, 0); ?></div>
            </div>
        </div>
        <div class="text-[9px] text-gray-500 font-medium">Coupons saved customers <span class="text-emerald-600 font-bold">₹<?php echo number_format($coupon_savings, 0); ?></span></div>
    </div>
</div>

<!-- MAIN DASHBOARD CONTENT -->
<div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
    
    <!-- LEFT: RECENT ORDERS -->
    <div class="lg:col-span-2 space-y-8">
        <div class="bg-white rounded-3xl shadow-sm border border-gray-100 overflow-hidden anim-up">
            <div class="p-6 border-b border-gray-100 flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
                <div class="flex items-center gap-4">
                    <h3 class="font-black text-xl crimson-pro text-gray-900">Recent Orders</h3>
                    <div id="bulk-actions" class="hidden flex items-center gap-2 animate-fade-in-right">
                        <select id="bulk-status-select" class="bg-gray-50 border border-gray-200 text-gray-700 text-xs font-bold rounded-lg px-3 py-2 outline-none focus:border-[#24B25D]">
                            <option value="">Status...</option>
                            <option value="pending">Pending</option>
                            <option value="confirmed">Confirmed</option>
                            <option value="shipped">Shipped</option>
                            <option value="delivered">Delivered</option>
                            <option value="cancelled">Cancelled</option>
                        </select>
                        <button onclick="applyBulkStatus()" class="bg-black text-white px-4 py-2 rounded-lg text-xs font-black uppercase tracking-widest hover:bg-[#24B25D] hover:text-black transition-all shadow-sm">
                            Apply
                        </button>
                    </div>
                </div>
                <a href="orders.php" class="bg-gray-50 text-gray-400 px-4 py-2 rounded-xl text-[9px] font-black uppercase tracking-widest hover:bg-black hover:text-[#24B25D] transition-all">View All</a>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="text-gray-400 text-[9px] font-black uppercase tracking-widest bg-gray-50/50 border-b border-gray-50">
                            <th class="p-4 w-10 text-center"><input type="checkbox" id="select-all" class="w-4 h-4 rounded border-gray-300 text-[#24B25D] focus:ring-[#24B25D] cursor-pointer" onclick="toggleSelectAll()"></th>
                            <th class="p-4">ID</th>
                            <th class="p-4">Customer</th>
                            <th class="p-4">Amount</th>
                            <th class="p-4">Status</th>
                            <th class="p-4 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="text-xs text-gray-600">
                        <?php foreach ($recent_orders as $o): ?>
                        <tr class="border-b border-gray-50 hover:bg-gray-50/50 transition-all">
                            <td class="p-4 text-center">
                                <input type="checkbox" name="selected_orders[]" value="<?php echo $o['id']; ?>" class="order-checkbox w-4 h-4 rounded border-gray-300 text-[#24B25D] focus:ring-[#24B25D] cursor-pointer" onclick="updateBulkState()">
                            </td>
                            <td class="p-4">
                                <span class="font-bold text-gray-900">#<?php echo $o['order_number'] ?: $o['id']; ?></span>
                            </td>
                            <td class="p-4">
                                <span class="font-medium">
                                    <?php 
                                        $addr = json_decode($o['shipping_address'] ?? '{}', true);
                                        echo $addr['name'] ?? 'Guest';
                                    ?>
                                </span>
                            </td>
                            <td class="p-4 font-black text-gray-900">₹<?php echo number_format($o['total']); ?></td>
                            <td class="p-4">
                                <div class="relative status-dropdown-container">
                                    <button onclick="toggleStatusDropdown(this, event)" class="status-btn px-3 py-1.5 rounded-xl text-[10px] font-black uppercase tracking-widest flex items-center gap-2 shadow-sm transition-all hover:scale-105 active:scale-95 <?php echo get_status_color($o['order_status']); ?>">
                                        <?php echo str_replace('_', ' ', $o['order_status']); ?>
                                        <i class="fas fa-chevron-down opacity-50 text-[10px]"></i>
                                    </button>
                                    
                                    <!-- Dropdown Menu -->
                                    <div class="status-menu hidden absolute left-0 top-full mt-2 w-32 bg-white rounded-xl shadow-xl border border-gray-100 z-50 overflow-hidden anim-up">
                                        <?php 
                                        $statuses = ['pending', 'confirmed', 'shipped', 'delivered', 'cancelled'];
                                        foreach($statuses as $s): 
                                            if($s === $o['order_status']) continue;
                                        ?>
                                        <button onclick="updateOrderStatus(<?php echo $o['id']; ?>, '<?php echo $s; ?>', this)" class="w-full text-left px-4 py-2.5 text-[10px] font-bold uppercase tracking-widest text-gray-500 hover:bg-gray-50 hover:text-black transition-colors border-b border-gray-50 last:border-0 block">
                                            <?php echo $s; ?>
                                        </button>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </td>
                            <td class="p-4 text-right">
                                <a href="orders.php?id=<?php echo $o['id']; ?>" class="w-8 h-8 inline-flex items-center justify-center bg-gray-50 text-gray-400 rounded-lg hover:bg-black hover:text-white transition shadow-sm"><i class="fas fa-arrow-right text-[10px]"></i></a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- RIGHT: ACTIVITY PULSE & NOTIFICATIONS -->
    <div class="space-y-8">
        
        <!-- Live Pulse & Intelligence -->
        <div class="bg-white p-8 rounded-[35px] shadow-sm border border-gray-100 anim-up" style="animation-delay: 100ms">
                <h3 class="font-bold text-xl font-heading text-gray-900 mb-6 flex items-center gap-2">
                    <span class="relative flex h-3 w-3">
                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-red-400 opacity-75"></span>
                        <span class="relative inline-flex rounded-full h-3 w-3 bg-red-500"></span>
                    </span>
                    Activity Pulse
                </h3>

                <div class="space-y-6">
                    <!-- Sales Today Indicator -->
                    <div class="p-6 bg-gradient-to-r from-green-600 to-[#24B25D] rounded-[30px] text-white shadow-lg relative overflow-hidden group mb-2">
                        <div class="relative z-10">
                            <div class="text-[10px] font-black uppercase tracking-widest opacity-70 mb-1">Market Velocity</div>
                            <div class="text-3xl font-black crimson-pro">₹<span id="sales-today-count"><?php echo number_format($sales_today); ?></span></div>
                            <p class="text-[10px] font-bold opacity-80 mt-1 italic">Today's Revenue</p>
                        </div>
                        <i class="fas fa-bolt absolute -right-2 -bottom-2 text-white/10 text-6xl group-hover:scale-125 transition-all duration-500"></i>
                    </div>

                <!-- New Orders Alert -->
                <?php if($pending_orders > 0): ?>
                <div class="p-4 bg-yellow-50 rounded-2xl border border-yellow-100 flex gap-4 hover:shadow-md transition">
                    <div class="w-10 h-10 rounded-xl bg-yellow-400 text-white flex items-center justify-center shrink-0 shadow-sm animate-bounce">
                        <i class="fas fa-shopping-basket"></i>
                    </div>
                    <div>
                        <div class="text-xs font-black text-yellow-800 uppercase tracking-widest">Action Required</div>
                        <p class="text-sm font-bold text-yellow-900"><?php echo $pending_orders; ?> pending orders need dispatching.</p>
                        <a href="orders.php?status_filter=pending" class="text-[10px] font-black text-yellow-600 hover:underline">Process Now &rarr;</a>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Top Selling Products -->
                <?php if(!empty($top_selling)): ?>
                <div class="space-y-4 pt-4 border-t border-gray-50">
                    <h4 class="text-[10px] font-black text-gray-400 uppercase tracking-widest px-2">Top Performers</h4>
                    <?php foreach($top_selling as $ts): ?>
                    <div class="flex items-center gap-4 group cursor-default">
                        <div class="w-12 h-12 bg-gray-100 rounded-xl overflow-hidden border border-gray-100 p-1 group-hover:border-[#24B25D] transition-colors">
                            <img src="<?php echo get_url($ts['image']); ?>" class="w-full h-full object-cover rounded-lg">
                        </div>
                        <div class="flex-1">
                            <div class="text-xs font-bold text-gray-900 leading-tight"><?php echo $ts['name']; ?></div>
                            <div class="text-[10px] text-gray-400 font-medium">₹<?php echo number_format($ts['price']); ?> • <span class="text-[#24B25D] font-black"><?php echo $ts['total_sold']; ?> Sold</span></div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>

                <!-- Top Customers -->
                <?php if(!empty($top_customers)): ?>
                <div class="space-y-4 pt-4 border-t border-gray-50">
                    <h4 class="text-[10px] font-black text-gray-400 uppercase tracking-widest px-2">High-Value Customers</h4>
                    <?php foreach($top_customers as $tc): ?>
                    <div class="flex items-center gap-4 group cursor-default">
                        <div class="w-10 h-10 bg-indigo-50 rounded-xl flex items-center justify-center text-indigo-600 font-black text-xs border border-indigo-100 group-hover:bg-indigo-600 group-hover:text-white transition-all capitalize">
                            <?php echo substr($tc['name'], 0, 1); ?>
                        </div>
                        <div class="flex-1">
                            <div class="text-xs font-bold text-gray-900 leading-tight"><?php echo $tc['name']; ?></div>
                            <div class="text-[10px] text-gray-400 font-medium"><?php echo $tc['order_count']; ?> Orders • <span class="text-indigo-600 font-black">₹<?php echo number_format($tc['total_spent']); ?> Spent</span></div>
                        </div>
                    </div>
                    <?php endforeach; ?>
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
        <div class="bg-gradient-to-br from-[#24B25D] to-[#0ea5e9] p-8 rounded-[35px] text-white shadow-xl shadow-green-200 anim-up" style="animation-delay: 200ms">
            <h4 class="font-black crimson-pro text-2xl mb-4">Grow Driyum.</h4>
            <p class="text-white/80 text-sm font-medium mb-8 leading-relaxed">Launch a new snack drop or update your regional rates to keep customers happy.</p>
            <div class="space-y-3">
                <a href="product_form.php" class="flex items-center justify-between bg-white text-black p-4 rounded-2xl font-black text-xs uppercase tracking-widest hover:scale-105 transition active:scale-95 shadow-lg"> New Snack <i class="fas fa-plus"></i></a>
                <a href="shipping.php" class="flex items-center justify-between bg-black/20 text-white p-4 rounded-2xl font-black text-xs uppercase tracking-widest hover:bg-black/30 transition shadow-sm"> Manage Rates <i class="fas fa-truck"></i></a>
            </div>
        </div>

    </div>
</div>

<!-- Dispatch Modal -->
<div id="dispatchModal" class="fixed inset-0 bg-black/60 backdrop-blur-sm hidden items-center justify-center z-[200]">
    <div class="bg-white rounded-[40px] p-10 w-full max-w-lg shadow-2xl anim-up">
        <div class="flex items-center gap-4 mb-8">
            <div class="w-16 h-16 bg-blue-50 text-blue-600 rounded-2xl flex items-center justify-center text-2xl">
                <i class="fas fa-shipping-fast"></i>
            </div>
            <div>
                <h3 class="text-3xl font-black crimson-pro text-gray-900">Dispatch Order</h3>
                <p class="text-gray-400 font-medium" id="dispatch-order-number">ORD-000000</p>
            </div>
        </div>
        
        <form action="orders.php" method="POST" id="dispatch-form" class="space-y-6">
            <input type="hidden" name="order_id" id="dispatch-order-id">
            <input type="hidden" name="dispatch_order" value="1">
            
            <div class="space-y-2">
                <label class="text-[10px] font-black uppercase tracking-widest text-gray-400 ml-4">Tracking Number</label>
                <input type="text" name="tracking_number" required placeholder="Paste tracking ID here..." class="w-full bg-gray-50 border-2 border-transparent focus:border-[#24B25D] focus:bg-white rounded-[24px] px-6 py-4 outline-none transition-all font-bold">
            </div>
            
            <div class="space-y-2">
                <label class="text-[10px] font-black uppercase tracking-widest text-gray-400 ml-4">Dispatch Date</label>
                <input type="date" name="dispatch_date" required value="<?php echo date('Y-m-d'); ?>" class="w-full bg-gray-50 border-2 border-transparent focus:border-[#24B25D] focus:bg-white rounded-[24px] px-6 py-4 outline-none transition-all font-bold">
            </div>

            <div class="flex gap-4 pt-4">
                <button type="button" onclick="closeDispatchModal()" class="flex-1 bg-gray-100 text-gray-500 py-5 rounded-[24px] font-black uppercase tracking-widest hover:bg-gray-200 transition-all">Cancel</button>
                <button type="submit" class="flex-1 bg-black text-white py-5 rounded-[24px] font-black uppercase tracking-widest shadow-xl hover:bg-[#24B25D] hover:text-black transition-all" id="dispatch-btn">Dispatch Now</button>
            </div>
        </form>
    </div>
</div>

<script>
// Dashboard Status Management Logic
function toggleSelectAll() {
    const parent = document.getElementById('select-all');
    document.querySelectorAll('.order-checkbox').forEach(cb => cb.checked = parent.checked);
    updateBulkState();
}

function updateBulkState() {
    const checked = document.querySelectorAll('.order-checkbox:checked').length;
    const actions = document.getElementById('bulk-actions');
    const parent = document.getElementById('select-all');
    
    // Update main checkbox state (indeterminate logic)
    const all = document.querySelectorAll('.order-checkbox').length;
    parent.indeterminate = checked > 0 && checked < all;
    parent.checked = checked === all;

    if (checked > 0) {
        actions.classList.remove('hidden');
    } else {
        actions.classList.add('hidden');
    }
}

async function applyBulkStatus() {
    const status = document.getElementById('bulk-status-select').value;
    if (!status) {
        if (typeof window.showAlert === 'function') await window.showAlert('Please select a status to apply.', { type: 'warning' });
        else alert('Please select a status to apply.');
        return;
    }

    const selected = Array.from(document.querySelectorAll('.order-checkbox:checked')).map(cb => cb.value);
    
    const ok = typeof window.showConfirm === 'function'
        ? await window.showConfirm(`Are you sure you want to change the status of ${selected.length} orders to "${status}"?`, {
            title: 'Bulk Status Update',
            type: 'warning',
            confirmText: 'Apply Status'
        })
        : confirm(`Are you sure you want to change the status of ${selected.length} orders to "${status}"?`);
    if (!ok) return;

    const btn = document.querySelector('#bulk-actions button');
    const originalText = btn.innerHTML;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
    btn.disabled = true;

    try {
        const formData = new FormData();
        formData.append('ajax_action', 'bulk_status');
        formData.append('status', status);
        selected.forEach(id => formData.append('ids[]', id));

        const response = await fetch('orders.php', {
            method: 'POST',
            body: formData
        });

        const data = await response.json();

        if (data.success) {
            location.reload();
        } else {
            alert('Error: ' + data.message);
        }
    } catch (e) {
        alert('Bulk update failed. Please try again.');
    } finally {
        btn.innerHTML = originalText;
        btn.disabled = false;
    }
}

// ... existing functions ...
async function updateOrderStatus(id, status, el) {
    const container = el.closest('.status-dropdown-container');
    const btn = container.querySelector('.status-btn');
    const originalContent = btn.innerHTML;
    
    btn.innerHTML = `<i class="fas fa-spinner fa-spin"></i>`;
    
    try {
        const formData = new FormData();
        formData.append('ajax_action', 'update_status');
        formData.append('id', id);
        formData.append('status', status);

        const response = await fetch('orders.php', {
            method: 'POST',
            body: formData
        });

        const data = await response.json();

        if (data.success) {
            btn.innerHTML = `${data.label} <i class="fas fa-chevron-down opacity-50 text-[10px]"></i>`;
            btn.className = `status-btn px-3 py-1.5 rounded-xl text-[10px] font-black uppercase tracking-widest flex items-center gap-2 shadow-sm transition-all hover:scale-105 active:scale-95 ${getStatusColor(status)}`;
            
            // Pulse success
            btn.classList.add('scale-110');
            setTimeout(() => btn.classList.remove('scale-110'), 200);
        } else {
            alert('Error: ' + data.message);
            btn.innerHTML = originalContent;
        }
    } catch (e) {
        alert('Status update failed');
        btn.innerHTML = originalContent;
    }
    container.querySelector('.status-menu').classList.add('hidden');
}

function getStatusColor(status) {
    switch(status) {
        case 'pending': return 'bg-yellow-400 text-black';
        case 'confirmed': return 'bg-indigo-600 text-white';
        case 'shipped': return 'bg-blue-600 text-white';
        case 'delivered': return 'bg-[#24B25D] text-black';
        case 'cancelled': return 'bg-red-600 text-white';
        default: return 'bg-gray-100 text-gray-800';
    }
}

function toggleStatusDropdown(btn, e) {
    e.stopPropagation();
    const menu = btn.nextElementSibling;
    const isHidden = menu.classList.contains('hidden');
    
    document.querySelectorAll('.status-menu').forEach(m => m.classList.add('hidden'));
    if(isHidden) menu.classList.remove('hidden');
}

function openDispatchModal(id, num) {
    document.getElementById('dispatch-order-id').value = id;
    document.getElementById('dispatch-order-number').textContent = 'Order #' + num;
    document.getElementById('dispatchModal').classList.replace('hidden', 'flex');
}

function closeDispatchModal() {
    document.getElementById('dispatchModal').classList.replace('flex', 'hidden');
}

// Handle Dispatch Submission
document.getElementById('dispatch-form').addEventListener('submit', async function(e) {
    e.preventDefault();
    const btn = document.getElementById('dispatch-btn');
    const original = btn.textContent;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
    btn.disabled = true;

    try {
        const formData = new FormData(this);
        formData.append('is_ajax', '1');
        const response = await fetch('orders.php', { method: 'POST', body: formData });
        const data = await response.json();

        if(data.success) {
            location.reload(); // Refresh to update all stats and lists
        } else {
            alert('Dispatch failed: ' + data.message);
            btn.textContent = original;
            btn.disabled = false;
        }
    } catch(err) {
        alert('Network error');
        btn.textContent = original;
        btn.disabled = false;
    }
});

document.addEventListener('click', () => {
    document.querySelectorAll('.status-menu').forEach(m => m.classList.add('hidden'));
});

// Analytics Charts
document.addEventListener('DOMContentLoaded', () => {
    // Sales Revenue Chart
    const salesCtx = document.getElementById('salesChart').getContext('2d');
    const salesData = <?php echo json_encode($sales_data); ?>;
    
    new Chart(salesCtx, {
        type: 'line',
        data: {
            labels: salesData.map(d => d.label),
            datasets: [{
                label: 'Revenue',
                data: salesData.map(d => d.value),
                borderColor: '#24B25D',
                backgroundColor: 'rgba(25, 220, 126, 0.1)',
                borderWidth: 4,
                tension: 0.4,
                fill: true,
                pointBackgroundColor: '#fff',
                pointBorderColor: '#24B25D',
                pointBorderWidth: 3,
                pointRadius: 6,
                pointHoverRadius: 8
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor: '#000',
                    padding: 12,
                    titleFont: { size: 10, weight: 'bold' },
                    bodyFont: { size: 14, weight: '900' },
                    callbacks: {
                        label: (ctx) => '₹' + ctx.raw.toLocaleString()
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: { display: false },
                    ticks: {
                        font: { size: 10, weight: 'bold' },
                        callback: (val) => '₹' + (val / 1000) + 'k'
                    }
                },
                x: {
                    grid: { display: false },
                    ticks: { font: { size: 10, weight: 'bold' } }
                }
            }
        }
    });

    // Category Doughnut Chart
    const catCtx = document.getElementById('categoryChart').getContext('2d');
    const catCountData = <?php echo json_encode($cat_data); ?>;
    const catRevData = <?php echo json_encode($cat_revenue_data); ?>;
    
    let categoryChart = new Chart(catCtx, {
        type: 'doughnut',
        data: {
            labels: catRevData.map(d => d.name),
            datasets: [{
                data: catRevData.map(d => d.revenue),
                backgroundColor: [
                    '#24B25D', '#0ea5e9', '#f59e0b', '#ec4899', '#8b5cf6', '#6366f1'
                ],
                borderWidth: 0,
                cutout: '75%'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        padding: 20,
                        usePointStyle: true,
                        font: { size: 10, weight: 'bold' }
                    }
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            const val = context.raw;
                            const isRev = categoryChart.data.datasets[0].label === 'Revenue';
                            return context.label + ': ' + (isRev ? '₹' + val.toLocaleString() : val + ' items');
                        }
                    }
                }
            }
        }
    });

    window.toggleCatChart = function(type) {
        const btnRev = document.getElementById('btn-cat-rev');
        const btnCount = document.getElementById('btn-cat-count');
        const title = document.querySelector('#categoryChart').closest('div').parentElement.querySelector('h3');
        const sub = document.querySelector('#categoryChart').closest('div').parentElement.querySelector('p');

        if (type === 'revenue') {
            categoryChart.data.labels = catRevData.map(d => d.name);
            categoryChart.data.datasets[0].data = catRevData.map(d => d.revenue);
            categoryChart.data.datasets[0].label = 'Revenue';
            btnRev.classList.add('bg-white', 'shadow-sm');
            btnRev.classList.remove('text-gray-400');
            btnCount.classList.remove('bg-white', 'shadow-sm');
            btnCount.classList.add('text-gray-400');
            title.textContent = 'Financial Split';
            sub.textContent = 'Revenue by Category';
        } else {
            categoryChart.data.labels = catCountData.map(d => d.name);
            categoryChart.data.datasets[0].data = catCountData.map(d => d.count);
            categoryChart.data.datasets[0].label = 'Quantity';
            btnCount.classList.add('bg-white', 'shadow-sm');
            btnCount.classList.remove('text-gray-400');
            btnRev.classList.remove('bg-white', 'shadow-sm');
            btnRev.classList.add('text-gray-400');
            title.textContent = 'Inventory Split';
            sub.textContent = 'Product distribution by Category';
        }
        categoryChart.update();
    };
    categoryChart.data.datasets[0].label = 'Revenue'; // Default label
});

// Real-time Stats Refresher
async function refreshLiveStats() {
    try {
        const response = await fetch('../api/get_live_stats.php');
        const data = await response.json();
        
        if (data.success) {
            // Update counts with subtle animation
            updateElementWithAnim('live-users-count', data.live_users);
            updateElementWithAnim('sales-today-count', data.sales_today);
            updateElementWithAnim('pending-orders-count', data.pending_orders);
            
            // Optional: Update document title if there are new pending orders
            if (data.pending_orders > 0) {
                document.title = `(${data.pending_orders}) Admin Dashboard | DRIYUM`;
            } else {
                document.title = `Admin Dashboard | DRIYUM`;
            }
        }
    } catch (e) {
        console.error("Stats refresh failed", e);
    }
}

function updateElementWithAnim(id, value) {
    const el = document.getElementById(id);
    if (!el) return;
    
    const currentVal = el.textContent.replace('₹', '').trim();
    if (currentVal !== String(value)) {
        el.classList.add('scale-110', 'text-[#24B25D]');
        el.textContent = value;
        setTimeout(() => {
            el.classList.remove('scale-110', 'text-[#24B25D]');
        }, 1000);
    }
}

// Refresh every 10 seconds for real-time feel
setInterval(refreshLiveStats, 10000);
document.addEventListener('DOMContentLoaded', refreshLiveStats);
</script>
<?php include 'includes/footer.php'; ?>


