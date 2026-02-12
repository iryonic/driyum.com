<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: " . get_url('login.php'));
    exit;
}

$user = fetch_one("SELECT * FROM users WHERE id = ?", [$_SESSION['user_id']]);

$query_orders = "SELECT o.*, (SELECT COUNT(*) FROM order_items WHERE order_id = o.id) as item_count 
                 FROM orders o 
                 WHERE o.user_id = ? 
                 ORDER BY o.created_at DESC";

$pagination = get_pagination_data($query_orders, [$_SESSION['user_id']], 5);
$orders = $pagination['records'];

// Fetch all for stats
$all_orders = fetch_all("SELECT total, order_status FROM orders WHERE user_id = ?", [$_SESSION['user_id']]);
$total_spent = 0;
foreach($all_orders as $o) {
    if($o['order_status'] != 'cancelled') $total_spent += $o['total'];
}

$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php 
    $page_title = 'My Account';
    $page_description = 'Manage your DRIYUM profile, view order history, and track your active snack shipments.';
    include 'includes/head.php'; 
    ?>
    <style>
        .nav-link-active {
            background: #24B25D !important;
            color: #000 !important;
            box-shadow: 0 10px 20px rgba(25, 220, 126, 0.2);
        }
        .nav-link-active i {
            color: #000 !important;
        }
    </style>
</head>
<body class="bg-[#FFFEDC] font-sans">

    <?php include 'includes/header.php'; ?>

    <div class="container mx-auto px-6 py-12">
        
        <!-- DASHBOARD HEADER -->
        <div class="bg-gray-900 rounded-[40px] p-8 md:p-12 text-white mb-12 relative overflow-hidden anim-up">
            <div class="relative z-10 flex flex-col md:flex-row justify-between items-center gap-8">
                <div class="flex items-center gap-6">
                    <div class="w-16 h-16 md:w-24 md:h-24 bg-[#24B25D] rounded-[30px] flex items-center justify-center text-black font-black text-3xl md:text-4xl shadow-xl">
                        <?php echo strtoupper(substr($user['name'], 0, 1)); ?>
                    </div>
                    <div>
                        <h1 class="text-3xl md:text-5xl font-heading font-black mb-2">Hello, <?php echo explode(' ', $user['name'])[0]; ?>!</h1>
                        <p class="text-gray-400 font-bold uppercase tracking-widest text-[10px]">Premium Member • Since <?php echo date('M Y', strtotime($user['created_at'])); ?></p>
                    </div>
                </div>
                
                <div class="flex gap-4 md:gap-12">
                    <div class="text-center group">
                        <div class="text-3xl md:text-4xl font-heading font-black text-[#24B25D] group-hover:scale-110 transition-transform">₹<?php echo number_format($total_spent); ?></div>
                        <div class="text-[10px] uppercase font-black text-gray-500 tracking-widest mt-1">Total Spent</div>
                    </div>
                    <div class="w-px h-12 bg-white/10 hidden md:block"></div>
                    <div class="text-center group">
                        <div class="text-3xl md:text-4xl font-heading font-black text-[#24B25D] group-hover:scale-110 transition-transform"><?php echo count($all_orders); ?></div>
                        <div class="text-[10px] uppercase font-black text-gray-500 tracking-widest mt-1">Purchases</div>
                    </div>
                </div>
            </div>
            
            <!-- Decor -->
            <div class="absolute -right-20 -bottom-20 w-64 h-64 bg-[#24B25D]/10 rounded-full blur-3xl"></div>
            <div class="absolute -left-10 -top-10 w-40 h-40 bg-white/5 rounded-full blur-2xl"></div>
        </div>

        <div class="flex flex-col lg:flex-row gap-10 items-start">
            
            <!-- ENHANCED SIDEBAR NAV -->
            <div class="w-full lg:w-80 shrink-0 space-y-6 relative lg:sticky lg:top-28">
                
                <!-- Profile Summary Card in Sidebar -->
                <div class="bg-white rounded-[32px] p-8 shadow-sm border border-gray-100 text-center relative overflow-hidden group">
                    <div class="absolute top-0 left-0 w-full h-2 bg-gradient-to-r from-[#24B25D] to-[#14c06d]"></div>
                    <div class="w-20 h-20 bg-gray-50 rounded-3xl mx-auto mb-4 flex items-center justify-center text-2xl group-hover:bg-[#24B25D] group-hover:text-white transition-all duration-500">
                        <i class="far fa-user"></i>
                    </div>
                    <h3 class="font-heading font-black text-xl text-gray-900"><?php echo $user['name']; ?></h3>
                    <p class="text-sm text-gray-400 font-medium mb-6"><?php echo $user['email']; ?></p>
                    <div class="flex justify-center gap-2">
                         <span class="px-3 py-1 bg-gray-50 text-gray-400 text-[9px] font-black uppercase tracking-tighter rounded-full border border-gray-100">Classic Account</span>
                         <?php if(is_admin()): ?><span class="px-3 py-1 bg-black text-[#24B25D] text-[9px] font-black uppercase tracking-tighter rounded-full">Admin Access</span><?php endif; ?>
                    </div>
                </div>

                <!-- Main Menu -->
                <div class="bg-white rounded-[32px] p-4 shadow-sm border border-gray-100 space-y-2">
                    <p class="text-[10px] font-black text-gray-300 uppercase tracking-[0.2em] mb-4 ml-4 mt-2 text-center lg:text-left">Navigation</p>
                    
                    <a href="<?php echo get_url('account.php'); ?>" class="flex items-center gap-4 px-6 py-4 rounded-2xl font-black transition-all group <?php echo $current_page == 'account.php' ? 'nav-link-active' : 'text-gray-500 hover:bg-gray-50 hover:text-black'; ?>">
                        <i class="fas fa-th-large <?php echo $current_page == 'account.php' ? 'text-black' : 'text-gray-300 group-hover:text-[#24B25D]'; ?> transition-colors w-5"></i> Dashboard
                    </a>

                    <a href="<?php echo get_url('settings.php'); ?>" class="flex items-center gap-4 px-6 py-4 rounded-2xl text-gray-500 hover:bg-gray-50 hover:text-black font-black transition-all group">
                        <i class="fas fa-cog text-gray-300 group-hover:text-[#24B25D] transition-colors w-5"></i> Settings
                    </a>
                    
                    <a href="<?php echo get_url('shop.php'); ?>" class="flex items-center gap-4 px-6 py-4 rounded-2xl text-gray-500 hover:bg-gray-50 hover:text-black font-black transition-all group">
                        <i class="fas fa-shopping-bag text-gray-300 group-hover:text-[#24B25D] transition-colors w-5"></i> Shop
                    </a>
                    
                    <a href="<?php echo get_url('track.php'); ?>" class="flex items-center gap-4 px-6 py-4 rounded-2xl text-gray-500 hover:bg-gray-50 hover:text-black font-black transition-all group">
                        <i class="fas fa-truck-fast text-gray-300 group-hover:text-[#24B25D] transition-colors w-5"></i> Track Order
                    </a>

                    <a href="<?php echo get_url('wishlist.php'); ?>" class="flex items-center gap-4 px-6 py-4 rounded-2xl text-gray-500 hover:bg-gray-50 hover:text-black font-black transition-all group">
                        <i class="fas fa-heart text-gray-300 group-hover:text-red-500 transition-colors w-5"></i> Wishlist
                    </a>

                    <?php 
                        // Affiliate Check to show menu item
                        $is_affiliate = fetch_one("SELECT id FROM affiliates WHERE user_id = ? AND status = 'active' AND is_approved = 1", [$_SESSION['user_id']]);
                        if($is_affiliate): 
                    ?>
                    <a href="<?php echo get_url('affiliate-dashboard.php'); ?>" class="flex items-center gap-4 px-6 py-4 rounded-2xl text-gray-500 hover:bg-gray-50 hover:text-black font-black transition-all group">
                        <i class="fas fa-bolt text-gray-300 group-hover:text-[#24B25D] transition-colors w-5"></i> Creator Hub
                    </a>
                    <?php endif; ?>

                    <div class="h-px bg-gray-50 my-4 mx-4"></div>
                    
                    <p class="text-[10px] font-black text-gray-300 uppercase tracking-[0.2em] mb-2 ml-4 text-center lg:text-left">Session</p>
                    <a href="<?php echo get_url('logout.php'); ?>" class="flex items-center gap-4 px-6 py-4 rounded-2xl text-red-400 hover:bg-red-50 font-black transition-all group">
                        <i class="fas fa-sign-out-alt text-red-200 group-hover:text-red-500 transition-colors w-5"></i> Logout
                    </a>
                </div>
                
                <?php if(is_admin()): ?>
                <a href="<?php echo get_url('admin/'); ?>" class="block bg-gray-900 text-white rounded-[32px] shadow-2xl hover:scale-[1.03] transition-all duration-500 relative overflow-hidden group p-1">
                    <div class="bg-gray-900 border border-white/10 rounded-[30px] p-8 relative z-10">
                         <div class="w-12 h-12 bg-[#24B25D] text-black rounded-2xl flex items-center justify-center mb-6 shadow-lg shadow-green-500/20 group-hover:rotate-12 transition-transform">
                            <i class="fas fa-crown text-xl"></i>
                         </div>
                         <h3 class="font-heading font-black text-2xl mb-1">Store Admin</h3>
                         <p class="text-gray-500 text-[10px] font-black uppercase tracking-widest group-hover:text-[#24B25D] transition-colors">Complete Management Access</p>
                    </div>
                </a>
                <?php endif; ?>
            </div>

            <!-- MAIN CONTENT -->
            <div class="flex-1 space-y-10">
                
                <!-- RECENT ORDERS -->
                <div>
                    <div class="flex justify-between items-end mb-8">
                        <div>
                            <h2 class="text-3xl font-heading font-black text-gray-900">Your Orders.</h2>
                            <p class="text-gray-400 font-bold uppercase tracking-widest text-[10px] mt-1">Most recent orders & history</p>
                        </div>
                    </div>

                    <?php if (empty($orders)): ?>
                        <div class="bg-white rounded-[40px] p-24 text-center border-2 border-dashed border-gray-100 anim-up relative overflow-hidden">
                             <!-- Decor -->
                            <div class="absolute -top-10 -left-10 w-40 h-40 bg-gray-50 rounded-full opacity-50"></div>
                            
                            <div class="relative z-10">
                                <div class="text-8xl mb-8 animate-bounce">📦</div>
                                <h3 class="text-2xl font-black font-heading text-gray-900 mb-2">No Snacks Here!</h3>
                                <p class="text-gray-500 font-medium mb-10 max-w-sm mx-auto">Your order history is currently empty. Head over to our shop to explore some dried goodness.</p>
                                <a href="<?php echo get_url('shop.php'); ?>" class="btn-chunky bg-[#111827] text-white px-12 py-5 shadow-2xl hover:bg-[#24B25D] hover:text-black transition-all">Start Your First Order</a>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="grid gap-6">
                            <?php foreach ($orders as $order): ?>
                            <div class="bg-white rounded-[40px] p-6 md:p-8 shadow-sm border border-gray-100 hover:border-[#24B25D] hover:shadow-xl transition-all group anim-up">
                                <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-6">
                                    <div class="flex items-center gap-6">
                                        <div class="w-18 h-18 bg-gray-50 rounded-[24px] flex items-center justify-center text-gray-300 group-hover:bg-[#24B25D]/10 group-hover:text-[#24B25D] transition-all duration-500 group-hover:scale-110">
                                            <i class="fas fa-box-open text-2xl"></i>
                                        </div>
                                        <div>
                                            <div class="flex items-center gap-4 mb-2">
                                                <span class="text-xl font-black text-gray-900">#<?php echo $order['order_number']; ?></span>
                                                <span class="h-1 w-1 rounded-full bg-gray-200"></span>
                                                <span class="text-[10px] font-black uppercase text-gray-400 tracking-widest"><?php echo date('M d, Y', strtotime($order['created_at'])); ?></span>
                                            </div>
                                            <div class="flex items-center gap-4">
                                                <span class="px-4 py-1.5 rounded-full text-[9px] font-black uppercase tracking-widest shadow-sm <?php echo get_status_color($order['order_status']); ?>">
                                                    ● <?php echo str_replace('_', ' ', $order['order_status']); ?>
                                                </span>
                                                <span class="text-[10px] font-black text-gray-400 uppercase tracking-tighter">📦 <?php echo $order['item_count']; ?> Product<?php echo $order['item_count'] > 1 ? 's' : ''; ?></span>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="w-full md:w-auto flex items-center justify-between md:justify-end gap-12 border-t md:border-t-0 border-gray-50 pt-8 md:pt-0">
                                        <div class="text-right">
                                            <p class="text-[10px] font-black uppercase text-gray-400 mb-0.5">Order Value</p>
                                            <p class="text-3xl font-heading font-black text-gray-900">₹<?php echo number_format($order['total']); ?></p>
                                        </div>
                                        <a href="<?php echo get_url('track.php?id=' . $order['order_number']); ?>" class="btn-chunky bg-[#111827] text-white px-10 py-3.5 shadow-xl group-hover:bg-[#24B25D] group-hover:text-black transition-all">View Details</a>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <div class="mt-8">
                            <?php echo render_pagination($pagination['total_pages'], $pagination['current_page']); ?>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- PROFILE INFO -->
                <div class="bg-gray-900 rounded-[50px] p-10 md:p-16 text-white shadow-3xl shadow-green-900/10 flex flex-col md:flex-row justify-between items-center gap-12 relative overflow-hidden">
                    <div class="relative z-10 flex-1">
                        <span class="w-12 h-1 bg-[#24B25D] block mb-8 rounded-full"></span>
                        <h2 class="text-4xl font-heading font-black mb-4">Account Security.</h2>
                        <p class="text-gray-400 font-medium text-lg leading-relaxed max-w-md">We're building more tools to help you manage your profile, saved addresses, and payment methods. Stay tuned for our next platform drop!</p>
                    </div>
                    
                    <div class="relative z-10 w-full md:w-auto">
                        <div class="bg-white/5 backdrop-blur-3xl rounded-[32px] p-8 border border-white/10 hover:border-[#24B25D]/50 transition-colors">
                            <div class="text-[10px] font-black uppercase text-gray-500 mb-2 tracking-[0.2em]">Verified Identity</div>
                            <div class="flex items-center gap-4">
                                <div class="w-10 h-10 bg-[#24B25D]/10 rounded-xl flex items-center justify-center text-[#24B25D]">
                                    <i class="fas fa-shield-alt"></i>
                                </div>
                                <div>
                                    <div class="font-bold text-lg"><?php echo $user['email']; ?></div>
                                    <div class="text-xs text-[#24B25D] font-bold">Standard Profile</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Decor -->
                    <div class="absolute -right-20 -top-20 w-80 h-80 bg-[#24B25D] rounded-full blur-[120px] opacity-10"></div>
                </div>

            </div>
        </div>
    </div>

    <?php include 'includes/footer.php'; ?>
</body>
</html>


