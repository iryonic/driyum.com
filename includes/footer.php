
<!-- MASTER FOOTER -->
<footer class="bg-black text-white pt-24 pb-40 relative overflow-hidden mt-20 rounded-t-[50px]">
    <!-- Background Gradients -->
    <div class="absolute top-0 left-1/2 -translate-x-1/2 w-full max-w-[1200px] h-[500px] bg-[#19DC7E] rounded-full blur-[150px] opacity-10 pointer-events-none"></div>

    <div class="container mx-auto px-6 relative z-10">
        <div class="grid grid-cols-1 md:grid-cols-12 gap-12 border-b border-white/10 pb-16">
            
            <!-- BRAND COLUMN (Left, massive) -->
            <div class="md:col-span-5 space-y-8">
                <a href="<?php echo get_url(''); ?>" class="inline-block group">
                    <span class="text-6xl md:text-8xl font-black font-['Fredoka'] tracking-tighter text-white group-hover:text-transparent group-hover:bg-clip-text group-hover:bg-gradient-to-r group-hover:from-[#19DC7E] group-hover:to-[#fff] transition-all duration-500">
                <img src="<?php echo get_url('assets/images/logo.png'); ?>" alt="<?php echo get_setting('store_name', 'DRIYUM'); ?>" class="w-40 inline-block"> <span class="text-[#19DC7E] group-hover:text-white">.</span>
                    </span>
                </a>
                <p class="text-xl text-gray-400 font-['Outfit'] max-w-md leading-relaxed">
                    <?php echo get_setting('footer_description', 'Redefining the art of snacking with premium, indulgence. Naturally sweet, unapologetically bold.'); ?>
                </p>
                <div class="flex flex-wrap gap-4 pt-4">
                    <?php if($insta = get_setting('instagram_url')): ?>
                    <a href="<?php echo $insta; ?>" target="_blank" class="w-12 h-12 rounded-full border border-white/20 flex items-center justify-center hover:bg-[#E4405F] hover:border-[#E4405F] hover:text-white transition-all duration-300 group shadow-lg">
                        <i class="fab fa-instagram text-xl group-hover:scale-110 transition p-1"></i>
                    </a>
                    <?php endif; ?>

                    <?php if($wa = get_setting('whatsapp_number')): ?>
                    <a href="https://wa.me/<?php echo preg_replace('/[^0-9]/', '', $wa); ?>" target="_blank" class="w-12 h-12 rounded-full border border-white/20 flex items-center justify-center hover:bg-[#25D366] hover:border-[#25D366] hover:text-white transition-all duration-300 group shadow-lg">
                        <i class="fab fa-whatsapp text-xl group-hover:scale-110 transition p-1"></i>
                    </a>
                    <?php endif; ?>
                    
                    <?php if($fb = get_setting('facebook_url')): ?>
                    <a href="<?php echo $fb; ?>" target="_blank" class="w-12 h-12 rounded-full border border-white/20 flex items-center justify-center hover:bg-[#1877F2] hover:border-[#1877F2] hover:text-white transition-all duration-300 group shadow-lg">
                        <i class="fab fa-facebook-f text-xl group-hover:scale-110 transition"></i>
                    </a>
                    <?php endif; ?>

                    <?php if($tw = get_setting('twitter_url')): ?>
                    <a href="<?php echo $tw; ?>" target="_blank" class="w-12 h-12 rounded-full border border-white/20 flex items-center justify-center hover:bg-[#000000] hover:border-[#000000] hover:border-white hover:text-white transition-all duration-300 group shadow-lg">
                        <i class="fab fa-twitter text-xl group-hover:scale-110 transition hover:border-white"></i>
                    </a>
                    <?php endif; ?>

                    <?php if($yt = get_setting('youtube_url')): ?>
                    <a href="<?php echo $yt; ?>" target="_blank" class="w-12 h-12 rounded-full border border-white/20 flex items-center justify-center hover:bg-[#FF0000] hover:border-[#FF0000] hover:text-white transition-all duration-300 group shadow-lg">
                        <i class="fab fa-youtube text-xl group-hover:scale-110 transition"></i>
                    </a>
                    <?php endif; ?>

                    <?php if($pin = get_setting('pinterest_url')): ?>
                    <a href="<?php echo $pin; ?>" target="_blank" class="w-12 h-12 rounded-full border border-white/20 flex items-center justify-center hover:bg-[#BD081C] hover:border-[#BD081C] hover:text-white transition-all duration-300 group shadow-lg">
                        <i class="fab fa-pinterest-p text-xl group-hover:scale-110 transition"></i>
                    </a>
                    <?php endif; ?>
                </div>
            </div>

            <!-- LINKS (Middle) -->
            <div class="md:col-span-2 space-y-6 pt-4">
                <h4 class="text-lg font-bold font-['Fredoka'] text-[#19DC7E] uppercase tracking-widest">Shop</h4>
                <ul class="space-y-4 font-['Outfit'] text-gray-400">
                    <li><a href="<?php echo get_url('shop'); ?>" class="hover:text-white hover:translate-x-2 transition-transform inline-block">All Snacks</a></li>
                    <li><a href="<?php echo get_url('shop?filter=new'); ?>" class="hover:text-white hover:translate-x-2 transition-transform inline-block">New Drops</a></li>
                    <li><a href="<?php echo get_url('shop?filter=best'); ?>" class="hover:text-white hover:translate-x-2 transition-transform inline-block">Bestsellers</a></li>
                </ul>
            </div>

            <div class="md:col-span-2 space-y-6 pt-4">
                <h4 class="text-lg font-bold font-['Fredoka'] text-[#19DC7E] uppercase tracking-widest">Support</h4>
                <ul class="space-y-4 font-['Outfit'] text-gray-400">
                    <li><a href="<?php echo get_url('about'); ?>" class="hover:text-white hover:translate-x-2 transition-transform inline-block">Our Story</a></li>
                    <li><a href="<?php echo get_url('track'); ?>" class="hover:text-white hover:translate-x-2 transition-transform inline-block">Track Order</a></li>
                    <li><a href="<?php echo get_url('contact'); ?>" class="hover:text-white hover:translate-x-2 transition-transform inline-block">Contact Us</a></li>
                    <li><a href="<?php echo get_url('privacy-policy'); ?>" class="hover:text-white hover:translate-x-2 transition-transform inline-block">Privacy & Terms</a></li>
                    <li><a href="<?php echo get_url('account'); ?>" class="hover:text-white hover:translate-x-2 transition-transform inline-block">My Account</a></li>
                </ul>
            </div>

            <!-- NEWSLETTER (Right) -->
            <div class="md:col-span-3 space-y-6 pt-4">
                <h4 class="text-lg font-bold font-['Fredoka'] text-[#19DC7E] uppercase tracking-widest">Stay Fresh</h4>
                <p class="text-gray-400 font-['Outfit']">Join the club for distinct drops and exclusive deals.</p>
                <form class="relative group" id="newsletter-form" onsubmit="subscribeNewsletter(event)">
                    <input type="email" name="email" required placeholder="Your email..." class="w-full bg-white/5 border border-white/10 rounded-full py-4 pl-6 pr-14 text-white placeholder-gray-500 focus:outline-none focus:border-[#19DC7E] focus:bg-white/10 transition-all">
                    <button type="submit" class="absolute top-1/2 right-2 -translate-y-1/2 w-10 h-10 bg-[#19DC7E] rounded-full flex items-center justify-center text-black hover:scale-110 transition shadow-lg shadow-green-900/50">
                        <i class="fas fa-arrow-right"></i>
                    </button>
                </form>
            </div>
        </div>

        <!-- COPYRIGHT -->
        <div class="pt-8 flex flex-col md:flex-row justify-between items-center text-gray-500 text-sm font-['Outfit']">
            <p>&copy; <?php echo date('Y'); ?> <?php echo get_setting('store_name', 'DRIYUM'); ?>. All rights reserved.</p>
            <p>Powered by <a href="https://irfanmanzoor.in/" target="_blank" class="text-[#19DC7E] hover:underline transition">EXORA.DEVS</a> </p>
            <div class="flex gap-6 mt-4 md:mt-0 opacity-50 grayscale hover:grayscale-0 transition-all duration-500">
                <i class="fab fa-cc-visa text-2xl"></i>
                <i class="fab fa-cc-mastercard text-2xl"></i>
                <i class="fa-brands fa-google-pay text-2xl"></i>
            </div>
        </div>
    </div>
</footer>

<!-- MOBILE NAV - CHUNKY PREMIUM - V2 Modern -->
<style>
    /* Modern iOS-Style Floating Dock */
    #driyum-mobile-dock-v2 {
        display: none;
        position: fixed;
        bottom: 30px;
        left: 0;
        right: 0;
        justify-content: center;
        z-index: 50; /* Below Header (100) and Modals */
        pointer-events: none; /* Container is passthrough */
    }

    @media screen and (max-width: 768px) {
        #driyum-mobile-dock-v2 {
            display: flex;
            animation: dock-pop-up 0.5s cubic-bezier(0.175, 0.885, 0.32, 1.275) forwards;
        }

        @keyframes dock-pop-up {
            from { transform: translateY(100px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }

        #driyum-mobile-dock-inner-v2 {
            pointer-events: auto;
            background: rgba(12, 12, 12, 0.85); /* Slate 900 Glass */
            backdrop-filter: blur(24px) saturate(140%);
            -webkit-backdrop-filter: blur(24px) saturate(140%);
            
            border: 1px solid rgba(27, 26, 26, 0.04);
            box-shadow: 
                0 20px 40px -5px rgba(0, 0, 0, 0.4),
                0 10px 10px -5px rgba(0, 0, 0, 0.3),
                inset 0 1px 0 rgba(255, 255, 255, 0.15); /* Top highlight */
            
            border-radius: 20px;
            padding: 0 28px;
            height: 76px;
            width: auto;
            min-width: 350px;
            max-width: 98%;
            
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
        }

        .dock-item-v2 {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            color: #94A3B8; /* Slate 400 */
            text-decoration: none;
            background: transparent;
            border: none;
            padding: 0;
            width: 52px;
            height: 100%;
            position: relative;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        /* Hover/Active Effects */
        .dock-item-v2 i {
            font-size: 24px;
            transition: transform 0.3s cubic-bezier(0.34, 1.56, 0.64, 1), color 0.2s;
            filter: drop-shadow(0 2px 4px rgba(0,0,0,0.1));
        }

        .dock-item-v2.active i {
            color: #19DC7E;
            transform: translateY(-5px) scale(1.1);
            filter: drop-shadow(0 0 12px rgba(25, 220, 126, 0.4));
        }

        .dock-item-v2.active::after {
            content: '';
            position: absolute;
            bottom: 14px;
            width: 5px;
            height: 5px;
            background: #19DC7E;
            border-radius: 50%;
            box-shadow: 0 0 8px #19DC7E;
            animation: dot-fade 0.3s ease-out;
        }

        /* FAB (Cart) Button */
        .dock-fab-wrapper-v2 {
            position: relative;
            width: 64px;
            height: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            transform: translateY(-28px);
            z-index: 10;
        }

        .dock-fab-v2 {
            width: 68px;
            height: 68px;
            background: linear-gradient(135deg, #19DC7E 0%, #059669 100%);
            border: 6px solid #FFFBEB; /* Matches site background, looks like cutout */
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #022c22;
            box-shadow: 
                0 12px 25px -5px rgba(25, 220, 126, 0.5),
                inset 0 2px 4px rgba(255, 255, 255, 0.3);
            transition: transform 0.2s cubic-bezier(0.34, 1.56, 0.64, 1);
            cursor: pointer;
        }

        .dock-fab-v2:active {
            transform: scale(0.92);
        }

        .dock-fab-v2 i {
            font-size: 26px;
        }

        .dock-badge-v2 {
            position: absolute;
            top: 2px;
            right: 2px;
            background: #EF4444;
            color: white;
            font-size: 11px;
            font-weight: 800;
            min-width: 22px;
            height: 22px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 99px;
            border: 3px solid #FFFBEB;
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
        }
        
        body { padding-bottom: 140px; }
    }
</style>

<div id="driyum-mobile-dock-v2">
    <div id="driyum-mobile-dock-inner-v2">
        <!-- Home -->
        <a href="<?php echo get_url(''); ?>" class="dock-item-v2 <?php echo in_array(basename($_SERVER['PHP_SELF']), ['index.php', '']) ? 'active' : ''; ?>">
            <i class="fas fa-home"></i>
        </a>

        <!-- Shop -->
        <a href="<?php echo get_url('shop'); ?>" class="dock-item-v2 <?php echo basename($_SERVER['PHP_SELF'])=='shop.php' ? 'active' : ''; ?>">
            <i class="fa-solid fa-bag-shopping"></i>
        </a>

        <!-- Cart FAB (Center) -->
        <div class="dock-fab-wrapper-v2">
            <a href="#" onclick="openCartSidebar(); return false;" class="dock-fab-v2">
                <i class="fas fa-shopping-cart"></i>
                <?php if(isset($_SESSION['cart']) && count($_SESSION['cart']) > 0): ?>
                    <span class="dock-badge-v2" id="mobile-cart-count"><?php echo count($_SESSION['cart']); ?></span>
                <?php endif; ?>
            </a>
        </div>

        <!-- Track -->
        <a href="<?php echo get_url('track'); ?>" class="dock-item-v2 <?php echo basename($_SERVER['PHP_SELF'])=='track.php' ? 'active' : ''; ?>">
            <i class="fas fa-map-location-dot"></i>
        </a>

        <!-- Menu (Hamburger) -->
        <button id="mobile-menu-trigger-btn" class="dock-item-v2">
            <i class="fas fa-bars"></i>
        </button>
    </div>
</div>

<script>
    // Robust Hamburger Trigger
    document.getElementById('mobile-menu-trigger-btn').addEventListener('click', function(e) {
        e.preventDefault();
        e.stopPropagation();
        
        // Try Global Function First
        if (typeof toggleMobileMenuDrawer === 'function') {
            toggleMobileMenuDrawer();
        } else {
            // Fallback: Direct DOM manipulation if JS failed
            const overlay = document.getElementById('mobile-menu-overlay');
            const drawer = document.getElementById('mobile-menu-drawer');
            
            if (overlay && drawer) {
                 const isHidden = overlay.classList.contains('hidden');
                 if(isHidden) {
                     overlay.classList.remove('hidden');
                     setTimeout(() => {
                        overlay.style.opacity = '1';
                        drawer.style.transform = 'translateX(0)';
                     }, 10);
                     document.body.style.overflow = 'hidden';
                 } else {
                     overlay.style.opacity = '0';
                     drawer.style.transform = 'translateX(-100%)';
                     setTimeout(() => overlay.classList.add('hidden'), 300);
                     document.body.style.overflow = '';
                 }
            } else {
                console.error("Mobile menu elements not found");
                alert("Menu unavailable");
            }
        }
    });
</script>

<!-- Toast Container for Notifications -->
<div id="toast-container" class="fixed bottom-24 right-4 z-[9999] md:bottom-4"></div>

<script>
function subscribeNewsletter(e) {
    e.preventDefault();
    const form = document.getElementById('newsletter-form');
    // ... existing logic ...
    const formData = new FormData(form);
    const btn = form.querySelector('button');
    const icon = btn.querySelector('i');
    
    icon.className = 'fas fa-spinner fa-spin';
    
    fetch('<?php echo get_url('api/subscribe.php'); ?>', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if(typeof showToast === 'function') showToast(data.message, data.success ? 'success' : 'error');
        if(data.success) form.reset();
    })
    .catch(error => { console.error('Error:', error); })
    .finally(() => { icon.className = 'fas fa-arrow-right'; });
}
</script>
