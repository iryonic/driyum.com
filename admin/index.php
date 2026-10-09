<?php 
include 'includes/header.php'; 

// Chart Range Configuration
$days = isset($_GET['range']) ? (int)$_GET['range'] : 7;
if(!in_array($days, [7, 15, 30])) $days = 7;

// Stats
$excluded_statuses = "'cancelled', 'pending_payment'";
$orders_count = fetch_one("SELECT COUNT(*) as c FROM orders WHERE order_status NOT IN ($excluded_statuses)")['c'] ?? 0;
$products_count = fetch_one("SELECT COUNT(*) as c FROM products WHERE is_active=1")['c'] ?? 0;
$revenue = fetch_one("SELECT SUM(total) as t FROM orders WHERE order_status NOT IN ($excluded_statuses)")['t'] ?? 0;
$pending_orders = fetch_one("SELECT COUNT(*) as c FROM orders WHERE order_status = 'pending'")['c'] ?? 0;
$subscribers_count = fetch_one("SELECT COUNT(*) as c FROM newsletter_subscribers WHERE is_active=1")['c'] ?? 0;
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
$abandoned_count = fetch_one("SELECT COUNT(*) as c FROM abandoned_carts")['c'] ?? 0;
$repeat_customers = fetch_one("SELECT COUNT(*) as c FROM (SELECT user_id FROM orders WHERE user_id IS NOT NULL AND order_status NOT IN ($excluded_statuses) GROUP BY user_id HAVING COUNT(id) > 1) as t")['c'] ?? 0;
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

<!-- PAGE HEADER -->
<div class="mb-6 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
    <div>
        <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Dashboard Overview</h1>
        <p class="text-xs text-slate-500 mt-0.5">Real-time performance metrics and operational analytics</p>
    </div>
    <div class="flex items-center gap-2">
        <a href="orders.php" class="btn-admin btn-admin-secondary text-xs">
            <i class="fas fa-box text-slate-400"></i> Manage Orders
        </a>
        <a href="product_form.php" class="btn-admin btn-admin-primary text-xs">
            <i class="fas fa-plus text-xs"></i> New Product
        </a>
    </div>
</div>

<!-- PRIMARY KPI CARDS (6-column responsive grid) -->
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6 gap-4 mb-6">
    
    <!-- Live Users -->
    <div class="bg-black rounded-xl p-4 border border-slate-200 shadow-xs flex flex-col justify-between hover:border-slate-300 transition-colors">
        <div class="flex items-center justify-between mb-3">
            <span class="text-xs font-semibold text-slate-200 uppercase tracking-wider">Live Visitors</span>
            <div class="w-8 h-8 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center text-xs relative">
                <i class="fas fa-users"></i>
                <span class="absolute -top-0.5 -right-0.5 flex h-2 w-2">
                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                    <span class="relative inline-flex rounded-full h-2 w-2 bg-emerald-500"></span>
                </span>
            </div>
        </div>
        <div>
            <div class="text-2xl font-bold text-[#35b224] leading-none" id="live-users-count"><?php echo $live_users_count; ?></div>
            <p class="text-[11px] text-slate-300 mt-1.5 flex items-center gap-1">
                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span> Active in last 5m
            </p>
        </div>
    </div>

    <!-- Revenue -->
    <div class="bg-white rounded-xl p-4 border border-slate-200 shadow-xs flex flex-col justify-between hover:border-slate-300 transition-colors">
        <div class="flex items-center justify-between mb-3">
            <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Total Revenue</span>
            <div class="w-8 h-8 rounded-lg bg-emerald-50 text-emerald-700 flex items-center justify-center text-xs">
                <i class="fas fa-rupee-sign"></i>
            </div>
        </div>
        <div>
            <div class="text-2xl font-bold text-slate-900 leading-none">₹<?php echo number_format($revenue, 0); ?></div>
            <p class="text-[11px] text-emerald-600 font-medium mt-1.5 flex items-center gap-1">
                <i class="fas fa-arrow-trend-up text-[10px]"></i> ₹<?php echo number_format($sales_today); ?> today
            </p>
        </div>
    </div>

    <!-- Total Orders -->
    <div class="bg-white rounded-xl p-4 border border-slate-200 shadow-xs flex flex-col justify-between hover:border-slate-300 transition-colors">
        <div class="flex items-center justify-between mb-3">
            <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Completed Orders</span>
            <div class="w-8 h-8 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center text-xs">
                <i class="fas fa-shopping-bag"></i>
            </div>
        </div>
        <div>
            <div class="text-2xl font-bold text-slate-900 leading-none"><?php echo $orders_count; ?></div>
            <p class="text-[11px] text-slate-400 mt-1.5">Processed orders</p>
        </div>
    </div>

    <!-- Pending Dispatch -->
    <div class="bg-white rounded-xl p-4 border border-slate-200 shadow-xs flex flex-col justify-between hover:border-slate-300 transition-colors relative overflow-hidden">
        <div class="flex items-center justify-between mb-3">
            <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Pending Orders</span>
            <div class="w-8 h-8 rounded-lg bg-amber-50 text-amber-600 flex items-center justify-center text-xs">
                <i class="fas fa-clock"></i>
            </div>
        </div>
        <div>
            <div class="text-2xl font-bold <?php echo $pending_orders > 0 ? 'text-amber-600' : 'text-slate-900'; ?> leading-none" id="pending-orders-count"><?php echo $pending_orders; ?></div>
            <p class="text-[11px] text-slate-400 mt-1.5">Needs fulfillment</p>
        </div>
        <?php if($pending_orders > 0): ?>
            <div class="absolute top-0 right-0 w-1 h-full bg-amber-500"></div>
        <?php endif; ?>
    </div>

    <!-- Active Products -->
    <div class="bg-white rounded-xl p-4 border border-slate-200 shadow-xs flex flex-col justify-between hover:border-slate-300 transition-colors">
        <div class="flex items-center justify-between mb-3">
            <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Active Products</span>
            <div class="w-8 h-8 rounded-lg bg-indigo-50 text-indigo-600 flex items-center justify-center text-xs">
                <i class="fas fa-boxes-stacked"></i>
            </div>
        </div>
        <div>
            <div class="text-2xl font-bold text-slate-900 leading-none"><?php echo $products_count; ?></div>
            <p class="text-[11px] text-slate-400 mt-1.5">Listed in catalog</p>
        </div>
    </div>

    <!-- Newsletter Subscribers -->
    <div class="bg-white rounded-xl p-4 border border-slate-200 shadow-xs flex flex-col justify-between hover:border-slate-300 transition-colors">
        <div class="flex items-center justify-between mb-3">
            <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Subscribers</span>
            <div class="w-8 h-8 rounded-lg bg-purple-50 text-purple-600 flex items-center justify-center text-xs">
                <i class="fas fa-envelope-open-text"></i>
            </div>
        </div>
        <div>
            <div class="text-2xl font-bold text-slate-900 leading-none"><?php echo $subscribers_count; ?></div>
            <p class="text-[11px] text-slate-400 mt-1.5">Active readership</p>
        </div>
    </div>

</div>

<!-- CHARTS SECTION -->
<div class="grid grid-cols-1 xl:grid-cols-3 gap-6 mb-6">
    
    <!-- Sales Revenue Stream Line Chart -->
    <div class="xl:col-span-2 bg-white rounded-xl p-5 border border-slate-200 shadow-xs">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3 mb-5">
            <div>
                <h3 class="font-bold text-base text-slate-900">Revenue Stream</h3>
                <p class="text-xs text-slate-400">Daily sales trends over the selected period</p>
            </div>
            <div class="flex items-center gap-1.5 bg-slate-100 p-1 rounded-lg">
                <a href="index.php?range=7" class="px-2.5 py-1 text-xs font-semibold rounded-md transition-colors <?php echo $days == 7 ? 'bg-white text-slate-900 shadow-2xs' : 'text-slate-500 hover:text-slate-800'; ?>">7 Days</a>
                <a href="index.php?range=15" class="px-2.5 py-1 text-xs font-semibold rounded-md transition-colors <?php echo $days == 15 ? 'bg-white text-slate-900 shadow-2xs' : 'text-slate-500 hover:text-slate-800'; ?>">15 Days</a>
                <a href="index.php?range=30" class="px-2.5 py-1 text-xs font-semibold rounded-md transition-colors <?php echo $days == 30 ? 'bg-white text-slate-900 shadow-2xs' : 'text-slate-500 hover:text-slate-800'; ?>">30 Days</a>
            </div>
        </div>
        <div class="h-64 relative">
            <canvas id="salesChart"></canvas>
        </div>
    </div>

    <!-- Category Performance Doughnut Chart -->
    <div class="bg-white rounded-xl p-5 border border-slate-200 shadow-xs flex flex-col">
        <div class="flex justify-between items-start mb-4">
            <div>
                <h3 class="font-bold text-base text-slate-900">Category Share</h3>
                <p class="text-xs text-slate-400" id="category-subtitle">Revenue distribution by category</p>
            </div>
            <div id="chart-toggle" class="flex bg-slate-100 p-1 rounded-lg">
                <button onclick="toggleCatChart('revenue')" id="btn-cat-rev" class="px-2.5 py-1 text-xs font-semibold rounded-md bg-white text-slate-900 shadow-2xs transition-all">Rev</button>
                <button onclick="toggleCatChart('count')" id="btn-cat-count" class="px-2.5 py-1 text-xs font-semibold rounded-md text-slate-500 hover:text-slate-800 transition-all">Qty</button>
            </div>
        </div>
        <div class="h-56 relative flex-1">
            <canvas id="categoryChart"></canvas>
        </div>
    </div>

</div>

<!-- INTELLIGENCE INSIGHTS (4 secondary metric cards) -->
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
    
    <!-- Average Order Value -->
    <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-xs">
        <div class="flex items-center gap-3 mb-2">
            <div class="w-8 h-8 rounded-lg bg-indigo-50 text-indigo-600 flex items-center justify-center text-xs">
                <i class="fas fa-calculator"></i>
            </div>
            <div>
                <div class="text-[11px] font-semibold text-slate-500 uppercase tracking-wider">Avg Order Value</div>
                <div class="text-lg font-bold text-slate-900">₹<?php echo number_format($aov, 0); ?></div>
            </div>
        </div>
        <p class="text-xs text-slate-500">Across <span class="font-semibold text-slate-700"><?php echo $orders_count; ?></span> completed orders</p>
    </div>

    <!-- Repeat Customer Rate -->
    <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-xs">
        <div class="flex items-center gap-3 mb-2">
            <div class="w-8 h-8 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center text-xs">
                <i class="fas fa-repeat"></i>
            </div>
            <div>
                <div class="text-[11px] font-semibold text-slate-500 uppercase tracking-wider">Repeat Buyer Rate</div>
                <div class="text-lg font-bold text-slate-900"><?php echo number_format($repeat_rate, 1); ?>%</div>
            </div>
        </div>
        <p class="text-xs text-slate-500"><span class="font-semibold text-slate-700"><?php echo $repeat_customers; ?></span> customers ordered multiple times</p>
    </div>

    <!-- Abandoned Carts -->
    <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-xs">
        <div class="flex items-center gap-3 mb-2">
            <div class="w-8 h-8 rounded-lg bg-amber-50 text-amber-600 flex items-center justify-center text-xs">
                <i class="fas fa-shopping-basket"></i>
            </div>
            <div>
                <div class="text-[11px] font-semibold text-slate-500 uppercase tracking-wider">Abandoned Carts</div>
                <div class="text-lg font-bold text-slate-900"><?php echo $abandoned_count; ?></div>
            </div>
        </div>
        <p class="text-xs text-slate-500"><a href="abandoned_carts.php" class="text-emerald-600 font-semibold hover:underline">Send recovery reminders &rarr;</a></p>
    </div>

    <!-- Affiliate & Promo Value -->
    <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-xs">
        <div class="flex items-center gap-3 mb-2">
            <div class="w-8 h-8 rounded-lg bg-purple-50 text-purple-600 flex items-center justify-center text-xs">
                <i class="fas fa-handshake"></i>
            </div>
            <div>
                <div class="text-[11px] font-semibold text-slate-500 uppercase tracking-wider">Affiliate Sales</div>
                <div class="text-lg font-bold text-slate-900">₹<?php echo number_format($affiliate_revenue, 0); ?></div>
            </div>
        </div>
        <p class="text-xs text-slate-500">Coupons saved customers <span class="font-semibold text-slate-700">₹<?php echo number_format($coupon_savings, 0); ?></span></p>
    </div>

</div>

<!-- MAIN OPERATIONAL GRID (Recent Orders + Pulse) -->
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    
    <!-- LEFT 2 COLUMNS: RECENT ORDERS TABLE -->
    <div class="lg:col-span-2">
        <div class="bg-white rounded-xl border border-slate-200 shadow-xs overflow-hidden">
            <div class="p-4 border-b border-slate-100 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3">
                <div class="flex items-center gap-3">
                    <h3 class="font-bold text-base text-slate-900">Recent Orders</h3>
                    <!-- Bulk Action Controls -->
                    <div id="bulk-actions" class="hidden flex items-center gap-2">
                        <select id="bulk-status-select" class="bg-slate-50 border border-slate-200 text-slate-700 text-xs font-semibold rounded-lg px-2.5 py-1.5 outline-none focus:border-[#004f42]">
                            <option value="">Apply Status...</option>
                            <option value="pending">Pending</option>
                            <option value="confirmed">Confirmed</option>
                            <option value="shipped">Shipped</option>
                            <option value="delivered">Delivered</option>
                            <option value="cancelled">Cancelled</option>
                        </select>
                        <button onclick="applyBulkStatus()" class="btn-admin btn-admin-primary btn-admin-sm">
                            Apply
                        </button>
                    </div>
                </div>
                <a href="orders.php" class="text-xs font-semibold text-emerald-700 hover:text-emerald-800 flex items-center gap-1">
                    View All Orders <i class="fas fa-arrow-right text-[10px]"></i>
                </a>
            </div>

            <div class="overflow-x-auto">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th class="w-10 text-center"><input type="checkbox" id="select-all" class="w-4 h-4 rounded border-slate-300 text-emerald-600 focus:ring-emerald-500 cursor-pointer" onclick="toggleSelectAll()"></th>
                            <th>Order</th>
                            <th>Customer</th>
                            <th>Amount</th>
                            <th>Status</th>
                            <th class="text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(empty($recent_orders)): ?>
                            <tr>
                                <td colspan="6" class="p-8 text-center text-slate-400 text-xs">
                                    No recent orders found.
                                </td>
                            </tr>
                        <?php else: foreach ($recent_orders as $o): ?>
                            <tr class="hover:bg-slate-50/70 transition-colors">
                                <td class="text-center">
                                    <input type="checkbox" name="selected_orders[]" value="<?php echo $o['id']; ?>" class="order-checkbox w-4 h-4 rounded border-slate-300 text-emerald-600 focus:ring-emerald-500 cursor-pointer" onclick="updateBulkState()">
                                </td>
                                <td>
                                    <span class="font-semibold text-slate-900 block leading-tight">#<?php echo $o['order_number'] ?: $o['id']; ?></span>
                                    <span class="text-[11px] text-slate-400"><?php echo date('M d, H:i', strtotime($o['created_at'])); ?></span>
                                </td>
                                <td>
                                    <?php 
                                        $addr = json_decode($o['shipping_address'] ?? '{}', true);
                                        $custName = $addr['name'] ?? 'Guest Customer';
                                    ?>
                                    <span class="font-medium text-slate-800 block truncate max-w-[160px]"><?php echo htmlspecialchars($custName); ?></span>
                                    <span class="text-[11px] text-slate-400 capitalize"><?php echo htmlspecialchars($o['payment_method']); ?></span>
                                </td>
                                <td class="font-bold text-slate-900">
                                    ₹<?php echo number_format($o['total'], 0); ?>
                                </td>
                                <td>
                                    <div class="relative status-dropdown-container inline-block">
                                        <button onclick="toggleStatusDropdown(this, event)" class="status-btn px-2.5 py-1 rounded-md text-[11px] font-semibold capitalize flex items-center gap-1.5 transition-colors <?php echo get_status_color($o['order_status']); ?>">
                                            <span><?php echo str_replace('_', ' ', $o['order_status']); ?></span>
                                            <i class="fas fa-chevron-down text-[8px] opacity-60"></i>
                                        </button>
                                        
                                        <!-- Status Menu Popover -->
                                        <div class="status-menu hidden absolute left-0 top-full mt-1 w-36 bg-white rounded-xl shadow-lg border border-slate-200 z-50 overflow-hidden py-1">
                                            <?php 
                                            $statuses = ['pending', 'confirmed', 'shipped', 'delivered', 'cancelled'];
                                            foreach($statuses as $s): 
                                                if($s === $o['order_status']) continue;
                                            ?>
                                            <button onclick="updateOrderStatus(<?php echo $o['id']; ?>, '<?php echo $s; ?>', this)" class="w-full text-left px-3 py-1.5 text-xs font-medium text-slate-700 hover:bg-slate-50 transition-colors capitalize">
                                                <?php echo $s; ?>
                                            </button>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                </td>
                                <td class="text-right">
                                    <a href="orders.php?id=<?php echo $o['id']; ?>" class="w-7 h-7 rounded-lg border border-slate-200 inline-flex items-center justify-center text-slate-500 hover:text-slate-900 hover:bg-slate-100 transition-colors" title="View details">
                                        <i class="fas fa-arrow-right text-[10px]"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- RIGHT 1 COLUMN: OPERATIONAL ACTIVITY PULSE -->
    <div class="space-y-4">
        
        <!-- Today's Market Velocity Banner -->
        <div class="bg-gradient-to-br from-[#004f42] to-[#00362c] text-white p-5 rounded-xl shadow-xs relative overflow-hidden">
            <div class="relative z-10">
                <span class="text-[10px] font-bold uppercase tracking-wider text-emerald-300">Today's Revenue</span>
                <div class="text-3xl font-extrabold mt-1">₹<span id="sales-today-count"><?php echo number_format($sales_today); ?></span></div>
                <p class="text-xs text-white/70 mt-1">Live synchronized sales tracking</p>
            </div>
            <i class="fas fa-bolt absolute -right-3 -bottom-3 text-white/10 text-7xl pointer-events-none"></i>
        </div>

        <!-- Pending Order Alert (if any) -->
        <?php if($pending_orders > 0): ?>
        <div class="p-3.5 bg-amber-50 rounded-xl border border-amber-200 flex items-start gap-3">
            <div class="w-8 h-8 rounded-lg bg-amber-500 text-white flex items-center justify-center shrink-0 text-xs mt-0.5">
                <i class="fas fa-exclamation"></i>
            </div>
            <div class="flex-1">
                <div class="text-xs font-bold text-amber-900">Action Required</div>
                <p class="text-xs text-amber-800 mt-0.5"><?php echo $pending_orders; ?> pending orders waiting to be packed and dispatched.</p>
                <a href="orders.php?status_filter=pending" class="text-xs font-semibold text-amber-900 hover:underline mt-1.5 inline-block">Process Orders &rarr;</a>
            </div>
        </div>
        <?php endif; ?>

        <!-- Top Performing Products -->
        <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-xs">
            <h4 class="text-xs font-bold uppercase tracking-wider text-slate-500 mb-3">Top Selling Snacks</h4>
            <?php if(empty($top_selling)): ?>
                <p class="text-xs text-slate-400 py-2">No sales data recorded yet.</p>
            <?php else: ?>
                <div class="space-y-3">
                    <?php foreach($top_selling as $ts): ?>
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 bg-slate-100 rounded-lg overflow-hidden shrink-0 border border-slate-200 p-0.5">
                            <img src="<?php echo get_url($ts['image']); ?>" class="w-full h-full object-cover rounded-md" alt="<?php echo htmlspecialchars($ts['name']); ?>">
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="text-xs font-semibold text-slate-900 truncate"><?php echo htmlspecialchars($ts['name']); ?></div>
                            <div class="text-[11px] text-slate-400">₹<?php echo number_format($ts['price']); ?> &bull; <span class="text-emerald-700 font-semibold"><?php echo $ts['total_sold']; ?> sold</span></div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- High-Value Customers -->
        <?php if(!empty($top_customers)): ?>
        <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-xs">
            <h4 class="text-xs font-bold uppercase tracking-wider text-slate-500 mb-3">Top Customers</h4>
            <div class="space-y-3">
                <?php foreach($top_customers as $tc): ?>
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 bg-indigo-50 rounded-lg flex items-center justify-center text-indigo-700 font-bold text-xs border border-indigo-100 uppercase shrink-0">
                        <?php echo substr($tc['name'] ?: 'C', 0, 1); ?>
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="text-xs font-semibold text-slate-900 truncate"><?php echo htmlspecialchars($tc['name']); ?></div>
                        <div class="text-[11px] text-slate-400"><?php echo $tc['order_count']; ?> orders &bull; <span class="text-indigo-600 font-semibold">₹<?php echo number_format($tc['total_spent']); ?></span></div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Critical Low Stock Alerts -->
        <?php if(!empty($low_stock)): ?>
        <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-xs">
            <h4 class="text-xs font-bold uppercase tracking-wider text-slate-500 mb-3">Critical Low Stock</h4>
            <div class="space-y-2">
                <?php foreach($low_stock as $ls): ?>
                <div class="flex items-center justify-between p-2 rounded-lg bg-rose-50/60 border border-rose-100">
                    <div class="min-w-0 flex-1 pr-2">
                        <div class="text-xs font-semibold text-rose-950 truncate"><?php echo htmlspecialchars($ls['name']); ?></div>
                    </div>
                    <span class="bg-rose-600 text-white text-[10px] font-bold px-2 py-0.5 rounded-full shrink-0">
                        <?php echo $ls['stock']; ?> left
                    </span>
                </div>
                <?php endforeach; ?>
            </div>
            <a href="inventory.php" class="text-xs font-semibold text-rose-700 hover:underline mt-2.5 inline-block">Manage stock levels &rarr;</a>
        </div>
        <?php endif; ?>

        <!-- Recent Customer Reviews -->
        <?php if(!empty($recent_reviews)): ?>
        <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-xs">
            <h4 class="text-xs font-bold uppercase tracking-wider text-slate-500 mb-3">Recent Feedback</h4>
            <div class="space-y-3">
                <?php foreach($recent_reviews as $rr): ?>
                <div class="text-xs border-b border-slate-100 last:border-0 pb-2 last:pb-0">
                    <div class="flex items-center justify-between text-slate-400 text-[10px] mb-1">
                        <span class="font-semibold text-slate-700"><?php echo htmlspecialchars($rr['user_name']); ?></span>
                        <span><?php echo get_time_ago($rr['created_at']); ?></span>
                    </div>
                    <p class="text-slate-800 line-clamp-2 italic">"<?php echo htmlspecialchars($rr['comment']); ?>"</p>
                    <span class="text-[10px] text-slate-400 block mt-1">on <span class="text-emerald-700 font-medium"><?php echo htmlspecialchars($rr['prod_name']); ?></span></span>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

    </div>

</div>

<!-- Dispatch Modal -->
<div id="dispatchModal" class="fixed inset-0 bg-slate-900/50 backdrop-blur-xs hidden items-center justify-center z-[200] p-4">
    <div class="bg-white rounded-2xl p-6 sm:p-8 w-full max-w-md shadow-2xl border border-slate-200 anim-fade-in">
        <div class="flex items-center gap-3 mb-6">
            <div class="w-10 h-10 bg-blue-50 text-blue-600 rounded-xl flex items-center justify-center text-lg">
                <i class="fas fa-shipping-fast"></i>
            </div>
            <div>
                <h3 class="text-lg font-bold text-slate-900">Dispatch Order</h3>
                <p class="text-xs text-slate-400" id="dispatch-order-number">Order #</p>
            </div>
        </div>
        
        <form action="orders.php" method="POST" id="dispatch-form" class="space-y-4">
            <input type="hidden" name="order_id" id="dispatch-order-id">
            <input type="hidden" name="dispatch_order" value="1">
            
            <div>
                <label class="admin-label">Tracking Number (India Post / Carrier)</label>
                <input type="text" name="tracking_number" required placeholder="e.g. EB123456789IN" class="admin-input">
            </div>
            
            <div>
                <label class="admin-label">Dispatch Date</label>
                <input type="date" name="dispatch_date" id="dispatch-date" required value="<?php echo date('Y-m-d'); ?>" class="admin-input">
            </div>

            <div>
                <label class="admin-label">Tracking Note (Customer visible)</label>
                <textarea name="tracking_note" id="dispatch-note" placeholder="e.g. Package dispatched via India Post Speed Post." class="admin-textarea h-20 resize-none"></textarea>
            </div>

            <div class="flex gap-2.5 pt-2">
                <button type="button" onclick="closeDispatchModal()" class="flex-1 btn-admin btn-admin-secondary">Cancel</button>
                <button type="submit" class="flex-1 btn-admin btn-admin-primary" id="dispatch-btn">Mark Shipped</button>
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
    
    if (parent) {
        const all = document.querySelectorAll('.order-checkbox').length;
        parent.indeterminate = checked > 0 && checked < all;
        parent.checked = checked === all && all > 0;
    }

    if (actions) {
        if (checked > 0) {
            actions.classList.remove('hidden');
        } else {
            actions.classList.add('hidden');
        }
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
        ? await window.showConfirm(`Change status of ${selected.length} orders to "${status}"?`, {
            title: 'Bulk Status Update',
            type: 'warning',
            confirmText: 'Apply Status'
        })
        : confirm(`Change status of ${selected.length} orders to "${status}"?`);
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
            btn.innerHTML = `<span>${data.label}</span> <i class="fas fa-chevron-down text-[8px] opacity-60"></i>`;
            btn.className = `status-btn px-2.5 py-1 rounded-md text-[11px] font-semibold capitalize flex items-center gap-1.5 transition-colors ${getStatusColor(status)}`;
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
        case 'pending': return 'bg-amber-100 text-amber-800';
        case 'confirmed': return 'bg-indigo-100 text-indigo-800';
        case 'shipped': return 'bg-sky-100 text-sky-800';
        case 'delivered': return 'bg-emerald-100 text-emerald-800';
        case 'cancelled': return 'bg-rose-100 text-rose-800';
        default: return 'bg-slate-100 text-slate-800';
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
            location.reload();
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
    
    const gradient = salesCtx.createLinearGradient(0, 0, 0, 240);
    gradient.addColorStop(0, 'rgba(0, 79, 66, 0.16)');
    gradient.addColorStop(1, 'rgba(0, 79, 66, 0.00)');

    new Chart(salesCtx, {
        type: 'line',
        data: {
            labels: salesData.map(d => d.label),
            datasets: [{
                label: 'Revenue',
                data: salesData.map(d => d.value),
                borderColor: '#004f42',
                backgroundColor: gradient,
                borderWidth: 2.5,
                tension: 0.35,
                fill: true,
                pointBackgroundColor: '#ffffff',
                pointBorderColor: '#004f42',
                pointBorderWidth: 2,
                pointRadius: 4,
                pointHoverRadius: 6
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor: '#0f172a',
                    padding: 10,
                    cornerRadius: 8,
                    titleFont: { size: 11, weight: '600' },
                    bodyFont: { size: 13, weight: '700' },
                    callbacks: {
                        label: (ctx) => '₹' + ctx.raw.toLocaleString()
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: { color: '#f1f5f9' },
                    ticks: {
                        color: '#64748b',
                        font: { size: 10, weight: '500' },
                        callback: (val) => '₹' + (val >= 1000 ? (val / 1000) + 'k' : val)
                    }
                },
                x: {
                    grid: { display: false },
                    ticks: { 
                        color: '#64748b',
                        font: { size: 10, weight: '500' } 
                    }
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
                    '#004f42', '#24B25D', '#0284c7', '#f59e0b', '#8b5cf6', '#ec4899'
                ],
                borderWidth: 2,
                borderColor: '#ffffff',
                cutout: '72%'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        padding: 14,
                        usePointStyle: true,
                        boxWidth: 8,
                        font: { size: 11, weight: '500' },
                        color: '#475569'
                    }
                },
                tooltip: {
                    backgroundColor: '#0f172a',
                    padding: 10,
                    cornerRadius: 8,
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
        const sub = document.getElementById('category-subtitle');

        if (type === 'revenue') {
            categoryChart.data.labels = catRevData.map(d => d.name);
            categoryChart.data.datasets[0].data = catRevData.map(d => d.revenue);
            categoryChart.data.datasets[0].label = 'Revenue';
            btnRev.className = 'px-2.5 py-1 text-xs font-semibold rounded-md bg-white text-slate-900 shadow-2xs transition-all';
            btnCount.className = 'px-2.5 py-1 text-xs font-semibold rounded-md text-slate-500 hover:text-slate-800 transition-all';
            sub.textContent = 'Revenue distribution by category';
        } else {
            categoryChart.data.labels = catCountData.map(d => d.name);
            categoryChart.data.datasets[0].data = catCountData.map(d => d.count);
            categoryChart.data.datasets[0].label = 'Quantity';
            btnCount.className = 'px-2.5 py-1 text-xs font-semibold rounded-md bg-white text-slate-900 shadow-2xs transition-all';
            btnRev.className = 'px-2.5 py-1 text-xs font-semibold rounded-md text-slate-500 hover:text-slate-800 transition-all';
            sub.textContent = 'Product quantity distribution by category';
        }
        categoryChart.update();
    };
    categoryChart.data.datasets[0].label = 'Revenue';
});

// Real-time Stats Refresher
async function refreshLiveStats() {
    try {
        const response = await fetch('../api/get_live_stats.php');
        const data = await response.json();
        
        if (data.success) {
            updateElementWithAnim('live-users-count', data.live_users);
            updateElementWithAnim('sales-today-count', data.sales_today);
            updateElementWithAnim('pending-orders-count', data.pending_orders);
            
            if (data.pending_orders > 0) {
                document.title = `(${data.pending_orders}) Dashboard | Driyum Admin`;
            } else {
                document.title = `Dashboard | Driyum Admin`;
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
        el.textContent = value;
    }
}

setInterval(refreshLiveStats, 10000);
document.addEventListener('DOMContentLoaded', refreshLiveStats);
</script>
<?php include 'includes/footer.php'; ?>
