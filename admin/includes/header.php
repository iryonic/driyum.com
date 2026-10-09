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
$total_orders_today = fetch_one("SELECT COUNT(*) as count FROM orders WHERE DATE(created_at) = CURDATE()")['count'] ?? 0;
$low_stock_count = fetch_one("SELECT COUNT(*) as count FROM products WHERE stock < 10 AND is_active = 1")['count'] ?? 0;

$curr_page = basename($_SERVER['PHP_SELF']);

// Page Titles Map for Breadcrumb
$page_meta = [
    'index.php' => ['title' => 'Dashboard', 'group' => 'Overview', 'icon' => 'fas fa-chart-pie'],
    'analytics.php' => ['title' => 'Product Analytics', 'group' => 'Overview', 'icon' => 'fas fa-chart-line'],
    'orders.php' => ['title' => 'Orders', 'group' => 'Sales', 'icon' => 'fas fa-box-open'],
    'generate_batch_shipments.php' => ['title' => 'Batch Printing', 'group' => 'Logistics', 'icon' => 'fas fa-print'],
    'generate_label.php' => ['title' => 'Shipping Label', 'group' => 'Logistics', 'icon' => 'fas fa-barcode'],
    'abandoned_carts.php' => ['title' => 'Abandoned Carts', 'group' => 'Sales', 'icon' => 'fas fa-shopping-cart'],
    'products.php' => ['title' => 'Product Catalog', 'group' => 'Catalog', 'icon' => 'fas fa-apple-alt'],
    'product_form.php' => ['title' => (isset($_GET['id']) ? 'Edit Product' : 'New Product'), 'group' => 'Catalog', 'icon' => 'fas fa-cube'],
    'product_sorting.php' => ['title' => 'Product Sorting', 'group' => 'Catalog', 'icon' => 'fas fa-arrows-alt'],
    'categories.php' => ['title' => 'Categories', 'group' => 'Catalog', 'icon' => 'fas fa-th-list'],
    'category_form.php' => ['title' => (isset($_GET['id']) ? 'Edit Category' : 'New Category'), 'group' => 'Catalog', 'icon' => 'fas fa-folder-plus'],
    'inventory.php' => ['title' => 'Inventory & Stock', 'group' => 'Catalog', 'icon' => 'fas fa-boxes'],
    'users.php' => ['title' => 'Customer Directory', 'group' => 'Customers', 'icon' => 'fas fa-users'],
    'create-admin.php' => ['title' => 'New Administrator', 'group' => 'System', 'icon' => 'fas fa-user-shield'],
    'affiliates.php' => ['title' => 'Affiliate Partners', 'group' => 'Growth', 'icon' => 'fas fa-handshake'],
    'subscribers.php' => ['title' => 'Newsletter Subscribers', 'group' => 'Growth', 'icon' => 'fas fa-envelope-open-text'],
    'coupons.php' => ['title' => 'Coupons & Discounts', 'group' => 'Marketing', 'icon' => 'fas fa-tags'],
    'reviews.php' => ['title' => 'Product Reviews', 'group' => 'Marketing', 'icon' => 'fas fa-star'],
    'manage_testimonials.php' => ['title' => 'Testimonials', 'group' => 'Marketing', 'icon' => 'fas fa-comment-dots'],
    'hero_slides.php' => ['title' => 'Hero Slides', 'group' => 'Storefront', 'icon' => 'fas fa-images'],
    'manage_home.php' => ['title' => 'Homepage Content', 'group' => 'Storefront', 'icon' => 'fas fa-store'],
    'manage_partners.php' => ['title' => 'Retail Partners', 'group' => 'Storefront', 'icon' => 'fas fa-map-marker-alt'],
    'manage_pages.php' => ['title' => 'Pages & CMS', 'group' => 'Storefront', 'icon' => 'fas fa-file-alt'],
    'manage_contact.php' => ['title' => 'Contact Messages ', 'group' => 'Storefront', 'icon' => 'fas fa-inbox'],
    'shipping.php' => ['title' => 'Shipping & Zones', 'group' => 'Settings', 'icon' => 'fas fa-truck'],
    'settings.php' => ['title' => 'Store Settings', 'group' => 'Settings', 'icon' => 'fas fa-cog'],
    'backup_db.php' => ['title' => 'Database Backup', 'group' => 'System', 'icon' => 'fas fa-database']
];

$active_meta = $page_meta[$curr_page] ?? ['title' => 'Administration', 'group' => 'Console', 'icon' => 'fas fa-shield-alt'];
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
    <title><?php echo htmlspecialchars($active_meta['title']); ?> | Driyum Admin</title>
    <link rel="icon" type="image/svg+xml" href="<?php echo get_url('assets/images/logo.svg'); ?>">
    <link rel="apple-touch-icon" href="<?php echo get_url('assets/images/logo.svg'); ?>">
    <link rel="shortcut icon" href="<?php echo get_url('assets/images/logo.svg'); ?>" type="image/x-icon">

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Figtree:ital,wght@0,300..900;1,300..900&family=Montserrat:wght@400..800&display=swap" rel="stylesheet">
    
    <!-- Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Tailwind CDN with Custom Config -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        brand: {
                            dark: '#004f42',
                            darker: '#003a31',
                            primary: '#15803d',
                            accent: '#24B25D',
                            light: '#f0fdf4'
                        }
                    },
                    fontFamily: {
                        sans: ['Figtree', 'system-ui', '-apple-system', 'sans-serif'],
                        heading: ['Montserrat', 'Figtree', 'sans-serif']
                    }
                }
            }
        }
    </script>

    <!-- Custom Admin Design System -->
    <link rel="stylesheet" href="../assets/css/admin.css?v=<?php echo filemtime('../assets/css/admin.css'); ?>">

    <style>
        .admin-sidebar { 
            height: 100vh; 
            position: fixed; 
            left: 0; 
            top: 0; 
            width: 260px; 
            overflow-y: auto; 
            background: #29e258;
            background: linear-gradient(180deg, #2de65c 0%, #29e258 42%, #1ec74d 100%);
            z-index: 100;
            border-right: 1px solid #1db846;
            box-shadow: 2px 0 18px rgba(25, 180, 65, 0.22);
            transition: transform 0.25s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        .admin-content-wrapper { 
            margin-left: 260px; 
            min-height: 100vh; 
            display: flex;
            flex-direction: column;
            transition: margin-left 0.25s cubic-bezier(0.4, 0, 0.2, 1); 
        }

        table{
            white-space: nowrap;
        }
        .admin-nav-item {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 7px 11px;
            border-radius: 10px;
            font-size: 13px;
            font-weight: 600;
            color: #02361d;
            background: rgba(255, 255, 255, 0.72);
            border: 1px solid rgba(255, 255, 255, 0.55);
            backdrop-filter: blur(6px);
            transition: all 0.18s cubic-bezier(0.4, 0, 0.2, 1);
            text-decoration: none;
            position: relative;
            box-shadow: 0 1px 2px rgba(0, 40, 15, 0.04);
        }
        .admin-nav-item .nav-icon-box {
            width: 28px;
            height: 28px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #ffffff;
            color: #047857;
            font-size: 11px;
            transition: all 0.18s ease;
            flex-shrink: 0;
            border: 1px solid rgba(0, 0, 0, 0.05);
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.04);
        }
        .admin-nav-item:hover {
            color: #012b16;
            background: #ffffff;
            border-color: #ffffff;
            box-shadow: 0 3px 8px rgba(0, 40, 15, 0.12);
            transform: translateX(2px);
        }
        .admin-nav-item:hover .nav-icon-box {
            background: #004f42;
            color: #ffffff;
            border-color: #004f42;
            box-shadow: 0 0 8px rgba(0, 79, 66, 0.25);
        }
        .admin-nav-item.active {
            color: #ffffff;
            background: #004f42;
            font-weight: 700;
            border: 1px solid #004f42;
            box-shadow: 0 4px 12px rgba(0, 79, 66, 0.35);
        }
        .admin-nav-item.active .nav-icon-box {
            background: #ffffff;
            color: #004f42;
            border-color: #ffffff;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }
        
        /* Mobile-Responsive Command Palette Overlay */
        @media (max-width: 639px) {
            #omni-results {
                position: fixed !important;
                top: 60px !important;
                left: 10px !important;
                right: 10px !important;
                width: auto !important;
                max-width: calc(100vw - 20px) !important;
                max-height: calc(100dvh - 75px) !important;
                margin: 0 !important;
                z-index: 100 !important;
                border-radius: 16px !important;
                box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.25), 0 8px 10px -6px rgba(0, 0, 0, 0.2) !important;
            }
            #omni-results-content {
                max-height: calc(100dvh - 195px) !important;
                -webkit-overflow-scrolling: touch;
            }
            #omni-category-tabs {
                padding: 6px 8px !important;
                gap: 4px !important;
            }
            .omni-tab-btn {
                padding: 4px 8px !important;
                font-size: 10.5px !important;
            }
        }
        @media (min-width: 640px) {
            #omni-results {
                position: absolute;
                right: 0;
                top: 100%;
                margin-top: 8px;
                width: 580px;
                max-width: 90vw;
                z-index: 50;
            }
        }

        @media (max-width: 1024px) {
            .admin-sidebar { 
                transform: translateX(-100%); 
            }
            .admin-sidebar.open { 
                transform: translateX(0); 
            }
            .admin-content-wrapper { 
                margin-left: 0; 
            }
            
            .sidebar-overlay {
                position: fixed;
                inset: 0;
                background: rgba(15, 23, 42, 0.6);
                backdrop-filter: blur(4px);
                z-index: 90;
                opacity: 0;
                pointer-events: none;
                transition: opacity 0.25s ease;
            }
            .sidebar-overlay.active {
                opacity: 1;
                pointer-events: auto;
            }
        }
    </style>

    <script>
        const BASE_URL = "<?php echo get_url(''); ?>";
    </script>
    <script src="<?php echo get_url('assets/js/dialogs.js'); ?>?v=<?php echo time(); ?>"></script>
</head>
<body class="admin-body min-h-screen">

    <!-- Google Tag Manager (noscript) -->
    <noscript><iframe src="https://www.googletagmanager.com/ns.html?id=GTM-T3LPLX64"
    height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
    <!-- End Google Tag Manager (noscript) -->

    <!-- SIDEBAR OVERLAY -->
    <div id="sidebar-overlay" class="sidebar-overlay" onclick="toggleSidebar(false)"></div>

    <!-- SIDEBAR (GREEN THEMED #29e258) -->
    <aside class="admin-sidebar flex flex-col">
        <!-- Brand Header with Live Online Status -->
        <div class="h-16 flex items-center justify-between px-6 border-b border-[#1db846] shrink-0 bg-white shadow-2xs">
            <a href="index.php" class="flex items-center gap-2.5 group">
                <img src="<?php echo get_url('assets/images/logo.svg'); ?>" alt="DRIYUM" width="92">
            </a>
            <button onclick="toggleSidebar(false)" class="lg:hidden text-slate-500 hover:text-slate-800 p-1.5 rounded-lg hover:bg-slate-100 transition-colors" aria-label="Close sidebar">
                <i class="fas fa-times text-sm"></i>
            </button>
        </div>

        <!-- Navigation Links -->
        <nav class="flex-1 px-3 py-4 space-y-4 overflow-y-auto custom-scrollbar">
            
            <!-- OVERVIEW -->
            <div>
                <div class="px-2 mb-1.5 text-[10px] font-extrabold text-[#024020] uppercase tracking-wider flex items-center gap-1.5">
                    <span class="w-1.5 h-1.5 rounded-full bg-[#024020]/70"></span>
                    <span>Overview</span>
                </div>
                <div class="space-y-1">
                    <a href="index.php" class="admin-nav-item group <?php echo $curr_page == 'index.php' ? 'active' : ''; ?>">
                        <span class="nav-icon-box"><i class="fas fa-chart-pie"></i></span>
                        <span class="flex-1">Dashboard</span>
                    </a>
                    <a href="analytics.php" class="admin-nav-item group <?php echo $curr_page == 'analytics.php' ? 'active' : ''; ?>">
                        <span class="nav-icon-box"><i class="fas fa-chart-line"></i></span>
                        <span class="flex-1">Analytics & Stats</span>
                        <span class="<?php echo $curr_page == 'analytics.php' ? 'bg-white/20 text-white border-white/30' : 'bg-white text-emerald-950 border-emerald-300'; ?> text-[9px] font-bold px-1.5 py-0.5 rounded border">New</span>
                    </a>
                    <a href="orders.php" class="admin-nav-item group <?php echo in_array($curr_page, ['orders.php', 'generate_batch_shipments.php', 'generate_label.php']) ? 'active' : ''; ?>">
                        <span class="nav-icon-box"><i class="fas fa-box-open"></i></span>
                        <span class="flex-1">Orders</span>
                        <?php if ($total_orders_today > 0): ?>
                            <span class="<?php echo in_array($curr_page, ['orders.php', 'generate_batch_shipments.php', 'generate_label.php']) ? 'bg-white/20 text-white border-white/30' : 'bg-white text-emerald-950 border-emerald-300'; ?> text-[10px] font-black px-1.5 py-0.5 rounded-md leading-none shadow-2xs border"><?php echo $total_orders_today; ?></span>
                        <?php endif; ?>
                    </a>
                    <a href="abandoned_carts.php" class="admin-nav-item group <?php echo $curr_page == 'abandoned_carts.php' ? 'active' : ''; ?>">
                        <span class="nav-icon-box"><i class="fas fa-shopping-cart"></i></span>
                        <span class="flex-1">Abandoned Carts</span>
                    </a>
                </div>
            </div>

            <!-- CATALOG & STOCK -->
            <div>
                <div class="px-2 mb-1.5 text-[10px] font-extrabold text-[#024020] uppercase tracking-widest flex items-center gap-1.5">
                    <span class="w-1.5 h-1.5 rounded-full bg-[#024020]/70"></span>
                    <span>Catalog & Stock</span>
                </div>
                <div class="space-y-1">
                    <a href="products.php" class="admin-nav-item group <?php echo in_array($curr_page, ['products.php', 'product_form.php']) ? 'active' : ''; ?>">
                        <span class="nav-icon-box"><i class="fas fa-layer-group"></i></span>
                        <span class="flex-1">Product Catalog</span>
                    </a>
                    <a href="product_sorting.php" class="admin-nav-item group <?php echo $curr_page == 'product_sorting.php' ? 'active' : ''; ?>">
                        <span class="nav-icon-box"><i class="fas fa-sort-amount-down"></i></span>
                        <span class="flex-1">Sorting</span>
                        <span class="bg-white/80 text-emerald-950 text-[9px] font-bold px-1.5 py-0.5 rounded border border-emerald-200">Drag</span>
                    </a>
                    <a href="categories.php" class="admin-nav-item group <?php echo in_array($curr_page, ['categories.php', 'category_form.php']) ? 'active' : ''; ?>">
                        <span class="nav-icon-box"><i class="fas fa-tags"></i></span>
                        <span class="flex-1">Categories</span>
                    </a>
                    <a href="inventory.php" class="admin-nav-item group <?php echo $curr_page == 'inventory.php' ? 'active' : ''; ?>">
                        <span class="nav-icon-box"><i class="fas fa-boxes"></i></span>
                        <span class="flex-1">Stock & Inventory</span>
                        <?php if ($low_stock_count > 0): ?>
                            <span class="bg-rose-500 text-white text-[10px] font-black px-1.5 py-0.5 rounded-md leading-none shadow-2xs animate-pulse"><?php echo $low_stock_count; ?></span>
                        <?php endif; ?>
                    </a>
                </div>
            </div>

            <!-- CUSTOMERS & AUDIENCE -->
            <div>
                <div class="px-2 mb-1.5 text-[10px] font-extrabold text-[#024020] uppercase tracking-wider flex items-center gap-1.5">
                    <span class="w-1.5 h-1.5 rounded-full bg-[#024020]/70"></span>
                    <span>Customers & Audience</span>
                </div>
                <div class="space-y-1">
                    <a href="users.php" class="admin-nav-item group <?php echo in_array($curr_page, ['users.php', 'create-admin.php']) ? 'active' : ''; ?>">
                        <span class="nav-icon-box"><i class="fas fa-users"></i></span>
                        <span class="flex-1">Customer Directory</span>
                    </a>
                    <a href="affiliates.php" class="admin-nav-item group <?php echo $curr_page == 'affiliates.php' ? 'active' : ''; ?>">
                        <span class="nav-icon-box"><i class="fas fa-user-check"></i></span>
                        <span class="flex-1">Affiliates & Partners</span>
                    </a>
                    <a href="subscribers.php" class="admin-nav-item group <?php echo $curr_page == 'subscribers.php' ? 'active' : ''; ?>">
                        <span class="nav-icon-box"><i class="fas fa-paper-plane"></i></span>
                        <span class="flex-1">Newsletter Audience</span>
                    </a>
                </div>
            </div>

            <!-- MARKETING & REPUTATION -->
            <div>
                <div class="px-2 mb-1.5 text-[10px] font-extrabold text-[#024020] uppercase tracking-wider flex items-center gap-1.5">
                    <span class="w-1.5 h-1.5 rounded-full bg-[#024020]/70"></span>
                    <span>Marketing & Social</span>
                </div>
                <div class="space-y-1">
                    <a href="coupons.php" class="admin-nav-item group <?php echo $curr_page == 'coupons.php' ? 'active' : ''; ?>">
                        <span class="nav-icon-box"><i class="fas fa-ticket-alt"></i></span>
                        <span class="flex-1">Coupons</span>
                    </a>
                    <a href="reviews.php" class="admin-nav-item group <?php echo $curr_page == 'reviews.php' ? 'active' : ''; ?>">
                        <span class="nav-icon-box"><i class="fas fa-star"></i></span>
                        <span class="flex-1">Product Reviews</span>
                    </a>
                    <a href="manage_testimonials.php" class="admin-nav-item group <?php echo $curr_page == 'manage_testimonials.php' ? 'active' : ''; ?>">
                        <span class="nav-icon-box"><i class="fas fa-quote-right"></i></span>
                        <span class="flex-1">Testimonials</span>
                    </a>
                </div>
            </div>

            <!-- STOREFRONT CONTENT CMS -->
            <div>
                <div class="px-2 mb-1.5 text-[10px] font-extrabold text-[#024020] uppercase tracking-wider flex items-center gap-1.5">
                    <span class="w-1.5 h-1.5 rounded-full bg-[#024020]/70"></span>
                    <span>Storefront CMS</span>
                </div>
                <div class="space-y-1">
                    <a href="hero_slides.php" class="admin-nav-item group <?php echo $curr_page == 'hero_slides.php' ? 'active' : ''; ?>">
                        <span class="nav-icon-box"><i class="fas fa-images"></i></span>
                        <span class="flex-1">Hero Slides</span>
                    </a>
                    <a href="manage_home.php" class="admin-nav-item group <?php echo $curr_page == 'manage_home.php' ? 'active' : ''; ?>">
                        <span class="nav-icon-box"><i class="fas fa-home"></i></span>
                        <span class="flex-1">Homepage Sections</span>
                    </a>
                    <a href="manage_partners.php" class="admin-nav-item group <?php echo $curr_page == 'manage_partners.php' ? 'active' : ''; ?>">
                        <span class="nav-icon-box"><i class="fas fa-handshake"></i></span>
                        <span class="flex-1">Retail Partners</span>
                    </a>
                    <a href="manage_pages.php" class="admin-nav-item group <?php echo $curr_page == 'manage_pages.php' ? 'active' : ''; ?>">
                        <span class="nav-icon-box"><i class="fas fa-file-contract"></i></span>
                        <span class="flex-1">Story & Policies CMS</span>
                    </a>
                    <a href="manage_contact.php" class="admin-nav-item group <?php echo $curr_page == 'manage_contact.php' ? 'active' : ''; ?>">
                        <span class="nav-icon-box"><i class="fas fa-envelope-open-text"></i></span>
                        <span class="flex-1">Contact Messages</span>
                    </a>
                </div>
            </div>

            <!-- SYSTEM LOGISTICS & SETTINGS -->
            <div>
                <div class="px-2 mb-1.5 text-[10px] font-extrabold text-[#024020] uppercase tracking-wider flex items-center gap-1.5">
                    <span class="w-1.5 h-1.5 rounded-full bg-[#024020]/70"></span>
                    <span>Logistics & Settings</span>
                </div>
                <div class="space-y-1">
                    <a href="shipping.php" class="admin-nav-item group <?php echo $curr_page == 'shipping.php' ? 'active' : ''; ?>">
                        <span class="nav-icon-box"><i class="fas fa-truck-fast"></i></span>
                        <span class="flex-1">Shipping & Rates</span>
                    </a>
                    <a href="settings.php" class="admin-nav-item group <?php echo $curr_page == 'settings.php' ? 'active' : ''; ?>">
                        <span class="nav-icon-box"><i class="fas fa-sliders-h"></i></span>
                        <span class="flex-1">Store Configuration</span>
                    </a>
                </div>
            </div>

        </nav>

        <!-- Sidebar Footer / Executive User Profile Card -->
        <div class="p-3 border-t border-[#1db846] shrink-0">
            <div class="flex items-center justify-between gap-3 p-2.5 rounded-xl bg-white border border-black/5 shadow-sm">
                <div class="flex items-center gap-2.5 min-w-0">
                    <div class="w-8 h-8 rounded-lg bg-[#004f42] text-white font-bold text-xs flex items-center justify-center shrink-0 shadow-2xs">
                        <?php echo strtoupper(substr($_SESSION['user_name'] ?? 'A', 0, 1)); ?>
                    </div>
                    <div class="min-w-0">
                        <div class="text-xs font-bold text-slate-800 truncate"><?php echo htmlspecialchars($_SESSION['user_name'] ?? 'Admin'); ?></div>
                        <div class="text-[10px] text-emerald-800 font-bold flex items-center gap-1">
                            <span class="w-1.5 h-1.5 rounded-full bg-[#29e258]"></span> Super Admin
                        </div>
                    </div>
                </div>
                <div class="flex items-center gap-1 shrink-0">
                    <a href="../" target="_blank" class="w-7 h-7 rounded-lg bg-slate-50 hover:bg-slate-100 text-slate-600 hover:text-slate-900 border border-slate-200/60 flex items-center justify-center transition-colors" title="View Storefront">
                        <i class="fas fa-external-link-alt text-[10px]"></i>
                    </a>
                    <a href="../logout.php" class="w-7 h-7 rounded-lg bg-slate-50 hover:bg-rose-50 text-slate-600 hover:text-rose-600 border border-slate-200/60 flex items-center justify-center transition-colors" title="Sign out">
                        <i class="fas fa-power-off text-[10px]"></i>
                    </a>
                </div>
            </div>
        </div>
    </aside>

    <!-- CONTENT WRAPPER -->
    <div class="admin-content-wrapper">
        
        <!-- TOP NAVIGATION BAR -->
        <header class="h-16 bg-white/95 backdrop-blur-md border-b border-slate-200 sticky top-0 z-40 px-2 lg:px-6 flex items-center justify-between gap-4 transition-all">
            
            <!-- Left: Toggle & Breadcrumb -->
            <div class="flex items-center gap-3 md:gap-4 min-w-0">
                <button onclick="toggleSidebar(true)" class="lg:hidden w-9 h-9 flex items-center justify-center rounded-lg border border-slate-200 text-slate-700 hover:bg-slate-50 transition-colors" aria-label="Open sidebar">
                    <i class="fas fa-bars text-sm"></i>
                </button>
                
                <div class="flex items-center gap-2 min-w-0">
                    <div class="w-8 h-8 rounded-lg bg-emerald-50 text-[#004f42] flex items-center justify-center shrink-0 border border-emerald-100 hidden sm:flex">
                        <i class="<?php echo $active_meta['icon']; ?> text-xs"></i>
                    </div>
                    <div class="min-w-0">
                        <div class="flex items-center gap-1.5 text-xs text-slate-400 font-medium hidden sm:flex" >
                            <span class="truncate"><?php echo $active_meta['group']; ?></span>
                            <i class="fas fa-chevron-right text-[9px] text-slate-300"></i>
                            <span class="text-slate-800 font-semibold truncate"><?php echo $active_meta['title']; ?></span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right: Search, Clock, Alerts, Profile -->
            <div class="flex items-center gap-2.5 sm:gap-3 shrink-0">
                
                <!-- OMNI MOBILE BACKDROP -->
                <div id="omni-mobile-backdrop" class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs z-40 hidden sm:hidden" onclick="closeOmniSearch()"></div>

                <!-- OMNI COMMAND PALETTE SEARCH -->
                <div class="relative" id="omni-search-container">
                    <div class="relative w-44 sm:w-64 md:w-80 lg:w-96 transition-all">
                        <input type="text" id="omni-search-input" autocomplete="off" spellcheck="false" placeholder="Search orders, snacks, users or jump to..." class="w-full bg-slate-50 hover:bg-slate-100/70 focus:bg-white border border-slate-200 focus:border-[#004f42] rounded-xl pl-9 pr-14 py-1.5 text-xs font-medium text-slate-800 placeholder:text-slate-400 outline-none transition-all shadow-xs focus:ring-2 focus:ring-[#004f42]/10">
                        <i id="omni-search-icon" class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                        <button type="button" id="omni-clear-btn" class="hidden absolute right-8 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-700 text-xs p-1" title="Clear">
                            <i class="fas fa-times-circle"></i>
                        </button>
                        <kbd class="hidden sm:inline-flex items-center gap-0.5 absolute right-2.5 top-1/2 -translate-y-1/2 text-[10px] font-semibold text-slate-400 bg-white border border-slate-200 px-1.5 py-0.5 rounded shadow-2xs pointer-events-none">
                            <span class="text-[9px]">⌘</span>K
                        </kbd>
                    </div>

                    <!-- Command Palette Dropdown -->
                    <div id="omni-results" class="hidden absolute right-0 top-full mt-2 w-[calc(100vw-2rem)] sm:w-[560px] md:w-[620px] bg-white rounded-2xl shadow-2xl border border-slate-200 overflow-hidden z-50 text-left">
                        <!-- Category Tabs Bar -->
                        <div id="omni-category-tabs" class="flex items-center gap-1 p-2 bg-slate-50 border-b border-slate-200 overflow-x-auto text-[11px] font-semibold text-slate-600 no-scrollbar">
                            <button type="button" class="omni-tab-btn active px-2.5 py-1 rounded-lg bg-white text-slate-900 border border-slate-200 shadow-2xs flex items-center gap-1.5 shrink-0" data-filter="all">
                                <span>All</span>
                                <span id="tab-count-all" class="text-[10px] px-1.5 py-0.2 rounded-full bg-slate-100 text-slate-600 font-bold">0</span>
                            </button>
                            <button type="button" class="omni-tab-btn px-2.5 py-1 rounded-lg hover:bg-white text-slate-600 hover:text-slate-900 transition-colors flex items-center gap-1.5 shrink-0" data-filter="quick_links">
                                <i class="fas fa-bolt text-[10px] text-amber-500"></i> Actions
                                <span id="tab-count-quick_links" class="text-[10px] px-1.5 py-0.2 rounded-full bg-slate-100 text-slate-600 font-bold">0</span>
                            </button>
                            <button type="button" class="omni-tab-btn px-2.5 py-1 rounded-lg hover:bg-white text-slate-600 hover:text-slate-900 transition-colors flex items-center gap-1.5 shrink-0" data-filter="products">
                                <i class="fas fa-cube text-[10px] text-emerald-600"></i> Products
                                <span id="tab-count-products" class="text-[10px] px-1.5 py-0.2 rounded-full bg-slate-100 text-slate-600 font-bold">0</span>
                            </button>
                            <button type="button" class="omni-tab-btn px-2.5 py-1 rounded-lg hover:bg-white text-slate-600 hover:text-slate-900 transition-colors flex items-center gap-1.5 shrink-0" data-filter="orders">
                                <i class="fas fa-box text-[10px] text-sky-600"></i> Orders
                                <span id="tab-count-orders" class="text-[10px] px-1.5 py-0.2 rounded-full bg-slate-100 text-slate-600 font-bold">0</span>
                            </button>
                            <button type="button" class="omni-tab-btn px-2.5 py-1 rounded-lg hover:bg-white text-slate-600 hover:text-slate-900 transition-colors flex items-center gap-1.5 shrink-0" data-filter="customers">
                                <i class="fas fa-user text-[10px] text-indigo-600"></i> Customers
                                <span id="tab-count-customers" class="text-[10px] px-1.5 py-0.2 rounded-full bg-slate-100 text-slate-600 font-bold">0</span>
                            </button>
                            <button type="button" class="omni-tab-btn px-2.5 py-1 rounded-lg hover:bg-white text-slate-600 hover:text-slate-900 transition-colors flex items-center gap-1.5 shrink-0" data-filter="coupons">
                                <i class="fas fa-ticket-alt text-[10px] text-purple-600"></i> Coupons
                                <span id="tab-count-coupons" class="text-[10px] px-1.5 py-0.2 rounded-full bg-slate-100 text-slate-600 font-bold">0</span>
                            </button>
                        </div>

                        <!-- Results Content -->
                        <div id="omni-results-content" class="max-h-[400px] overflow-y-auto p-2"></div>

                        <!-- Command Palette Footer -->
                        <div class="px-3.5 py-2 bg-slate-50 border-t border-slate-200 flex items-center justify-between text-[11px] text-slate-500 font-medium">
                            <div class="flex items-center gap-3">
                                <span class="flex items-center gap-1"><kbd class="px-1.5 py-0.5 bg-white border border-slate-200 rounded text-[9px] font-bold text-slate-600 shadow-2xs">↑</kbd><kbd class="px-1.5 py-0.5 bg-white border border-slate-200 rounded text-[9px] font-bold text-slate-600 shadow-2xs">↓</kbd> Navigate</span>
                                <span class="flex items-center gap-1"><kbd class="px-1.5 py-0.5 bg-white border border-slate-200 rounded text-[9px] font-bold text-slate-600 shadow-2xs">↵</kbd> Select</span>
                                <span class="flex items-center gap-1"><kbd class="px-1.5 py-0.5 bg-white border border-slate-200 rounded text-[9px] font-bold text-slate-600 shadow-2xs">Tab</kbd> Filter</span>
                                <span class="flex items-center gap-1"><kbd class="px-1.5 py-0.5 bg-white border border-slate-200 rounded text-[9px] font-bold text-slate-600 shadow-2xs">esc</kbd> Dismiss</span>
                            </div>
                            <span class="text-[10px] text-slate-400 font-bold uppercase tracking-wider hidden sm:inline">Command Palette</span>
                        </div>
                    </div>
                </div>

                <!-- LIVE STORE STATUS & CLOCK -->
                <div class="hidden xl:flex items-center gap-2 px-3 py-1.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-medium text-slate-600">
                    <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                    <span id="header-clock" class="tabular-nums font-semibold text-slate-700">--:--</span>
                </div>

                <!-- NOTIFICATIONS BUTTON & DROPDOWN -->
                <div class="relative" id="notif-dropdown">
                    <button onclick="toggleNotifDropdown(event)" class="w-9 h-9 rounded-xl border border-slate-200 bg-white hover:bg-slate-50 flex items-center justify-center text-slate-600 hover:text-slate-900 transition-colors relative" aria-label="Notifications">
                        <i class="fas fa-bell text-xs"></i>
                        <?php if ($unread_count > 0): ?>
                            <span class="absolute top-1.5 right-1.5 w-2 h-2 bg-rose-500 rounded-full ring-2 ring-white"></span>
                        <?php endif; ?>
                    </button>
                    
                    <div id="notif-dropdown-content" class="hidden absolute right-0 top-full mt-2 w-80 sm:w-88 bg-white rounded-xl shadow-xl border border-slate-200 z-50 overflow-hidden">
                        <div class="px-4 py-3 border-b border-slate-100 flex items-center justify-between bg-slate-50">
                            <span class="text-xs font-bold text-slate-800">Notifications</span>
                            <?php if ($unread_count > 0): ?>
                                <span class="bg-rose-100 text-rose-700 text-[10px] font-semibold px-2 py-0.5 rounded-full"><?php echo $unread_count; ?> Unread</span>
                            <?php else: ?>
                                <span class="text-[11px] text-slate-400">All caught up</span>
                            <?php endif; ?>
                        </div>
                        <div class="max-h-80 overflow-y-auto" id="notif-list">
                            <?php if (empty($unread_notifs)): ?>
                                <div class="py-8 px-4 text-center text-slate-400">
                                    <i class="fas fa-check-circle text-slate-300 text-2xl mb-2"></i>
                                    <p class="text-xs font-medium">No new notifications</p>
                                </div>
                            <?php else: foreach ($unread_notifs as $notif): ?>
                                <a href="<?php echo htmlspecialchars($notif['link'] ?: '#'); ?>" onclick="markRead(<?php echo $notif['id']; ?>)" class="block px-4 py-3 hover:bg-slate-50 border-b border-slate-50 last:border-0 transition-colors">
                                    <p class="text-xs font-semibold text-slate-800 leading-snug"><?php echo htmlspecialchars($notif['message']); ?></p>
                                    <span class="text-[10px] text-slate-400 mt-1 flex items-center gap-1">
                                        <i class="far fa-clock text-[9px]"></i> <?php echo get_time_ago($notif['created_at']); ?>
                                    </span>
                                </a>
                            <?php endforeach; endif; ?>
                        </div>
                    </div>
                </div>

                <!-- USER MENU -->
                <div class="relative" id="user-menu-dropdown">
                    <button onclick="document.getElementById('user-menu-content').classList.toggle('hidden')" class="flex items-center gap-2 p-1 pl-1.5 sm:pr-3 rounded-xl border border-slate-200 bg-white hover:bg-slate-50 transition-colors">
                        <div class="w-7 h-7 rounded-lg bg-[#004f42] text-white flex items-center justify-center font-bold text-xs shadow-2xs">
                            A
                        </div>
                        <span class="hidden sm:inline-block text-xs font-semibold text-slate-800">Admin</span>
                        <i class="fas fa-chevron-down text-[9px] text-slate-400 hidden sm:inline-block"></i>
                    </button>
                    
                    <div id="user-menu-content" class="hidden absolute right-0 top-full mt-2 w-48 bg-white rounded-xl shadow-xl border border-slate-200 z-50 overflow-hidden py-1">
                        <div class="px-4 py-2 border-b border-slate-100">
                            <p class="text-xs font-bold text-slate-800">Administrator</p>
                            <p class="text-[11px] text-slate-400 truncate">Super User</p>
                        </div>
                        <a href="settings.php" class="flex items-center gap-2.5 px-4 py-2 text-xs font-medium text-slate-700 hover:bg-slate-50 transition-colors">
                            <i class="fas fa-cog text-slate-400 text-xs w-4"></i> Store Settings
                        </a>
                        <a href="../" target="_blank" class="flex items-center gap-2.5 px-4 py-2 text-xs font-medium text-slate-700 hover:bg-slate-50 transition-colors">
                            <i class="fas fa-external-link-alt text-slate-400 text-xs w-4"></i> View Live Store
                        </a>
                        <div class="border-t border-slate-100 my-1"></div>
                        <a href="../logout.php" class="flex items-center gap-2.5 px-4 py-2 text-xs font-medium text-rose-600 hover:bg-rose-50 transition-colors">
                            <i class="fas fa-power-off text-rose-500 text-xs w-4"></i> Logout
                        </a>
                    </div>
                </div>

            </div>
        </header>

        <!-- MAIN PAGE CONTAINER -->
        <main class="flex-1 p-4 sm:p-6 lg:p-8 max-w-[1600px] w-full mx-auto">
            
            <!-- Global Toast Container -->
            <div id="toast-container" class="fixed top-20 right-6 z-[9999] flex flex-col gap-2 pointer-events-none"></div>

            <!-- Flash Session Alerts -->
            <?php if (isset($_SESSION['success'])): ?>
                <div class="mb-5 p-3.5 bg-emerald-50 border border-emerald-200 text-emerald-800 rounded-xl font-medium text-xs flex items-center justify-between shadow-2xs anim-fade-in flash-msg">
                    <div class="flex items-center gap-2.5">
                        <i class="fas fa-check-circle text-emerald-600 text-sm"></i>
                        <span><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></span>
                    </div>
                    <button onclick="this.parentElement.remove()" class="text-emerald-500 hover:text-emerald-700 p-1"><i class="fas fa-times"></i></button>
                </div>
            <?php endif; ?>

            <?php if (isset($_SESSION['error'])): ?>
                <div class="mb-5 p-3.5 bg-rose-50 border border-rose-200 text-rose-800 rounded-xl font-medium text-xs flex items-center justify-between shadow-2xs anim-fade-in flash-msg">
                    <div class="flex items-center gap-2.5">
                        <i class="fas fa-exclamation-circle text-rose-600 text-sm"></i>
                        <span><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></span>
                    </div>
                    <button onclick="this.parentElement.remove()" class="text-rose-500 hover:text-rose-700 p-1"><i class="fas fa-times"></i></button>
                </div>
            <?php endif; ?>

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
            closeOmniSearch();
            document.getElementById('notif-dropdown-content').classList.add('hidden');
            document.getElementById('user-menu-content').classList.add('hidden');
        }
        if (e.key === '/' || (e.metaKey && e.key === 'k') || (e.ctrlKey && e.key === 'k')) {
            e.preventDefault();
            document.getElementById('omni-search-input').focus();
        }
    });

    function toggleNotifDropdown(e) {
        e.stopPropagation();
        document.getElementById('notif-dropdown-content').classList.toggle('hidden');
        document.getElementById('user-menu-content').classList.add('hidden');
        closeOmniSearch();
    }

    const omniInput = document.getElementById('omni-search-input');
    const omniResults = document.getElementById('omni-results');
    const omniResultsContent = document.getElementById('omni-results-content');
    const omniClearBtn = document.getElementById('omni-clear-btn');
    const omniSearchIcon = document.getElementById('omni-search-icon');
    let searchTimeout = null;
    let omniData = null;
    let activeCategory = 'all';
    let selectedItemIndex = -1;

    function openOmniSearch() {
        if (!omniResults) return;
        omniResults.classList.remove('hidden');
        document.getElementById('omni-mobile-backdrop')?.classList.remove('hidden');
    }

    function closeOmniSearch() {
        if (!omniResults) return;
        omniResults.classList.add('hidden');
        document.getElementById('omni-mobile-backdrop')?.classList.add('hidden');
        selectedItemIndex = -1;
    }

    // Quick Navigation shortcuts for zero-state
    const defaultQuickLinks = [
        { type: 'quick_link', title: 'Dashboard Overview', subtitle: 'Live visitors, store KPIs & sales pulse', url: 'index.php', icon: 'fas fa-chart-pie', category: 'Quick Action' },
        { type: 'quick_link', title: 'Product Analytics & Stats', subtitle: 'Unit sales, revenue & product velocity', url: 'analytics.php', icon: 'fas fa-chart-line', category: 'Quick Action' },
        { type: 'quick_link', title: 'Product Catalog', subtitle: 'Manage prices, stock & snacks', url: 'products.php', icon: 'fas fa-layer-group', category: 'Quick Action' },
        { type: 'quick_link', title: 'Add New Product', subtitle: 'Upload a new snack or combo listing', url: 'product_form.php', icon: 'fas fa-plus-circle', category: 'Quick Action' },
        { type: 'quick_link', title: 'Orders Fulfillment', subtitle: 'Track & process customer shipments', url: 'orders.php', icon: 'fas fa-box-open', category: 'Quick Action' },
        { type: 'quick_link', title: 'Product Sorting', subtitle: 'Drag & drop storefront showcase order', url: 'product_sorting.php', icon: 'fas fa-sort-amount-down', category: 'Quick Action' },
        { type: 'quick_link', title: 'Inventory & Stock Watch', subtitle: 'Low stock alerts and SKU health', url: 'inventory.php', icon: 'fas fa-boxes-stacked', category: 'Quick Action' },
        { type: 'quick_link', title: 'Store Configuration', subtitle: 'Payment gateway, branding & settings', url: 'settings.php', icon: 'fas fa-sliders-h', category: 'Quick Action' }
    ];

    if (omniInput) {
        // Show default shortcuts on focus if empty
        omniInput.addEventListener('focus', () => {
            const q = omniInput.value.trim();
            if (q.length === 0) {
                renderDefaultState();
            } else if (omniData) {
                openOmniSearch();
            }
        });

        omniInput.addEventListener('input', (e) => {
            const q = e.target.value.trim();
            if (q.length > 0) {
                omniClearBtn?.classList.remove('hidden');
            } else {
                omniClearBtn?.classList.add('hidden');
                renderDefaultState();
                return;
            }

            if (q.length < 1) return;

            clearTimeout(searchTimeout);
            if (omniSearchIcon) omniSearchIcon.className = 'fas fa-spinner fa-spin absolute left-3 top-1/2 -translate-y-1/2 text-emerald-600 text-xs';

            searchTimeout = setTimeout(async () => {
                try {
                    const response = await fetch(`api/omni_search.php?q=${encodeURIComponent(q)}`);
                    const data = await response.json();
                    omniData = data;
                    selectedItemIndex = -1;
                    updateTabCounts(data.categories);
                    renderFilteredResults(q);
                } catch (err) {
                    console.error('Search failed', err);
                } finally {
                    if (omniSearchIcon) omniSearchIcon.className = 'fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-xs';
                }
            }, 180);
        });

        // Keyboard Navigation (ArrowUp, ArrowDown, Enter, Tab, Escape)
        omniInput.addEventListener('keydown', (e) => {
            if (omniResults.classList.contains('hidden')) return;

            const visibleItems = omniResultsContent.querySelectorAll('.omni-result-item');
            if (visibleItems.length === 0) return;

            if (e.key === 'ArrowDown') {
                e.preventDefault();
                selectedItemIndex = (selectedItemIndex + 1) >= visibleItems.length ? 0 : selectedItemIndex + 1;
                highlightSelectedItem(visibleItems);
            } else if (e.key === 'ArrowUp') {
                e.preventDefault();
                selectedItemIndex = (selectedItemIndex - 1) < 0 ? visibleItems.length - 1 : selectedItemIndex - 1;
                highlightSelectedItem(visibleItems);
            } else if (e.key === 'Enter') {
                if (selectedItemIndex >= 0 && visibleItems[selectedItemIndex]) {
                    e.preventDefault();
                    visibleItems[selectedItemIndex].click();
                }
            } else if (e.key === 'Tab') {
                e.preventDefault();
                cycleFilterTabs(e.shiftKey ? -1 : 1);
            }
        });
    }

    if (omniClearBtn) {
        omniClearBtn.addEventListener('click', () => {
            omniInput.value = '';
            omniClearBtn.classList.add('hidden');
            omniInput.focus();
            renderDefaultState();
        });
    }

    // Category Tabs click handler
    document.querySelectorAll('.omni-tab-btn').forEach(btn => {
        btn.addEventListener('click', (e) => {
            e.stopPropagation();
            document.querySelectorAll('.omni-tab-btn').forEach(b => {
                b.className = 'omni-tab-btn px-2.5 py-1 rounded-lg hover:bg-white text-slate-600 hover:text-slate-900 transition-colors flex items-center gap-1.5 shrink-0';
            });
            btn.className = 'omni-tab-btn active px-2.5 py-1 rounded-lg bg-white text-slate-900 border border-slate-200 shadow-2xs flex items-center gap-1.5 shrink-0 font-bold';
            activeCategory = btn.getAttribute('data-filter') || 'all';
            selectedItemIndex = -1;
            renderFilteredResults(omniInput.value.trim());
        });
    });

    function cycleFilterTabs(direction = 1) {
        const tabs = Array.from(document.querySelectorAll('.omni-tab-btn'));
        const currentIdx = tabs.findIndex(t => t.getAttribute('data-filter') === activeCategory);
        let nextIdx = currentIdx + direction;
        if (nextIdx < 0) nextIdx = tabs.length - 1;
        if (nextIdx >= tabs.length) nextIdx = 0;
        tabs[nextIdx].click();
    }

    function highlightSelectedItem(items) {
        items.forEach((item, idx) => {
            if (idx === selectedItemIndex) {
                item.classList.add('bg-emerald-50/90', 'border-l-4', 'border-[#004f42]');
                item.classList.remove('border-l-4', 'border-transparent');
                item.scrollIntoView({ block: 'nearest', behavior: 'smooth' });
            } else {
                item.classList.remove('bg-emerald-50/90', 'border-l-4', 'border-[#004f42]');
                item.classList.add('border-l-4', 'border-transparent');
            }
        });
    }

    function updateTabCounts(cats) {
        if (!cats) return;
        const total = (cats.quick_links?.length || 0) + (cats.products?.length || 0) + (cats.orders?.length || 0) + (cats.customers?.length || 0) + (cats.coupons?.length || 0);
        document.getElementById('tab-count-all').textContent = total;
        document.getElementById('tab-count-quick_links').textContent = cats.quick_links?.length || 0;
        document.getElementById('tab-count-products').textContent = cats.products?.length || 0;
        document.getElementById('tab-count-orders').textContent = cats.orders?.length || 0;
        document.getElementById('tab-count-customers').textContent = cats.customers?.length || 0;
        document.getElementById('tab-count-coupons').textContent = cats.coupons?.length || 0;
    }

    function renderDefaultState() {
        document.getElementById('tab-count-all').textContent = defaultQuickLinks.length;
        document.getElementById('tab-count-quick_links').textContent = defaultQuickLinks.length;
        document.getElementById('tab-count-products').textContent = 0;
        document.getElementById('tab-count-orders').textContent = 0;
        document.getElementById('tab-count-customers').textContent = 0;
        document.getElementById('tab-count-coupons').textContent = 0;

        let html = `
            <div class="px-3 py-1.5 text-[10px] font-bold text-slate-400 uppercase tracking-widest flex items-center justify-between">
                <span>Quick Navigation</span>
                <span class="text-[9px] lowercase font-normal text-slate-400">Jump anywhere instantly</span>
            </div>
            <div class="space-y-0.5">
        `;
        defaultQuickLinks.forEach((item, idx) => {
            html += renderItemCard(item, '');
        });
        html += `</div>`;
        omniResultsContent.innerHTML = html;
        openOmniSearch();
    }

    function highlightText(text, query) {
        if (!query || !text) return text || '';
        const regex = new RegExp(`(${query.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')})`, 'gi');
        return String(text).replace(regex, '<mark class="bg-amber-100 text-slate-900 font-bold px-0.5 rounded">$1</mark>');
    }

    function renderItemCard(res, query) {
        const titleHighlight = highlightText(res.title, query);
        const subHighlight = highlightText(res.subtitle, query);

        let iconOrImg = '';
        if (res.image) {
            iconOrImg = `<img src="../${res.image}" class="w-9 h-9 rounded-lg object-cover shrink-0 border border-slate-200 bg-slate-50">`;
        } else {
            const iconMap = {
                'quick_link': 'fas fa-bolt text-amber-600 bg-amber-50 border-amber-200',
                'order': 'fas fa-box text-sky-600 bg-sky-50 border-sky-200',
                'customer': 'fas fa-user text-indigo-600 bg-indigo-50 border-indigo-200',
                'coupon': 'fas fa-ticket-alt text-purple-600 bg-purple-50 border-purple-200',
                'product': 'fas fa-cube text-emerald-700 bg-emerald-50 border-emerald-200'
            };
            const cls = iconMap[res.type] || 'fas fa-compass text-slate-600 bg-slate-100 border-slate-200';
            iconOrImg = `<div class="w-9 h-9 rounded-lg flex items-center justify-center shrink-0 border text-xs ${cls}"><i class="${res.icon || 'fas fa-arrow-right'}"></i></div>`;
        }

        let badgeHtml = '';
        if (res.badge) {
            const badgeClasses = {
                'emerald': 'bg-emerald-50 text-emerald-800 border-emerald-200',
                'sky': 'bg-sky-50 text-sky-800 border-sky-200',
                'amber': 'bg-amber-50 text-amber-800 border-amber-200',
                'rose': 'bg-rose-50 text-rose-800 border-rose-200',
                'indigo': 'bg-indigo-50 text-indigo-800 border-indigo-200',
                'slate': 'bg-slate-100 text-slate-700 border-slate-200'
            };
            const bCls = badgeClasses[res.badge_color] || badgeClasses.slate;
            badgeHtml = `<span class="px-2 py-0.5 rounded-md text-[10px] font-bold border shrink-0 ${bCls}">${res.badge}</span>`;
        }

        return `
            <a href="${res.url}" class="omni-result-item flex items-center gap-3 p-2 hover:bg-slate-50/90 rounded-xl transition-all border-l-4 border-transparent group">
                ${iconOrImg}
                <div class="min-w-0 flex-1">
                    <p class="text-xs font-bold text-slate-800 truncate group-hover:text-[#004f42] transition-colors">${titleHighlight}</p>
                    <p class="text-[11px] text-slate-500 truncate mt-0.5">${subHighlight}</p>
                </div>
                ${badgeHtml}
                <i class="fas fa-arrow-right text-[10px] text-slate-300 group-hover:text-[#004f42] group-hover:translate-x-0.5 transition-all shrink-0 ml-1"></i>
            </a>
        `;
    }

    function renderFilteredResults(query) {
        if (!omniData || !omniData.categories) {
            renderDefaultState();
            return;
        }

        const cats = omniData.categories;
        let itemsToRender = [];

        if (activeCategory === 'all') {
            itemsToRender = [
                ...(cats.quick_links || []),
                ...(cats.products || []),
                ...(cats.orders || []),
                ...(cats.customers || []),
                ...(cats.coupons || [])
            ];
        } else {
            itemsToRender = cats[activeCategory] || [];
        }

        if (itemsToRender.length === 0) {
            omniResultsContent.innerHTML = `
                <div class="py-12 px-4 text-center">
                    <div class="w-12 h-12 rounded-full bg-slate-100 text-slate-400 flex items-center justify-center mx-auto mb-3 text-base">
                        <i class="fas fa-search"></i>
                    </div>
                    <p class="text-xs font-bold text-slate-700">No results found for "${query}"</p>
                    <p class="text-[11px] text-slate-400 mt-1">Try searching by order number (#1024), customer email, product name, or "settings".</p>
                </div>
            `;
            openOmniSearch();
            return;
        }

        let html = '';
        if (activeCategory === 'all') {
            const sections = [
                { key: 'quick_links', label: '⚡ Quick Navigation', icon: 'fas fa-bolt' },
                { key: 'products', label: '📦 Products', icon: 'fas fa-cube' },
                { key: 'orders', label: '🛒 Customer Orders', icon: 'fas fa-box' },
                { key: 'customers', label: '👥 Registered Customers', icon: 'fas fa-users' },
                { key: 'coupons', label: '🎟️ Promo Vouchers', icon: 'fas fa-ticket-alt' }
            ];

            sections.forEach(sec => {
                const secItems = cats[sec.key] || [];
                if (secItems.length > 0) {
                    html += `
                        <div class="pt-2 pb-1 first:pt-0">
                            <div class="px-2.5 py-1 text-[10px] font-bold text-slate-400 uppercase tracking-widest flex items-center justify-between">
                                <span>${sec.label}</span>
                                <span class="text-[9px] bg-slate-100 px-1.5 py-0.2 rounded font-bold text-slate-500">${secItems.length}</span>
                            </div>
                            <div class="space-y-0.5 mt-0.5">
                                ${secItems.map(item => renderItemCard(item, query)).join('')}
                            </div>
                        </div>
                    `;
                }
            });
        } else {
            html = `<div class="space-y-0.5">${itemsToRender.map(item => renderItemCard(item, query)).join('')}</div>`;
        }

        omniResultsContent.innerHTML = html;
        openOmniSearch();
    }

    document.addEventListener('click', (e) => {
        if (!document.getElementById('omni-search-container')?.contains(e.target)) closeOmniSearch();
        if (!document.getElementById('notif-dropdown')?.contains(e.target)) document.getElementById('notif-dropdown-content')?.classList.add('hidden');
        if (!document.getElementById('user-menu-dropdown')?.contains(e.target)) document.getElementById('user-menu-content')?.classList.add('hidden');
    });

    // Toast Notification Utility
    function showToast(message, type = 'success') {
        const container = document.getElementById('toast-container');
        if(!container) return;
        const toast = document.createElement('div');
        toast.className = `p-3 rounded-xl shadow-lg border flex items-center gap-2.5 anim-fade-in pointer-events-auto min-w-[280px] max-w-sm text-xs font-medium ${type === 'success' ? 'bg-slate-900 text-white border-slate-800' : 'bg-rose-50 text-rose-800 border-rose-200'}`;
        toast.innerHTML = `
            <i class="fas ${type === 'success' ? 'fa-check-circle text-emerald-400' : 'fa-exclamation-circle text-rose-500'}"></i>
            <span class="flex-1">${message}</span>
        `;
        container.appendChild(toast);
        setTimeout(() => {
            toast.style.opacity = '0';
            toast.style.transform = 'translateY(-8px)';
            toast.style.transition = 'all 0.2s ease';
            setTimeout(() => toast.remove(), 200);
        }, 4000);
    }

    // Auto-hide existing flash messages
    document.querySelectorAll('.flash-msg').forEach(msg => {
        setTimeout(() => {
            msg.style.opacity = '0';
            msg.style.transform = 'translateY(-10px)';
            msg.style.transition = 'all 0.25s ease';
            setTimeout(() => msg.remove(), 250);
        }, 5000);
    });

    // Background Queue Trigger
    setTimeout(() => {
        fetch('<?php echo get_url('process_queue.php'); ?>')
            .then(r => r.json())
            .then(data => {
                if(data.processed > 0) console.log(`Background queue processed ${data.processed} items.`);
            }).catch(e => {});
    }, 2500);
    </script>
