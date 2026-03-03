<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';
$featured = get_featured_products(10);

// Fetch Wishlist IDs for active states
$wishlist_ids = [];
if (isset($_SESSION['user_id'])) {
    $wishlist_res = fetch_all("SELECT product_id FROM wishlist WHERE user_id = ?", [$_SESSION['user_id']]);
    $wishlist_ids = array_column($wishlist_res, 'product_id');
}

// Initialize Hero Variants early for global head access
$slides_data = get_hero_slides();
if(!empty($slides_data)) {
    $hero_variants = [];
    foreach($slides_data as $s) {
        $pid = $s['product_id'];
        if(empty($pid)) {
            // Try to match by title as fallback
            $match = fetch_one("SELECT id FROM products WHERE name LIKE ? LIMIT 1", ["%".$s['title']."%"]);
            $pid = $match ? $match['id'] : fetch_one("SELECT id FROM products WHERE is_active = 1 LIMIT 1")['id'];
        }
        $hero_variants[] = [
            'id' => $s['id'],
            'product_id' => $pid,
            'name' => $s['title'],
            'price' => $s['price'] > 0 ? $s['price'] : '249',
            'image' => $s['image'],
            'bg' => $s['accent_color'],
            'v1' => $s['v_text_1'] ?? 'SNACKING',
            'v2' => $s['v_text_2'] ?? 'REIMAGINED',
            'tagline' => $s['badge_text'] ?? 'Your New Healthy Habit'
        ];
    }
} else {
    // Fallback to featured products
    $hero_variants = [];
    $featured_subset = array_slice($featured, 0, 4);
    foreach($featured_subset as $f) {
        $hero_variants[] = [
            'id' => $f['id'],
            'product_id' => $f['id'],
            'name' => $f['name'],
            'price' => $f['price'],
            'image' => $f['image'],
            'bg' => '#19DC7E',
            'v1' => 'ORGANIC',
            'v2' => 'HARVEST',
            'tagline' => 'Your New Healthy Habit'
        ];
    }
}

// Global High-End Fallback if still empty
if(empty($hero_variants)) {
    $hero_variants = [
        ['id'=>1, 'product_id'=>1, 'name'=>'Signature Almonds', 'price'=>249, 'image'=>'assets/images/nuts/almonds.png', 'bg'=>'#19DC7E', 'v1'=>'PURE', 'v2'=>'ENERGY', 'tagline'=>'Your New Healthy Habit'],
        ['id'=>2, 'product_id'=>2, 'name'=>'Crispy Apple Chips', 'price'=>199, 'image'=>'assets/images/chips/apple.png', 'bg'=>'#EDB02C', 'v1'=>'NATURE\'S', 'v2'=>'SWEET', 'tagline'=>'Your New Healthy Habit'],
        ['id'=>3, 'product_id'=>3, 'name'=>'Spiced Walnuts', 'price'=>299, 'image'=>'assets/images/nuts/walnut.png', 'bg'=>'#F67E42', 'v1'=>'BOLD', 'v2'=>'CRUNCH', 'tagline'=>'Your New Healthy Habit'],
        ['id'=>4, 'product_id'=>4, 'name'=>'Sweet Berries', 'price'=>349, 'image'=>'assets/images/berries.png', 'bg'=>'#EC4899', 'v1'=>'WILD', 'v2'=>'PICKED', 'tagline'=>'Your New Healthy Habit']
    ];
}

$wishlist_json = json_encode($wishlist_ids);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php 
    $page_title = 'Your New Healthy Habit';
    $page_description = "Experience 100% natural, premium healthy snacks from the heart of Kashmir. No added sugar, no guilt—just pure indulgence delivered to your door.";
    include 'includes/head.php'; 
    ?>
    <!-- Custom Scroll Styles -->       
    <style>
        html { overflow-x: clip; }
        body { position: relative; overflow-x: clip; min-height: 100vh; }
        .hide-scrollbar::-webkit-scrollbar { display: none; }
        .hide-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
        .snap-x-mandatory { scroll-snap-type: x mandatory; }
        .snap-center { scroll-snap-align: center; }

        .briry{
           border-radius: 20% 10px 20% 10px;
        }
       /* @media (max-width: 1024px) {
        .briry{
          border-radius: 30% 30% 10px 10px;
        }
       } */
    </style>
    <!-- HERO LOGIC: VIVID SCENE SWITCHER -->
    <script>
        let heroAutoPlay;
        let currentHeroIndex = 0;
        const heroVariants = <?php echo json_encode($hero_variants); ?>;
        const wishlistIds = <?php echo json_encode($wishlist_ids); ?>;

        function switchHeroProduct(index, el) {
            if(index === undefined || index === null) return;
            currentHeroIndex = parseInt(index);
            const data = heroVariants[currentHeroIndex];
            if(!data) return;
            
            // Clear existing autoplay
            resetHeroTimer();

            // Update Thumbnails Progress
            document.querySelectorAll('.hero-thumb').forEach(t => {
                t.classList.add('opacity-40');
                t.classList.remove('active-scene', 'opacity-100');
                const progress = t.querySelector('.thumb-progress');
                if(progress) progress.style.width = '0%';
            });
            
            if(el) {
                el.classList.add('active-scene', 'opacity-100');
                el.classList.remove('opacity-40');
            }

            const mainImg = document.getElementById('hero-main-img');
            const mainTitle = document.getElementById('hero-main-title');
            const mainPrice = document.getElementById('hero-price');
            const accentPanel = document.getElementById('hero-accent-panel');
            const vTexts = document.querySelectorAll('.hero-v-text');
            const heartBtn = document.getElementById('hero-heart-btn');

            if(!mainImg) return;

            // Step 1: Arc Exit Animation (Up & Right)
            mainImg.style.animation = 'none';
            mainImg.offsetHeight; // force reflow
            mainImg.style.animation = 'arcExit 0.75s cubic-bezier(1, 0, 0, 1) forwards';
            
            if(accentPanel) {
                accentPanel.style.filter = 'blur(40px)';
                accentPanel.style.transform = 'scale(1.3) skewX(-15deg) translate(80px, -40px)';
                accentPanel.style.opacity = '0.3';
            }

            setTimeout(() => {
                // Step 2: Content Handover
                let imgPath = data.image;
                if (!imgPath.includes('http')) {
                    imgPath = '<?php echo get_url(''); ?>' + imgPath;
                }
                mainImg.src = imgPath;

                if(mainPrice) mainPrice.innerText = data.price || '249';
                
                // Update ATC Button
                const cartBtn = document.getElementById('hero-atc-btn');
                const cartWrap = document.getElementById('hero-atc-wrap');
                if(cartBtn && data.product_id) {
                    cartBtn.setAttribute('onclick', `addToCart(${data.product_id}, this, 1)`);
                    if(cartWrap) cartWrap.style.display = 'block';
                }
                
                // Update Wishlist
                if(heartBtn && data.product_id) {
                    heartBtn.setAttribute('onclick', `toggleWishlist(${data.product_id}, this)`);
                    heartBtn.className = heartBtn.className.replace(/active|text-red-500|text-[#19DC7E]/g, '').trim();
                    if(wishlistIds.includes(parseInt(data.product_id))) {
                        heartBtn.classList.add('active', 'text-red-500');
                        heartBtn.querySelector('i').className = 'fas fa-heart text-xl lg:text-2xl transition-all duration-300';
                    } else {
                        heartBtn.classList.add('text-[#19DC7E]');
                        heartBtn.querySelector('i').className = 'far fa-heart text-xl lg:text-2xl transition-all duration-300';
                    }
                }

                if(mainTitle) {
                    const words = (data.name || 'PURE CRUNCH').toUpperCase().split(' ');
                    mainTitle.innerHTML = words.slice(0, 2).join(' ') + (words.length > 2 ? '<br>' + words.slice(2).join(' ') : '');
                    mainTitle.style.animation = 'none';
                    mainTitle.offsetHeight;
                    mainTitle.style.animation = 'revealUp 0.8s cubic-bezier(0.19, 1, 0.22, 1) forwards';
                }

                // Step 3: Color Scene Transition
                const sceneColor = data.bg || '#19DC7E';
                if(accentPanel) {
                    accentPanel.style.backgroundColor = sceneColor;
                }
                
                if(vTexts.length >= 2) {
                    vTexts[0].innerText = data.v1 || 'SNACKING';
                    vTexts[1].innerText = data.v2 || 'REIMAGINED';
                }

                // Step 4: Arc Entrance Animation (From Bottom Left)
                mainImg.style.animation = 'none';
                mainImg.offsetHeight; // force reflow
                mainImg.style.animation = 'arcEnter 0.9s cubic-bezier(0.19, 1, 0.22, 1) forwards';
                
                if(accentPanel) {
                    accentPanel.style.filter = 'blur(0px)';
                    accentPanel.style.transform = 'scale(1) skewX(0deg) translate(0, 0)';
                    accentPanel.style.opacity = '1';
                }

                // Restore floating animation after scene entry
                setTimeout(() => {
                    if(mainImg.style.animationName === 'arcEnter') {
                        mainImg.style.animation = 'floatSlow 6s ease-in-out infinite';
                    }
                }, 900);
                
                startHeroTimer();
            }, 650);
        }

        function startHeroTimer() {
            const activeThumb = document.querySelector('.hero-thumb.active-scene .thumb-progress');
            if(activeThumb) {
                activeThumb.style.transition = 'width 6s linear';
                activeThumb.style.width = '100%';
            }
            heroAutoPlay = setTimeout(nextHero, 6000);
        }

        function resetHeroTimer() {
            clearTimeout(heroAutoPlay);
        }

        function nextHero() {
            currentHeroIndex = (currentHeroIndex + 1) % heroVariants.length;
            const thumbs = document.querySelectorAll('.hero-thumb');
            switchHeroProduct(currentHeroIndex, thumbs[currentHeroIndex]);
        }

        function prevHero() {
            currentHeroIndex = (currentHeroIndex - 1 + heroVariants.length) % heroVariants.length;
            const thumbs = document.querySelectorAll('.hero-thumb');
            switchHeroProduct(currentHeroIndex, thumbs[currentHeroIndex]);
        }

        window.addEventListener('load', () => {
            startHeroTimer();
        });
    </script>
</head>
<body class="bg-[#FFFEDC]">

    <?php include 'includes/header.php'; ?>

    <?php
    $hero_slides = get_hero_slides();
    if (empty($hero_slides)) {
        // Fallback to static hero if no slides are in the database
        $hero = fetch_one("SELECT * FROM homepage_sections WHERE section_name = 'hero'");
        if(!$hero) {
            $hero = [
                'title' => "YOUR NEW HEALTHY HABIT.",
                'subtitle' => "Absolutely No Sugar. 100% Guilt-Free.",
                'cta_text' => "Start Crunching",
                'cta_link' => "shop.php",
                'image' => "assets/images/hero.jpg"
            ];
        } else {
            // Map homepage_sections fields to slide fields for consistency
            $hero['title'] = $hero['heading'];
            $hero['subtitle'] = $hero['subheading'];
            $hero['image'] = $hero['media_url'];
        }
        $hero_slides = [$hero];
    }
    
    // Fetch stats for the hero overlay
    $show_stats = get_setting('show_hero_stats', 'on');
    $total_reviews = fetch_one("SELECT COUNT(*) as c FROM reviews")['c'] ?? 5231;
    $active_sale = fetch_one("SELECT * FROM sale_countdowns WHERE is_active = 1 AND end_date > NOW() LIMIT 1");
    
    // Fetch trust badges for the marquee
    $trust_badges = fetch_all("SELECT * FROM trust_badges WHERE is_active = 1 ORDER BY sort_order ASC");
    ?>

    <?php if($active_sale): ?>
    <!-- PREMIUM FLASH SALE TICKER -->
    <div class="bg-[#19DC7E] py-2 lg:py-3 relative z-[50] overflow-hidden">
        <!-- Animated Background Pulse -->
        <div class="absolute inset-0 bg-white/10 animate-pulse"></div>
        
        <div class="container mx-auto px-4 sm:px-6 relative z-10 flex flex-col md:flex-row items-center justify-between gap-3 md:gap-4">
            <div class="flex items-center gap-3 md:gap-4">
                <div class="hidden lg:flex items-center gap-2">
                    <span class="w-2 h-2 rounded-full bg-[#002A23] animate-ping"></span>
                    <span class="text-[10px] font-black uppercase tracking-[0.2em] text-[#002A23]/60 italic">Live Sale</span>
                </div>
                <h3 class="text-xs md:text-sm lg:text-base font-black text-[#002A23] uppercase tracking-tighter text-center md:text-left font-heading">
                    <?php echo htmlspecialchars($active_sale['title']); ?>
                </h3>
            </div>

            <!-- THE COUNTDOWN -->
            <div class="hero-sale-timer-row flex items-center gap-2.5 sm:gap-4 lg:gap-6 bg-[#002A23] px-4 sm:px-5 py-1 sm:py-1.5 rounded-full shadow-lg" data-end="<?php echo $active_sale['end_date']; ?>">
                <div class="flex items-center gap-2 sm:gap-3">
                    <div class="flex flex-col items-center">
                        <span class="hero-days text-xs sm:text-sm lg:text-base font-black text-[#19DC7E] leading-none">00</span>
                        <span class="text-[6px] sm:text-[7px] font-bold text-white/40 uppercase">Days</span>
                    </div>
                    <span class="text-[#19DC7E]/30 font-black text-[10px] sm:text-xs">:</span>
                    <div class="flex flex-col items-center">
                        <span class="hero-hours text-xs sm:text-sm lg:text-base font-black text-[#19DC7E] leading-none">00</span>
                        <span class="text-[6px] sm:text-[7px] font-bold text-white/40 uppercase">Hrs</span>
                    </div>
                    <span class="text-[#19DC7E]/30 font-black text-[10px] sm:text-xs">:</span>
                    <div class="flex flex-col items-center">
                        <span class="hero-mins text-xs sm:text-sm lg:text-base font-black text-[#19DC7E] leading-none">00</span>
                        <span class="text-[6px] sm:text-[7px] font-bold text-white/40 uppercase">Min</span>
                    </div>
                    <span class="text-[#19DC7E]/30 font-black text-[10px] sm:text-xs">:</span>
                    <div class="flex flex-col items-center">
                        <span class="hero-secs text-xs sm:text-sm lg:text-base font-black text-[#19DC7E] leading-none">00</span>
                        <span class="text-[6px] sm:text-[7px] font-bold text-white/40 uppercase">Sec</span>
                    </div>
                </div>
                <!-- Action Link (Optional) -->
                <a href="shop" class="hidden md:flex items-center gap-2 group">
                    <span class="text-[9px] font-black text-white uppercase tracking-widest group-hover:text-[#19DC7E] transition-colors">Shop Now</span>
                    <i class="fas fa-arrow-right text-[8px] text-[#19DC7E] group-hover:translate-x-1 transition-transform"></i>
                </a>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- MODERN BRANDED HERO (Optimized & Fully Responsive) -->
    <section class="relative min-h-[max(650px,90vh)] lg:h-[95vh] w-full bg-[#002A23] overflow-hidden flex flex-col pt-[80px] lg:pt-0" id="modern-hero">
        
        <!-- MAIN CONTENT WRAPPER -->
        <div class="flex-grow flex flex-col-reverse lg:flex-row relative z-10 w-full h-full">
            
            <!-- LEFT AREA: BRAND PITCH & SELECTOR -->
            <div class="w-full lg:w-[55%] flex flex-col justify-center px-6 sm:px-10 md:px-16 lg:px-24 py-12 lg:py-0 relative z-20 text-center lg:text-left">
                <div class="max-w-2xl mx-auto lg:mx-0">
                    <!-- Top Tagline -->
                    <span class="text-[#19DC7E] font-black uppercase tracking-[0.4em] text-[10px] sm:text-xs lg:text-sm mb-4 block anim-reveal-up" style="animation-delay: 0.1s">
                        <?php echo $hero_variants[0]['tagline'] ?? 'Your New Healthy Habit'; ?>
                    </span>
                    
                    <!-- Main Catchy Headline (Fluid Typography) -->
                    <h1 id="hero-main-title" class="text-[clamp(2.5rem,8vw,6rem)] font-black leading-[0.95] text-white tracking-tighter uppercase mb-6 lg:mb-12 anim-reveal-up font-heading" style="animation-delay: 0.2s">
                        <?php 
                            $first_title = $hero_variants[0]['name'] ?? 'PURE CRUNCH';
                            $words = explode(' ', strtoupper($first_title));
                            echo implode(' ', array_slice($words, 0, 2));
                            if (count($words) > 2) {
                                echo '<br>' . implode(' ', array_slice($words, 2));
                            }
                        ?>
                    </h1>

                    <!-- Badges Row (Optimized for Mobile) -->
                    <div class="flex flex-wrap items-center justify-center lg:justify-start gap-3 md:gap-4 mb-10 lg:mb-16 anim-reveal-up" id="hero-badges" style="animation-delay: 0.3s">
                        <!-- Price Badge -->
                        <div class="bg-white/10 backdrop-blur-3xl border border-white/10 px-5 lg:px-8 py-3 lg:py-4 rounded-2xl md:rounded-3xl flex flex-col items-center">
                            <div class="flex items-baseline gap-1">
                                <span class="text-white/40 text-xs font-black uppercase tracking-widest">₹</span>
                                <span id="hero-price" class="text-xl md:text-2xl lg:text-3xl font-black text-white leading-none"><?php echo $hero_variants[0]['price'] ?: '249'; ?></span>
                            </div>
                            <span class="text-[7px] lg:text-[10px] font-black uppercase tracking-widest text-white/40 mt-1">FOR SALE!</span>
                        </div>
                        <!-- Add to Cart Badge (Functional) -->
                        <div class="anim-reveal-up" id="hero-atc-wrap" style="animation-delay: 0.35s;">
                            <button id="hero-atc-btn" 
                                    onclick="addToCart(<?php echo $hero_variants[0]['product_id']; ?>, this, 1)"
                                    class="bg-[#19DC7E] px-6 lg:px-10 py-3 lg:py-5 rounded-2xl md:rounded-3xl flex items-center gap-3 lg:gap-4 text-[#002A23] shadow-xl hover:bg-white hover:scale-105 transition-all duration-300 group">
                                <div class="w-8 h-8 lg:w-10 lg:h-10 rounded-full bg-[#002A23] flex items-center justify-center text-white group-hover:bg-[#19DC7E] group-hover:text-[#002A23] transition-colors">
                                    <i class="fas fa-shopping-basket text-[10px] lg:text-sm"></i>
                                </div>
                                <div class="flex flex-col items-start">
                                    <span class="text-[9px] lg:text-xs font-black uppercase tracking-widest leading-none">BUY</span>
                                    <span class="text-[7px] lg:text-[10px] font-black uppercase tracking-widest opacity-60">NOW</span>
                                </div>
                            </button>
                        </div>
                    </div>
                    
                    <!-- Mobile Selector (Moved below for better reach) -->
                    <div class="flex lg:hidden overflow-x-auto hide-scrollbar gap-3 mb-8 w-full px-1">
                        <?php foreach($hero_variants as $index => $variant): ?>
                        <button onclick="switchHeroProduct(<?php echo $index; ?>, this)" 
                                class="hero-thumb shrink-0 w-16 h-16 active:scale-95 group transition-all duration-500 overflow-hidden relative <?php echo $index === 0 ? 'active-scene opacity-100' : 'opacity-40'; ?>">
                            <div class="aspect-square w-full h-full bg-white/10 backdrop-blur-md rounded-xl border border-white/10 p-2 flex items-center justify-center">
                                <img src="<?php echo get_url($variant['image']); ?>" class="w-10 h-auto">
                            </div>
                        </button>
                        <?php endforeach; ?>
                    </div>

                    <!-- THUMBNAIL SELECTOR (Desktop Only Grid) -->
                    <div class="hidden lg:grid grid-cols-4 gap-4 max-w-sm anim-reveal-up" style="animation-delay: 0.4s">
                        <?php 
                        foreach($hero_variants as $index => $variant): 
                        ?>
                        <button onclick="switchHeroProduct(<?php echo $index; ?>, this)" 
                                class="hero-thumb w-auto h-auto active:scale-95 group transition-all duration-500 overflow-hidden relative <?php echo $index === 0 ? 'active-scene opacity-100' : 'opacity-40 hover:opacity-100'; ?>">
                            <div class="aspect-square w-full h-full bg-white/5 backdrop-blur-md rounded-xl md:rounded-2xl border border-white/10 p-2 flex items-center justify-center overflow-hidden">
                                <img src="<?php echo get_url($variant['image']); ?>" class="w-full h-auto transform group-hover:scale-110 transition-transform" alt="">
                            </div>
                            <!-- Progress Bar -->
                            <div class="absolute bottom-0 left-0 h-1 bg-[#19DC7E] transition-all duration-300 pointer-events-none thumb-progress" style="width: 0%"></div>
                        </button>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- RIGHT AREA: ACCENT PANEL & PRODUCT (Advanced Responsive Composition) -->
            <div class="w-full lg:w-[45%] relative min-h-[50vh] md:min-h-[50vh] lg:h-full flex items-center justify-center mt-auto lg:mt-0">
                
                <!-- ACCENT BACKGROUND (Dynamic Fluid Shape) -->
                <div id="hero-accent-panel" class="absolute inset-y-0 right-0 w-full lg:w-[85%] lg:rounded-l-[100px] xl:rounded-l-[150px] transform transition-all duration-1000 ease-out z-0" style="background-color: <?php echo $hero_variants[0]['bg'] ?? '#19DC7E'; ?>"></div>

                <!-- VERTICAL TEXT CONTAINER (Safe Visibility) -->
                <div class="absolute inset-0 flex items-center justify-center lg:justify-end pointer-events-none select-none z-10 overflow-hidden px-10">
                    <div id="hero-vertical-text-box" class="flex flex-col gap-4 transform opacity-10 lg:opacity-100 lg:mr-12 xl:mr-20">
                         <span class="hero-v-text text-[clamp(4rem,15vw,10rem)] font-black text-[#002A23]/10 uppercase leading-none tracking-tighter lg:text-[#002A23]/10" style="-webkit-text-stroke: 1px rgba(0,42,35,0.2);">
                            <?php echo $hero_variants[0]['v1'] ?? 'HEALTHY'; ?>
                         </span>
                         <span class="hero-v-text text-[clamp(4rem,15vw,10rem)] font-black text-[#002A23]/10 uppercase leading-none tracking-tighter lg:text-[#002A23]/10" style="-webkit-text-stroke: 1px rgba(0,42,35,0.2);">
                            <?php echo $hero_variants[0]['v2'] ?? 'SNACKING'; ?>
                         </span>
                    </div>
                </div>

                <!-- MAIN PRODUCT (Enhanced Center/Mobile Composition) -->
                <div id="hero-main-img-container" class="relative z-20 w-full flex items-center justify-center p-8 sm:p-12 lg:p-0">
                    <div class="relative group/main max-w-[400px] sm:max-w-none">
                        <!-- Bottom Shadow -->
                        <div class="absolute -bottom-6 md:-bottom-12 left-1/2 -translate-x-1/2 w-[70%] h-12 bg-black/30 blur-[40px] md:blur-[60px] rounded-full scale-y-50 opacity-60"></div>
                        
                        <!-- The Image -->
                        <img id="hero-main-img" 
                             src="<?php echo get_url($hero_variants[0]['image']); ?>" 
                             class="w-full max-w-[280px] sm:max-w-[400px] lg:max-w-[450px] xl:max-w-[500px] h-auto drop-shadow-[0_45px_75px_rgba(0,0,0,0.4)] anim-float-slow transform group-hover/main:scale-105 transition-all duration-1000 briry" 
                             alt="Premium Snack">
                        
                        <!-- Wishlist Toggle -->
                <div id="hero-heart-btn" 
                     onclick="toggleWishlist(<?php echo $hero_variants[0]['product_id'] ?? 0; ?>, this)"
                     class="absolute top-[25%] -right-4 md:-right-8 w-14 h-14 md:w-16 md:h-16 bg-white rounded-full flex items-center justify-center shadow-[0_20px_40px_rgba(0,0,0,0.2)] cursor-pointer hover:scale-110 active:scale-95 transition-all z-30 group <?php echo in_array($hero_variants[0]['product_id'] ?? 0, $wishlist_ids) ? 'active text-red-500' : 'text-[#19DC7E]'; ?>">
                    <i class="<?php echo in_array($hero_variants[0]['product_id'] ?? 0, $wishlist_ids) ? 'fas' : 'far'; ?> fa-heart text-xl md:text-2xl transition-all duration-300"></i>
                </div>
                    </div>
                </div>

                <!-- Navigation Controls (Bottom Right - Fixed Mobile Position) -->
                <div class="absolute bottom-6 md:bottom-10 right-6 md:right-10 lg:right-20 flex gap-3 md:gap-4 z-30">
                    <button onclick="prevHero()" class="w-11 h-11 md:w-14 md:h-14 rounded-2xl bg-black/30 backdrop-blur-xl border border-white/10 flex items-center justify-center text-white hover:bg-white hover:text-black transition-all active:scale-90">
                        <i class="fas fa-chevron-left text-sm md:text-base"></i>
                    </button>
                    <button onclick="nextHero()" class="w-11 h-11 md:w-14 md:h-14 rounded-2xl bg-[#002A23] text-white flex items-center justify-center shadow-2xl hover:bg-[#19DC7E] hover:text-[#002A23] transition-all active:scale-90">
                         <i class="fas fa-chevron-right text-sm md:text-base"></i>
                    </button>
                </div>

            </div>
        </div>

    </section>

    <!-- INFINITE BRAND TRUST MARQUEE -->
    <?php if(!empty($trust_badges)): ?>
    <section class="bg-white py-10 lg:py-16 overflow-hidden border-b border-gray-100 relative group/marquee">
        <!-- Subtle Background Glow -->
        <div class="absolute top-0 right-0 w-[100px] h-full bg-gradient-to-l from-white via-white/80 to-transparent z-10 pointer-events-none"></div>
        <div class="absolute top-0 left-0 w-[100px] h-full bg-gradient-to-r from-white via-white/80 to-transparent z-10 pointer-events-none"></div>

        <div class="flex whitespace-nowrap marquee-wrapper">
            <div class="flex items-center gap-16 lg:gap-24 marquee-content px-8">
                <?php foreach($trust_badges as $badge): 
                    $bg = str_contains($badge['bg_color'], '[') ? substr($badge['bg_color'], 4, 7) : $badge['bg_color'];
                    $ic = str_contains($badge['icon_color'], '[') ? substr($badge['icon_color'], 6, 7) : $badge['icon_color'];
                ?>
                <div class="flex items-center gap-5 group/badge cursor-default shrink-0">
                    <div class="w-14 h-14 lg:w-20 lg:h-20 rounded-2xl md:rounded-3xl flex items-center justify-center shadow-[0_10px_30px_-10px_rgba(0,0,0,0.1)] transform group-hover/badge:scale-110 group-hover/badge:rotate-3 transition-all duration-500" style="background-color: <?php echo $bg; ?>; color: <?php echo $ic; ?>;">
                        <i class="<?php echo $badge['icon']; ?> text-2xl lg:text-3xl"></i>
                    </div>
                    <div class="flex flex-col">
                        <span class="text-[10px] lg:text-xs font-black uppercase tracking-[0.2em] text-gray-400 mb-0.5"><?php echo $badge['subtitle']; ?></span>
                        <span class="text-base lg:text-xl font-black text-[#002A23] uppercase tracking-tighter"><?php echo $badge['title']; ?></span>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- Duplicate for Infinite Loop -->
            <div class="flex items-center gap-16 lg:gap-24 marquee-content px-8" aria-hidden="true">
                <?php foreach($trust_badges as $badge): 
                    $bg = str_contains($badge['bg_color'], '[') ? substr($badge['bg_color'], 4, 7) : $badge['bg_color'];
                    $ic = str_contains($badge['icon_color'], '[') ? substr($badge['icon_color'], 6, 7) : $badge['icon_color'];
                ?>
                <div class="flex items-center gap-5 group/badge shrink-0">
                    <div class="w-14 h-14 lg:w-20 lg:h-20 rounded-2xl md:rounded-3xl flex items-center justify-center shadow-sm transform group-hover/badge:scale-110 transition-all duration-500" style="background-color: <?php echo $bg; ?>; color: <?php echo $ic; ?>;">
                        <i class="<?php echo $badge['icon']; ?> text-2xl lg:text-3xl"></i>
                    </div>
                    <div class="flex flex-col">
                        <span class="text-[10px] lg:text-xs font-black uppercase tracking-[0.2em] text-gray-400 mb-0.5"><?php echo $badge['subtitle']; ?></span>
                        <span class="text-base lg:text-xl font-black text-[#002A23] uppercase tracking-tighter"><?php echo $badge['title']; ?></span>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <!-- MODERN HERO SPECIFIC STYLES -->
    <style>
        #modern-hero { font-family: 'Montserrat', sans-serif; }

        @keyframes revealUp {
            from { transform: translateY(30px); opacity: 0; filter: blur(5px); }
            to { transform: translateY(0); opacity: 1; filter: blur(0); }
        }
        .anim-reveal-up { animation: revealUp 0.8s cubic-bezier(0.19, 1, 0.22, 1) forwards; opacity: 0; }

        @keyframes floatSlow {
            0%, 100% { transform: translateY(0) rotate(0deg); }
            50% { transform: translateY(-30px) rotate(-1.5deg); }
        }
        .anim-float-slow { animation: floatSlow 6s ease-in-out infinite; }

        @keyframes arcExit {
            0% { transform: translate(0, 0) rotate(0deg) scale(1); opacity: 1; filter: blur(0); }
            100% { transform: translate(500px, -300px) rotate(45deg) scale(0.6); opacity: 0; filter: blur(20px); }
        }

        @keyframes arcEnter {
            0% { transform: translate(-500px, 300px) rotate(-45deg) scale(1.6); opacity: 0; filter: blur(20px); }
            100% { transform: translate(0, 0) rotate(0deg) scale(1); opacity: 1; filter: blur(0); }
        }

        /* Vertical Text Styling - Responsive Mode */
        @media (min-width: 1024px) {
            #hero-vertical-text-box {
                writing-mode: vertical-rl;
                text-orientation: mixed;
            }
        }

        /* Mobile Adjustments for High Impact Composition */
        @media (max-width: 1023px) {
            #hero-accent-panel {
                inset: auto 0 0 0;
                width: 100%;
                height: 55%;
                border-radius: 40px 40px 0 0;
            }
            #hero-vertical-text-box {
                flex-direction: row;
                gap: 2rem;
                opacity: 0.1;
                pointer-events: none;
            }
            .hero-v-text { white-space: nowrap; }
        }

        @media (max-width: 640px) {
            #hero-main-title { letter-spacing: -0.05em; }
            .hero-v-text { font-size: 25vw !important; }
        }
        /* Infinite Marquee */
        @keyframes marquee {
            0% { transform: translateX(0); }
            100% { transform: translateX(-100%); }
        }
        .marquee-content {
            animation: marquee 40s linear infinite;
        }
        .marquee-wrapper:hover .marquee-content {
            animation-play-state: paused;
        }

        /* Active Scene Style */
        .hero-thumb.active-scene { 
            opacity: 1 !important; 
            box-shadow: 0 0 20px rgba(25, 220, 126, 0.2); 
            border-color: rgba(25, 220, 126, 0.4); 
        }
    </style>









    <script>
    (function() {
        // Find all timer rows
        const timerRows = document.querySelectorAll('.hero-sale-timer-row');
        if(timerRows.length === 0) return;
        
        // Use the first row to determine the end time
        const rawDate = timerRows[0].getAttribute('data-end');
        if(!rawDate) return;

        // universal format: YYYY/MM/DD HH:MM:SS
        const dateStr = rawDate.replace(/-/g, "/");
        const endDate = new Date(dateStr).getTime();
        
        if (isNaN(endDate)) {
            console.error("Invalid sale end date:", rawDate);
            return;
        }

        const updateTimer = () => {
            const now = new Date().getTime();
            const distance = endDate - now;
            
            timerRows.forEach(row => {
                if (distance < 0) {
                    // Find the main banner container to hide
                    const ticker = row.closest('.bg-\\[\\#19DC7E\\]') || row.parentElement.parentElement;
                    if (ticker) ticker.style.display = 'none';
                    row.style.display = 'none';
                    return;
                }

                const d = Math.floor(distance / (1000 * 60 * 60 * 24));
                const h = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
                const m = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
                const s = Math.floor((distance % (1000 * 60)) / 1000);

                const elDays = row.querySelector('.hero-days');
                const elHours = row.querySelector('.hero-hours');
                const elMin = row.querySelector('.hero-mins');
                const elSec = row.querySelector('.hero-secs');

                if(elDays) elDays.innerText = d.toString().padStart(2, '0');
                if(elHours) elHours.innerText = h.toString().padStart(2, '0');
                if(elMin) elMin.innerText = m.toString().padStart(2, '0');
                if(elSec) elSec.innerText = s.toString().padStart(2, '0');
            });
        };

        // Run immediately and then every second
        updateTimer();
        setInterval(updateTimer, 1000);
    })();
    </script>




    <!-- CATEGORIES CAROUSEL (Scroll Snap) -->
    <section class="py-16 anim-up delay-200 overflow-hidden">
        <div class="container mx-auto px-6 mb-8 flex justify-between items-end">
             <div>
                    <span class="text-[#24B25D] font-black tracking-[0.2em] uppercase text-xs mb-3 block font-sans">Browse by Vibe</span>
                    <h2 class="text-3xl md:text-5xl font-heading font-black text-gray-900 leading-none">Find Your Crunch </h2>
                </div>

            <!-- Category Slider Controls -->
            <div class="hidden md:flex gap-3">
                <button onclick="scrollCategories('left')" class="w-16 h-16 rounded-[24px] bg-white border-3 border-gray-100 flex items-center justify-center text-gray-400 hover:border-black hover:text-black hover:rotate-[-5deg] transition-all shadow-sm active:scale-90" aria-label="Previous categories">
                    <i class="fas fa-arrow-left text-xl"></i>
                </button>
                <button onclick="scrollCategories('right')" class="w-16 h-16 rounded-[24px] bg-black text-[#24B25D] flex items-center justify-center hover:scale-105 hover:rotate-[5deg] transition-all shadow-2xl active:scale-90" aria-label="Next categories">
                    <i class="fas fa-arrow-right text-xl"></i>
                </button>
            </div>
        </div>

        <!-- Constrained Slider Wrapper -->
        <div class="w-full relative overflow-hidden">
            <div id="category-slider-container" class="flex w-full overflow-x-auto gap-6 px-6 pb-12 hide-scrollbar scroll-smooth">
                <?php 
                $cats = get_all_categories();
                $styles = [
                    ['bg'=>'bg-[#E0F2FE]', 'text'=>'text-[#0c4a6e]', 'sub'=>'SWEET & TANGY', 'emoji'=>'🍎'],
                    ['bg'=>'bg-[#DCFCE7]', 'text'=>'text-[#14532d]', 'sub'=>'TRADITIONAL', 'emoji'=>'🥦'],
                    ['bg'=>'bg-[#FEF3C7]', 'text'=>'text-[#78350f]', 'sub'=>'POWER SNACK', 'emoji'=>'🌰'],
                    ['bg'=>'bg-[#FEE2E2]', 'text'=>'text-[#7f1d1d]', 'sub'=>'HOT & SPICY', 'emoji'=>'🌶️'],
                    ['bg'=>'bg-[#F3E8FF]', 'text'=>'text-[#581c87]', 'sub'=>'EXOTIC', 'emoji'=>'🍇']
                ];
                $i = 0;
                ?>
                <?php foreach($cats as $c): 
                    $s = $styles[$i % count($styles)];
                    $i++;
                ?>
                <div class="flex-none w-[85vw] sm:w-[calc(50%-1.5rem)] md:w-[calc(50%-1.5rem)] lg:w-[calc(33.333%-2rem)] xl:w-[calc(25%-2.5rem)] snap-start h-full">
                    <a href="<?php echo category_url($c['slug']); ?>" class="block h-[400px] md:h-[450px] <?php echo $s['bg']; ?> rounded-[50px] p-6 flex flex-col justify-between relative overflow-hidden group transition-all duration-500 hover:shadow-[0_20px_50px_rgba(0,0,0,0.1)] hover:-translate-y-2">
                
                <!-- Inner Glow/Border for 3D Feel -->
                <div class="absolute inset-0 border border-white/40 rounded-[50px] z-20 pointer-events-none"></div>
                <div class="absolute inset-0 border-2 border-white/20 rounded-[50px] z-20 pointer-events-none translate-y-1 translate-x-1 blur-[1px]"></div>

                <!-- Holographic Sheen Animation -->
                <div class="absolute inset-0 bg-gradient-to-tr from-transparent via-white/30 to-transparent skew-x-12 translate-x-[-200%] group-hover:translate-x-[200%] transition-transform duration-1000 ease-in-out z-30 pointer-events-none"></div>

                <!-- Text Container with Glass Effect -->
                <div class="relative z-20 bg-white/40 backdrop-blur-md rounded-[20px] md:rounded-[30px] p-4 md:p-6 border border-white/60 shadow-[0_8px_32px_rgba(255,255,255,0.2)] group-hover:scale-[1.02] transition-transform duration-500 origin-top-left">
                    <div class="flex justify-between items-start mb-1 md:mb-2">
                        <span class="px-2 py-1 md:px-3 md:py-1 rounded-full text-[8px] md:text-[10px] font-black tracking-widest uppercase bg-white/80 backdrop-blur-sm <?php echo $s['text']; ?> shadow-sm"><?php echo $s['sub']; ?></span>
                        <div class="w-8 h-8 md:w-10 md:h-10 rounded-full bg-white flex items-center justify-center shadow-sm group-hover:bg-black group-hover:text-white transition-colors duration-300">
                             <i class="fas fa-arrow-right -rotate-45 group-hover:rotate-0 transition-transform duration-300 text-xs md:text-base"></i>
                        </div>
                    </div>
                    <h3 class="text-xl md:text-4xl font-heading font-black <?php echo $s['text']; ?> leading-none mb-1 md:mb-2 drop-shadow-sm card-title"><?php echo $c['name']; ?></h3>
                    <p class="<?php echo $s['text']; ?>/80 font-sans font-bold text-[10px] md:text-sm tracking-wide flex items-center gap-2">
                        <span class="w-1.5 h-1.5 md:w-2 md:h-2 rounded-full bg-current animate-pulse"></span>
                        <?php echo $c['product_count']; ?> Varieties
                    </p>
                </div>
                
                <!-- Main Image - Floating 3D Effect -->
                <?php if($c['image']): ?>
                    <div class="absolute inset-0 flex items-end justify-center z-10 perspective-[1000px]">
                         <img src="<?php echo $c['image']; ?>" alt="<?php echo htmlspecialchars($c['name']); ?>" class="w-64 h-64 object-contain transform translate-y-8 scale-95 group-hover:translate-y-0 group-hover:scale-110 group-hover:rotate-3 transition duration-700 ease-out drop-shadow-2xl brightness-105">
                    </div>
                <?php else: ?>
                    <!-- Fallback Emoji Art -->
                    <div class="absolute bottom-0 left-1/2 -translate-x-1/2 text-[12rem] transform translate-y-10 group-hover:translate-y-0 group-hover:scale-110 group-hover:rotate-6 transition duration-700 opacity-90 filter drop-shadow-2xl grayscale-[0.2] group-hover:grayscale-0">
                        <?php echo $s['emoji']; ?>
                    </div>
                <?php endif; ?>


                <!-- Background Abstract Blob -->
                <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-64 h-64 bg-white/30 rounded-full blur-3xl opacity-0 group-hover:opacity-100 transition duration-700"></div>
            </a>
                </div>
            <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- CHUNKY PRODUCT GRID (Clickable Cards) -->
    <section class="py-16 bg-white rounded-t-[3rem] shadow-[0_-20px_40px_rgba(0,0,0,0.05)] relative z-20 overflow-hidden">
        <div class="cantainer mx-auto px-6">
            <div class="flex flex-col md:flex-row justify-between items-start md:items-end mb-16 gap-6">
                <div>
                    <span class="text-[#24B25D] font-black tracking-[0.2em] uppercase text-xs mb-3 block font-sans">Fresh From The Farm</span>
                    <h2 class="text-3xl md:text-5xl font-heading font-black text-gray-900 leading-none">Hot Drops <span class="text-[#24B25D]">🔥</span></h2>
                </div>
                <!-- Slider Controls -->
                <div class="flex gap-3">
                    <button onclick="scrollProducts('left')" class="w-16 h-16 rounded-[24px] bg-white border-3 border-gray-100 flex items-center justify-center text-gray-400 hover:border-black hover:text-black hover:rotate-[-5deg] transition-all shadow-sm active:scale-90" aria-label="Previous products">
                        <i class="fas fa-arrow-left text-xl"></i>
                    </button>
                    <button onclick="scrollProducts('right')" class="w-16 h-16 rounded-[24px] bg-black text-[#24B25D] flex items-center justify-center hover:scale-105 hover:rotate-[5deg] transition-all shadow-2xl active:scale-90" aria-label="Next products">
                        <i class="fas fa-arrow-right text-xl"></i>
                    </button>
                </div>
            </div>
            
            <!-- Constrained Product Slider Wrapper -->
            <div class="w-full relative overflow-hidden">
                <div id="product-slider-container" class="flex flex-row flex-nowrap w-full gap-6 overflow-x-auto hide-scrollbar scroll-smooth px-2 pb-12 snap-x mandatory">
                <?php 
                $delay = 0;
                $default_bg_colors = ['#E0F2FE', '#DCFCE7', '#FEF3C7', '#FEE2E2', '#F3E8FF', '#FFEDD5'];
                $i = 0;
                foreach ($featured as $p): 
                    $db_color = !empty($p['bg_color']) ? $p['bg_color'] : null;
                    $color_raw = $db_color ?: $default_bg_colors[$i % count($default_bg_colors)];
                    $card_bg = adjust_brightness($color_raw, -15);
                    $i++;
                    $delay += 100;
                ?>
                <!-- Premium Product Card Wrapper -->
                <div class="flex-none flex-shrink-0 w-[85vw] sm:w-[calc(50%-1.5rem)] md:w-[calc(50%-1.5rem)] lg:w-[calc(33.333%-2rem)] xl:w-[calc(25%-2.5rem)] snap-start h-full">
                    <div class="group relative rounded-[50px] hover:shadow-[0_45px_90px_rgba(0,0,0,0.15)] transition-all duration-700 overflow-hidden border-4 border-white/50 hover:border-white h-full flex flex-col anim-up" style="background-color: <?php echo $card_bg; ?>; animation-delay: <?php echo $delay; ?>ms">
                        
                        <!-- White Overlay (Fades out on hover for fuller color) -->
                        <div class="absolute inset-0 bg-white/60 group-hover:bg-white/0 transition-colors duration-700 pointer-events-none"></div>
                        
                        <!-- Dynamic Glow Highlight (Appears on hover) -->
                        <div class="absolute -inset-1 bg-gradient-to-tr from-white/30 via-transparent to-white/30 opacity-0 group-hover:opacity-100 transition-opacity duration-700 blur-2xl z-0"></div>

                        <!-- Link Wrapper -->
                        <a href="<?php echo product_url($p['slug']); ?>" class="block flex-1 relative p-2 z-10">
                            
                            <!-- Image Area with 3D Float -->
                            <div class="bg-white/80 rounded-[42px] aspect-square mb-6 overflow-hidden relative flex items-center justify-center group-hover:bg-white transition-all duration-700 shadow-inner group-hover:shadow-none">
                                <!-- Background Bloom -->
                                <div class="absolute inset-0 bg-gradient-to-tr from-white via-transparent to-white/50 opacity-0 group-hover:opacity-100 transition duration-700 z-0"></div>
                                
                                <img src="<?php echo get_url($p['image']); ?>" 
                                     loading="lazy"
                                     alt="<?php echo htmlspecialchars($p['name']); ?>"
                                     class="w-[85%] h-[85%] object-contain transform group-hover:scale-110 group-hover:-rotate-6 group-hover:-translate-y-4 transition duration-700 ease-out z-10 filter drop-shadow-[0_10px_10px_rgba(0,0,0,0.05)] group-hover:drop-shadow-[0_30px_30px_rgba(0,0,0,0.1)] <?php echo $p['stock'] <= 0 ? 'grayscale' : ''; ?>">                                
                                <!-- Badges -->
                                <div class="absolute top-6 left-6 flex flex-col gap-2 z-20 items-start">
                                    <?php if(isset($p['is_new']) && $p['is_new']): ?>
                                        <span class="bg-[#24B25D] text-black text-[10px] font-black px-4 py-1.5 rounded-full shadow-lg shadow-green-200 uppercase tracking-widest backdrop-blur-md transform group-hover:scale-110 transition-transform">NEW ✨</span>
                                    <?php endif; ?>
                                    <?php if(isset($p['discount_percentage']) && $p['discount_percentage'] > 0): ?>
                                        <span class="bg-black text-white text-[10px] font-black px-4 py-1.5 rounded-full shadow-lg h-8 flex items-center justify-center uppercase tracking-widest transform group-hover:rotate-12 transition-transform">-<?php echo $p['discount_percentage']; ?>% OFF</span>
                                    <?php endif; ?>
                                    
                                    <!-- Stock Indicator Badge -->
                                    <?php if($p['stock'] <= 0): ?>
                                        <span class="bg-red-500 text-white text-[10px] font-black px-4 py-1.5 rounded-full shadow-lg uppercase tracking-widest">Sold Out</span>
                                    <?php elseif($p['stock'] < 10): ?>
                                        <span class="bg-amber-500 text-white text-[10px] font-black px-4 py-1.5 rounded-full shadow-lg uppercase tracking-widest animate-pulse">Few Left</span>
                                    <?php endif; ?>
                                </div>

                                <!-- Heart Icon -->
                                <?php $is_wishlisted = in_array($p['id'], $wishlist_ids); ?>
                                <button onclick="event.preventDefault(); toggleWishlist(<?php echo $p['id']; ?>, this)" class="absolute top-5 right-5 w-12 h-12 bg-white rounded-[18px] flex items-center justify-center shadow-lg <?php echo $is_wishlisted ? 'active text-red-500' : 'text-gray-300'; ?> hover:text-red-500 hover:scale-110 transition-all duration-300 z-20 group/heart active:scale-90 border border-gray-50" aria-label="Add <?php echo htmlspecialchars($p['name']); ?> to Wishlist">
                                    <i class="<?php echo $is_wishlisted ? 'fas' : 'far'; ?> fa-heart group-hover/heart:animate-bounce"></i>
                                </button>
                            </div>

                            <!-- Content -->
                            <div class="px-6 pb-2 relative">
                                <div class="flex flex-col gap-1">
                                    <div class="flex items-center gap-2">
                                        <span class="w-2 h-2 rounded-full bg-[#24B25D] opacity-50"></span>
                                        <p class="text-gray-400 text-[10px] font-black uppercase tracking-[0.2em] font-sans">Premium Select</p>
                                    </div>
                                    <h3 class="text-2xl font-black font-heading text-gray-900 group-hover:text-black transition leading-tight py-1 card-title"><?php echo $p['name']; ?></h3>
                                   
                                </div>
                            </div>
                        </a>
                        
                        <!-- Glass Action Bar (Floating at bottom) -->
                        <div class="px-3 pb-4 md:px-5 md:pb-5 pt-2 md:pt-3 mt-auto z-20 relative">
                            <div class="bg-white rounded-[24px] md:rounded-[30px] p-2 md:p-3 action-bar-chunky border-2 border-transparent group-hover:border-white group-hover:shadow-[0_15px_40px_rgba(0,0,0,0.08)] transition-all duration-500">
                                 
                                 <!-- Price -->
                                 <div class="pl-2 md:pl-4 flex flex-col leading-none">
                                    <?php if(isset($p['original_price']) && $p['original_price'] > $p['price']): ?>
                                        <span class="text-[9px] md:text-[11px] text-gray-400 font-bold line-through decoration-red-400/50 block mb-0.5">₹<?php echo $p['original_price']; ?></span>
                                    <?php endif; ?>
                                    <span class="text-xl md:text-3xl font-black text-gray-900 font-heading tracking-tighter">₹<?php echo $p['price']; ?></span>
                                 </div>

                                 <div class="flex gap-1 md:gap-2">
                                    <!-- Quick Buy -->
                                    <button onclick="event.stopPropagation(); quickBuy(<?php echo $p['id']; ?>, this)" 
                                        <?php echo $p['stock'] <= 0 ? 'disabled' : ''; ?>
                                        class="w-10 h-10 md:w-14 md:h-14 rounded-xl md:rounded-2xl <?php echo $p['stock'] <= 0 ? 'bg-gray-100 text-gray-300' : 'bg-amber-50 text-amber-500 hover:bg-amber-400 hover:text-white hover:scale-105 active:scale-95'; ?> flex items-center justify-center transition-all duration-300 group/btn" aria-label="Quick buy <?php echo htmlspecialchars($p['name']); ?>">
                                        <i class="fas fa-bolt text-sm md:text-xl group-hover/btn:animate-pulse"></i>
                                    </button>
                                    <!-- Add Cart -->
                                    <button onclick="event.stopPropagation(); addToCart(<?php echo $p['id']; ?>, this)" 
                                        <?php echo $p['stock'] <= 0 ? 'disabled' : ''; ?>
                                        class="w-10 h-10 md:w-14 md:h-14 rounded-xl md:rounded-2xl <?php echo $p['stock'] <= 0 ? 'bg-gray-100 text-gray-300' : 'bg-black text-[#24B25D] hover:bg-[#24B25D] hover:text-black hover:scale-105 active:scale-95'; ?> flex items-center justify-center transition-all duration-300 group/btn" aria-label="Add <?php echo htmlspecialchars($p['name']); ?> to Bag">
                                        <i class="fas fa-shopping-bag text-sm md:text-xl group-hover/btn:rotate-12 transition-transform"></i>
                                    </button>
                                 </div>
                            </div>
                        </div>

                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
            
            <div class="text-center mt-16">
                <a href="<?php echo get_url('shop'); ?>" class="btn-chunky btn-outline px-12 py-4 text-lg border-2">View All Products</a>
            </div>
        </div>
    </section>

    <!-- CINEMATIC BRAND VIDEO SECTION -->
    <?php
    $vid_sec = fetch_one("SELECT * FROM homepage_sections WHERE section_name = 'video_brand_story'");
    // Fallback if DB fetch fails
    if(!$vid_sec) {
        $vid_sec = [
            'heading' => "FROM KASHMIR \nWITH LOVE.",
            'subheading' => "Experience the journey of our premium treats. No machines, just mountain air and traditional processing.",
            'media_url' => "assets/images/hero.jpg",
            'video_url' => "#"
        ];
    }
    ?>
    <section class="py-10 px-4 mb-20 md:mb-0" id="video_brand_story">
        <div class="cantainer mx-auto">
            <div class="relative w-full rounded-[40px] overflow-hidden shadow-2xl group cursor-pointer aspect-[16/10] md:aspect-video bg-black">
                
                <!-- Video Placeholder (Dynamic Image) -->
                <img src="<?php echo $vid_sec['media_url']; ?>" alt="Brand Story Video Thumbnail" class="w-full h-full object-cover opacity-60 group-hover:opacity-40 transition duration-700 transform group-hover:scale-105">
                
                <!-- Play Button (Glassmorphism) -->
                <button onclick="openVideoModal('<?php echo $vid_sec['video_url']; ?>')" class="absolute inset-0 flex items-center justify-center z-30 w-full h-full cursor-pointer focus:outline-none" aria-label="Play Brand Story Video">                    <div class="w-16 h-16 md:w-24 md:h-24 bg-white/10 backdrop-blur-md rounded-full border border-white/30 flex items-center justify-center group-hover:scale-110 transition duration-500 shadow-[0_0_50px_rgba(25,220,126,0.5)] group-hover:bg-[#24B25D] group-hover:border-transparent">
                        <i class="fas fa-play text-2xl md:text-4xl text-white ml-2"></i>
                    </div>
                </button>

                <!-- Text Overlay -->
                <div class="absolute bottom-0 left-0 w-full p-6 md:p-12 bg-gradient-to-t from-black via-black/60 to-transparent z-20 pointer-events-none">
                    <div class="max-w-3xl pointer-events-auto">
                        <div class="inline-block bg-[#24B25D] text-black text-[10px] md:text-xs font-bold px-3 py-1 rounded-full mb-3 md:mb-4 uppercase tracking-widest transform translate-y-4 opacity-0 group-hover:translate-y-0 group-hover:opacity-100 transition duration-500 delay-100">
                            Watch Brand Story
                        </div>
                        <h2 class="text-2xl md:text-5xl font-heading font-bold text-white mb-2 md:mb-4 leading-tight transform translate-y-4 group-hover:translate-y-0 transition duration-500 delay-200">
                            <?php echo nl2br(htmlspecialchars($vid_sec['heading'])); ?>
                        </h2>
                        <p class="text-gray-200 font-sans text-sm md:text-xl max-w-xl opacity-0 group-hover:opacity-100 transform translate-y-4 group-hover:translate-y-0 transition duration-500 delay-300 hidden md:block">
                            <?php echo nl2br(htmlspecialchars($vid_sec['subheading'])); ?>
                        </p>
                    </div>
                </div>
                </div>

                <!-- Scrolling Marquee inside video container -->
                <div class="absolute top-10 right-0 bg-white/10 backdrop-blur-md border border-white/20 py-2 px-6 rounded-l-full transform translate-x-4 group-hover:translate-x-0 transition duration-500">
                    <span class="text-white font-bold uppercase tracking-widest text-xs flex items-center gap-2">
                        <i class="fas fa-circle text-red-500 animate-pulse text-[8px]"></i> Watch Our Story
                    </span>
                </div>

            </div>
        </div>
    </section>

    <!-- CINEMATIC SPOTLIGHT REVIEWS -->
    <?php
    $testimonials = fetch_all("SELECT * FROM testimonials WHERE is_active=1 ORDER BY created_at DESC LIMIT 5");
    if(empty($testimonials)) {
        // Fallback Data
        $testimonials = [
            ['name'=>'Sarah Jenkins', 'location'=>'Mumbai', 'message'=>'These apple chips are wildly addictive. I finished the family pack in one sitting.', 'rating'=>5],
            ['name'=>'Michael T.', 'location'=>'Delhi', 'message'=>'Finally a snack that my kids love and I don\'t feel guilty about. Pure genius.', 'rating'=>5],
            ['name'=>'Priya Kapoor', 'location'=>'Bangalore', 'message'=>'The texture is unreal. Not too hard, just the right amount of crunch.', 'rating'=>5]
        ];
    }
    ?>
    <section class="py-20 md:py-32 bg-black relative overflow-hidden text-white" id="reviews-section">
        <!-- Background Accents -->
        <div class="absolute top-0 left-0 w-full h-full opacity-20 pointer-events-none">
            <div class="absolute top-[-20%] left-[-10%] w-[300px] md:w-[500px] h-[300px] md:h-[500px] bg-[#24B25D] rounded-full blur-[100px] md:blur-[150px] animate-pulse"></div>
            <div class="absolute bottom-[-20%] right-[-10%] w-[300px] md:w-[500px] h-[300px] md:h-[500px] bg-blue-600 rounded-full blur-[100px] md:blur-[150px] animate-pulse" style="animation-delay: 2s;"></div>
        </div>

        <div class="container mx-auto px-6 relative z-10">
            
            <div class="text-center mb-10 md:mb-16">
                 <div class="inline-flex items-center gap-2 border border-white/20 rounded-full px-4 py-2 bg-white/5 backdrop-blur-md mb-6">
                     <i class="fas fa-heart text-[#24B25D]"></i>
                     <span class="text-[10px] md:text-xs font-bold tracking-[0.2em] uppercase">Wall of Love</span>
                 </div>
            </div>

            <div class="relative max-w-4xl mx-auto text-center" id="review-slider">
                
                <!-- Review Items -->
                <?php foreach($testimonials as $k => $t): ?>
                <div class="review-slide absolute inset-0 transition-opacity duration-700 ease-[cubic-bezier(0.23,1,0.32,1)] <?php echo $k===0 ? 'opacity-100 relative' : 'opacity-0 absolute pointer-events-none'; ?>" data-index="<?php echo $k; ?>">
                    
                    <div class="mb-6 md:mb-8 text-[#24B25D] text-xl md:text-2xl flex justify-center gap-1 md:gap-2">
                         <?php for($i=0; $i<$t['rating']; $i++) echo '<i class="fas fa-star"></i>'; ?>
                    </div>
                    
                    <h2 class="text-xl md:text-5xl lg:text-6xl font-heading font-bold leading-tight mb-6 md:mb-10 min-h-[120px] md:min-h-auto flex items-center justify-center p-4">
                        "<?php echo $t['message']; ?>"
                    </h2>
                    
                    <div class="flex flex-col items-center">
                        <h4 class="text-lg md:text-xl font-bold font-sans"><?php echo $t['name']; ?></h4>
                        <?php if(!empty($t['location'])): ?>
                            <span class="text-gray-500 text-xs md:text-sm font-bold uppercase tracking-widest mt-1"><?php echo $t['location']; ?></span>
                        <?php endif; ?>
                    </div>

                </div>
                <?php endforeach; ?>

            </div>

            <!-- Mobile-Friendly Controls -->
            <div class="flex flex-col-reverse md:flex-row justify-center items-center gap-6 md:gap-8 mt-12 md:mt-20 relative z-20">
                <!-- Mobile Arrows + Dots Container -->
                <div class="flex items-center gap-6 w-full justify-center md:w-auto">
                    <button onclick="prevReview()" class="w-12 h-12 md:w-14 md:h-14 rounded-full border border-white/20 flex items-center justify-center hover:bg-white hover:text-black transition duration-300 group active:scale-95" aria-label="Previous Review">
                        <i class="fas fa-arrow-left text-lg md:text-xl group-hover:-translate-x-1 transition-transform"></i>
                    </button>
                    
                    <!-- Indicators (Visible on Desktop/Tablet, Smaller on Mobile) -->
                    <div class="flex gap-2 md:gap-3">
                        <?php foreach($testimonials as $k => $t): ?>
                        <button onclick="goToReview(<?php echo $k; ?>)" class="w-8 h-1 md:w-12 md:h-1 rounded-full bg-white/20 hover:bg-[#24B25D] transition-all duration-300 review-dot <?php echo $k===0 ? 'bg-[#24B25D]' : ''; ?>" data-index="<?php echo $k; ?>" aria-label="Go to Review <?php echo $k+1; ?>"></button>
                        <?php endforeach; ?>
                    </div>

                    <button onclick="nextReview()" class="w-12 h-12 md:w-14 md:h-14 rounded-full border border-white/20 flex items-center justify-center hover:bg-white hover:text-black transition duration-300 group active:scale-95" aria-label="Next Review">
                        <i class="fas fa-arrow-right text-lg md:text-xl group-hover:translate-x-1 transition-transform"></i>
                    </button>
                </div>
            </div>

        </div>
    </section>

    <!-- Review Slider Logic -->
    <script>
        let currentReview = 0;
        const totalReviews = <?php echo count($testimonials); ?>;
        
        function showReview(index) {
            const slides = document.querySelectorAll('.review-slide');
            const dots = document.querySelectorAll('.review-dot');
            
            slides.forEach(slide => {
                slide.classList.remove('opacity-100', 'translate-x-0', 'relative');
                slide.classList.add('opacity-0', 'absolute');
                // Slide direction could be handled better, but simpler is safer for now
                slide.style.visibility = 'hidden'; 
            });

            const active = slides[index];
            active.style.visibility = 'visible';
            active.classList.remove('opacity-0', 'absolute', 'translate-x-[100px]');
            active.classList.add('opacity-100', 'translate-x-0', 'relative');

            dots.forEach(d => d.classList.remove('bg-[#24B25D]'));
            dots[index].classList.add('bg-[#24B25D]');
            
            currentReview = index;
        }

        function nextReview() {
            let next = (currentReview + 1) % totalReviews;
            showReview(next);
        }

        function prevReview() {
            let prev = (currentReview - 1 + totalReviews) % totalReviews;
            showReview(prev);
        }

        function goToReview(index) {
            showReview(index);
        }
        
        // Auto Play
        setInterval(nextReview, 6000);
    </script>



    <?php include 'includes/footer.php'; ?>
   
    
    <!-- Header Scripts -->
    <!-- CORE INTERACTIVITY SCRIPTS -->
    <script>
        function scrollProducts(direction) {
            const container = document.getElementById('product-slider-container');
            if(!container) return;
            const card = container.querySelector('.flex-none');
            if(!card) return;
            
            if (direction === 'right') {
                // If we are at the end, loop back to start
                if (Math.ceil(container.scrollLeft + container.clientWidth) >= container.scrollWidth) {
                    container.scrollTo({ left: 0, behavior: 'smooth' });
                    return;
                }
            }
            
            const scrollAmount = direction === 'left' ? -card.offsetWidth * 1.1 : card.offsetWidth * 1.1;
            container.scrollBy({ left: scrollAmount, behavior: 'smooth' });
        }

        // Auto-Slide Hot Drops
        let productInterval;
        function startProductAutoSlide() {
            productInterval = setInterval(() => {
                scrollProducts('right');
            }, 3000); // Every 3 seconds
        }
        
        document.addEventListener('DOMContentLoaded', () => {
            const productContainer = document.getElementById('product-slider-container');
            if (productContainer) {
                startProductAutoSlide();
                // Pause on hover
                productContainer.addEventListener('mouseenter', () => clearInterval(productInterval));
                productContainer.addEventListener('mouseleave', startProductAutoSlide);
            }
        });

        function scrollCategories(direction) {
            const container = document.getElementById('category-slider-container');
            if(!container) return;
            const card = container.querySelector('.flex-none');
            if(!card) return;
            const scrollAmount = direction === 'left' ? -card.offsetWidth * 1.5 : card.offsetWidth * 1.5;
            container.scrollBy({ left: scrollAmount, behavior: 'smooth' });
        }
    </script>
</body>
</html>


