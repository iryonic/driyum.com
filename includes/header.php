<?php
// Functionality checks
require_once 'includes/affiliate_tracker.php'; // Affiliate Tracking
$is_logged_in = isset($_SESSION['user_id']); // Assuming session format

// Maintenance Mode Check
$maintenance = get_setting('maintenance_mode', 'off');
if ($maintenance === 'on' && !isset($_SESSION['is_admin']) && basename($_SERVER['PHP_SELF']) !== 'maintenance.php' && strpos($_SERVER['PHP_SELF'], 'admin/') === false) {
    header("Location: " . get_url('maintenance.php'));
    exit;
}

// Fetch Dynamic Settings
$store_name = get_setting('store_name', 'DRIYUM');
$announcement_raw = get_setting('announcement_text', '🚀 Free Shipping on All Orders Over ₹499 • 🌿 100% Organic & Natural');
$announcement_parts = explode('•', $announcement_raw);
// Run periodic tasks (Abandoned cart reminders, etc)
run_crons();
?>

<!-- Global SEO tags are handled in head.php -->
<?php
// Header specific logic if needed
?>

<!-- INFINITE MARQUEE -->
<div class="bg-black text-white overflow-hidden py-2.5 relative z-[40] border-b border-[#19DC7E]/30">
    <!-- Gradient Overlay for fade effect on edges -->
    <div class="absolute left-0 top-0 bottom-0 w-20 bg-gradient-to-r from-black to-transparent z-10"></div>
    <div class="absolute right-0 top-0 bottom-0 w-20 bg-gradient-to-l from-black to-transparent z-10"></div>

    <div class="flex whitespace-nowrap animate-[marquee_20s_linear_infinite] hover:[animation-play-state:paused] items-center">
        <?php for($i=0; $i<10; $i++): ?>
            <?php foreach($announcement_parts as $msg): ?>
                <div class="flex items-center gap-3 mx-6">
                    <span class="w-1.5 h-1.5 rounded-full bg-[#19DC7E] animate-pulse"></span>
                    <span class="font-['Fredoka'] font-bold text-xs tracking-widest uppercase text-gray-200">
                        <?php echo trim($msg); ?>
                    </span>
                    <i class="fas fa-star text-[8px] text-[#19DC7E]/50 ml-3"></i>
                </div>
            <?php endforeach; ?>
        <?php endfor; ?>
    </div>
</div>

<style>
@keyframes marquee { 0% { transform: translateX(0); } 100% { transform: translateX(-50%); } }
</style>

<!-- DESKTOP HEADER -->
<header class="hidden md:block sticky top-0 z-[100] bg-white/80 backdrop-blur-xl border-b border-gray-100 transition-all shadow-sm">
    <div class="container mx-auto px-4 lg:px-8 h-20 lg:h-24 flex items-center justify-between">
        
        <!-- Logo -->
        <a href="<?php echo get_url(''); ?>" class="flex items-center gap-2 group shrink-0" aria-label="<?php echo $store_name; ?> - Home">
            <img src="<?php echo get_url('assets/images/logo.png'); ?>" alt="<?php echo $store_name; ?> Logo" width="100" height="100" class="w-20 lg:w-24">
        </a>

        <!-- Navigation with Mega Menu -->
        <nav class="h-full flex items-center gap-3 lg:gap-8 overflow-hidden" aria-label="Desktop Navigation">
            <a href="<?php echo get_url(''); ?>" class="font-['Fredoka'] font-semibold text-gray-600 hover:text-[#19DC7E] transition whitespace-nowrap text-sm lg:text-base">Home</a>
            <a href="<?php echo get_url('shop'); ?>" class="font-['Fredoka'] font-semibold text-gray-600 hover:text-[#19DC7E] transition whitespace-nowrap text-sm lg:text-base">Shop</a>
            <a href="<?php echo get_url('about'); ?>" class="font-['Fredoka'] font-semibold text-gray-600 hover:text-[#19DC7E] transition whitespace-nowrap text-sm lg:text-base">Story</a>
            
            <!-- MEGA MENU TRIGGER -->
            <div class="mega-menu-trigger h-full flex items-center cursor-pointer group">
                <a href="<?php echo get_url('shop'); ?>" class="font-['Fredoka'] font-semibold text-gray-600 group-hover:text-[#19DC7E] transition py-2 whitespace-nowrap text-sm lg:text-base">
                     Categories <i class="fas fa-chevron-down ml-1 text-[10px] opacity-50" aria-hidden="true"></i>
                </a>
                
                <!-- MEGA MENU CONTENT -->
                <div class="mega-menu">
                    <div class="container mx-auto grid grid-cols-4 gap-8">
                        <div>
                            <h4 class="text-[#19DC7E] mb-4 text-lg font-['Fredoka']">Fruits</h4>
                            <ul class="space-y-2 font-['Outfit'] text-gray-500">
                                <li><a href="<?php echo get_url('category/fruit-snacks?q=apple'); ?>" class="hover:text-black hover:translate-x-1 transition-transform inline-block">Kashmiri Apple</a></li>
                                <li><a href="<?php echo get_url('category/fruit-snacks?q=kiwi'); ?>" class="hover:text-black hover:translate-x-1 transition-transform inline-block">Premium Kiwi</a></li>
                                <li><a href="<?php echo get_url('category/fruit-snacks?q=banana'); ?>" class="hover:text-black hover:translate-x-1 transition-transform inline-block">Dried Banana</a></li>
                            </ul>
                        </div>
                        <div>
                            <h4 class="text-[#19DC7E] mb-4 text-lg font-['Fredoka']">Vegetable</h4>
                            <ul class="space-y-2 font-['Outfit'] text-gray-500">
                                <li><a href="<?php echo get_url('category/hokh-suin?q=al-hach'); ?>" class="hover:text-black hover:translate-x-1 transition-transform inline-block">Al Hach (Bottle Gourd)</a></li>
                                <li><a href="<?php echo get_url('category/hokh-suin?q=vangan-hach'); ?>" class="hover:text-black hover:translate-x-1 transition-transform inline-block">Vangan Hach (Brinjal)</a></li>
                            </ul>
                        </div>
                        <div>
                            <h4 class="text-[#19DC7E] mb-4 text-lg font-['Fredoka']">Combos</h4>
                            <ul class="space-y-2 font-['Outfit'] text-gray-500">
                                <li><a href="<?php echo get_url('category/combos'); ?>" class="hover:text-black hover:translate-x-1 transition-transform inline-block">Value 3-Packs</a></li>
                                <li><a href="<?php echo get_url('category/combos?q=mix'); ?>" class="hover:text-black hover:translate-x-1 transition-transform inline-block">Mixed Fruit Packs</a></li>
                                <li><a href="<?php echo get_url('category/combos?q=hamper'); ?>" class="hover:text-black hover:translate-x-1 transition-transform inline-block">Grand Kashmir Hamper</a></li>
                            </ul>
                        </div>
                         <!-- Highlight -->
                        <div class="bg-[#f0fdf4] rounded-2xl p-6 flex gap-4 items-center border border-[#19DC7E]/20">
                            <div class="flex-1">
                                <span class="badge bg-[#19DC7E] text-white mb-2 inline-block">BEST VALUE</span>
                                <h3 class="text-xl mb-2 font-['Fredoka']">The Grand Hamper</h3>
                                <a href="<?php echo get_url('product/grand-kashmir-hamper'); ?>" class="btn-chunky bg-white text-black text-[10px] py-2 px-4 shadow-sm border-2 border-gray-100 hover:border-[#19DC7E]">All in One</a>
                            </div>
                            <img src="<?php echo get_url('assets/images/products/hamper.jpg'); ?>" alt="Grand Kashmir Hamper" width="80" height="80" class="w-20 h-20 object-contain rounded-xl mix-blend-multiply">
                        </div>
                    </div>
                </div>
            </div>

            <a href="<?php echo get_url('track'); ?>" class="font-['Fredoka'] font-semibold text-gray-600 hover:text-[#19DC7E] transition whitespace-nowrap text-sm lg:text-base hidden xl:block">Track Order</a>
            <a href="<?php echo get_url('contact'); ?>" class="font-['Fredoka'] font-semibold text-gray-600 hover:text-[#19DC7E] transition whitespace-nowrap text-sm lg:text-base hidden xl:block">Contact</a>
        </nav>

        <!-- Actions -->
        <div class="flex items-center gap-2 lg:gap-4 shrink-0">
            <button onclick="toggleSearch()" class="w-9 h-9 lg:w-10 lg:h-10 rounded-full bg-white border-2 border-gray-100 flex items-center justify-center text-gray-600 hover:border-[#19DC7E] hover:text-[#19DC7E] transition" aria-label="Open search">
                <i class="fas fa-search text-sm lg:text-base"></i>
            </button>
            <a href="<?php echo get_url('wishlist'); ?>" class="w-9 h-9 lg:w-10 lg:h-10 rounded-full bg-white border-2 border-gray-100 flex items-center justify-center text-gray-600 hover:border-[#19DC7E] hover:text-red-500 transition" aria-label="View Favorites">
                <i class="far fa-heart text-sm lg:text-base"></i>
            </a>

            <!-- Account Dropdown -->
            <div class="relative group">
                <button class="w-9 h-9 lg:w-10 lg:h-10 rounded-full bg-white border-2 border-gray-100 flex items-center justify-center text-gray-600 hover:border-[#19DC7E] hover:text-[#19DC7E] transition" aria-label="Account menu">
                    <i class="fas fa-user text-sm lg:text-base"></i>
                </button>
                <div class="absolute right-0 top-full mt-2 w-48 bg-white rounded-xl shadow-xl border border-gray-100 p-2 opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all transform z-50">
                    <?php if ($is_logged_in): ?>
                        <div class="px-4 py-2 border-b border-gray-100 mb-2">
                             <div class="text-xs text-gray-400 font-bold uppercase">Hello</div>
                             <div class="font-bold text-gray-900"><?php echo htmlspecialchars($_SESSION['user_name']); ?></div> 
                        </div>
                        <a href="<?php echo get_url('account'); ?>" class="block px-4 py-2 hover:bg-gray-50 rounded-lg text-sm"><i class="fas fa-boxes mr-2" aria-hidden="true"></i>My Account</a>
                        <a href="<?php echo get_url('settings'); ?>" class="block px-4 py-2 hover:bg-gray-50 rounded-lg text-sm"><i class="fas fa-cog mr-2" aria-hidden="true"></i>Settings</a>
                        <?php if($_SESSION['is_admin'] == 1) { ?>
                        <a href="<?php echo get_url('admin/'); ?>" class="block px-4 py-2 hover:bg-gray-50 rounded-lg text-sm"> <i class="fas fa-crown mr-2" aria-hidden="true"></i>Admin Panel</a>
                        <?php } ?>  
                        <a href="<?php echo get_url('logout'); ?>" class="block px-4 py-2 hover:bg-red-50 text-red-500 rounded-lg text-sm"><i class="fas fa-sign-out-alt mr-2" aria-hidden="true"></i>Logout</a>
                    <?php else: ?>
                        <a href="<?php echo get_url('login'); ?>" class="block px-4 py-2 hover:bg-gray-50 rounded-lg text-sm text-gray-700 font-bold">Login</a>
                        <a href="<?php echo get_url('register'); ?>" class="block px-4 py-2 hover:bg-[#19DC7E]/10 rounded-lg text-sm text-[#19DC7E] font-bold">Create Account</a>
                        <div class="border-t border-gray-100 my-2"></div>
                        <a href="<?php echo get_url('track'); ?>" class="block px-4 py-2 hover:bg-gray-50 rounded-lg text-sm text-gray-500">Track Order</a>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Cart Trigger -->
            <button onclick="openCartSidebar()" class="relative btn-chunky bg-[#111827] text-white hover:bg-[#19DC7E] border-none px-4 lg:px-6 py-2 shrink-0 h-10 lg:h-12 flex items-center" aria-label="Open shopping bag">
                <i class="fas fa-shopping-bag mr-2 text-sm lg:text-base" aria-hidden="true"></i>
                <span id="cart-count" class="text-xs lg:text-sm font-bold">Bag</span>
            </button>
        </div>
    </div>
</header>

<!-- MOBILE TOP BAR -->
<header class="md:hidden sticky top-0 z-[100] bg-white/80 backdrop-blur-xl border-b border-gray-100 h-20 flex items-center justify-between px-6 shadow-sm">
    <div class="flex items-center gap-2">
        <button id="mobile-search-trigger" onclick="toggleSearch()" class="w-10 h-10 flex items-center justify-center text-gray-700 hover:text-black transition-colors"><i class="fas fa-search"></i></button>
    </div>
    <a href="<?php echo get_url(''); ?>" class="flex items-center"><img src="<?php echo get_url('assets/images/logo.png'); ?>" alt="<?php echo $store_name; ?>" class="w-24"></a>
    <button onclick="openCartSidebar()" class="w-10 h-10 flex items-center justify-center text-gray-700 relative hover:text-black transition-colors">
        <i class="fas fa-shopping-bag"></i>
    </button>
</header>

<!-- MOBILE MENU DRAWER -->
<div id="mobile-menu-overlay" onclick="toggleMobileMenuDrawer()" class="fixed inset-0 bg-black/60 z-[3000] hidden transition-opacity duration-300 backdrop-blur-sm"></div>
<div id="mobile-menu-drawer" class="fixed top-0 left-0 h-full w-[310px] bg-white z-[3001] transform -translate-x-full transition-transform duration-500 flex flex-col shadow-2xl">
    
    <!-- Drawer Header -->
    <div class="px-6 pt-10 pb-6 flex justify-between items-center border-b border-gray-50">
        <a href="<?php echo get_url(''); ?>">
            <img src="<?php echo get_url('assets/images/logo.png'); ?>" class="w-20">
        </a>
        <button onclick="toggleMobileMenuDrawer()" class="w-10 h-10 flex items-center justify-center rounded-xl bg-gray-50 text-gray-400 hover:text-black shadow-sm transition-all active:scale-90">
            <i class="fas fa-times text-lg"></i>
        </button>
    </div>

    <!-- Drawer Content -->
    <div class="flex-1 overflow-y-auto px-6 py-8 no-scrollbar">
        
        <!-- Navigation Section -->
        <div class="mb-10">
            <span class="text-[10px] font-black uppercase tracking-[0.2em] text-gray-400 mb-6 block ml-2">Shop & Explore</span>
            <ul class="space-y-2">
                <li>
                    <a href="<?php echo get_url(''); ?>" class="flex items-center gap-4 px-4 py-3 rounded-2xl bg-[#19DC7E]/5 text-gray-900 group">
                        <div class="w-10 h-10 rounded-xl bg-[#19DC7E] text-black flex items-center justify-center text-sm shadow-sm group-hover:rotate-12 transition-transform">
                            <i class="fas fa-home"></i>
                        </div>
                        <span class="text-xl font-['Fredoka'] font-bold">Home</span>
                    </a>
                </li>
                <li>
                    <a href="<?php echo get_url('shop'); ?>" class="flex items-center gap-4 px-4 py-3 rounded-2xl hover:bg-gray-50 text-gray-600 hover:text-black transition-all group">
                        <div class="w-10 h-10 rounded-xl bg-gray-100 text-gray-400 flex items-center justify-center text-sm group-hover:bg-[#19DC7E] group-hover:text-black transition-colors">
                            <i class="fas fa-shopping-bag"></i>
                        </div>
                        <span class="text-xl font-['Fredoka'] font-bold">Shop Packs</span>
                    </a>
                </li>
                <li>
                    <a href="<?php echo get_url('track'); ?>" class="flex items-center gap-4 px-4 py-3 rounded-2xl hover:bg-gray-50 text-gray-600 hover:text-black transition-all group">
                        <div class="w-10 h-10 rounded-xl bg-gray-100 text-gray-400 flex items-center justify-center text-sm group-hover:bg-[#19DC7E] group-hover:text-black transition-colors">
                            <i class="fas fa-truck-fast"></i>
                        </div>
                        <span class="text-xl font-['Fredoka'] font-bold">Track Order</span>
                    </a>
                </li>
                <li>
                    <a href="<?php echo get_url('about'); ?>" class="flex items-center gap-4 px-4 py-3 rounded-2xl hover:bg-gray-50 text-gray-600 hover:text-black transition-all group">
                        <div class="w-10 h-10 rounded-xl bg-gray-100 text-gray-400 flex items-center justify-center text-sm group-hover:bg-[#19DC7E] group-hover:text-black transition-colors">
                            <i class="fas fa-leaf"></i>
                        </div>
                        <span class="text-xl font-['Fredoka'] font-bold">Our Story</span>
                    </a>
                </li>
                <li>
                    <a href="<?php echo get_url('contact'); ?>" class="flex items-center gap-4 px-4 py-3 rounded-2xl hover:bg-gray-50 text-gray-600 hover:text-black transition-all group">
                        <div class="w-10 h-10 rounded-xl bg-gray-100 text-gray-400 flex items-center justify-center text-sm group-hover:bg-[#19DC7E] group-hover:text-black transition-colors">
                            <i class="fas fa-headset"></i>
                        </div>
                        <span class="text-xl font-['Fredoka'] font-bold">Support</span>
                    </a>
                </li>
            </ul>
        </div>

        <!-- Account Section -->
        <div class="mb-10">
            <span class="text-[10px] font-black uppercase tracking-[0.2em] text-gray-400 mb-6 block ml-2">Personalize</span>
            <div class="bg-gray-900 rounded-[30px] p-6 text-white relative overflow-hidden">
                <!-- Background Blob -->
                <div class="absolute -right-6 -bottom-6 w-24 h-24 bg-[#19DC7E] rounded-full blur-3xl opacity-20"></div>

                <?php if ($is_logged_in): ?>
                    <div class="relative z-10">
                        <div class="flex items-center gap-3 mb-4">
                            <div class="w-10 h-10 rounded-full bg-[#19DC7E] flex items-center justify-center text-black font-black uppercase text-sm">
                                <?php echo substr($_SESSION['user_name'], 0, 1); ?>
                            </div>
                            <h4 class="text-lg font-['Fredoka'] font-black leading-none">Hi, <?php echo explode(' ', htmlspecialchars($_SESSION['user_name']))[0]; ?>!</h4>
                        </div>
                        <div class="grid grid-cols-1 gap-2">
                            <?php if($_SESSION['is_admin'] == 1): ?>
                                <a href="<?php echo get_url('admin/'); ?>" class="flex items-center gap-3 px-4 py-2 bg-white/10 hover:bg-white/20 rounded-xl text-xs font-bold transition-colors">
                                    <i class="fas fa-crown text-[#19DC7E]"></i> Admin Panel
                                </a>
                            <?php endif; ?>
                            <a href="<?php echo get_url('account'); ?>" class="flex items-center gap-3 px-4 py-2 hover:bg-white/10 rounded-xl text-xs font-bold transition-colors">
                                <i class="fas fa-user-circle opacity-50"></i> Dashboard
                            </a>
                            <a href="<?php echo get_url('logout'); ?>" class="flex items-center gap-3 px-4 py-2 text-red-400 hover:bg-red-500/10 rounded-xl text-xs font-bold transition-colors">
                                <i class="fas fa-sign-out-alt"></i> Logout
                            </a>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="relative z-10">
                        <h4 class="text-xl font-['Fredoka'] font-black mb-2">Join the Club</h4>
                        <p class="text-[10px] text-gray-400 font-bold uppercase tracking-widest mb-6">Earn points on every bite.</p>
                        <div class="flex gap-2">
                            <a href="<?php echo get_url('login'); ?>" class="flex-1 bg-white text-black text-center py-3 rounded-xl font-black text-xs hover:scale-105 transition-transform">Login</a>
                            <a href="<?php echo get_url('register'); ?>" class="flex-1 bg-[#19DC7E] text-black text-center py-3 rounded-xl font-black text-xs hover:scale-105 transition-transform shadow-[0_10px_20px_rgba(25,220,126,0.2)]">Join</a>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Support Info & Socials -->
        <div class="px-2">
             <div class="flex flex-col gap-4 mb-8">
                 <div class="flex items-center gap-3 text-gray-400">
                     <i class="fas fa-phone-alt text-xs"></i>
                     <span class="text-xs font-bold">+91 9622 931 931</span>
                 </div>
                 <div class="flex items-center gap-3 text-gray-400">
                     <i class="fas fa-envelope text-xs"></i>
                     <span class="text-xs font-bold">hello@driyum.com</span>
                 </div>
             </div>

             <div class="flex gap-3">
                 <a href="#" class="w-10 h-10 rounded-xl bg-gray-50 flex items-center justify-center text-gray-400 hover:bg-[#E1306C] hover:text-white transition-all"><i class="fab fa-instagram"></i></a>
                 <a href="#" class="w-10 h-10 rounded-xl bg-gray-50 flex items-center justify-center text-gray-400 hover:bg-[#1877F2] hover:text-white transition-all"><i class="fab fa-facebook-f"></i></a>
                 <a href="#" class="w-10 h-10 rounded-xl bg-gray-50 flex items-center justify-center text-gray-400 hover:bg-[#1DA1F2] hover:text-white transition-all"><i class="fab fa-twitter"></i></a>
             </div>
        </div>

    </div>
</div>

<!-- SEARCH MODAL -->
<!-- SEARCH MODAL (PREMIUM) -->
<!-- SEARCH MODAL (PREMIUM) -->
<div id="search-modal-overlay" onclick="toggleSearch()" class="fixed inset-0 bg-black/90 z-[2000] hidden opacity-0 transition-opacity duration-300 flex flex-col items-center justify-start p-4 pt-20 md:p-6 backdrop-blur-md">
    <div id="search-modal" onclick="event.stopPropagation()" class="w-full max-w-5xl relative transform -translate-y-12 transition-transform duration-500 flex flex-col max-h-full">
        
        <button onclick="toggleSearch()" class="absolute -top-16 right-0 md:-top-20 md:right-0 w-10 h-10 md:w-14 md:h-14 flex items-center justify-center rounded-full bg-white/10 text-white text-lg md:text-xl hover:bg-white hover:text-black hover:rotate-90 transition-all duration-300 z-50"><i class="fas fa-times"></i></button>
        
        <div class="text-center mb-4 md:mb-8 shrink-0">
            <span class="inline-block px-3 py-1 md:px-4 md:py-1 rounded-full border border-[#19DC7E]/30 text-[#19DC7E] text-xs md:text-[10px] font-black uppercase tracking-[0.3em] bg-[#19DC7E]/5 mb-2 md:mb-4">Search The Store</span>
        </div>

        <form action="<?php echo get_url('shop'); ?>" method="GET" class="relative group mb-8 md:mb-12 shrink-0">
            <input type="text" name="q" id="header-search-input" placeholder="Craving?" class="search-input-premium w-full text-center placeholder-white/20 focus:placeholder-white/5 text-4xl md:text-7xl h-auto leading-tight">
            <button type="submit" class="absolute right-0 top-1/2 -translate-y-1/2 text-2xl md:text-4xl text-white/30 group-focus-within:text-[#19DC7E] transition-all hover:scale-110"><i class="fas fa-arrow-right"></i></button>
        </form>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 md:gap-8 items-start overflow-hidden flex-1 min-h-0">
            <!-- Quick Links -->
            <div class="hidden md:block">
                 <span class="text-[10px] font-black uppercase tracking-[0.3em] text-white/40 mb-6 block">Trending Now</span>
                 <div class="flex flex-wrap gap-3">
                    <a href="<?php echo get_url('shop?q=apple'); ?>" class="px-6 py-2.5 rounded-[16px] bg-white/5 border border-white/10 text-white font-bold hover:bg-[#19DC7E] hover:text-black hover:border-transparent transition-all hover:-translate-y-1">🍎 Apple Chips</a>
                    <a href="<?php echo get_url('shop?q=walnut'); ?>" class="px-6 py-2.5 rounded-[16px] bg-white/5 border border-white/10 text-white font-bold hover:bg-[#19DC7E] hover:text-black hover:border-transparent transition-all hover:-translate-y-1">🌰 Walnuts</a>
                    <a href="<?php echo get_url('shop?q=apricot'); ?>" class="px-6 py-2.5 rounded-[16px] bg-white/5 border border-white/10 text-white font-bold hover:bg-[#19DC7E] hover:text-black hover:border-transparent transition-all hover:-translate-y-1">🍑 Apricots</a>
                    <a href="<?php echo get_url('shop?q=gift'); ?>" class="px-6 py-2.5 rounded-[16px] bg-white/5 border border-white/10 text-white font-bold hover:bg-[#19DC7E] hover:text-black hover:border-transparent transition-all hover:-translate-y-1">🎁 Gifts</a>
                </div>
            </div>

            <!-- Dynamic Results -->
            <div class="h-full flex flex-col">
                 <span class="text-xs md:text-[10px] font-black uppercase tracking-[0.3em] text-white/40 mb-4 md:mb-6 block shrink-0">Results</span>
                 <div id="search-results" class="overflow-y-auto hide-scrollbar space-y-3 pr-2 flex-1 pb-10">
                     <!-- Populated by JS -->
                 </div>
            </div>
        </div>
    </div>
</div>

<!-- SIDEBAR CART -->
<div id="cart-sidebar-overlay" onclick="closeCartSidebar()" class="fixed inset-0 bg-black/60 z-[2000] hidden opacity-0 transition-opacity duration-300 backdrop-blur-sm"></div>
<div id="cart-sidebar" class="cart-drawer-enhanced fixed top-0 right-0 h-full w-[90%] md:w-[550px] max-w-[550px] bg-[#f8fafc] z-[2001] transform translate-x-full transition-transform duration-500 flex flex-col shadow-2xl">
    <div class="px-6 md:px-8 pt-safe-top pt-8 md:pt-12 pb-6 md:pb-8 flex justify-between items-center bg-white border-b border-gray-100 shrink-0">
        <div>
            <span class="text-[10px] font-black uppercase tracking-[0.3em] text-[#19DC7E] mb-1 block">Your Stash</span>
            <h2 class="text-2xl md:text-3xl font-['Fredoka'] font-black text-gray-900">Shopping Bag.</h2>
        </div>
        <button onclick="closeCartSidebar()" class="w-10 h-10 md:w-12 md:h-12 flex items-center justify-center rounded-2xl bg-gray-50 text-gray-400 hover:bg-black hover:text-white transition-all">
            <i class="fas fa-times text-lg md:text-xl"></i>
        </button>
    </div>
    
    <div id="cart-items-container" class="flex-1 overflow-y-auto px-4 md:px-8 py-6 md:py-10 space-y-4 md:space-y-6">
        <!-- populated by chunky.js -->
    </div>
    
    <div class="px-6 md:px-8 py-8 md:py-10 bg-white border-t border-gray-100 shadow-[0_-10px_40px_rgba(0,0,0,0.03)] relative z-20 shrink-0 pb-12 md:pb-8">
        <div class="flex justify-between items-end mb-4 md:mb-6">
            <div>
                <span class="text-[10px] font-black uppercase tracking-widest text-gray-400 mb-1 block">Subtotal</span>
                <span class="text-[10px] md:text-xs text-gray-300 font-bold hidden md:block">Tax & shipping calculated at checkout</span>
            </div>
            <span id="cart-total" class="text-3xl md:text-4xl font-['Fredoka'] font-black tracking-tighter text-gray-900">₹0</span>
        </div>
        <a href="<?php echo get_url('checkout'); ?>" class="w-full flex items-center justify-between bg-[#111827] text-white p-5 md:p-6 rounded-[24px] hover:bg-[#19DC7E] hover:text-black transition-all group shadow-2xl hover:shadow-[#19DC7E]/20 hover:-translate-y-1">
            <span class="text-lg md:text-xl font-black">Secure Checkout</span>
            <div class="w-8 h-8 md:w-10 md:h-10 bg-white/10 rounded-full flex items-center justify-center group-hover:bg-black/10 group-hover:text-black transition-colors">
                <i class="fas fa-arrow-right -rotate-45 group-hover:rotate-0 transition-transform text-sm md:text-base"></i>
            </div>
        </a>
    </div>
</div>

<script>
    // CRITICAL UI FUNCTIONS - INLINED FOR RELIABILITY
    // These run immediately, ensuring buttons work even if chunky.js is slow/blocked

    // 1. Search Toggle
    if (!window.toggleSearch) {
        window.toggleSearch = function() {
            const overlay = document.getElementById('search-modal-overlay');
            const modal = document.getElementById('search-modal');
            const input = document.getElementById('header-search-input');
            
            if (!overlay || !modal) return;
            
            const isHidden = overlay.classList.contains('hidden');
            if (isHidden) {
                overlay.classList.remove('hidden');
                // Force reflow
                void overlay.offsetWidth; 
                overlay.style.opacity = '1';
                modal.style.transform = 'translateY(0)';
                document.body.style.overflow = 'hidden';
                if(input) setTimeout(() => input.focus(), 100);
            } else {
                overlay.style.opacity = '0';
                modal.style.transform = 'translateY(-3rem)';
                setTimeout(() => overlay.classList.add('hidden'), 300);
                document.body.style.overflow = '';
            }
        };
    }



    // 3. Cart Sidebar Toggle
    if (!window.openCartSidebar) {
        window.openCartSidebar = function() {
            const overlay = document.getElementById('cart-sidebar-overlay');
            const sidebar = document.getElementById('cart-sidebar');
            if (!overlay || !sidebar) return;

            overlay.classList.remove('hidden');
            void overlay.offsetWidth;
            overlay.style.opacity = '1';
            sidebar.style.transform = 'translateX(0)';
            document.body.style.overflow = 'hidden';
            
            // Try to load items if function exists, or dispatch event
            if (typeof loadCartItems === 'function') {
                loadCartItems();
            } else {
                // Dispatch event for chunky.js to catch later
                document.dispatchEvent(new CustomEvent('cart:open'));
            }
        };
    }

    if (!window.closeCartSidebar) {
        window.closeCartSidebar = function() {
            const overlay = document.getElementById('cart-sidebar-overlay');
            const sidebar = document.getElementById('cart-sidebar');
            if (!overlay || !sidebar) return;

            overlay.style.opacity = '0';
            sidebar.style.transform = 'translateX(100%)';
            setTimeout(() => overlay.classList.add('hidden'), 300);
            document.body.style.overflow = '';
        };
    }

    // BIND EVENTS MANUALLY JUST IN CASE
    document.addEventListener('DOMContentLoaded', function() {
        const searchBtn = document.getElementById('mobile-search-trigger');
        if(searchBtn) searchBtn.onclick = function(e) { e.preventDefault(); window.toggleSearch(); };
    });

    <?php 
    $flash = get_flash_message();
    if ($flash): ?>
        document.addEventListener('DOMContentLoaded', function() {
            if (typeof showToast === 'function') {
                showToast("<?php echo addslashes($flash['message']); ?>", "<?php echo $flash['type']; ?>");
            }
        });
    <?php endif; ?>
</script>
