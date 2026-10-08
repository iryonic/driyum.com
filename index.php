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

// Fetch Partners for "Available At" section
$partners = fetch_all("SELECT * FROM partners WHERE is_active = 1 ORDER BY sort_order ASC", []);

// Initialize Hero Variants early for global head access
$slides_data = get_hero_slides();
if(!empty($slides_data)) {
    $hero_variants = [];
    foreach($slides_data as $s) {
        $pid = $s['product_id'] ?? 0;
        $pslug = $s['product_slug'] ?? '';

        $hero_variants[] = [
            'id' => $s['id'],
            'product_id' => $pid,
            'slug' => $pslug,
            'name' => $s['title'] ?? '',
            'price' => isset($s['price']) && $s['price'] > 0 ? $s['price'] : '249',
            'image' => $s['image'] ?? '',
            'image_tablet' => $s['image_tablet'] ?? null,
            'image_mobile' => $s['image_mobile'] ?? null,
            'bg' => $s['accent_color'] ?? '#24B25D',
            'v1' => $s['v_text_1'] ?? 'SNACKING',
            'v2' => $s['v_text_2'] ?? 'REIMAGINED',
            'tagline' => $s['badge_text'] ?? 'Your New Healthy Habit',
            'cta_link' => $s['cta_link'] ?? '',
            'cta_text' => $s['cta_text'] ?? 'ORDER NOW',
            'alt_text' => $s['alt_text'] ?? '',
            'show_title' => isset($s['show_title']) ? (int)$s['show_title'] : 1,
            'show_subtitle' => isset($s['show_subtitle']) ? (int)$s['show_subtitle'] : 1,
            'show_cta' => isset($s['show_cta']) ? (int)$s['show_cta'] : 1
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
            'slug' => $f['slug'],
            'name' => $f['name'],
            'price' => $f['price'],
            'image' => $f['image'],
            'image_tablet' => null,
            'image_mobile' => null,
            'bg' => '#24B25D',
            'v1' => 'ORGANIC',
            'v2' => 'HARVEST',
            'tagline' => 'Your New Healthy Habit',
            'cta_link' => 'product/' . $f['slug'],
            'cta_text' => 'ORDER NOW',
            'alt_text' => $f['name'],
            'show_title' => 1,
            'show_subtitle' => 1,
            'show_cta' => 1
        ];
    }
}

// Global High-End Fallback if still empty
if(empty($hero_variants)) {
    $hero_variants = [
        ['id'=>1, 'product_id'=>1, 'slug'=>'signature-almonds', 'name'=>'Signature Almonds', 'price'=>249, 'image'=>'assets/images/nuts/almonds.png', 'image_tablet'=>null, 'image_mobile'=>null, 'bg'=>'#24B25D', 'v1'=>'PURE', 'v2'=>'ENERGY', 'tagline'=>'Your New Healthy Habit', 'cta_link'=>'shop', 'cta_text'=>'ORDER NOW', 'alt_text'=>'Signature Almonds', 'show_title'=>1, 'show_subtitle'=>1, 'show_cta'=>1],
        ['id'=>2, 'product_id'=>2, 'slug'=>'crispy-apple-chips', 'name'=>'Crispy Apple Chips', 'price'=>199, 'image'=>'assets/images/chips/apple.png', 'image_tablet'=>null, 'image_mobile'=>null, 'bg'=>'#24B25D', 'v1'=>'NATURE\'S', 'v2'=>'SWEET', 'tagline'=>'Your New Healthy Habit', 'cta_link'=>'shop', 'cta_text'=>'ORDER NOW', 'alt_text'=>'Crispy Apple Chips', 'show_title'=>1, 'show_subtitle'=>1, 'show_cta'=>1],
        ['id'=>3, 'product_id'=>3, 'slug'=>'spiced-walnuts', 'name'=>'Spiced Walnuts', 'price'=>299, 'image'=>'assets/images/nuts/walnut.png', 'image_tablet'=>null, 'image_mobile'=>null, 'bg'=>'#24B25D', 'v1'=>'BOLD', 'v2'=>'CRUNCH', 'tagline'=>'Your New Healthy Habit', 'cta_link'=>'shop', 'cta_text'=>'ORDER NOW', 'alt_text'=>'Spiced Walnuts', 'show_title'=>1, 'show_subtitle'=>1, 'show_cta'=>1],
        ['id'=>4, 'product_id'=>4, 'slug'=>'sweet-berries', 'name'=>'Sweet Berries', 'price'=>349, 'image'=>'assets/images/berries.png', 'image_tablet'=>null, 'image_mobile'=>null, 'bg'=>'#24B25D', 'v1'=>'WILD', 'v2'=>'PICKED', 'tagline'=>'Your New Healthy Habit', 'cta_link'=>'shop', 'cta_text'=>'ORDER NOW', 'alt_text'=>'Sweet Berries', 'show_title'=>1, 'show_subtitle'=>1, 'show_cta'=>1]
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

        /* Partners Marquee Animation for Mobile/Tablet */
        @media (max-width: 1023px) {
            .partners-marquee-container {
                mask-image: linear-gradient(to right, transparent, black 15%, black 85%, transparent);
                -webkit-mask-image: linear-gradient(to right, transparent, black 15%, black 85%, transparent);
                cursor: grab;
            }
            .partners-marquee-container:active { cursor: grabbing; }
            .partners-marquee-content {
                animation: partners-marquee 40s linear infinite;
            }
            .partners-marquee-container:hover .partners-marquee-content {
                animation-play-state: paused;
            }
        }

        @keyframes partners-marquee {
            0% { transform: translateX(0); }
            100% { transform: translateX(-50%); }
        }

     
    </style>
</head>
<body class="bg-[#FFFEDC]">

    <?php include 'includes/header.php'; ?>

    <?php
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
            <div class="hero-sale-timer-row flex items-center gap-2.5 sm:gap-4 lg:gap-4 bg-[#002A23] px-4 sm:px-5 py-1 sm:py-1.5 rounded-full shadow-lg" data-end="<?php echo $active_sale['end_date']; ?>">
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
    <?php endif; ?>    <!-- MODERN BRANDED HERO SLIDER -->
    <style>
        .hero-banner-container {
            width: 100%;
            aspect-ratio: 1350 / 620;
            max-height: 80vh;
        }
        @media (max-width: 991px) {
            .hero-banner-container {
                aspect-ratio: 1024 / 640;
                max-height: 75vh;
            }
        }
        @media (max-width: 576px) {
            .hero-banner-container {
                aspect-ratio: 1 / 1;
                max-height: 85vh;
            }
        }
        .banner-slide {
            transition: opacity 0.8s cubic-bezier(0.4, 0, 0.2, 1), transform 0.8s cubic-bezier(0.4, 0, 0.2, 1);
            will-change: opacity, transform;
        }
        .banner-slide.active {
            opacity: 1 !important;
            z-index: 10;
            transform: scale(1) translateZ(0) !important;
            pointer-events: auto;
        }
        .banner-slide.inactive {
            opacity: 0 !important;
            z-index: 0;
            transform: scale(1.02) translateZ(0) !important;
            pointer-events: none;
            position: absolute !important;
            top: 0; left: 0; width: 100%; height: 100%;
        }
        .slider-dot {
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
    </style>

    <section id="hero-slider-section" class="relative hero-banner-container mx-auto overflow-hidden bg-[#0A1A17] select-none group">
        <div class="relative w-full h-full">
            <?php foreach($hero_variants as $index => $slide): 
                $slide_target = trim($slide['cta_link'] ?? '');
                if(empty($slide_target) && !empty($slide['slug'])) {
                    $slide_target = 'product/' . $slide['slug'];
                }
                if(!empty($slide_target)) {
                    if(strpos($slide_target, 'http://') === 0 || strpos($slide_target, 'https://') === 0) {
                        $slide_href = $slide_target;
                    } else {
                        $slide_href = get_url(ltrim($slide_target, '/'));
                    }
                } else {
                    $slide_href = '';
                }
                
                $desktop_img = $slide['image'] ?? '';
                $tablet_img = $slide['image_tablet'] ?? '';
                $mobile_img = $slide['image_mobile'] ?? '';
                $fallback_img = $desktop_img ?: ($tablet_img ?: $mobile_img);
                $alt_text = htmlspecialchars($slide['alt_text'] ?: ($slide['name'] ?: 'Driyum Special Offer Banner'));
            ?>
            <div class="banner-slide <?php echo $index === 0 ? 'active' : 'inactive'; ?> absolute inset-0 w-full h-full" data-index="<?php echo $index; ?>">
                <?php if(!empty($slide_href)): ?>
                <!-- Whole Banner Click Target -->
                <a href="<?php echo htmlspecialchars($slide_href); ?>" class="absolute inset-0 z-10 w-full h-full" aria-label="<?php echo $alt_text; ?>"></a>
                <?php endif; ?>

                <!-- Clean Responsive Picture Banner (No Text Overlay) -->
                <div class="absolute inset-0 w-full h-full overflow-hidden select-none">
                    <picture>
                        <?php if(!empty($mobile_img)): ?>
                            <source media="(max-width: 576px)" srcset="<?php echo get_url($mobile_img); ?>">
                        <?php endif; ?>
                        <?php if(!empty($tablet_img)): ?>
                            <source media="(max-width: 991px)" srcset="<?php echo get_url($tablet_img); ?>">
                        <?php endif; ?>
                        <img src="<?php echo get_url($fallback_img); ?>" 
                             class="w-full h-full object-cover object-center" 
                             alt="<?php echo $alt_text; ?>"
                             loading="<?php echo $index === 0 ? 'eager' : 'lazy'; ?>"
                             <?php if($index === 0): ?>fetchpriority="high"<?php endif; ?>>
                    </picture>
                </div>
            </div>
            <?php endforeach; ?>

            <?php if(count($hero_variants) > 1): ?>
            <!-- Side Arrows (Visible on Hover / Desktop) -->
            <div class="absolute inset-y-0 left-3 md:left-6 z-30 flex items-center opacity-0 group-hover:opacity-100 transition-opacity">
                <button type="button" onclick="event.stopPropagation(); prevSlide();" aria-label="Previous Slide" class="w-10 h-10 md:w-12 md:h-12 rounded-full border border-white/20 bg-black/40 backdrop-blur-md text-white hover:bg-[#24B25D] hover:border-[#24B25D] transition-all flex items-center justify-center shadow-lg cursor-pointer">
                    <i class="fas fa-chevron-left text-xs md:text-sm"></i>
                </button>
            </div>
            <div class="absolute inset-y-0 right-3 md:right-6 z-30 flex items-center opacity-0 group-hover:opacity-100 transition-opacity">
                <button type="button" onclick="event.stopPropagation(); nextSlide();" aria-label="Next Slide" class="w-10 h-10 md:w-12 md:h-12 rounded-full border border-white/20 bg-black/40 backdrop-blur-md text-white hover:bg-[#24B25D] hover:border-[#24B25D] transition-all flex items-center justify-center shadow-lg cursor-pointer">
                    <i class="fas fa-chevron-right text-xs md:text-sm"></i>
                </button>
            </div>

            <!-- Centered Bottom Dots -->
            <div class="absolute bottom-3 md:bottom-5 left-1/2 -translate-x-1/2 flex items-center gap-2 z-[30]" style="z-index: 30;">
                <?php foreach($hero_variants as $i => $v): ?>
                    <button type="button" onclick="event.stopPropagation(); goToSlide(<?php echo $i; ?>);" aria-label="Slide <?php echo $i + 1; ?>" class="h-1.5 rounded-full transition-all slider-dot cursor-pointer <?php echo $i === 0 ? 'bg-[#24B25D] w-8 md:w-12' : 'bg-white/40 hover:bg-white/70 w-3 md:w-4'; ?>"></button>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <?php 
            $hero_wave = get_hero_wave_data(); 
            ?>
            <!-- Organic Wavy Divider with Creamy Accent Border (Dynamic Admin Manageable) -->
            <div class="absolute bottom-0 left-0 w-full pointer-events-none select-none leading-none z-[25] translate-y-[14px]" style="z-index: 25;">
                <svg class="w-full h-[38px] sm:h-[56px] md:h-[76px] lg:h-[94px] block" viewBox="0 0 1440 320" preserveAspectRatio="none" xmlns="http://www.w3.org/2000/svg">
                    <defs>
                        <!-- Depth Drop Shadow -->
                        <filter id="heroWaveShadow" x="-5%" y="-35%" width="110%" height="170%" filterUnits="userSpaceOnUse">
                            <feDropShadow dx="0" dy="-4" stdDeviation="5" flood-color="#000000" flood-opacity="0.14" />
                        </filter>
                    </defs>
                    <?php if ($hero_wave['intensity'] > 0): ?>
                    <!-- Creamy Flowing Back Accent Border (Depth & Fun Vibe) -->
                    <path d="<?php echo htmlspecialchars($hero_wave['cream_path']); ?>" 
                          fill="#FFFEDC" 
                          fill-opacity="0.95" 
                          filter="url(#heroWaveShadow)"></path>
                    <?php endif; ?>
                    <!-- Main White Organic Wave -->
                    <path d="<?php echo htmlspecialchars($hero_wave['white_path']); ?>" 
                          fill="#FFFFFF"></path>
                </svg>
            </div>
        </div>
    </section>

    <!-- NOW AVAILABLE AT SECTION (Original Panoramic Text Row) -->
    <section id="partners-section" class="bg-white border-b border-gray-100 overflow-hidden relative">
        <div class="container mx-auto px-10 lg:px-40 py-6 md:py-10">
            <div class="flex flex-col lg:flex-row items-center justify-between gap-8 lg:gap-16">
                <!-- Sidebar Title -->
                <div class="flex-shrink-0">
                    <span class="text-[12px] md:text-[14px] font-black tracking-[0.3em] text-gray-900 uppercase">NOW AVAILABLE AT</span>
                </div>
                
                <!-- Partners Horizon -->
                <div class="flex-grow w-full overflow-auto availbleatsection">
                    <div class="flex flex-nowrap items-center lg:justify-end gap-12 md:gap-16 lg:gap-24 min-w-max pb-2 md:pb-0">
                        <?php 
                        $partner_list = [
                            ['name' => 'Ecogrocery', 'location' => 'Lal Nagar'],
                            ['name' => 'Basket', 'location' => 'Boulevard'],
                            ['name' => 'Extracts', 'location' => 'RAJBAGH'],
                            ['name' => 'City Max', 'location' => 'Peerbagh'],
                            ['name' => 'Pick N Choose', 'location' => 'BAGHAT']
                        ];
                        
                        foreach($partner_list as $p): 
                        ?>
                            <div class="flex flex-col items-center group cursor-default">
                                 <h4 class="text-xl md:text-3xl font-serif font-black text-gray-900 leading-none transition-colors group-hover:text-[#24B25D]">
                                    <?php echo $p['name']; ?>
                                 </h4>
                                 <span class="text-[9px] md:text-[10px] font-bold text-gray-300 uppercase tracking-widest mt-1.5 border-t border-gray-50 pt-1 w-full text-center group-hover:text-gray-500 transition-colors">
                                    <?php echo $p['location']; ?>
                                 </span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- HERO SLIDER LOGIC -->
    <script>
        let currentSlide = 0;
        const totalSlides = <?php echo count($hero_variants); ?>;
        let slideTimer = null;
        const slideDelay = 5000;

        function showSlide(index) {
            const slides = document.querySelectorAll('.banner-slide');
            const dots = document.querySelectorAll('.slider-dot');

            if(!slides.length) return;

            // Safe wrap index
            index = (index + slides.length) % slides.length;

            slides.forEach((slide, i) => {
                if(i === index) {
                    slide.classList.remove('inactive');
                    slide.classList.add('active');
                } else {
                    slide.classList.remove('active');
                    slide.classList.add('inactive');
                }
            });

            dots.forEach((d, i) => {
                if(i === index) {
                    d.classList.remove('bg-white/40', 'w-3', 'md:w-4');
                    d.classList.add('bg-[#24B25D]', 'w-8', 'md:w-12');
                } else {
                    d.classList.remove('bg-[#24B25D]', 'w-8', 'md:w-12');
                    d.classList.add('bg-white/40', 'w-3', 'md:w-4');
                }
            });

            currentSlide = index;
        }

        function nextSlide() {
            showSlide(currentSlide + 1);
            resetTimer();
        }

        function prevSlide() {
            showSlide(currentSlide - 1);
            resetTimer();
        }

        function goToSlide(index) {
            showSlide(index);
            resetTimer();
        }

        function startTimer() {
            if(totalSlides > 1) {
                stopTimer();
                slideTimer = setInterval(nextSlide, slideDelay);
            }
        }

        function stopTimer() {
            if(slideTimer) {
                clearInterval(slideTimer);
                slideTimer = null;
            }
        }

        function resetTimer() {
            stopTimer();
            startTimer();
        }

        document.addEventListener('DOMContentLoaded', () => {
            const sliderSection = document.getElementById('hero-slider-section');
            if(sliderSection && totalSlides > 1) {
                // Pause autoplay when hovering over slider
                sliderSection.addEventListener('mouseenter', stopTimer);
                sliderSection.addEventListener('mouseleave', startTimer);

                // Touch Swipe for mobile/tablet
                let touchstartX = 0;
                let touchstartY = 0;
                let touchendX = 0;
                let touchendY = 0;

                sliderSection.addEventListener('touchstart', e => {
                    touchstartX = e.changedTouches[0].screenX;
                    touchstartY = e.changedTouches[0].screenY;
                }, {passive: true});

                sliderSection.addEventListener('touchend', e => {
                    touchendX = e.changedTouches[0].screenX;
                    touchendY = e.changedTouches[0].screenY;
                    const diffX = touchendX - touchstartX;
                    const diffY = touchendY - touchstartY;
                    if (Math.abs(diffX) > 40 && Math.abs(diffX) > Math.abs(diffY)) {
                        if (diffX < 0) nextSlide();
                        else prevSlide();
                    }
                }, {passive: true});
            }

            if(totalSlides > 0) {
                showSlide(0);
                startTimer();
            }

            // Expose globally
            window.nextSlide = nextSlide;
            window.prevSlide = prevSlide;
            window.goToSlide = goToSlide;
        });
    </script>









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




    <!-- COMBO BUNDLES SECTION -->
    <section class="py-4 md:py-8 bg-white relative overflow-hidden anim-up">
        <div class="container mx-auto px-6  mb-12 md:mb-16 flex flex-col md:flex-row justify-between items-start md:items-end gap-6 md:gap-8">
            <div class="anim-reveal">
                <span class="text-[#24B25D] font-black tracking-[0.2em] uppercase text-[10px] mb-2 md:mb-3 block">Curated Value Droplets</span>
                <h2 class="text-4xl md:text-6xl font-heading font-black text-gray-900 leading-none tracking-tighter">Our <span class="text-[#24B25D]">Combos.</span></h2>
            </div>
            <div class="flex items-center gap-4 w-full md:w-auto justify-end">
                <div class="flex gap-2.5 md:gap-3">
                    <button onclick="document.getElementById('combo-slider-container').scrollBy({left: -350, behavior: 'smooth'})" class="w-12 h-12 md:w-14 md:h-14 rounded-2xl bg-white border-2 border-gray-100 flex items-center justify-center text-gray-400 hover:border-black hover:text-black transition-all shadow-sm active:scale-90">
                        <i class="fas fa-arrow-left"></i>
                    </button>
                    <button onclick="document.getElementById('combo-slider-container').scrollBy({left: 350, behavior: 'smooth'})" class="w-12 h-12 md:w-14 md:h-14 rounded-2xl bg-black text-[#24B25D] flex items-center justify-center hover:scale-105 transition-all shadow-xl active:scale-90">
                        <i class="fas fa-arrow-right"></i>
                    </button>
                </div>
            </div>
        </div>

        <!-- Combo Slider Wrapper -->
        <div class="w-full relative overflow-hidden">
            <div id="combo-slider-container" class="flex w-full overflow-x-auto gap-4 px-8  pb-12 hide-scrollbar scroll-smooth">
                <?php 
                // 1. First, fetch real Combo/Bundle/Pack products (Now using the explicit is_combo flag)
                $combos = fetch_all("SELECT * FROM products WHERE is_combo = 1 AND is_active = 1 ORDER BY CASE WHEN stock > 0 THEN 0 ELSE 1 END ASC, combo_sort_order ASC, id DESC");
                
                // Gap Filling: If less than 4 combos exist, fill with featured gems to ensure a crisp UI
                $combo_count = count($combos);
                if($combo_count < 4) {
                    $needed = 4 - $combo_count;
                    $ids = !empty($combos) ? implode(',', array_column($combos, 'id')) : '0';
                    $fillers = fetch_all("SELECT * FROM products WHERE is_active = 1 AND is_featured = 1 AND id NOT IN ($ids) ORDER BY CASE WHEN stock > 0 THEN 0 ELSE 1 END ASC, combo_sort_order ASC, id DESC LIMIT $needed");
                    $combos = array_merge($combos, $fillers);
                }

                // Ensure in-stock items always appear before out-of-stock items, respecting custom combo sort order
                usort($combos, function($a, $b) {
                    $a_in = ($a['stock'] > 0) ? 0 : 1;
                    $b_in = ($b['stock'] > 0) ? 0 : 1;
                    if ($a_in !== $b_in) return $a_in - $b_in;
                    $a_sort = isset($a['combo_sort_order']) ? (int)$a['combo_sort_order'] : (int)$a['id'];
                    $b_sort = isset($b['combo_sort_order']) ? (int)$b['combo_sort_order'] : (int)$b['id'];
                    if ($a_sort !== $b_sort) return $a_sort - $b_sort;
                    return $b['id'] - $a['id'];
                });

                $i = 0;
                $delay = 0;
                $default_colors = ['#E0F2FE', '#DCFCE7', '#FEF3C7', '#FEE2E2', '#F3E8FF', '#FFEDD5'];

                foreach($combos as $p): 
                    $color_raw = !empty($p['bg_color']) ? $p['bg_color'] : $default_colors[$i % count($default_colors)];
                    $i++;
                    $delay += 100;
                ?>
                <!-- Standardized Boutique Card -->
                <div class="flex-none flex-shrink-0 w-[82vw] sm:w-[48%] md:w-[32.5%] lg:w-[31%] xl:w-[24%] snap-start px-2 md:px-3 pb-8 h-full">
                    <a href="<?php echo product_url($p['slug']); ?>" class="block h-full group">
                        <!-- Outer Tinted Container (Alternating Brand Colors) -->
                        <div class="rounded-[2.5rem] p-2.5 md:p-3 transition-transform duration-500 group-hover:scale-[1.02] h-full flex flex-col anim-up shadow-sm border border-[#004F42]/5" style="background-color: <?php echo $color_raw; ?>; animation-delay: <?php echo $delay; ?>ms">
                            
                            <!-- Inner White Card -->
                            <div class="bg-white rounded-[2rem] p-4 px-6 flex flex-col flex-1 h-full shadow-sm">
                                
                                <!-- Product Image Area -->
                                <div class="relative w-full aspect-[4/3] rounded-2xl overflow-hidden mb-4 bg-gray-50/50">
                                    <img src="<?php echo get_url($p['image']); ?>" 
                                         loading="lazy"
                                         alt="<?php echo htmlspecialchars($p['name']); ?>"
                                         class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-700 <?php echo $p['stock'] <= 0 ? 'grayscale opacity-75' : ''; ?>">
                                    
                                    <!-- Premium Bundle Branding -->
                                    <div class="absolute top-4 left-4 flex flex-col gap-2 z-10">
                                        
                                        <?php if(isset($p['original_price']) && $p['original_price'] > $p['price']): 
                                            $savings = round((($p['original_price'] - $p['price']) / $p['original_price']) * 100);
                                        ?>
                                            <div class="bg-[#EDB02C] text-white text-[10px] font-black px-3 py-1.5 rounded-full shadow-2xl border border-white/20 anim-pulse-subtle">
                                                SAVE <?php echo $savings; ?>%
                                            </div>
                                        <?php endif; ?>
                                    </div>

                                    <!-- Stock Status Badge -->
                                    <?php if($p['stock'] <= 0): ?>
                                        <div class="absolute inset-0 bg-white/40 backdrop-blur-[2px] flex items-center justify-center z-10">
                                            <span class="bg-white text-black text-[10px] font-black px-4 py-2 rounded-full shadow-xl uppercase tracking-widest border border-gray-100">Out of Stock</span>
                                        </div>
                                    <?php endif; ?>

                                    <!-- Quick Discovery Arrow -->
                                    <div class="absolute top-4 right-4 w-10 h-10 rounded-2xl bg-white/95 backdrop-blur-sm flex items-center justify-center text-gray-900 shadow-xl group-hover:bg-[#24B25D] group-hover:text-white transition-all duration-500 scale-90 group-hover:scale-100 z-20 overflow-hidden">
                                         <i class="fas fa-arrow-right -rotate-45 group-hover:rotate-0 transition-transform duration-500 text-xs"></i>
                                    </div>
                                </div>

                                <!-- High-Quality Stars & Branding -->
                                <div class="flex gap-1 mb-2">
                                    <i class="fas fa-star text-[#EDB12B] text-[10px]"></i>
                                    <i class="fas fa-star text-[#EDB12B] text-[10px]"></i>
                                    <i class="fas fa-star text-[#EDB12B] text-[10px]"></i>
                                    <i class="fas fa-star text-[#EDB12B] text-[10px]"></i>
                                    <i class="fas fa-star text-[#EDB12B] text-[10px]"></i>
                                </div>

                                <!-- Title & Specs -->
                                <div class="mb-3 lg:mb-5">
                                    <h3 class="text-base sm:text-lg lg:text-xl font-black text-[#004F42] leading-tight mb-1 truncate-1 group-hover:text-[#24B25D] transition-colors"><?php echo $p['name']; ?></h3>
                                </div>

                                <!-- Interactive Price Strip -->
                                <div class="flex items-center justify-between mb-5 mt-auto">
                                    <div class="flex flex-col">
                                        <?php if(isset($p['original_price']) && $p['original_price'] > $p['price']): ?>
                                            <span class="text-[10px] text-gray-400 font-bold line-through">MRP <?php echo format_price($p['original_price']); ?></span>
                                        <?php endif; ?>
                                        <span class="text-2xl lg:text-3xl font-black text-black leading-none tracking-tighter">
                                            <?php echo format_price($p['price']); ?>
                                        </span>
                                    </div>
                                    <div class="flex gap-1.5 md:gap-2">
                                        <button onclick="event.preventDefault(); event.stopPropagation(); toggleWishlist(<?php echo $p['id']; ?>, this)" class="w-9 h-9 md:w-11 md:h-11 bg-gray-50 text-gray-300 rounded-xl flex items-center justify-center hover:bg-white hover:text-red-500 transition-all border border-gray-100">
                                            <i class="far fa-heart text-[13px] md:text-[15px]"></i>
                                        </button>
                                        <?php if($p['stock'] <= 0): ?>
                                            <button disabled class="w-9 h-9 md:w-11 md:h-11 bg-gray-100 text-gray-300 rounded-xl flex items-center justify-center cursor-not-allowed">
                                                <i class="fas fa-ban text-[13px] md:text-[15px]"></i>
                                            </button>
                                        <?php else: ?>
                                            <button onclick="event.preventDefault(); event.stopPropagation(); addToCart(<?php echo $p['id']; ?>, this)" class="w-9 h-9 md:w-11 md:h-11 bg-black text-[#24B25D] rounded-xl flex items-center justify-center hover:bg-[#24B25D] hover:text-white transition-all shadow-md active:scale-90">
                                                <i class="fas fa-shopping-bag text-[13px] md:text-[15px]"></i>
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <!-- Full Action Button -->
                                <?php if($p['stock'] <= 0): ?>
                                    <button disabled 
                                        class="w-full bg-gray-100 text-gray-400 py-3 lg:py-4 rounded-xl font-black text-[14px] lg:text-[15px] xl:text-[17px] uppercase tracking-widest cursor-not-allowed flex items-center justify-center">
                                        <span>Out of Stock</span>
                                    </button>
                                <?php else: ?>
                                    <button onclick="event.preventDefault(); event.stopPropagation(); quickBuy(<?php echo $p['id']; ?>, this)" 
                                        class="w-full bg-[#24B25D] hover:bg-[#004F42] text-white py-3 lg:py-4 rounded-xl font-black text-[14px] lg:text-[15px] xl:text-[17px] uppercase tracking-widest transition-all shadow-md active:scale-95 group/buy flex items-center justify-center gap-2">
                                        <span>Quick Buy</span> <i class="fas fa-bolt group-hover/buy:animate-pulse"></i>
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </a>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- THE DRIYUM DIFFERENCE (Full Width Direct Comparison) -->
    <section class="h-auto md:h-[50vh] lg:h-screen bg-white w-full relative overflow-hidden flex flex-col justify-center p-0 m-0">
        <!-- Full Width Comparison Grid (Breakout for perfect edge-to-edge) -->
        <div class="flex flex-col md:flex-row gap-0 w-screen relative left-1/2 -translate-x-1/2 h-full md:h-full shadow-2xl border-y border-gray-100/50 bg-black overflow-hidden" id="comparison-trigger">
            
            <!-- Pillar: The "Oily" Junk Choice -->
            <div class="w-full md:w-1/2 relative group overflow-hidden bg-gray-100 flex flex-col py-16 md:py-8 will-change-transform" id="junk-pillar">
                <!-- Junk Snacking Background -->
                <div class="absolute inset-0 z-0">
                    <img src="<?php echo get_url('assets/images/unhealthy_junk.png'); ?>" alt="Unhealthy Junk Snacking" class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-[10000ms] grayscale-40">
                    <div class="absolute inset-0 bg-gradient-to-t from-black/95 via-black/40 to-transparent"></div>
                </div>
                <!-- Simple Language Content -->
                <div class="relative z-10 p-10 md:p-24 flex flex-col h-full text-white">
                        <div class="flex items-center gap-4 mb-10">
                            <div class="w-12 h-12 rounded-2xl bg-red-500/20 backdrop-blur-md flex items-center justify-center text-red-400 border border-red-500/20">
                                <i class="fas fa-burger text-xl"></i>
                            </div>
                            <h3 class="text-2xl font-black uppercase tracking-widest text-white/80">Oily Junk</h3>
                        </div>

                        <ul class="space-y-8 mb-12">
                            <li class="flex items-start gap-4">
                                <div class="mt-1 w-6 h-6 rounded-full bg-red-500 flex items-center justify-center shrink-0 shadow-lg shadow-red-500/30">
                                    <i class="fas fa-times text-white text-[10px] font-black"></i>
                                </div>
                                <div>
                                    <p class="font-black text-white text-lg leading-none mb-1">Fried in Oil</p>
                                    <p class="text-white/60 text-sm font-bold leading-relaxed">Most snacks are deep-fried and greasy.</p>
                                </div>
                            </li>
                            <li class="flex items-start gap-4">
                                <div class="mt-1 w-6 h-6 rounded-full bg-red-500 flex items-center justify-center shrink-0 shadow-lg shadow-red-500/30">
                                    <i class="fas fa-times text-white text-[10px] font-black"></i>
                                </div>
                                <div>
                                    <p class="font-black text-white text-lg leading-none mb-1">Added Chemicals</p>
                                    <p class="text-white/60 text-sm font-bold leading-relaxed">Made with powders and fake colors.</p>
                                </div>
                            </li>
                            <li class="flex items-start gap-4">
                                <div class="mt-1 w-6 h-6 rounded-full bg-red-500 flex items-center justify-center shrink-0 shadow-lg shadow-red-500/30">
                                    <i class="fas fa-times text-white text-[10px] font-black"></i>
                                </div>
                                <div>
                                    <p class="font-black text-white text-lg leading-none mb-1">Too Much Salt/Sugar</p>
                                    <p class="text-white/60 text-sm font-bold leading-relaxed">Added sugar and syrups make you heavy.</p>
                                </div>
                            </li>
                        </ul>
                    </div>
                </div>

                <!-- Pillar: The Driyum Choice -->
                <div class="w-full md:w-1/2 relative group overflow-hidden bg-[#002A23] flex flex-col py-16 md:py-8 will-change-transform" id="driyum-pillar">
                    <!-- Healthy Driyum Background (User Provided Image) -->
                    <div class="absolute inset-0 z-0">
                        <img src="<?php echo get_url('assets/images/driyumchoice.jpeg'); ?>" alt="Healthy Driyum Choice" class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-[10000ms]">
                        <div class="absolute inset-0 bg-gradient-to-t from-[#002A23]/95 via-[#002A23]/60 to-transparent"></div>
                    </div>

                    <!-- Simple Language Content -->
                    <div class="relative z-10 p-10 md:p-24 flex flex-col h-full text-white">
                        <div class="flex items-center gap-4 mb-10">
                            <div class="w-12 h-12 rounded-2xl bg-[#24B25D] flex items-center justify-center text-[#002A23] shadow-lg shadow-[#24B25D]/30 border-2 border-white/10">
                                <i class="fas fa-leaf text-xl text-[#002A23] font-black"></i>
                            </div>
                            <h3 class=" font-black uppercase tracking-widest text-[#24B25D]"><img src="./assets/images/logo.png" alt="" class="w-32 "></h3>
                        </div>

                        <ul class="space-y-8 mb-12">
                            <li class="flex items-start gap-4 transform transition-transform hover:translate-x-1 duration-300">
                                <div class="mt-1 w-6 h-6 rounded-full bg-[#19DC7E] flex items-center justify-center shrink-0 shadow-lg shadow-[#19DC7E]/40">
                                    <i class="fas fa-check text-[#002A23] text-[10px] font-black"></i>
                                </div>
                                <div>
                                    <p class="font-black text-white text-lg leading-none mb-1">No Oil</p>
                                    <p class="text-[#19DC7E]/80 text-sm font-bold leading-relaxed">Not fried. No grease. Only dry fruits.</p>
                                </div>
                            </li>
                            <li class="flex items-start gap-4 transform transition-transform hover:translate-x-1 duration-300">
                                <div class="mt-1 w-6 h-6 rounded-full bg-[#19DC7E] flex items-center justify-center shrink-0 shadow-lg shadow-[#19DC7E]/40">
                                    <i class="fas fa-check text-[#002A23] text-[10px] font-black"></i>
                                </div>
                                <div>
                                    <p class="font-black text-white text-lg leading-none mb-1">Pure Raw Nature</p>
                                    <p class="text-[#19DC7E]/80 text-sm font-bold leading-relaxed">No chemicals. Pure fruits from the farm.</p>
                                </div>
                            </li>
                            <li class="flex items-start gap-4 transform transition-transform hover:translate-x-1 duration-300">
                                <div class="mt-1 w-6 h-6 rounded-full bg-[#19DC7E] flex items-center justify-center shrink-0 shadow-lg shadow-[#19DC7E]/40">
                                    <i class="fas fa-check text-[#002A23] text-[10px] font-black"></i>
                                </div>
                                <div>
                                    <p class="font-black text-white text-lg leading-none mb-1">Natural Sweetness</p>
                                    <p class="text-[#19DC7E]/80 text-sm font-bold leading-relaxed">Energy from nature that keeps you active.</p>
                                </div>
                            </li>
                        </ul>

                        <!-- Trust Statement -->
                        <div class="mt-auto pt-10 border-t border-white/10 flex items-center justify-between">
                            <span class="text-[9px] font-black uppercase tracking-[0.2em] text-[#24B25D]">The Better Choice</span>
                            <div class="flex items-center gap-3">
                                <div class="h-1.5 w-1.5 rounded-full bg-[#24B25D] animate-pulse"></div>
                                <span class="text-[9px] font-black text-white/40 uppercase">Healthy Everyday</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- GSAP Comparison Animation Engine -->
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            if (typeof gsap === 'undefined') {
                console.warn('GSAP not loaded. Animation fallback to static.');
                return;
            }
            
            gsap.registerPlugin(ScrollTrigger);
            
            const trigger = document.getElementById('comparison-trigger');
            if(!trigger) return;

            // Set Initial States - offset more aggressively to ensure clean entry
            gsap.set("#junk-pillar", { xPercent: -100, opacity: 0 });
            gsap.set("#driyum-pillar", { xPercent: 100, opacity: 0 });

            // Pillar Smooth Expansion Timeline
            const tl = gsap.timeline({
                scrollTrigger: {
                    trigger: trigger,
                    start: "top 100%", 
                    end: "top 10%",   // Finish even earlier for maximum impact
                    scrub: 0.4,       // Very responsive
                    toggleActions: "play none none reverse"
                }
            });

            tl.to("#junk-pillar", { xPercent: 0, opacity: 1, ease: "power2.out" }, 0)
              .to("#driyum-pillar", { xPercent: 0, opacity: 1, ease: "power2.out" }, 0);
        });
    </script>

    <!-- UNIFIED PRODUCT GRID (Exactly Like Combo Section) -->
    <section class="py-16 md:py-24 bg-[#f8f9fa] relative overflow-hidden border-t border-gray-100/50">
        <div class="container mx-auto px-6  mb-10 md:mb-16 flex flex-col md:flex-row justify-between items-start md:items-end gap-6 md:gap-8">
            <div class="anim-reveal">
                <span class="text-[#24B25D] font-black tracking-[0.2em] uppercase text-[10px] mb-2 md:mb-3 block">Fresh From The Farm</span>
                <h2 class="text-4xl md:text-6xl font-heading font-black text-gray-900 leading-none tracking-tighter">Our <span class="text-[#24B25D]">Products.</span></h2>
            </div>
            <div class="flex items-center gap-4 w-full md:w-auto justify-end">
                <div class="flex gap-2.5 md:gap-3">
                    <button onclick="document.getElementById('product-slider-container').scrollBy({left: -350, behavior: 'smooth'})" class="w-12 h-12 md:w-14 md:h-14 rounded-2xl bg-white border-2 border-gray-100 flex items-center justify-center text-gray-400 hover:border-black hover:text-black transition-all shadow-sm active:scale-90">
                        <i class="fas fa-arrow-left"></i>
                    </button>
                    <button onclick="document.getElementById('product-slider-container').scrollBy({left: 350, behavior: 'smooth'})" class="w-12 h-12 md:w-14 md:h-14 rounded-2xl bg-black flex items-center justify-center text-[#24B25D] hover:bg-[#24B25D] hover:text-black transition-all shadow-xl shadow-[#24B25D]/20 active:scale-90">
                        <i class="fas fa-arrow-right"></i>
                    </button>
                </div>
            </div>
        </div>
            
            <!-- Constrained Product Slider Wrapper -->
            <div class="w-full relative overflow-hidden">
                <div id="product-slider-container" class="flex flex-row flex-nowrap w-full gap-4 overflow-x-auto hide-scrollbar scroll-smooth px-2 pb-12 snap-x mandatory">
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
                <!-- "The Boutique Collective" Reference Card -->
                <div class="flex-none flex-shrink-0 w-[82vw] sm:w-[48%] md:w-[32%] lg:w-[24%] snap-start px-2 md:px-3 pb-8 h-full">
                    <!-- Outer Tinted Container (Alternating Brand Colors) -->
                    <a href="<?php echo product_url($p['slug']); ?>" class="block h-full group">
                        <div class="rounded-[2.5rem] p-2.5 md:p-3 transition-transform duration-500 group-hover:scale-[1.02] h-full flex flex-col anim-up shadow-sm border border-[#004F42]/5" style="background-color: <?php echo $color_raw; ?>; animation-delay: <?php echo $delay; ?>ms">
                            
                            <!-- Inner White Card -->
                            <div class="bg-white rounded-[2rem] p-4 px-6 flex flex-col flex-1 h-full shadow-sm">
                                
                                <!-- Product Image (Reference Corners) -->
                                <div class="relative w-full aspect-[4/3] rounded-2xl overflow-hidden mb-4 bg-gray-50/50">
                                    <img src="<?php echo get_url($p['image']); ?>" 
                                         loading="lazy"
                                         alt="<?php echo htmlspecialchars($p['name']); ?>"
                                         class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-700 <?php echo $p['stock'] <= 0 ? 'grayscale opacity-75' : ''; ?>">
                                    
                                    <!-- Premium Savings Badge -->
                                    <?php if(isset($p['original_price']) && $p['original_price'] > $p['price']): 
                                        $savings = round((($p['original_price'] - $p['price']) / $p['original_price']) * 100);
                                    ?>
                                        <div class="absolute top-4 left-4 bg-[#EDB02C] text-white text-[9px] font-black px-2.5 py-1 rounded-full shadow-lg border border-white/10 backdrop-blur-sm">
                                            -<?php echo $savings; ?>% OFF
                                        </div>
                                    <?php endif; ?>

                                    <!-- Stock Status Badge -->
                                    <?php if($p['stock'] <= 0): ?>
                                        <div class="absolute inset-0 bg-white/40 backdrop-blur-[2px] flex items-center justify-center z-10">
                                            <span class="bg-white text-black text-[10px] font-black px-4 py-2 rounded-full shadow-xl uppercase tracking-widest border border-gray-100">Out of Stock</span>
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <!-- High-Quality Stars & Branding -->
                                <div class="flex gap-1 mb-2">
                                    <i class="fas fa-star text-[#EDB12B] text-[10px]"></i>
                                    <i class="fas fa-star text-[#EDB12B] text-[10px]"></i>
                                    <i class="fas fa-star text-[#EDB12B] text-[10px]"></i>
                                    <i class="fas fa-star text-[#EDB12B] text-[10px]"></i>
                                    <i class="fas fa-star text-[#EDB12B] text-[10px]"></i>
                                </div>

                                <!-- Title & Specs -->
                                <div class="mb-5">
                                    <h3 class="text-xl font-black text-[#004F42] leading-tight mb-1 truncate-1 group-hover:text-[#24B25D] transition-colors"><?php echo $p['name']; ?></h3>
                                    <p class="text-gray-400 text-[10px] font-bold uppercase tracking-widest">
                                        <?php echo !empty($p['tagline']) ? $p['tagline'] : 'Premium Natural Selection'; ?>
                                    </p>
                                </div>

                                <!-- Interactive Price Strip (Reference Layout) -->
                                <div class="flex items-center justify-between mb-5 mt-auto">
                                    <div class="flex flex-col">
                                        <?php if(isset($p['original_price']) && $p['original_price'] > $p['price']): ?>
                                            <span class="text-[10px] text-gray-400 font-bold line-through">MRP <?php echo format_price($p['original_price']); ?></span>
                                        <?php endif; ?>
                                        <span class="text-4xl font-black text-black leading-none">
                                            <?php echo format_price($p['price']); ?>
                                        </span>
                                    </div>
                                    <!-- Cart Icon Pill -->
                                    <?php if($p['stock'] <= 0): ?>
                                        <button disabled class="w-12 h-12 bg-gray-100 text-gray-300 rounded-xl flex items-center justify-center cursor-not-allowed z-10" title="Out of stock">
                                            <i class="fas fa-ban text-sm"></i>
                                        </button>
                                    <?php else: ?>
                                        <button onclick="event.preventDefault(); event.stopPropagation(); addToCart(<?php echo $p['id']; ?>, this)" class="w-12 h-12 bg-[#212121] text-white rounded-xl flex items-center justify-center hover:bg-[#24B25D] hover:scale-110 transition-all shadow-md active:scale-95 z-10">
                                            <i class="fas fa-shopping-cart text-sm"></i>
                                        </button>
                                    <?php endif; ?>
                                </div>

                                <!-- "Buy Now" Action -->
                                <?php if($p['stock'] <= 0): ?>
                                    <button disabled 
                                        class="w-full bg-gray-100 text-gray-400 py-4 rounded-2xl font-black text-[18px] cursor-not-allowed z-10">
                                        OUT OF STOCK
                                    </button>
                                <?php else: ?>
                                    <button onclick="event.preventDefault(); event.stopPropagation(); quickBuy(<?php echo $p['id']; ?>, this)" 
                                        class="w-full bg-[#24B25D] hover:bg-[#004F42] text-white py-4 rounded-2xl font-black text-[18px] transition-all shadow-md active:scale-95 z-10">
                                        BUY NOW <i class="fas fa-bolt ml-1 group-hover/buy:animate-pulse"></i>
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </a>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        
            
            <div class="text-center mt-16">
                <a href="<?php echo get_url('shop'); ?>" class="btn  rounded-xl hover:bg-[#f67e42] hover:text-white btn-outline px-12 py-4 text-lg border-2">View All Products</a>
            </div>
        </div>
    </section>
    <!-- THE CRAFT JOURNEY (Immersive Discovery Boards) -->
    <section class="bg-black relative overflow-hidden" id="craft-journey-trigger">
        
        <!-- Step 1: Selection (Full Billboard) -->
        <div class="relative w-full h-[60vh]  md:h-[40vh ] flex items-center overflow-hidden border-b border-white/5 craft-board" id="craft-step-1">
            <div class="absolute inset-0 z-0 bg-black">
                <img src="<?php echo get_url('assets/images/craft_selection.png'); ?>" alt="Selection" class="w-full h-full object-cover opacity-60 scale-110 craft-img">
                <div class="absolute inset-0 bg-gradient-to-r from-black via-black/40 to-transparent"></div>
            </div>
            <div class="container mx-auto px-6 md:px-24 relative z-10">
                <div class="max-w-2xl craft-content opacity-0 transform translate-x-[-50px]">
                    <span class="text-[#24B25D] font-black tracking-[0.3em] uppercase text-xs mb-4 block">Stage 01</span>
                    <h2 class="text-5xl md:text-8xl font-heading font-black text-white leading-none tracking-tighter mb-8 uppercase">Nature's <br><span class="text-[#24B25D]">Best.</span></h2>
                    <p class="text-white/60 font-bold text-lg md:text-xl leading-relaxed">We select only the ripest, hand-picked fruits from our partner orchards in Kashmir. Each piece is inspected for the Driyum standard of vibrant color and natural peak sweetness.</p>
                </div>
            </div>
        </div>

        <!-- Step 2: Precision Dehydration (Full Billboard) -->
        <div class="relative w-full h-[60vh]  md:h-[40vh ] flex items-center overflow-hidden border-b border-white/5 craft-board" id="craft-step-2">
            <div class="absolute inset-0 z-0 bg-black">
                <img src="<?php echo get_url('assets/images/craft_dehydration.png'); ?>" alt="Dehydration" class="w-full h-full object-cover opacity-60 scale-110 craft-img">
                <div class="absolute inset-0 bg-gradient-to-l from-black via-black/40 to-transparent"></div>
            </div>
            <div class="container mx-auto px-6 md:px-24 flex justify-end relative z-10">
                <div class="max-w-2xl text-right craft-content opacity-0 transform translate-x-[50px]">
                    <span class="text-[#24B25D] font-black tracking-[0.3em] uppercase text-xs mb-4 block">Stage 02</span>
                    <h2 class="text-5xl md:text-8xl font-heading font-black text-white leading-none tracking-tighter mb-8 uppercase">Slow <br><span class="text-[#24B25D]">Dehydrate.</span></h2>
                    <p class="text-white/60 font-bold text-lg md:text-xl leading-relaxed">No frying. No oils. Our precision dehydration uses gentle heat to remove moisture while keeping 100% of the fiber and natural nutrients locked deep inside the fruit.</p>
                </div>
            </div>
        </div>

        <!-- Step 3: Boutique Packing (Full Billboard) -->
        <div class="relative w-full h-[60vh]  md:h-[40vh ] flex items-center overflow-hidden craft-board" id="craft-step-3">
            <div class="absolute inset-0 z-0 bg-black">
                <img src="<?php echo get_url('assets/images/craft_packing.png'); ?>" alt="Packing" class="w-full h-full object-cover opacity-70 scale-110 craft-img">
                <div class="absolute inset-0 bg-gradient-to-t from-black via-black/40 to-transparent"></div>
            </div>
            <div class="container mx-auto px-6 md:px-24 relative z-10">
                <div class="max-w-2xl craft-content opacity-0 transform translate-y-[50px]">
                    <span class="text-[#24B25D] font-black tracking-[0.3em] uppercase text-xs mb-4 block">Stage 03</span>
                    <h2 class="text-5xl md:text-8xl font-heading font-black text-white leading-none tracking-tighter mb-8 uppercase">Purely <br><span class="text-[#24B25D]">Packed.</span></h2>
                    <p class="text-white/60 font-bold text-lg md:text-xl leading-relaxed">Sealed in clean-room environments. Our boutique packing ensures that every crunch reaches you as fresh as the day it was harvested. Pure fruit. Zero additives.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- GSAP Craft Journey Animation Engine -->
    <script>
        document.addEventListener('DOMContentLoaded', () => {
             if (typeof gsap === 'undefined') return;

             const boards = document.querySelectorAll('.craft-board');
             
             boards.forEach((board, i) => {
                 const content = board.querySelector('.craft-content');
                 const img = board.querySelector('.craft-img');
                 if(!content || !img) return;

                 // Dynamic Move Vectors
                 let xDir = i % 2 === 0 ? -80 : 80;
                 let yDir = (i === 2) ? 60 : 0;

                 // Set Initial State
                 gsap.set(content, { opacity: 0, x: xDir, y: yDir });

                 // Content Reveal with early finish
                 gsap.to(content, {
                    opacity: 1,
                    x: 0,
                    y: 0,
                    ease: "power2.out",
                    scrollTrigger: {
                        trigger: board,
                        start: "top 90%",
                        end: "top 45%", // Finish earlier so user sees the text clearly
                        scrub: 0.4
                    }
                 });

                 // High Performance Image Scaling - purely parallax
                 gsap.fromTo(img,
                    { scale: 1.12, rotation: 1 },
                    { 
                        scale: 1, 
                        rotation: 0, 
                        ease: "none",
                        scrollTrigger: {
                            trigger: board,
                            start: "top bottom",
                            end: "bottom top",
                            scrub: true
                        }
                    }
                 );
             });
        });
    </script>


    <?php if(!empty($trust_badges)): ?>
    <section class="bg-white py-10 md:py-10 overflow-hidden border-b border-gray-100 relative group/marquee">
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
    <section class="py-4 px-4 mb-20 md:mb-0" id="video_brand_story">
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
    <section class="py-4 md:py-8 bg-black relative overflow-hidden text-white" id="reviews-section">
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
            <div class="flex flex-col-reverse md:flex-row justify-center items-center gap-4 md:gap-8 mt-12 md:mt-20 relative z-20">
                <!-- Mobile Arrows + Dots Container -->
                <div class="flex items-center gap-4 w-full justify-center md:w-auto">
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


