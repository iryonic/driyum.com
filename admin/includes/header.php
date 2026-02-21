<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

// Strict Admin Check
if (!isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
    header("Location: ../login.php");
    exit;
}

// Fetch Notifications
$unread_notifs = fetch_all("SELECT * FROM admin_notifications WHERE is_read = 0 ORDER BY created_at DESC LIMIT 5");
$unread_count = count($unread_notifs);

// Get Store Stats for Sidebar
$total_orders_today = fetch_one("SELECT COUNT(*) as count FROM orders WHERE DATE(created_at) = CURDATE()")['count'];
$low_stock_count = fetch_one("SELECT COUNT(*) as count FROM products WHERE stock < 10")['count'];

?>
<!DOCTYPE html>
<html lang="en">
<head>

<!-- Google Tag Manager -->
<script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=
'https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
})(window,document,'script','dataLayer','GTM-T3LPLX64');</script>
<!-- End Google Tag Manager -->

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Driyum Admin</title>
    <link rel="icon" type="image/png" href="<?php echo get_url('assets/images/logo.svg'); ?>">
    <link rel="apple-touch-icon" href="<?php echo get_url('assets/images/logo.svg'); ?>">
    <link rel="shortcut icon" href="<?php echo get_url('assets/images/logo.svg'); ?>" type="image/x-icon">

    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/chunky.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Montserrat:wght@100..900&family=Figtree:wght@300..900&display=swap');
        
        body { font-family: 'Figtree', sans-serif; background-color: #fcfdfe; color: #1e293b; }
        .crimson-pro { font-family: 'Montserrat', sans-serif; }
        
        .admin-sidebar { 
            height: 100vh; 
            position: fixed; 
            left: 0; 
            top: 0; 
            width: 260px; 
            overflow-y: auto; 
            background: #004f42;
            z-index: 100;
            border-right: 1px solid rgba(255,255,255,0.05);
            transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        .admin-sidebar::-webkit-scrollbar { width: 3px; }
        .admin-sidebar::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.05); border-radius: 10px; }
        
        .admin-content { margin-left: 260px; padding: 2rem 3rem; min-height: 100vh; transition: margin-left 0.3s ease; }
        
        .nav-link {
            transition: all 0.2s ease;
            position: relative;
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 10px 16px;
            border-radius: 12px;
            font-size: 13px;
        }
        
        .nav-link:hover { background: rgba(255, 255, 255, 0.03); color: white; }

        .nav-link-active {
            background: rgba(25, 220, 126, 0.1) !important;
            color: #24B25D !important;
            font-weight: 700;
        }
        .nav-link-active::before {
            content: '';
            position: absolute;
            left: 0;
            top: 20%;
            bottom: 20%;
            width: 3px;
            background: #24B25D;
            border-radius: 0 4px 4px 0;
        }
        
        @media (max-width: 1024px) {
            .admin-sidebar { transform: translateX(-100%); }
            .admin-sidebar.open { transform: translateX(0); }
            .admin-content { margin-left: 0; padding: 1.5rem; }
            
            .sidebar-overlay {
                position: fixed;
                inset: 0;
                background: rgba(0, 0, 0, 0.4);
                backdrop-filter: blur(8px);
                z-index: 90;
                opacity: 0;
                pointer-events: none;
                transition: opacity 0.3s ease;
            }
            .sidebar-overlay.active {
                opacity: 1;
                pointer-events: auto;
            }
        }

        #omni-results {
            backdrop-filter: blur(20px);
            background: rgba(255, 255, 255, 0.95);
        }
        
        /* Custom Scrollbar for sidebar */
        .custom-scrollbar::-webkit-scrollbar { width: 4px; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background: #334155; border-radius: 10px; }
        .custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
        @keyframes slideIn {
            from { transform: translateX(100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }
        .anim-up { animation: slideIn 0.3s ease-out forwards; }
    </style>
    <script>
        const BASE_URL = "<?php echo get_url(''); ?>";
    </script>
</head>
<body class="bg-gray-50">

<!-- Google Tag Manager (noscript) -->
<noscript><iframe src="https://www.googletagmanager.com/ns.html?id=GTM-T3LPLX64"
height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
<!-- End Google Tag Manager (noscript) -->


    <!-- SIDEBAR OVERLAY -->
    <div id="sidebar-overlay" class="sidebar-overlay" onclick="toggleSidebar(false)"></div>

    <!-- SIDEBAR -->
    <aside class="admin-sidebar bg-[#004f42] text-white p-5 flex flex-col">
        <div class="mb-8 flex items-center justify-between px-2">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 bg-[#24B25D] rounded-xl flex items-center justify-center text-black shadow-[0_0_20px_rgba(25,220,126,0.3)]">
                    <i class="fas fa-bolt text-xs"></i>
                </div>
                <span class="font-heading font-bold text-xl tracking-tight">Driyum<span class="text-[#24B25D]">.</span></span>
            </div>
            <button onclick="toggleSidebar(false)" class="lg:hidden text-gray-500 hover:text-white">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <nav class="space-y-6 flex-1 font-sans">
            
            <div>
                <h4 class="px-4 text-[9px] font-black text-gray-500 uppercase tracking-[0.2em] mb-3 opacity-40">Main</h4>
                <div class="space-y-1">
                    <?php $p = basename($_SERVER['PHP_SELF']); ?>
                    <a href="index.php" class="nav-link <?php echo $p=='index.php'?'nav-link-active':'text-white'; ?>">
                        <i class="fas fa-chart-line w-5 text-sm"></i> <span>Dashboard</span>
                    </a>
                    <a href="orders.php" class="nav-link <?php echo ($p=='orders.php' || $p=='generate_batch_shipments.php')?'nav-link-active':'text-white'; ?>">
                        <i class="fas fa-box w-5 text-sm"></i> 
                        <span>Orders</span>
                        <?php if($total_orders_today > 0): ?>
                            <span class="ml-auto bg-[#24B25D] text-black text-[9px] font-black px-1.5 py-0.5 rounded-full"><?php echo $total_orders_today; ?></span>
                        <?php endif; ?>
                    </a>
                    <a href="abandoned_carts.php" class="nav-link <?php echo $p=='abandoned_carts.php'?'nav-link-active':'text-white'; ?>">
                        <i class="fas fa-shopping-cart w-5 text-sm"></i> <span>Abandoned Carts</span>
                    </a>
                </div>
            </div>

            <div>
                <h4 class="px-4 text-[9px] font-black text-gray-500 uppercase tracking-[0.2em] mb-3 opacity-40">Products</h4>
                <div class="space-y-1">
                    <a href="products.php" class="nav-link <?php echo ($p=='products.php' || $p=='product_form.php')?'nav-link-active':'text-white'; ?>">
                        <i class="fas fa-apple-alt w-5 text-sm"></i> <span>Product List</span>
                    </a>
                    <a href="categories.php" class="nav-link <?php echo ($p=='categories.php' || $p=='category_form.php')?'nav-link-active':'text-white'; ?>">
                        <i class="fas fa-th-list w-5 text-sm"></i> <span>Categories</span>
                    </a>
                    <a href="inventory.php" class="nav-link <?php echo $p=='inventory.php'?'nav-link-active':'text-white'; ?>">
                        <i class="fas fa-boxes w-5 text-sm"></i> <span>Stock Status</span>
                        <?php if($low_stock_count > 0): ?>
                            <span class="ml-auto bg-red-500 text-white text-[9px] font-black px-1.5 py-0.5 rounded-full"><?php echo $low_stock_count; ?></span>
                        <?php endif; ?>
                    </a>
                </div>
            </div>

            <div>
                <h4 class="px-4 text-[9px] font-black text-gray-500 uppercase tracking-[0.2em] mb-3 opacity-40">People</h4>
                <div class="space-y-1">
                    <a href="users.php" class="nav-link <?php echo $p=='users.php'?'nav-link-active':'text-white'; ?>">
                        <i class="fas fa-users w-5 text-sm"></i> <span>Customers</span>
                    </a>
                    <a href="affiliates.php" class="nav-link <?php echo $p=='affiliates.php'?'nav-link-active':'text-white'; ?>">
                        <i class="fas fa-user-tag w-5 text-sm"></i> <span>Affiliates</span>
                    </a>
                    <a href="subscribers.php" class="nav-link <?php echo $p=='subscribers.php'?'nav-link-active':'text-white'; ?>">
                        <i class="fas fa-envelope w-5 text-sm"></i> <span>Newsletter</span>
                    </a>
                </div>
            </div>

            <div>
                <h4 class="px-4 text-[9px] font-black text-gray-500 uppercase tracking-[0.2em] mb-3 opacity-40">Marketing</h4>
                <div class="space-y-1">
                    <a href="coupons.php" class="nav-link <?php echo $p=='coupons.php'?'nav-link-active':'text-white'; ?>">
                        <i class="fas fa-percentage w-5 text-sm"></i> <span>Coupons</span>
                    </a>
                    <a href="reviews.php" class="nav-link <?php echo $p=='reviews.php'?'nav-link-active':'text-white'; ?>">
                        <i class="fas fa-star w-5 text-sm"></i> <span>Reviews</span>
                    </a>
                    <a href="manage_testimonials.php" class="nav-link <?php echo $p=='manage_testimonials.php'?'nav-link-active':'text-white'; ?>">
                        <i class="fas fa-comment w-5 text-sm"></i> <span>Testimonials</span>
                    </a>
                </div>
            </div>

            <div>
                <h4 class="px-4 text-[9px] font-black text-gray-500 uppercase tracking-[0.2em] mb-3 opacity-40">Website</h4>
                <div class="space-y-1">
                    <a href="manage_home.php" class="nav-link <?php echo $p=='manage_home.php'?'nav-link-active':'text-white'; ?>">
                        <i class="fas fa-home w-5 text-sm"></i> <span>Homepage</span>
                    </a>
                    <a href="manage_pages.php" class="nav-link <?php echo $p=='manage_pages.php'?'nav-link-active':'text-white'; ?>">
                        <i class="fas fa-file w-5 text-sm"></i> <span>Pages</span>
                    </a>
                    <a href="manage_contact.php" class="nav-link <?php echo $p=='manage_contact.php'?'nav-link-active':'text-white'; ?>">
                        <i class="fas fa-inbox w-5 text-sm"></i> <span>Messages</span>
                    </a>
                    <a href="shipping.php" class="nav-link <?php echo $p=='shipping.php'?'nav-link-active':'text-white'; ?>">
                        <i class="fas fa-truck w-5 text-sm"></i> <span>Shipping</span>
                    </a>
                    <a href="settings.php" class="nav-link <?php echo $p=='settings.php'?'nav-link-active':'text-white'; ?>">
                        <i class="fas fa-tools w-5 text-sm"></i> <span>Settings</span>
                    </a>
                </div>
            </div>
        </nav>

        <div class="pt-6 mt-6 border-t border-white/5 pb-4">
            <a href="../logout.php" class="flex items-center justify-between px-4 py-3 rounded-xl bg-white/5 text-white hover:text-red-400 transition group">
                <span class="text-[10px] font-black uppercase tracking-widest">Logout</span>
                <i class="fas fa-power-off text-xs"></i>
            </a>
        </div>
    </aside>

    <main class="admin-content">
        <!-- Global Toast Container -->
        <div id="toast-container" class="fixed top-24 right-8 z-[9999] flex flex-col gap-3 pointer-events-none"></div>

        <!-- Flash Messages -->
        <?php if (isset($_SESSION['success'])): ?>
            <div id="flash-success" class="mb-6 p-4 bg-green-50 border border-green-100 text-green-600 rounded-2xl font-bold flex items-center justify-between anim-up shadow-sm flash-msg">
                <div class="flex items-center gap-3">
                    <i class="fas fa-check-circle"></i>
                    <span class="text-xs"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></span>
                </div>
                <button onclick="this.parentElement.remove()" class="text-green-300 hover:text-green-600"><i class="fas fa-times"></i></button>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            <div id="flash-error" class="mb-6 p-4 bg-red-50 border border-red-100 text-red-600 rounded-2xl font-bold flex items-center justify-between anim-up shadow-sm flash-msg">
                <div class="flex items-center gap-3">
                    <i class="fas fa-exclamation-circle"></i>
                    <span class="text-xs"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></span>
                </div>
                <button onclick="this.parentElement.remove()" class="text-red-300 hover:text-red-600"><i class="fas fa-times"></i></button>
            </div>
        <?php endif; ?>

        <header class="mb-8 sticky top-0 z-40 bg-white/80 backdrop-blur-xl border-b border-gray-100 px-6 py-4 -mx-6 -mt-8 mb-8 flex flex-col md:flex-row justify-between items-center transition-all duration-300">
            <div class="flex items-center gap-6 w-full md:w-auto">
                <button onclick="toggleSidebar(true)" class="lg:hidden w-10 h-10 flex items-center justify-center bg-black border border-gray-100 rounded-xl text-green-500 shadow-sm">
                    <i class="fas fa-bars"></i>
                </button>
                
                <!-- BREADCRUMBS -->
                <div class="hidden md:flex flex-col">
                    <h2 class="text-xl font-black text-gray-900 crimson-pro leading-none flex items-center gap-2">
                        <?php 
                        $page_titles = [
                            'index.php' => 'Dashboard',
                            'orders.php' => 'Orders',
                            'products.php' => 'Products',
                            'inventory.php' => 'Inventory',
                            'users.php' => 'Customers',
                            'settings.php' => 'Settings',
                            'shipping.php' => 'Shipping',
                            'generate_batch_shipments.php' => 'Batch Printing'
                        ];
                        $curr_page = basename($_SERVER['PHP_SELF']);
                        echo $page_titles[$curr_page] ?? 'Admin Panel';
                        ?>
                    </h2>
                    <div class="flex items-center gap-2 text-[10px] font-bold text-white uppercase tracking-widest mt-1">
                        <span class="text-black hover:text-green-500 transition-colors cursor-pointer">Home</span>
                        <i class="fas fa-chevron-right text-[8px] text-gray-400 opacity-80"></i>
                        <span class="text-[#24B25D]"><?php echo $page_titles[$curr_page] ?? 'Page'; ?></span>
                    </div>
                </div>
            </div>

            <div class="flex items-center gap-4 w-full md:w-auto mt-4 md:mt-0">
                
                <!-- SEARCH -->
                <div class="relative group w-full md:w-64" id="omni-search-container">
                    <input type="text" id="omni-search-input" placeholder="Search..." class="w-full bg-gray-50/50 border border-gray-100 focus:border-[#24B25D] focus:bg-white rounded-xl pl-10 pr-12 py-2.5 text-xs font-bold transition-all outline-none shadow-sm group-hover:shadow-md">
                    <i class="fas fa-search absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-300 group-focus-within:text-[#24B25D] transition-colors text-xs"></i>
                    <div class="absolute right-3 top-1/2 -translate-y-1/2 pointer-events-none">
                        <span class="bg-white border border-gray-200 text-white text-[9px] font-black px-1.5 py-0.5 rounded shadow-sm">/</span>
                    </div>
                    <div id="omni-results" class="absolute left-0 w-screen md:w-80 md:left-auto md:right-0 top-full mt-2 bg-white rounded-2xl shadow-xl border border-gray-100 overflow-hidden hidden z-[200]">
                        <div id="omni-results-content" class="max-h-96 overflow-y-auto p-2 custom-scrollbar"></div>
                    </div>
                </div>

                <div class="hidden sm:flex items-center gap-2 bg-white border border-gray-100 px-3 py-1.5 rounded-xl shadow-sm">
                    <span class="w-1.5 h-1.5 rounded-full bg-[#24B25D] animate-pulse"></span>
                    <div id="header-clock" class="text-[10px] font-black text-gray-900 tracking-widest tabular-nums">--:--</div>
                </div>
                
                <div class="relative" id="notif-dropdown">
                    <button onclick="toggleNotifDropdown(event)" class="w-10 h-10 bg-white hover:bg-gray-50 rounded-xl flex items-center justify-center transition-all relative border border-gray-100 group shadow-sm">
                        <i class="fas fa-bell text-gray-400 group-hover:text-black transition-colors text-sm"></i>
                        <?php if ($unread_count > 0): ?>
                            <span class="absolute -top-1 -right-1 w-2.5 h-2.5 bg-red-500 rounded-full border-2 border-white"></span>
                        <?php endif; ?>
                    </button>
                    <!-- Notification content remains same -->
                    <div id="notif-dropdown-content" class="fixed inset-x-4 top-40 md:absolute md:right-0 md:top-full md:mt-4 md:inset-x-auto w-auto md:w-80 bg-white rounded-3xl shadow-[0_20px_50px_rgba(0,0,0,0.1)] border border-gray-100 hidden z-[200] overflow-hidden">
                        <div class="p-5 border-b border-gray-50 flex items-center justify-between">
                            <span class="text-[10px] font-black uppercase tracking-widest text-gray-400">Notifications</span>
                            <?php if($unread_count > 0): ?>
                                <span class="bg-red-50 text-red-500 text-[8px] font-black px-2 py-0.5 rounded-full uppercase"><?php echo $unread_count; ?> NEW</span>
                            <?php endif; ?>
                        </div>
                        <div class="max-h-80 overflow-y-auto custom-scrollbar" id="notif-list">
                             <?php if (empty($unread_notifs)): ?>
                                <div class="p-10 text-center">
                                    <i class="fas fa-check-double text-gray-100 text-3xl mb-3 block"></i>
                                    <p class="text-[10px] text-gray-400 font-black uppercase tracking-widest">No Alerts</p>
                                </div>
                             <?php else: foreach($unread_notifs as $notif): ?>
                                <a href="<?php echo htmlspecialchars($notif['link'] ?: '#'); ?>" onclick="markRead(<?php echo $notif['id']; ?>)" class="block p-5 hover:bg-gray-50 border-b border-gray-50 last:border-0 transition-all">
                                    <p class="text-[11px] font-bold text-gray-900 leading-snug mb-1.5"><?php echo htmlspecialchars($notif['message']); ?></p>
                                    <p class="text-[9px] text-gray-400 font-bold uppercase tracking-widest flex items-center gap-2">
                                        <i class="fas fa-clock text-[8px]"></i> <?php echo get_time_ago($notif['created_at']); ?>
                                    </p>
                                </a>
                             <?php endforeach; endif; ?>
                        </div>
                    </div>
                </div>
                
                <!-- USER PROFILE MENU -->
                <div class="relative group" id="user-menu-dropdown">
                    <button onclick="document.getElementById('user-menu-content').classList.toggle('hidden')" class="flex items-center gap-3 bg-white hover:bg-gray-50 border border-gray-100 rounded-xl p-1 pr-4 transition-all shadow-sm">
                        <div class="w-8 h-8 rounded-lg bg-black text-[#24B25D] flex items-center justify-center font-black text-xs">
                            A
                        </div>
                        <div class="text-left hidden md:block">
                            <div class="text-[10px] font-black text-gray-900 leading-none">Admin</div>
                            <div class="text-[8px] font-bold text-gray-400 uppercase tracking-widest">Super User</div>
                        </div>
                        <i class="fas fa-chevron-down text-[8px] text-gray-300 ml-2"></i>
                    </button>
                    
                    <div id="user-menu-content" class="hidden absolute right-0 top-full mt-2 w-48 bg-white rounded-2xl shadow-xl border border-gray-100 z-[200] overflow-hidden">
                        <a href="settings.php" class="flex items-center gap-3 px-4 py-3 hover:bg-gray-50 transition-colors">
                            <i class="fas fa-cog text-gray-400 text-xs"></i>
                            <span class="text-[11px] font-bold text-gray-700">Settings</span>
                        </a>
                        <a href="../" target="_blank" class="flex items-center gap-3 px-4 py-3 hover:bg-gray-50 transition-colors border-t border-gray-50">
                            <i class="fas fa-external-link-alt text-gray-400 text-xs"></i>
                            <span class="text-[11px] font-bold text-gray-700">View Store</span>
                        </a>
                        <a href="../logout.php" class="flex items-center gap-3 px-4 py-3 hover:bg-red-50 transition-colors border-t border-gray-50 text-red-500 group">
                            <i class="fas fa-power-off text-xs group-hover:text-red-600"></i>
                            <span class="text-[11px] font-bold group-hover:text-red-600">Logout</span>
                        </a>
                    </div>
                </div>
            </div>
        </header>

        <script>
        function updateHeaderClock() {
            const el = document.getElementById('header-clock');
            if(!el) return;
            const now = new Date();
            el.textContent = now.toLocaleTimeString('en-US', { hour12: false, hour: '2-digit', minute: '2-digit', second: '2-digit' });
        }
        setInterval(updateHeaderClock, 1000);
        updateHeaderClock();

        async function markRead(id) {
            try { await fetch('mark_notification_read.php?id=' + id); } catch(e) {}
        }

        function toggleSidebar(force) {
            const sidebar = document.querySelector('.admin-sidebar');
            const overlay = document.getElementById('sidebar-overlay');
            if (force === true) { sidebar.classList.add('open'); overlay.classList.add('active'); }
            else if (force === false) { sidebar.classList.remove('open'); overlay.classList.remove('active'); }
            else { const isOpen = sidebar.classList.toggle('open'); overlay.classList.toggle('active', isOpen); }
        }

        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                toggleSidebar(false);
                document.getElementById('omni-results').classList.add('hidden');
                document.getElementById('notif-dropdown-content').classList.add('hidden');
                document.getElementById('user-menu-content').classList.add('hidden');
            }
            if (e.key === '/' || (e.metaKey && e.key === 'k')) {
                e.preventDefault();
                document.getElementById('omni-search-input').focus();
            }
        });

        function toggleNotifDropdown(e) {
            e.stopPropagation();
            document.getElementById('notif-dropdown-content').classList.toggle('hidden');
            document.getElementById('user-menu-content').classList.add('hidden');
            document.getElementById('omni-results').classList.add('hidden');
        }

        const omniInput = document.getElementById('omni-search-input');
        const omniResults = document.getElementById('omni-results');
        const omniResultsContent = document.getElementById('omni-results-content');
        let searchTimeout = null;

        omniInput.addEventListener('input', (e) => {
            const q = e.target.value.trim();
            clearTimeout(searchTimeout);
            if (q.length < 2) { omniResults.classList.add('hidden'); return; }

            searchTimeout = setTimeout(async () => {
                try {
                    const response = await fetch(`api/omni_search.php?q=${encodeURIComponent(q)}`);
                    const data = await response.json();
                    renderOmniResults(data);
                } catch (err) { console.error('Search failed', err); }
            }, 300);
        });

        function renderOmniResults(results) {
            if (results.length === 0) {
                omniResultsContent.innerHTML = `<div class="p-10 text-center text-gray-400"><p class="text-[10px] font-black uppercase tracking-widest">No results</p></div>`;
            } else {
                let html = '';
                results.forEach(res => {
                    const icon = res.image 
                        ? `<img src="../${res.image}" class="w-10 h-10 rounded-xl object-cover shadow-sm">`
                        : `<div class="w-10 h-10 rounded-xl bg-gray-50 flex items-center justify-center text-gray-400 text-xs"><i class="${res.icon || 'fas fa-info-circle'}"></i></div>`;
                    html += `
                        <a href="${res.url}" class="flex items-center gap-4 p-4 hover:bg-gray-50 rounded-2xl transition-all group/item">
                            ${icon}
                            <div>
                                <p class="text-xs font-black text-gray-900 group-hover/item:text-[#24B25D] transition-colors">${res.title}</p>
                                <p class="text-[9px] font-bold text-gray-400 uppercase tracking-widest">${res.subtitle}</p>
                            </div>
                            <div class="ml-auto opacity-0 group-hover/item:opacity-100 transition-all translate-x-2 group-hover/item:translate-x-0">
                                <i class="fas fa-arrow-right text-[10px] text-[#24B25D]"></i>
                            </div>
                        </a>
                    `;
                });
                omniResultsContent.innerHTML = html;
            }
            omniResults.classList.remove('hidden');
        }

        document.addEventListener('click', (e) => {
            if (!document.getElementById('omni-search-container').contains(e.target)) omniResults.classList.add('hidden');
            if (!document.getElementById('notif-dropdown').contains(e.target)) document.getElementById('notif-dropdown-content').classList.add('hidden');
            if (!document.getElementById('user-menu-dropdown').contains(e.target)) document.getElementById('user-menu-content').classList.add('hidden');
        });

        // Toast & Auto-hide Logic
        function showToast(message, type = 'success') {
            const container = document.getElementById('toast-container');
            const toast = document.createElement('div');
            toast.className = `p-4 rounded-2xl shadow-xl border flex items-center gap-3 anim-up pointer-events-auto min-w-[300px] ${type === 'success' ? 'bg-black text-white border-white/10' : 'bg-red-50 text-red-600 border-red-100'}`;
            toast.innerHTML = `
                <i class="fas ${type === 'success' ? 'fa-check-circle text-[#24B25D]' : 'fa-exclamation-circle'}"></i>
                <span class="text-[11px] font-bold">${message}</span>
            `;
            container.appendChild(toast);
            setTimeout(() => {
                toast.classList.add('opacity-0', 'translate-x-full');
                setTimeout(() => toast.remove(), 500);
            }, 5000);
        }

        // Auto-hide existing flash messages
        document.querySelectorAll('.flash-msg').forEach(msg => {
            setTimeout(() => {
                msg.style.opacity = '0';
                msg.style.transform = 'translateY(-20px)';
                setTimeout(() => msg.remove(), 500);
            }, 6000);
        });

        // Background Queue Trigger
        setTimeout(() => {
            fetch('<?php echo get_url('process_queue.php'); ?>')
                .then(r => r.json())
                .then(data => {
                    if(data.processed > 0) console.log(`Background queue processed ${data.processed} items.`);
                }).catch(e => {});
        }, 2000);
        </script>


