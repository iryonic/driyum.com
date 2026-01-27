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
$low_stock = fetch_all("SELECT name, stock, image FROM products WHERE stock <= 5 AND is_active = 1 LIMIT 3");
$recent_reviews = fetch_all("SELECT r.*, u.name as user_name, p.name as prod_name FROM reviews r JOIN users u ON r.user_id = u.id JOIN products p ON r.product_id = p.id ORDER BY r.created_at DESC LIMIT 3");

// Business Intelligence Stats
$sales_today = fetch_one("SELECT SUM(total) as t FROM orders WHERE DATE(created_at) = CURDATE() AND order_status != 'cancelled'")['t'] ?? 0;
$top_selling = fetch_all("SELECT p.name, SUM(oi.quantity) as total_sold, p.image, p.price FROM order_items oi JOIN products p ON oi.product_id = p.id GROUP BY p.id ORDER BY total_sold DESC LIMIT 3");

// Chart Data: Last 7 Days Sales
$sales_data = [];
for($i = 6; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $label = date('D', strtotime($date));
    $val = fetch_one("SELECT SUM(total) as t FROM orders WHERE DATE(created_at) = ? AND order_status != 'cancelled'", [$date])['t'] ?? 0;
    $sales_data[] = ['label' => $label, 'value' => (float)$val];
}

// Category Distribution
$cat_data = fetch_all("SELECT c.name, COUNT(p.id) as count FROM categories c LEFT JOIN products p ON c.id = p.category_id GROUP BY c.id");
?>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

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

<!-- BUSINESS INTELLIGENCE SECTION -->
<div class="grid grid-cols-1 xl:grid-cols-3 gap-8 mb-12">
    <!-- Sales Chart -->
    <div class="xl:col-span-2 bg-white p-10 rounded-[40px] shadow-sm border border-gray-100 anim-up">
        <div class="flex justify-between items-center mb-8">
            <div>
                <h3 class="font-bold text-2xl font-['Fredoka'] text-gray-900">Revenue Stream</h3>
                <p class="text-xs text-gray-400 font-bold uppercase tracking-widest mt-1">Performance over the last 7 days</p>
            </div>
            <div class="flex items-center gap-2 bg-gray-50 px-4 py-2 rounded-full">
                <span class="w-2 h-2 rounded-full bg-[#19DC7E]"></span>
                <span class="text-[10px] font-black uppercase tracking-widest text-gray-400">Live Velocity</span>
            </div>
        </div>
        <div class="h-80 relative">
            <canvas id="salesChart"></canvas>
        </div>
    </div>

    <!-- Category Performance -->
    <div class="bg-white p-10 rounded-[40px] shadow-sm border border-gray-100 anim-up" style="animation-delay: 100ms">
        <h3 class="font-bold text-2xl font-['Fredoka'] text-gray-900 mb-2">Category Spread</h3>
        <p class="text-xs text-gray-400 font-bold uppercase tracking-widest mb-8">Product distribution by sector</p>
        <div class="h-64 flex items-center justify-center">
            <canvas id="categoryChart"></canvas>
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
                                <div class="relative inline-block status-dropdown-container">
                                    <button onclick="toggleStatusDropdown(this, event)" class="status-btn px-3 py-1.5 rounded-xl text-[10px] font-black uppercase tracking-widest flex items-center gap-2 shadow-sm transition-all hover:scale-105 active:scale-95 <?php echo get_status_color($o['order_status']); ?>">
                                        <?php echo str_replace('_', ' ', $o['order_status']); ?> 
                                        <i class="fas fa-chevron-down opacity-50 text-[10px] transition-transform duration-300"></i>
                                    </button>
                                    
                                    <!-- Dropdown Menu (Standardized with Orders Page) -->
                                    <div class="status-menu absolute left-0 top-full mt-2 w-48 bg-white rounded-2xl shadow-2xl border border-gray-100 py-3 hidden z-50 overflow-hidden transform origin-top-left transition-all">
                                        <a href="javascript:void(0)" onclick="updateOrderStatus(<?php echo $o['id']; ?>, 'pending', this)" class="block px-4 py-2 hover:bg-yellow-50 text-yellow-600 font-bold text-[10px] uppercase tracking-widest transition-colors">Pending</a>
                                        <a href="javascript:void(0)" onclick="updateOrderStatus(<?php echo $o['id']; ?>, 'confirmed', this)" class="block px-4 py-2 hover:bg-indigo-50 text-indigo-600 font-bold text-[10px] uppercase tracking-widest transition-colors">Confirm</a>
                                        <a href="javascript:void(0)" onclick="openDispatchModal(<?php echo $o['id']; ?>, '<?php echo $o['id']; ?>')" class="block px-4 py-2 hover:bg-blue-50 text-blue-600 font-bold text-[10px] uppercase tracking-widest transition-colors">Ship / Dispatch</a>
                                        <a href="javascript:void(0)" onclick="updateOrderStatus(<?php echo $o['id']; ?>, 'delivered', this)" class="block px-4 py-2 hover:bg-green-50 text-green-600 font-bold text-[10px] uppercase tracking-widest transition-colors">Delivered</a>
                                        <div class="border-t border-gray-50 my-1"></div>
                                        <a href="javascript:void(0)" onclick="updateOrderStatus(<?php echo $o['id']; ?>, 'cancelled', this)" class="block px-4 py-2 hover:bg-red-50 text-red-600 font-bold text-[10px] uppercase tracking-widest transition-colors">Cancel</a>
                                    </div>
                                </div>
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
        
        <!-- Live Pulse & Intelligence -->
        <div class="bg-white p-8 rounded-[35px] shadow-sm border border-gray-100 anim-up" style="animation-delay: 100ms">
                <h3 class="font-bold text-xl font-['Fredoka'] text-gray-900 mb-6 flex items-center gap-2">
                    <span class="relative flex h-3 w-3">
                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-red-400 opacity-75"></span>
                        <span class="relative inline-flex rounded-full h-3 w-3 bg-red-500"></span>
                    </span>
                    Activity Pulse
                </h3>

                <div class="space-y-6">
                    <!-- Sales Today Indicator -->
                    <div class="p-6 bg-gradient-to-r from-green-600 to-[#19DC7E] rounded-[30px] text-white shadow-lg relative overflow-hidden group mb-2">
                        <div class="relative z-10">
                            <div class="text-[10px] font-black uppercase tracking-widest opacity-70 mb-1">Market Velocity</div>
                            <div class="text-3xl font-black fredoka">₹<?php echo number_format($sales_today); ?></div>
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
                        <div class="w-12 h-12 bg-gray-100 rounded-xl overflow-hidden border border-gray-100 p-1 group-hover:border-[#19DC7E] transition-colors">
                            <img src="<?php echo get_url($ts['image']); ?>" class="w-full h-full object-cover rounded-lg">
                        </div>
                        <div class="flex-1">
                            <div class="text-xs font-bold text-gray-900 leading-tight"><?php echo $ts['name']; ?></div>
                            <div class="text-[10px] text-gray-400 font-medium">₹<?php echo number_format($ts['price']); ?> • <span class="text-[#19DC7E] font-black"><?php echo $ts['total_sold']; ?> Sold</span></div>
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

<!-- Dispatch Modal -->
<div id="dispatchModal" class="fixed inset-0 bg-black/60 backdrop-blur-sm hidden items-center justify-center z-[200]">
    <div class="bg-white rounded-[40px] p-10 w-full max-w-lg shadow-2xl anim-up">
        <div class="flex items-center gap-4 mb-8">
            <div class="w-16 h-16 bg-blue-50 text-blue-600 rounded-2xl flex items-center justify-center text-2xl">
                <i class="fas fa-shipping-fast"></i>
            </div>
            <div>
                <h3 class="text-3xl font-black fredoka text-gray-900">Dispatch Order</h3>
                <p class="text-gray-400 font-medium" id="dispatch-order-number">ORD-000000</p>
            </div>
        </div>
        
        <form action="orders.php" method="POST" id="dispatch-form" class="space-y-6">
            <input type="hidden" name="order_id" id="dispatch-order-id">
            <input type="hidden" name="dispatch_order" value="1">
            
            <div class="space-y-2">
                <label class="text-[10px] font-black uppercase tracking-widest text-gray-400 ml-4">Tracking Number</label>
                <input type="text" name="tracking_number" required placeholder="Paste tracking ID here..." class="w-full bg-gray-50 border-2 border-transparent focus:border-[#19DC7E] focus:bg-white rounded-[24px] px-6 py-4 outline-none transition-all font-bold">
            </div>
            
            <div class="space-y-2">
                <label class="text-[10px] font-black uppercase tracking-widest text-gray-400 ml-4">Dispatch Date</label>
                <input type="date" name="dispatch_date" required value="<?php echo date('Y-m-d'); ?>" class="w-full bg-gray-50 border-2 border-transparent focus:border-[#19DC7E] focus:bg-white rounded-[24px] px-6 py-4 outline-none transition-all font-bold">
            </div>

            <div class="flex gap-4 pt-4">
                <button type="button" onclick="closeDispatchModal()" class="flex-1 bg-gray-100 text-gray-500 py-5 rounded-[24px] font-black uppercase tracking-widest hover:bg-gray-200 transition-all">Cancel</button>
                <button type="submit" class="flex-1 bg-black text-white py-5 rounded-[24px] font-black uppercase tracking-widest shadow-xl hover:bg-[#19DC7E] hover:text-black transition-all" id="dispatch-btn">Dispatch Now</button>
            </div>
        </form>
    </div>
</div>

<script>
// Dashboard Status Management Logic
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
        case 'delivered': return 'bg-[#19DC7E] text-black';
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
                borderColor: '#19DC7E',
                backgroundColor: 'rgba(25, 220, 126, 0.1)',
                borderWidth: 4,
                tension: 0.4,
                fill: true,
                pointBackgroundColor: '#fff',
                pointBorderColor: '#19DC7E',
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
    const catData = <?php echo json_encode($cat_data); ?>;
    
    new Chart(catCtx, {
        type: 'doughnut',
        data: {
            labels: catData.map(d => d.name),
            datasets: [{
                data: catData.map(d => d.count),
                backgroundColor: [
                    '#19DC7E', '#0ea5e9', '#f59e0b', '#ec4899', '#8b5cf6', '#6366f1'
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
                }
            }
        }
    });
});
</script>
<?php include 'includes/footer.php'; ?>
