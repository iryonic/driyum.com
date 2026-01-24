
<!-- MASTER FOOTER -->
<footer class="bg-black text-white pt-24 pb-24 relative overflow-hidden mt-20 rounded-t-[50px]">
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

                    <!-- add whatsapp -->
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

<!-- MOBILE FLOATING NAVIGATION (Modern Dock) -->


<div id="mobile-bottom-nav-container">
    <style>
        /* Default hidden on desktop */
        #mobile-bottom-nav-container {
            display: none;
        }

        @media (max-width: 1024px) {
            #mobile-bottom-nav-container {
                display: block !important;
                position: fixed !important;
                bottom: 20px !important;
                left: 16px !important;
                right: 16px !important;
                z-index: 2147483647 !important;
            }
            /* Add padding to body to prevent content cutoff */
            body {
                padding-bottom: 100px !important;
            }
        }
    </style>
    
    <div class="bg-[#111827] text-white rounded-[35px] shadow-[0_10px_40px_rgba(0,0,0,0.4)] border border-white/10 h-[70px] flex items-center justify-between px-6 backdrop-blur-md">
        
        <!-- Home -->
        <a href="<?php echo get_url(''); ?>" class="flex flex-col items-center justify-center gap-1 w-12 text-gray-400 hover:text-[#19DC7E] transition-colors <?php echo in_array(basename($_SERVER['PHP_SELF']), ['index.php', '']) ? 'text-[#19DC7E]' : ''; ?>">
            <i class="fas fa-home text-xl"></i>
        </a>

        <!-- Shop -->
        <a href="<?php echo get_url('shop'); ?>" class="flex flex-col items-center justify-center gap-1 w-12 text-gray-400 hover:text-[#19DC7E] transition-colors <?php echo basename($_SERVER['PHP_SELF'])=='shop.php' ? 'text-[#19DC7E]' : ''; ?>">
            <i class="fas fa-store text-xl"></i>
        </a>

        <!-- Cart (Floating Action Button) -->
        <a href="#" onclick="openCartSidebar(); return false;" class="relative -top-6 w-16 h-16 bg-[#19DC7E] rounded-full flex items-center justify-center text-black border-4 border-[#fffbeb] shadow-lg transform hover:scale-110 transition-transform">
            <i class="fas fa-shopping-bag text-2xl"></i>
            <?php if(isset($_SESSION['cart']) && count($_SESSION['cart']) > 0): ?>
            <span id="mobile-cart-count" class="absolute -top-1 -right-1 bg-red-500 text-white text-[10px] font-bold w-6 h-6 flex items-center justify-center rounded-full border-2 border-white">
                <?php echo count($_SESSION['cart']); ?>
            </span>
            <?php endif; ?>
        </a>

        <!-- Track -->
        <a href="<?php echo get_url('track'); ?>" class="flex flex-col items-center justify-center gap-1 w-12 text-gray-400 hover:text-[#19DC7E] transition-colors <?php echo basename($_SERVER['PHP_SELF'])=='track.php' ? 'text-[#19DC7E]' : ''; ?>">
            <i class="fas fa-box text-xl"></i>
        </a>

        <!-- Menu/Account -->
        <button onclick="toggleMobileMenuDrawer()" class="flex flex-col items-center justify-center gap-1 w-12 text-gray-400 hover:text-[#19DC7E] transition-colors">
            <i class="fas fa-bars text-xl"></i>
        </button>

    </div>
</div>

<!-- Toast Container for Notifications -->
<div id="toast-container" class="fixed bottom-24 right-4 z-[9999] md:bottom-4"></div>

<script>
function subscribeNewsletter(e) {
    e.preventDefault();
    const form = document.getElementById('newsletter-form');
    const formData = new FormData(form);
    const btn = form.querySelector('button');
    const icon = btn.querySelector('i');
    
    // Loading State
    icon.className = 'fas fa-spinner fa-spin';
    
    fetch('<?php echo get_url('api/subscribe.php'); ?>', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if(typeof showToast === 'function') {
            showToast(data.message, data.success ? 'success' : 'error');
        }
        
        if(data.success) {
            form.reset();
        }
    })
    .catch(error => {
        console.error('Error:', error);
        if(typeof showToast === 'function') {
            showToast('Something went wrong. Please try again.', 'error');
        }
    })
    .finally(() => {
        icon.className = 'fas fa-arrow-right';
    });
}
</script>

