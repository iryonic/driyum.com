<?php
// Functionality checks
require_once 'includes/affiliate_tracker.php'; // Affiliate Tracking
$is_logged_in = isset($_SESSION['user_id']); // Assuming session format

try {
    // Maintenance Mode Check
    $maintenance = get_setting('maintenance_mode', 'off');
    if ($maintenance === 'on' && !isset($_SESSION['is_admin']) && basename($_SERVER['PHP_SELF']) !== 'maintenance.php' && strpos($_SERVER['PHP_SELF'], 'admin/') === false) {
        header("Location: " . get_url('maintenance.php'));
        exit;
    }

    // Fetch Dynamic Settings
    $store_name = get_setting('store_name', 'DRIYUM');
    $announcement_raw = get_setting('announcement_text', '🚀 Free Shipping on All Orders Over ₹499 • 🌿 100% Organic & Natural');
    $announcement_bg = get_setting('announcement_bg_color', '#004f42');
    
    // Convert popular emojis to FA icons for premium feel
    $announcement_raw = str_replace(
        ['🚀', '🌿', '✨', '⚡', '📦'], 
        ['<i class="fas fa-bolt text-[#19DC7E] mr-2"></i>', '<i class="fas fa-leaf text-[#19DC7E] mr-2"></i>', '<i class="fas fa-star text-[#19DC7E] mr-2"></i>', '<i class="fas fa-bolt text-[#19DC7E] mr-2"></i>', '<i class="fas fa-box text-[#19DC7E] mr-2"></i>'], 
        $announcement_raw
    );
    
    $announcement_parts = explode('•', $announcement_raw);

    // Support & Social Settings
    $support_phone = get_setting('support_phone', '+91 9419809801');
    $support_email = get_setting('support_email', 'contact@driyum.com');
    // Run periodic tasks (Abandoned cart reminders, etc)
    run_crons();
} catch (Throwable $e) {
    // Silence DB errors in header and use safe defaults
    $store_name = 'DRIYUM';
    $announcement_parts = ['🚀 Welcome to Driyum • 🌿 All Organic Goodness'];
    $support_phone = '+91 9419809801';
    $support_email = 'contact@driyum.com';
}
?>

<!-- Global SEO tags are handled in head.php -->
<?php
// Header specific logic if needed
?>

<!-- ULTRA FUN PREMIUM PRELOADER -->
<style>
    #premium-preloader {
        position: fixed;
        inset: 0;
        background-color: #FFFBEB;
        z-index: 200000;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all 0.6s cubic-bezier(0.85, 0, 0.15, 1);
        overflow: hidden;
    }
    #premium-preloader.hide {
        opacity: 0;
        visibility: hidden;
        transform: scale(1.2); /* Zoom reveal effect */
    }
    .loader-content {
        text-align: center;
        animation: loader-enter 0.6s cubic-bezier(0.16, 1, 0.3, 1) forwards;
        position: relative;
        z-index: 10;
    }
    
    /* Sparkle Background */
    .loader-bg-sparkles {
        position: absolute;
        inset: 0;
        z-index: 1;
        opacity: 0.4;
    }
    
    .loader-logo-wrap {
        position: relative;
        display: inline-block;
        margin-bottom: 25px;
    }
    .loader-logo-main {
        width: 110px;
        animation: loader-bounce 0.8s cubic-bezier(0.45, 0.05, 0.55, 0.95) infinite alternate;
        z-index: 2;
        position: relative;
    }
    .loader-logo-shadow {
        width: 60px;
        height: 6px;
        background: rgba(0,0,0,0.05);
        border-radius: 50%;
        margin: -10px auto 0;
        animation: shadow-pulse 0.8s ease-in-out infinite alternate;
    }
    
    /* Fun Floating Emojis */
    .floating-emoji {
        position: absolute;
        font-size: 28px;
        opacity: 0;
        pointer-events: none;
        animation: emoji-float-pro 4s ease-in-out infinite;
    }
    @keyframes emoji-float-pro {
        0% { transform: translate(0, 0) scale(0.5) rotate(0deg); opacity: 0; }
        20% { opacity: 0.8; transform: translate(var(--tw-x1), var(--tw-y1)) scale(1.2) rotate(10deg); }
        80% { opacity: 0.8; transform: translate(var(--tw-x2), var(--tw-y2)) scale(1) rotate(-10deg); }
        100% { transform: translate(var(--tw-x3), var(--tw-y3)) scale(0.5) rotate(0deg); opacity: 0; }
    }

    @keyframes loader-bounce {
        from { transform: translateY(0); }
        to { transform: translateY(-30px); }
    }
    @keyframes shadow-pulse {
        from { transform: scale(1); opacity: 0.2; }
        to { transform: scale(1.5); opacity: 0.05; }
    }

    .loader-funny-text {
        font-family: 'Delius', cursive;
        font-weight: 700;
        color: #111827;
        font-size: 15px;
        margin-bottom: 20px;
        height: 24px;
        display: block;
        transition: all 0.3s;
        filter: blur(0px);
    }
    .loader-funny-text.blur {
        filter: blur(4px);
        opacity: 0;
        transform: translateY(-5px);
    }

    .chunky-loader-bar-wrap {
        width: 160px;
        height: 8px;
        background: #f1f5f9;
        border-radius: 20px;
        margin: 0 auto;
        overflow: hidden;
        border: 2px solid white;
        box-shadow: 0 4px 10px rgba(0,0,0,0.05);
    }
    .chunky-loader-fill {
        height: 100%;
        background: #19DC7E;
        box-shadow: 0 0 15px rgba(25, 220, 126, 0.5);
        animation: progress-shimmer 1.5s infinite linear;
        width: 100%;
        transform-origin: left;
    }
</style>

<div id="premium-preloader">
    <div class="loader-bg-sparkles" id="sparkle-wrap"></div>
    
    <div class="loader-content">
        <!-- Floating icons for fun (No emojis for premium feel) -->
        <span class="floating-emoji text-[#19DC7E]" style="--tw-x1:-60px; --tw-y1:-80px; --tw-x2:-80px; --tw-y2:-140px; --tw-x3:-100px; --tw-y3:-200px; left:0; top:0; animation-delay:0s;"><i class="fas fa-seedling"></i></span>
        <span class="floating-emoji text-pink-400" style="--tw-x1:60px; --tw-y1:-70px; --tw-x2:90px; --tw-y2:-120px; --tw-x3:120px; --tw-y3:-180px; right:0; top:10px; animation-delay:0.5s;"><i class="fas fa-apple-whole"></i></span>
        <span class="floating-emoji text-amber-500" style="--tw-x1:-50px; --tw-y1:60px; --tw-x2:-70px; --tw-y2:110px; --tw-x3:-90px; --tw-y3:160px; left:20px; bottom:20px; animation-delay:1s;"><i class="fas fa-cookie-bite"></i></span>
        <span class="floating-emoji text-emerald-400" style="--tw-x1:50px; --tw-y1:50px; --tw-x2:70px; --tw-y2:100px; --tw-x3:90px; --tw-y3:150px; right:20px; bottom:0px; animation-delay:1.5s;"><i class="fas fa-leaf"></i></span>
        
        <div class="loader-logo-wrap">
            <img src="<?php echo get_url('assets/images/logoicon.png'); ?>" alt="Loading..." class="loader-logo-main">
            <div class="loader-logo-shadow"></div>
        </div>
        
        <span class="loader-funny-text" id="loader-msg">Getting the flavor ready...</span>
        
        <div class="chunky-loader-bar-wrap">
            <div class="chunky-loader-fill"></div>
        </div>
    </div>
</div>

<script>
    const messages = [
        "Waking up the fruits...",
        "Measuring the crunch level...",
        "Adding a pinch of Kashmir...",
        "Testing for deliciousness...",
        "Almost flavor-ready!"
    ];
    let msgIndex = 0;
    const msgEl = document.getElementById('loader-msg');
    
    const cycleText = setInterval(() => {
        if(msgEl) {
            msgEl.classList.add('blur');
            setTimeout(() => {
                msgIndex = (msgIndex + 1) % messages.length;
                msgEl.innerText = messages[msgIndex];
                msgEl.classList.remove('blur');
            }, 300);
        }
    }, 1200);

    // Create subtle sparkles
    const sparkleWrap = document.getElementById('sparkle-wrap');
    if(sparkleWrap) {
        for(let i=0; i<30; i++) {
            const sparkle = document.createElement('div');
            sparkle.style.position = 'absolute';
            sparkle.style.width = '2px';
            sparkle.style.height = '2px';
            sparkle.style.background = '#19DC7E';
            sparkle.style.borderRadius = '50%';
            sparkle.style.left = Math.random() * 100 + '%';
            sparkle.style.top = Math.random() * 100 + '%';
            sparkle.style.opacity = Math.random();
            sparkle.style.animation = `pulse ${1 + Math.random() * 2}s infinite alternate`;
            sparkleWrap.appendChild(sparkle);
        }
    }

    function hidePreloader() {
        const preloader = document.getElementById('premium-preloader');
        if (preloader && !preloader.dataset.hidden) {
            clearInterval(cycleText);
            preloader.dataset.hidden = true;
            preloader.classList.add('hide');
            setTimeout(() => preloader.remove(), 700);
        }
    }
    
    window.addEventListener('load', hidePreloader);
    setTimeout(hidePreloader, 4500); 
</script>

<!-- INFINITE MARQUEE -->
<div class="text-white overflow-hidden py-1.5 md:py-2.5 relative z-[40] border-b border-[#19DC7E]/30 font-cute" style="background-color: <?php echo $announcement_bg; ?>;">
    <!-- Gradient Overlay for fade effect on edges -->
    <div class="absolute left-0 top-0 bottom-0 w-20 bg-gradient-to-r from-black to-transparent z-10"></div>
    <div class="absolute right-0 top-0 bottom-0 w-20 bg-gradient-to-l from-black to-transparent z-10"></div>

    <div class="flex whitespace-nowrap animate-[marquee_20s_linear_infinite] hover:[animation-play-state:paused] items-center">
        <?php for($i=0; $i<10; $i++): ?>
            <?php foreach($announcement_parts as $msg): ?>
                <div class="flex items-center gap-3 mx-6">
                    <span class="w-1.5 h-1.5 rounded-full bg-[#19DC7E] animate-pulse"></span>
                    <span class="font-heading font-bold text-xs tracking-widest uppercase text-white">
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
            <img src="<?php echo get_url('assets/images/logo.svg'); ?>" alt="<?php echo $store_name; ?> Logo" width="100" height="100" class="w-20 lg:w-24">
        </a>

        <!-- Navigation with Mega Menu -->
        <nav class="h-full flex items-center gap-3 lg:gap-8 overflow-hidden" aria-label="Desktop Navigation">
            <a href="<?php echo get_url(''); ?>" class="font-heading font-semibold text-gray-600 hover:text-[#19DC7E] transition whitespace-nowrap text-sm ">Home</a>
            <a href="<?php echo get_url('shop'); ?>" class="font-heading font-semibold text-gray-600 hover:text-[#19DC7E] transition whitespace-nowrap text-sm ">Shop</a>
            <a href="<?php echo get_url('about'); ?>" class="font-heading font-semibold text-gray-600 hover:text-[#19DC7E] transition whitespace-nowrap text-sm ">Story</a>
            
            <!-- MEGA MENU TRIGGER -->
            <div class="mega-menu-trigger h-full flex items-center cursor-pointer group">
                <a href="<?php echo get_url('shop'); ?>" class="font-heading font-semibold text-gray-600 group-hover:text-[#19DC7E] transition py-2 whitespace-nowrap text-sm ">
                     Categories <i class="fas fa-chevron-down ml-1 text-[10px] opacity-50" aria-hidden="true"></i>
                </a>
                
                <!-- MEGA MENU CONTENT -->
                <div class="mega-menu">
                    <div class="container mx-auto grid grid-cols-4 gap-8">
                        <?php 
                        try {
                            $header_cats = array_slice(get_all_categories(), 0, 3);
                            foreach($header_cats as $cat): 
                            ?>
                            <div>
                                <h4 class="text-[#19DC7E] mb-4 text-lg font-heading"><?php echo htmlspecialchars($cat['name']); ?></h4>
                                <ul class="space-y-2 font-sans text-gray-500">
                                    <?php 
                                    $cat_prods = fetch_all("SELECT id, name, slug FROM products WHERE category_id = ? AND is_active = 1 LIMIT 3", [$cat['id']]);
                                    if(empty($cat_prods)):
                                    ?>
                                        <li class="text-xs opacity-50">Coming Soon...</li>
                                    <?php else: ?>
                                        <?php foreach($cat_prods as $cp): ?>
                                        <li><a href="<?php echo get_url('product/' . $cp['slug']); ?>" class="hover:text-black hover:translate-x-1 transition-transform inline-block"><?php echo htmlspecialchars($cp['name']); ?></a></li>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                    <li><a href="<?php echo get_url('category/' . $cat['slug']); ?>" class="text-[10px] font-black uppercase text-[#19DC7E] hover:underline mt-2 inline-block">View All</a></li>
                                </ul>
                            </div>
                            <?php endforeach; ?>

                            <!-- Highlight (Featured Product) -->
                            <?php 
                            $highlight = fetch_one("SELECT id, name, slug, image FROM products WHERE is_active = 1 AND is_featured = 1 ORDER BY RAND() LIMIT 1");
                            if(!$highlight) $highlight = fetch_one("SELECT id, name, slug, image FROM products WHERE is_active = 1 ORDER BY created_at DESC LIMIT 1");
                            
                            if($highlight):
                            ?>
                            <div class="bg-[#f0fdf4] rounded-2xl p-6 flex gap-4 items-center border border-[#19DC7E]/20">
                                <div class="flex-1">
                                    <span class="badge bg-[#19DC7E] text-white mb-2 inline-block">FEATURED</span>
                                    <h3 class="text-xl mb-2 font-heading line-clamp-2"><?php echo $highlight['name']; ?></h3>
                                    <a href="<?php echo get_url('product/' . $highlight['slug']); ?>" class="btn-chunky bg-white text-black text-[10px] py-2 px-4 shadow-sm border-2 border-gray-100 hover:border-[#19DC7E]">Shop Now</a>
                                </div>
                                <img src="<?php echo get_url(ltrim($highlight['image'], './')); ?>" alt="<?php echo $highlight['name']; ?>" width="80" height="80" class="w-20 h-20 object-contain rounded-xl mix-blend-multiply">
                            </div>
                            <?php endif; ?>
                        <?php } catch (Throwable $e) { ?>
                            <div class="col-span-4 py-8 text-center text-gray-400 font-bold uppercase tracking-widest text-xs">
                                <i class="fas fa-shopping-bag mb-3 text-2xl block text-[#19DC7E]/30"></i>
                                Ready to Browse? <br>
                                <a href="<?php echo get_url('shop'); ?>" class="text-[#19DC7E] underline hover:text-black">Enter Driyum Shop</a>
                            </div>
                        <?php } ?>
                    </div>
                </div>
            </div>

            <a href="<?php echo get_url('track'); ?>" class="font-heading font-semibold text-gray-600 hover:text-[#19DC7E] transition whitespace-nowrap text-sm  hidden xl:block">Track Order</a>
            <a href="<?php echo get_url('contact'); ?>" class="font-heading font-semibold text-gray-600 hover:text-[#19DC7E] transition whitespace-nowrap text-sm  hidden xl:block">Contact</a>
        </nav>

        <!-- Actions -->
        <div class="flex items-center gap-2 lg:gap-4 shrink-0">
            <button onclick="toggleSearch()" class="w-9 h-9 lg:w-10 lg:h-10 rounded-full bg-white border-2 border-gray-100 flex items-center justify-center text-gray-600 hover:border-[#19DC7E] hover:text-[#19DC7E] transition" aria-label="Open search">
                <i class="fas fa-search text-sm "></i>
            </button>
            <a href="<?php echo get_url('wishlist'); ?>" class="w-9 h-9 lg:w-10 lg:h-10 rounded-full bg-white border-2 border-gray-100 flex items-center justify-center text-gray-600 hover:border-[#19DC7E] hover:text-red-500 transition" aria-label="View Favorites">
                <i class="far fa-heart text-sm "></i>
            </a>

            <!-- Account Dropdown -->
            <div class="relative group">
                <button class="w-9 h-9 lg:w-10 lg:h-10 rounded-full bg-white border-2 border-gray-100 flex items-center justify-center text-gray-600 hover:border-[#19DC7E] hover:text-[#19DC7E] transition" aria-label="Account menu">
                    <i class="fas fa-user text-sm "></i>
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
                <i class="fas fa-shopping-bag mr-2 text-sm " aria-hidden="true"></i>
                <span id="cart-count" class="text-xs lg:text-sm font-bold">Bag</span>
            </button>
        </div>
    </div>
</header>

<!-- MOBILE TOP BAR -->
<header class="md:hidden sticky top-0 z-[100] bg-white/80 backdrop-blur-xl border-b border-gray-100 h-16 flex items-center justify-between px-6 shadow-sm">
    <div class="flex items-center gap-2">
        <button id="mobile-search-trigger" onclick="toggleSearch()" class="w-10 h-10 flex items-center justify-center text-gray-700 hover:text-black transition-colors" aria-label="Search"><i class="fas fa-search"></i></button>
    </div>
    <a href="<?php echo get_url(''); ?>" class="flex items-center"><img src="<?php echo get_url('assets/images/logo.svg'); ?>" alt="<?php echo $store_name; ?> - Home" class="w-24"></a>
    <button onclick="openCartSidebar()" class="w-10 h-10 flex items-center justify-center text-gray-700 relative hover:text-black transition-colors" aria-label="Open Shopping Bag">
        <i class="fas fa-shopping-bag"></i>
    </button>
</header>

<!-- MOBILE MENU DRAWER -->
<div id="mobile-menu-overlay" onclick="toggleMobileMenuDrawer()" class="fixed inset-0 bg-black/60 z-[3000] hidden transition-opacity duration-300 backdrop-blur-sm"></div>
<div id="mobile-menu-drawer" class="fixed top-0 left-0 h-full w-[310px] bg-white z-[3001] transform -translate-x-full transition-transform duration-500 flex flex-col shadow-2xl">
    
    <!-- Drawer Header -->
    <div class="px-6 pt-10 pb-6 flex justify-between items-center border-b border-gray-50">
        <a href="<?php echo get_url(''); ?>">
            <img src="<?php echo get_url('assets/images/logo.svg'); ?>" class="w-20">
        </a>
        <button onclick="toggleMobileMenuDrawer()" class="w-10 h-10 flex items-center justify-center rounded-xl bg-gray-50 text-gray-400 hover:text-black shadow-sm transition-all active:scale-90" aria-label="Close Mobile Menu">
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
                        <span class="text-xl font-heading font-bold">Home</span>
                    </a>
                </li>
                <li>
                    <a href="<?php echo get_url('shop'); ?>" class="flex items-center gap-4 px-4 py-3 rounded-2xl hover:bg-gray-50 text-gray-600 hover:text-black transition-all group">
                        <div class="w-10 h-10 rounded-xl bg-gray-100 text-gray-400 flex items-center justify-center text-sm group-hover:bg-[#19DC7E] group-hover:text-black transition-colors">
                            <i class="fas fa-shopping-bag"></i>
                        </div>
                        <span class="text-xl font-heading font-bold">Shop Packs</span>
                    </a>
                </li>
                <li>
                    <a href="<?php echo get_url('track'); ?>" class="flex items-center gap-4 px-4 py-3 rounded-2xl hover:bg-gray-50 text-gray-600 hover:text-black transition-all group">
                        <div class="w-10 h-10 rounded-xl bg-gray-100 text-gray-400 flex items-center justify-center text-sm group-hover:bg-[#19DC7E] group-hover:text-black transition-colors">
                            <i class="fas fa-truck-fast"></i>
                        </div>
                        <span class="text-xl font-heading font-bold">Track Order</span>
                    </a>
                </li>
                <li>
                    <a href="<?php echo get_url('about'); ?>" class="flex items-center gap-4 px-4 py-3 rounded-2xl hover:bg-gray-50 text-gray-600 hover:text-black transition-all group">
                        <div class="w-10 h-10 rounded-xl bg-gray-100 text-gray-400 flex items-center justify-center text-sm group-hover:bg-[#19DC7E] group-hover:text-black transition-colors">
                            <i class="fas fa-leaf"></i>
                        </div>
                        <span class="text-xl font-heading font-bold">Our Story</span>
                    </a>
                </li>
                <li>
                    <a href="<?php echo get_url('wishlist'); ?>" class="flex items-center gap-4 px-4 py-3 rounded-2xl hover:bg-gray-50 text-gray-600 hover:text-black transition-all group">
                        <div class="w-10 h-10 rounded-xl bg-gray-100 text-gray-400 flex items-center justify-center text-sm group-hover:bg-[#19DC7E] group-hover:text-black transition-colors">
                            <i class="fas fa-heart"></i>
                        </div>
                        <span class="text-xl font-heading font-bold">Wishlist</span>
                    </a>
                </li>
                <li>
                    <a href="<?php echo get_url('contact'); ?>" class="flex items-center gap-4 px-4 py-3 rounded-2xl hover:bg-gray-50 text-gray-600 hover:text-black transition-all group">
                        <div class="w-10 h-10 rounded-xl bg-gray-100 text-gray-400 flex items-center justify-center text-sm group-hover:bg-[#19DC7E] group-hover:text-black transition-colors">
                            <i class="fas fa-headset"></i>
                        </div>
                        <span class="text-xl font-heading font-bold">Support</span>
                    </a>
                </li>
            </ul>
        </div>

        <!-- Categories Section -->
        <div class="mb-10">
            <span class="text-[10px] font-black uppercase tracking-[0.2em] text-gray-400 mb-6 block ml-2">Categories</span>
            <div class="flex flex-wrap gap-2">
                <?php foreach(get_all_categories() as $cat): ?>
                    <a href="<?php echo get_url('category/' . $cat['slug']); ?>" class="px-4 py-2 bg-gray-50 rounded-xl text-xs font-bold text-gray-600 hover:bg-[#19DC7E] hover:text-black transition-all"><?php echo htmlspecialchars($cat['name']); ?></a>
                <?php endforeach; ?>
            </div>
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
                            <h4 class="text-lg font-heading font-black leading-none">Hi, <?php echo explode(' ', htmlspecialchars($_SESSION['user_name']))[0]; ?>!</h4>
                        </div>
                        <div class="grid grid-cols-1 gap-2">
                            <?php if($_SESSION['is_admin'] == 1): ?>
                                <a href="<?php echo get_url('admin/'); ?>" class="flex items-center gap-3 px-4 py-2 bg-white/10 hover:bg-white/20 rounded-xl text-xs font-bold transition-colors">
                                    <i class="fas fa-crown text-[#19DC7E]"></i> Admin Panel
                                </a>
                            <?php endif; ?>
                            <a href="<?php echo get_url('account'); ?>" class="flex items-center gap-3 px-4 py-2 hover:bg-white/10 rounded-xl text-xs font-bold transition-colors">
                                <i class="fas fa-user-circle opacity-50"></i> Account
                            </a>
                            <a href="<?php echo get_url('logout'); ?>" class="flex items-center gap-3 px-4 py-2 text-red-400 hover:bg-red-500/10 rounded-xl text-xs font-bold transition-colors">
                                <i class="fas fa-sign-out-alt"></i> Logout
                            </a>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="relative z-10">
                        <h4 class="text-xl font-heading font-black mb-2">Join the Club</h4>
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
                     <span class="text-xs font-bold"><?php echo htmlspecialchars($support_phone); ?></span>
                 </div>
                 <div class="flex items-center gap-3 text-gray-400">
                     <i class="fas fa-envelope text-xs"></i>
                     <span class="text-xs font-bold"><?php echo htmlspecialchars($support_email); ?></span>
                 </div>
             </div>

             <div class="flex gap-3">
                 <?php if($url = get_setting('instagram_url')): ?>
                 <a href="<?php echo htmlspecialchars($url); ?>" target="_blank" class="w-10 h-10 rounded-xl bg-gray-50 flex items-center justify-center text-gray-400 hover:bg-[#E1306C] hover:text-white transition-all"><i class="fab fa-instagram"></i></a>
                 <?php endif; ?>
                 <?php if($url = get_setting('facebook_url')): ?>
                 <a href="<?php echo htmlspecialchars($url); ?>" target="_blank" class="w-10 h-10 rounded-xl bg-gray-50 flex items-center justify-center text-gray-400 hover:bg-[#1877F2] hover:text-white transition-all"><i class="fab fa-facebook-f"></i></a>
                 <?php endif; ?>
                 <?php if($url = get_setting('twitter_url')): ?>
                 <a href="<?php echo htmlspecialchars($url); ?>" target="_blank" class="w-10 h-10 rounded-xl bg-gray-50 flex items-center justify-center text-gray-400 hover:bg-[#1DA1F2] hover:text-white transition-all"><i class="fab fa-twitter"></i></a>
                 <?php endif; ?>
             </div>
        </div>

    </div>
</div>

<!-- SEARCH MODAL -->
<!-- SEARCH MODAL (PREMIUM) -->
<!-- SEARCH MODAL (PREMIUM) -->
<div id="search-modal-overlay" onclick="toggleSearch()" class="fixed inset-0 bg-black/90 z-[2000] hidden opacity-0 transition-opacity duration-300 flex flex-col items-center justify-start p-4 pt-20 md:p-6 backdrop-blur-md">
    <div id="search-modal" onclick="event.stopPropagation()" class="w-full max-w-5xl relative transform -translate-y-12 transition-transform duration-500 flex flex-col max-h-full">
        
        <button onclick="toggleSearch()" class="absolute -top-16 right-0 md:-top-20 md:right-0 w-10 h-10 md:w-14 md:h-14 flex items-center justify-center rounded-full bg-white/10 text-white text-lg md:text-xl hover:bg-white hover:text-black hover:rotate-90 transition-all duration-300 z-50" aria-label="Close Search"><i class="fas fa-times"></i></button>
        
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
            <h2 class="text-2xl md:text-3xl font-heading font-black text-gray-900">Shopping Bag.</h2>
        </div>
        <button onclick="closeCartSidebar()" class="w-10 h-10 md:w-12 md:h-12 flex items-center justify-center rounded-2xl bg-gray-50 text-gray-400 hover:bg-black hover:text-white transition-all" aria-label="Close Shopping Bag">
            <i class="fas fa-times text-lg md:text-xl"></i>
        </button>
    </div>
    
    <!-- Free Shipping Progress Bar -->
    <div id="free-shipping-progress-container" class="px-6 md:px-8 pt-6 hidden">
        <div class="flex justify-between items-center mb-2">
            <span id="free-shipping-msg" class="text-[10px] font-black uppercase tracking-widest text-gray-500"></span>
            <span id="free-shipping-icon" class="text-lg">🚚</span>
        </div>
        <div class="h-1.5 w-full bg-gray-100 rounded-full overflow-hidden">
            <div id="free-shipping-bar" class="h-full bg-[#19DC7E] w-0 transition-all duration-1000"></div>
        </div>
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
            <span id="cart-total" class="text-3xl md:text-4xl font-heading font-black tracking-tighter text-gray-900">₹0</span>
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
