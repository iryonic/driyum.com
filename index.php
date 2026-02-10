<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';
$featured = get_featured_products(5);

// Fetch Wishlist IDs for active states
$wishlist_ids = [];
if (isset($_SESSION['user_id'])) {
    $wishlist_res = fetch_all("SELECT product_id FROM wishlist WHERE user_id = ?", [$_SESSION['user_id']]);
    $wishlist_ids = array_column($wishlist_res, 'product_id');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php 
    $page_title = 'The Art of Healthy Snacking';
    $page_description = "Experience 100% natural, premium healthy snacks from the heart of Kashmir. No added sugar, no guilt—just pure, crunchy indulgence delivered to your door.";
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
    </style>
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
    $active_sale = fetch_one("SELECT * FROM sale_countdowns WHERE is_active = 1 LIMIT 1");
    ?>

    <!-- ALPINO-INSPIRED BRANDED SLIDER -->
    <section class="relative overflow-hidden group/hero bg-[#004F42] overflow-x-hidden">
        
        <!-- RESPONSIVE FLOATING SALE TIMER -->
        <?php if($active_sale): ?>
        <div class="absolute bottom-24 lg:top-[120px] left-1/2 -translate-x-1/2 lg:left-auto lg:right-12 lg:translate-x-0 z-[60] anim-up-delayed pointer-events-none">
            <div class="hero-sale-timer-row flex lg:flex-col items-center gap-2 md:gap-3 bg-black/20 lg:bg-black/40 backdrop-blur-3xl border border-white/10 p-1.5 md:p-2.5 rounded-full lg:rounded-[2.5rem] shadow-2xl pointer-events-auto" data-end="<?php echo $active_sale['end_date']; ?>">
                <div class="bg-[#F67E42] text-white px-3 md:px-5 py-1.5 md:py-2 rounded-full flex items-center gap-1.5 shadow-lg shadow-[#F67E42]/20">
                    <span class="w-1.5 h-1.5 rounded-full bg-white animate-pulse"></span>
                    <span class="text-[8px] md:text-[10px] font-black uppercase tracking-widest leading-none whitespace-nowrap">Sale Ends</span>
                </div>
                <div class="flex items-center gap-3 md:gap-5 text-[#FFFEDC] px-2 md:px-4">
                    <div class="flex flex-col items-center"><span class="font-black text-xs md:text-2xl hero-days leading-none">00</span><span class="text-[5px] md:text-[7px] opacity-40 uppercase font-black mt-1">Days</span></div>
                    <div class="flex flex-col items-center"><span class="font-black text-xs md:text-2xl hero-hours leading-none">00</span><span class="text-[5px] md:text-[7px] opacity-40 uppercase font-black mt-1">Hrs</span></div>
                    <div class="flex flex-col items-center"><span class="font-black text-xs md:text-2xl hero-mins leading-none">00</span><span class="text-[5px] md:text-[7px] opacity-40 uppercase font-black mt-1">Min</span></div>
                    <div class="flex flex-col items-center"><span class="text-[#24B25D] font-black text-xs md:text-2xl hero-secs leading-none">00</span><span class="text-[5px] md:text-[7px] opacity-40 uppercase font-black mt-1">Sec</span></div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- ATMOSPHERIC LAYERS & LIGHT FLARES -->
        <div class="absolute inset-0 pointer-events-none z-[5] opacity-[0.05]" style="background-image: url('https://www.transparenttextures.com/patterns/carbon-fibre.png');"></div>
        <div class="absolute inset-0 pointer-events-none z-[5] bg-gradient-to-b from-[#004F42]/30 via-transparent to-[#004F42]/50"></div>
        <div class="absolute -top-[50%] -left-[20%] w-[150%] h-[150%] bg-gradient-to-br from-white/5 to-transparent rounded-full blur-[150px] pointer-events-none z-[6] animate-pulse-slow"></div>

        <div class="swiper heroSwiper w-full h-[90vh] md:h-[min(90vh,70vw)] lg:h-[90vh]">
            <div class="swiper-wrapper">
                <?php 
                $brand_accents = ['bg-[#24B25D]', 'bg-[#F67E42]', 'bg-[#17775D]', 'bg-[#EDB02C]'];
                foreach ($hero_slides as $index => $slide): 
                    $color = $brand_accents[$index % count($brand_accents)];
                ?>
                    <div class="swiper-slide relative overflow-hidden flex items-center">
                        
                        <!-- Background Effects -->
                        <div class="absolute inset-0 z-0 bg-[#004F42]/40 transition-colors duration-1000"></div>
                        <div class="absolute -right-[20%] lg:-right-[10%] -top-[10%] w-[120%] lg:w-[70%] h-[120%] <?php echo $color; ?> skew-x-[-12deg] z-0 opacity-10 lg:opacity-100 overflow-hidden transition-transform duration-[2s] swiper-bg-skew shadow-[-50px_0_100px_rgba(0,0,0,0.2)]" data-swiper-parallax="20%">
                             <div class="absolute inset-0 opacity-10" style="background-image: url('data:image/svg+xml,%3Csvg width=\"60\" height=\"60\" viewBox=\"0 0 60 60\" xmlns=\"http://www.w3.org/2000/svg\"%3E%3Cg fill=\"white\" fill-rule=\"evenodd\"%3E%3Cpath d=\"M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z\"/%3E%3C/g%3E%3C/svg%3E');"></div>
                        </div>

                        <!-- Content Grid: Prioritize Text Width (60/40 Split) -->
                        <div class="container mx-auto px-4 md:px-12 relative z-10 grid grid-cols-1 lg:grid-cols-[1.1fr_0.9fr] xl:grid-cols-[1.2fr_0.8fr] gap-6 md:gap-8 lg:gap-16 items-center h-full pt-20 lg:pt-0">
                            
                            <!-- Cinematic Background Title (Depth Layer: Subtle Texture) -->
                            <div class="absolute inset-x-0 top-0 lg:-top-10 pointer-events-none z-0 overflow-hidden select-none opacity-[0.04] lg:opacity-[0.06] flex justify-center" data-swiper-parallax="-400">
                                <span class="text-[30vw] lg:text-[25vw] font-black uppercase tracking-tighter leading-none whitespace-nowrap lg:-rotate-12 select-none filter blur-[2px]" style="-webkit-text-stroke: 1px #FFFEDC; color: transparent;">
                                    <?php echo htmlspecialchars($slide['title'] ?? ''); ?>
                                </span>
                            </div>

                            <!-- Text Content: Primary on Mobile (Centered for small screens) -->
                            <div class="text-center lg:text-left order-1 lg:order-1 relative z-30 pb-12 lg:pb-0">
                                <!-- Brand Badge -->
                                <?php if($slide['show_badge'] ?? 1): ?>
                                <div class="inline-flex items-center gap-1 md:gap-2.5 bg-[#FFFEDC]/5 backdrop-blur-2xl border border-white/10 rounded-full px-3.5 md:px-5 py-1.5 md:py-2.5 mb-2.5 md:mb-8 transform swiper-badge-anim overflow-hidden relative group/badge mx-auto lg:mx-0 shadow-xl" data-swiper-parallax="-300">
                                    <div class="absolute inset-0 bg-gradient-to-r from-transparent via-white/20 to-transparent -translate-x-full group-hover/badge:translate-x-full transition-transform duration-1000"></div>
                                    <div class="w-2 md:w-2.5 h-2 md:h-2.5 rounded-full bg-[#24B25D] animate-pulse shadow-[0_0_10px_#24B25D]"></div>
                                    <span class="text-[8px] md:text-[10px] font-black uppercase tracking-[0.25em] text-[#FFFEDC]/90 italic"><?php echo htmlspecialchars($slide['badge_text'] ?? '100% Pure & Natural'); ?></span>
                                </div>
                                <?php endif; ?>

                                <!-- Headline: Dynamic & Readable -->
                                <?php if($slide['show_title'] ?? 1): ?>
                                <div class="relative mb-5 lg:mb-10" data-swiper-parallax="-500">
                                    <h1 class="text-3xl sm:text-5xl md:text-6xl lg:text-7xl xl:text-[8rem] font-black leading-[1.05] lg:leading-[0.9] text-[#FFFEDC] tracking-tighter uppercase swiper-title-anim filter drop-shadow-[0_30px_50px_rgba(0,0,0,0.4)] transform-gpu perspective-1000">
                                        <?php echo nl2br(htmlspecialchars($slide['title'] ?? '')); ?>
                                    </h1>
                                    <div class="absolute -left-10 top-0 w-1.5 h-full bg-gradient-to-b from-[#24B25D] via-[#24B25D]/40 to-transparent rounded-full opacity-40 hidden xl:block" data-swiper-parallax="-200"></div>
                                </div>
                                <?php endif; ?>

                                <!-- Description: Better Vertical Rhythm -->
                                <?php if($slide['show_subtitle'] ?? 1): ?>
                                <div class="max-w-xl mx-auto lg:mx-0 mb-5 lg:mb-10 opacity-0 transform swiper-subtitle-anim" data-swiper-parallax="-700">
                                    <p class="text-[12px] md:text-base lg:text-xl text-[#FFFEDC]/60 font-medium leading-relaxed md:leading-relaxed crimson-pro italic tracking-wide">
                                        <?php echo htmlspecialchars($slide['subtitle'] ?? ''); ?>
                                    </p>
                                </div>
                                <?php endif; ?>

                                <!-- Action Buttons -->
                                <?php if($slide['show_cta'] ?? 1): ?>
                                <div class="flex flex-wrap items-center justify-center lg:justify-start gap-4 md:gap-7 swiper-cta-anim opacity-0" data-swiper-parallax="-900">
                                    <a href="<?php echo get_url($slide['cta_link']); ?>" 
                                       class="group relative inline-flex items-center justify-center px-6 md:px-9 py-2.5 md:py-4 overflow-hidden font-black text-black bg-[#FFFEDC] rounded-lg md:rounded-[1.5rem] hover:bg-[#F67E42] hover:text-white transition-all duration-[600ms] shadow-[0_20px_40px_rgba(0,0,0,0.3)] active:scale-95 border-2 border-transparent hover:border-white/20">
                                        <span class="relative z-10 uppercase tracking-widest text-[9px] md:text-sm"><?php echo htmlspecialchars($slide['cta_text']); ?></span>
                                        <i class="fas fa-arrow-right ml-2 md:ml-3 group-hover:translate-x-2 transition-transform relative z-10 text-[10px] md:text-sm"></i>
                                    </a>
                                    
                                    <div class="flex items-center gap-2.5 md:gap-4 group cursor-pointer" onclick="window.scrollTo({top: 800, behavior: 'smooth'})">
                                        <div class="w-8 h-8 md:w-12 md:h-12 rounded-full border border-white/20 bg-white/5 backdrop-blur-md flex items-center justify-center group-hover:bg-[#24B25D] group-hover:border-transparent transition-all duration-500 scale-90 group-hover:scale-100">
                                            <i class="fas fa-play text-[#FFFEDC] text-[8px] md:text-sm ml-0.5"></i>
                                        </div>
                                        <div class="flex flex-col text-left">
                                            <span class="text-[6px] md:text-[9px] font-black uppercase tracking-[0.3em] text-[#FFFEDC]/60 group-hover:text-[#24B25D] transition-colors leading-none mb-0.5">Explore</span>
                                            <span class="text-[7px] md:text-[11px] font-black uppercase tracking-widest text-[#FFFEDC] group-hover:translate-x-1 transition-transform">Our Roots</span>
                                        </div>
                                    </div>
                                </div>
                                <?php endif; ?>
                            </div>

                            <!-- Product Hero Image: cinematic Presentation -->
                            <div class="relative order-1 lg:order-2 h-[22vh] lg:h-[75vh] flex items-center justify-center lg:pt-0 pt-0" data-swiper-parallax="200">
                                <div class="relative w-full max-w-[280px] md:max-w-[420px] lg:max-w-none group/img">
                                    <!-- Premium Glow Behind Image -->
                                    <div class="absolute inset-0 bg-white/20 blur-[120px] rounded-full scale-75 opacity-0 group-hover/img:opacity-100 transition-opacity duration-1000"></div>
                                    
                                    <div class="relative z-10 p-4 md:p-8 rounded-[40px] md:rounded-[80px] bg-white/5 backdrop-blur-md border border-white/10 shadow-2xl overflow-hidden swiper-image-anim">
                                        <img src="<?php echo get_url($slide['image']); ?>" 
                                             class="w-full h-auto product-hero-img drop-shadow-[0_45px_65px_rgba(0,0,0,0.4)] group-hover/img:scale-105 transition-transform duration-[2s] ease-out" 
                                             alt="Snack Image"
                                             loading="lazy">
                                        
                                        <!-- Ambient Highlights on Container -->
                                        <div class="absolute top-0 right-0 w-32 h-32 bg-white/10 rounded-full blur-3xl -translate-y-12 translate-x-12"></div>
                                        <div class="absolute bottom-0 left-0 w-32 h-32 <?php echo $color; ?>/20 rounded-full blur-3xl translate-y-12 -translate-x-12"></div>
                                    </div>

                                    <!-- Floating Decor Elements (Desktop) -->
                                    <div class="absolute -top-16 -right-16 animate-float hidden lg:block opacity-60 pointer-events-none" style="animation-duration: 8s">
                                        <i class="fas fa-leaf text-[#24B25D] text-5xl rotate-45 filter drop-shadow-xl"></i>
                                    </div>
                                    <div class="absolute -bottom-10 -left-10 animate-float-reverse hidden lg:block opacity-40 pointer-events-none" style="animation-duration: 12s">
                                        <i class="fas fa-seedling text-[#EDB02C] text-4xl -rotate-12 filter drop-shadow-lg"></i>
                                    </div>
                                    <div class="absolute top-1/2 -left-24 animate-float hidden lg:block opacity-20 pointer-events-none" style="animation-delay: 2s; animation-duration: 10s">
                                        <i class="fas fa-sun text-[#EDB02C] text-7xl"></i>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Numbering: Vertical Sidebar -->
                        <div class="absolute left-6 lg:left-12 top-1/2 -translate-y-1/2 hidden md:flex flex-col gap-4 lg:gap-8 items-center opacity-10" data-swiper-parallax="-100">
                            <span class="text-3xl lg:text-7xl font-black text-[#FFFEDC] leading-none tracking-tighter">0<?php echo $index + 1; ?></span>
                            <div class="w-[1px] lg:w-[2px] h-12 lg:h-32 bg-gradient-to-b from-[#FFFEDC] to-transparent"></div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- NAVIGATION POD -->
            <div class="absolute bottom-16 md:bottom-20 left-0 w-full z-40">
                <div class="container mx-auto px-4 md:px-12 flex items-center justify-between">
                    <!-- Progress: Desktop only -->
                    <div class="flex-1 max-w-[150px] lg:max-w-[200px] h-[2px] bg-[#FFFEDC]/10 relative overflow-hidden hidden lg:block">
                        <div class="absolute inset-0 bg-[#24B25D] swiper-progress-anim origin-left"></div>
                    </div>

                    <!-- Navigation Pod: Floating for reachability -->
                    <div class="flex items-center gap-3 lg:gap-4 bg-[#004F42]/80 backdrop-blur-3xl border border-white/10 p-1.5 lg:p-2 rounded-xl lg:rounded-2xl shadow-2xl ml-auto lg:ml-0">
                         <button class="swiper-button-prev-hero w-10 h-10 lg:w-12 lg:h-12 rounded-lg lg:rounded-xl hover:bg-[#F67E42] hover:text-white text-[#FFFEDC] flex items-center justify-center transition-all bg-white/5">
                            <i class="fas fa-arrow-left text-xs lg:text-sm"></i>
                         </button>
                         <button class="swiper-button-next-hero w-10 h-10 lg:w-12 lg:h-12 rounded-lg lg:rounded-xl hover:bg-[#F67E42] hover:text-white text-[#FFFEDC] flex items-center justify-center transition-all bg-white/5">
                            <i class="fas fa-arrow-right text-xs lg:text-sm"></i>
                         </button>
                    </div>
                </div>
            </div>

            <!-- SITE TRUST MARQUEE -->
            <div class="absolute bottom-0 left-0 w-full z-30 bg-[#004F42] py-2.5 md:py-4 overflow-hidden border-t border-white/5">
                <div class="animate-marquee whitespace-nowrap">
                    <?php for($i=0; $i<3; $i++): ?>
                    <div class="inline-flex items-center uppercase font-black tracking-widest text-[8px] md:text-[10px]">
                        <div class="flex items-center gap-2.5 md:gap-4 mx-6 md:mx-12 text-[#FFFEDC]">
                            <div class="w-6 h-6 md:w-8 md:h-8 rounded-full bg-[#24B25D]/20 flex items-center justify-center"><i class="fas fa-check text-[#24B25D] text-[10px]"></i></div>
                            <span>NO ADDED SUGAR</span>
                        </div>
                        <div class="flex items-center gap-2.5 md:gap-4 mx-6 md:mx-12 text-[#FFFEDC]">
                            <div class="w-6 h-6 md:w-8 md:h-8 rounded-full bg-[#24B25D]/20 flex items-center justify-center"><i class="fas fa-leaf text-[#24B25D] text-[10px]"></i></div>
                            <span>PLANT BASED PROTEIN</span>
                        </div>
                        <div class="flex items-center gap-2.5 md:gap-4 mx-6 md:mx-12 text-[#FFFEDC]">
                            <div class="w-6 h-6 md:w-8 md:h-8 rounded-full bg-[#F67E42]/20 flex items-center justify-center"><i class="fas fa-bolt text-[#F67E42] text-[10px]"></i></div>
                            <span>HIGH ENERGY HARVEST</span>
                        </div>
                    </div>
                    <?php endfor; ?>
                </div>
            </div>
        </div>
    </section>

    <style>
        /* ADVANCED CINEMATIC ANIMATIONS */
        .heroSwiper .swiper-slide-active .swiper-title-anim { animation: fadeInUpCinematic 1.6s cubic-bezier(0.19, 1, 0.22, 1) forwards; }
        .heroSwiper .swiper-slide-active .swiper-subtitle-anim { animation: fadeInUpCinematic 1.6s cubic-bezier(0.19, 1, 0.22, 1) 0.3s forwards; }
        .heroSwiper .swiper-slide-active .swiper-cta-anim { animation: fadeInUpCinematic 1.6s cubic-bezier(0.19, 1, 0.22, 1) 0.5s forwards; }
        .heroSwiper .swiper-slide-active .swiper-image-anim { animation: floatingPremium 8s ease-in-out infinite; }
        .heroSwiper .swiper-slide-active .swiper-bg-skew { animation: cinematicSlash 2s cubic-bezier(0.19, 1, 0.22, 1) forwards; }
        
        @keyframes fadeInUpCinematic {
            from { opacity: 0; transform: translateY(40px) scale(0.98) rotateX(-10deg); filter: blur(10px); }
            to { opacity: 1; transform: translateY(0) scale(1) rotateX(0); filter: blur(0); }
        }

        @keyframes floatingPremium {
            0%, 100% { transform: translateY(0) rotate(0) scale(1); filter: drop-shadow(0 40px 60px rgba(0,0,0,0.3)); }
            50% { transform: translateY(-35px) rotate(4deg) scale(1.04); filter: drop-shadow(0 60px 80px rgba(0,0,0,0.4)); }
        }

        @keyframes animate-float-reverse {
            0%, 100% { transform: translate(0, 0) rotate(0); }
            50% { transform: translate(-20px, 20px) rotate(-10deg); }
        }
            from { transform: translateX(100%) skewX(-15deg); opacity: 0; }
            to { transform: translateX(0) skewX(-12deg); opacity: 1; }
        }

        @keyframes fadeInUpHero {
            from { opacity: 0; transform: translateY(80px) scale(0.95); filter: blur(10px); }
            to { opacity: 1; transform: translateY(0) scale(1); filter: blur(0); }
        }

        @keyframes animate-pulse-slow {
            0%, 100% { opacity: 0.3; transform: scale(0.9); }
            50% { opacity: 0.6; transform: scale(1.1); }
        }
        .animate-pulse-slow { animation: animate-pulse-slow 8s ease-in-out infinite; }

        @keyframes float {
            0%, 100% { transform: translateY(0) rotate(45deg); }
            50% { transform: translateY(-20px) rotate(55deg); }
        }
        .animate-float { animation: float 6s ease-in-out infinite; }

        .heroSwiper .swiper-button-disabled {
            opacity: 0.3;
            cursor: not-allowed;
            pointer-events: none;
        }

        /* Glass Text Effect */
        .swiper-title-anim {
            background: linear-gradient(to bottom, #FFFEDC 0%, #FFFEDC 50%, rgba(255,254,220,0.7) 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        .swiper-slide-active ~ .swiper-progress-anim { width: 0; }
        
        .product-hero-img {
            max-height: 80%;
            width: auto;
            object-fit: contain;
        }

        .swiper-slide:not(.swiper-slide-active) .swiper-title-anim,
        .swiper-slide:not(.swiper-slide-active) .swiper-subtitle-anim,
        .swiper-slide:not(.swiper-slide-active) .swiper-cta-anim {
            opacity: 0;
        }
    </style>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const progressBar = document.querySelector('.swiper-progress-anim');
            
            const heroSwiper = new Swiper('.heroSwiper', {
                loop: true,
                speed: 1200,
                parallax: true,
                autoplay: {
                    delay: 7000,
                    disableOnInteraction: false,
                },
                grabCursor: true,
                watchSlidesProgress: true,
                loopedSlides: 5,
                navigation: {
                    nextEl: '.swiper-button-next-hero',
                    prevEl: '.swiper-button-prev-hero',
                },
                on: {
                    init: function () {
                        if(progressBar) progressBar.style.width = '100%';
                    },
                    slideChangeTransitionStart: function () {
                        if(progressBar) {
                            progressBar.style.transition = 'none';
                            progressBar.style.width = '0';
                        }
                    },
                    slideChangeTransitionEnd: function () {
                        if(progressBar) {
                            setTimeout(() => {
                                progressBar.style.transition = 'width 7s linear';
                                progressBar.style.width = '100%';
                            }, 50);
                        }
                    }
                }
            });
        });
    </script>




    <script>
    (function() {
        const timerRows = document.querySelectorAll('.hero-sale-timer-row');
        if(timerRows.length === 0) return;
        
        const dateStr = timerRows[0].dataset.end.replace(' ', 'T');
        const endDate = new Date(dateStr).getTime();
        
        const update = () => {
            const now = new Date().getTime();
            const distance = endDate - now;
            
            timerRows.forEach(row => {
                if (distance < 0) {
                    row.style.display = 'none';
                    return;
                }

                const d = Math.floor(distance / (1000 * 60 * 60 * 24));
                const h = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
                const m = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
                const s = Math.floor((distance % (1000 * 60)) / 1000);

                const daysEl = row.querySelector('.hero-days');
                const hoursEl = row.querySelector('.hero-hours');
                const minsEl = row.querySelector('.hero-mins');
                const secsEl = row.querySelector('.hero-secs');

                if(daysEl) daysEl.innerText = d.toString().padStart(2, '0');
                if(hoursEl) hoursEl.innerText = h.toString().padStart(2, '0');
                if(minsEl) minsEl.innerText = m.toString().padStart(2, '0');
                if(secsEl) secsEl.innerText = s.toString().padStart(2, '0');
            });
        };

        setInterval(update, 1000);
        update();
    })();
    </script>




    <!-- CATEGORIES CAROUSEL (Scroll Snap) -->
    <section class="py-16 anim-up delay-200 overflow-hidden">
        <div class="container mx-auto px-6 mb-8 flex justify-between items-end">
            <div>
                <span class="text-[#24B25D] font-bold tracking-wider uppercase text-sm mb-2 block">Browse by Vibe</span>
                <h2 class="text-3xl md:text-5xl font-['Crimson_Pro'] font-bold text-gray-900">Find Your Crunch</h2>
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
                    <h3 class="text-xl md:text-4xl font-['Crimson_Pro'] font-black <?php echo $s['text']; ?> leading-none mb-1 md:mb-2 drop-shadow-sm card-title"><?php echo $c['name']; ?></h3>
                    <p class="<?php echo $s['text']; ?>/80 font-['Inter'] font-bold text-[10px] md:text-sm tracking-wide flex items-center gap-2">
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
                    <span class="text-[#24B25D] font-black tracking-[0.2em] uppercase text-xs mb-3 block font-['Inter']">Fresh From The Farm</span>
                    <h2 class="text-4xl md:text-7xl font-['Crimson_Pro'] font-black text-gray-900 leading-none">New Drops <span class="text-[#24B25D]">🔥</span></h2>
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
                <div id="product-slider-container" class="flex flex-row flex-nowrap w-full gap-6 overflow-x-auto hide-scrollbar scroll-smooth px-6 pb-12 snap-x mandatory">
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
                                        <p class="text-gray-400 text-[10px] font-black uppercase tracking-[0.2em] font-['Inter']">Premium Select</p>
                                    </div>
                                    <h3 class="text-2xl font-black font-['Crimson_Pro'] text-gray-900 group-hover:text-black transition leading-tight py-1 card-title"><?php echo $p['name']; ?></h3>
                                    <div class="flex text-yellow-400 text-[10px] gap-1 mt-1">
                                        <i class="fas fa-star text-[8px]"></i><i class="fas fa-star text-[8px]"></i><i class="fas fa-star text-[8px]"></i><i class="fas fa-star text-[8px]"></i><i class="fas fa-star text-[8px]"></i>
                                        <span class="text-gray-400 text-[9px] font-black uppercase tracking-widest ml-1">(4.9)</span>
                                    </div>
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
                                    <span class="text-xl md:text-3xl font-black text-gray-900 font-['Crimson_Pro'] tracking-tighter">₹<?php echo $p['price']; ?></span>
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
                        <h2 class="text-2xl md:text-5xl font-['Crimson_Pro'] font-bold text-white mb-2 md:mb-4 leading-tight transform translate-y-4 group-hover:translate-y-0 transition duration-500 delay-200">
                            <?php echo nl2br(htmlspecialchars($vid_sec['heading'])); ?>
                        </h2>
                        <p class="text-gray-200 font-['Inter'] text-sm md:text-xl max-w-xl opacity-0 group-hover:opacity-100 transform translate-y-4 group-hover:translate-y-0 transition duration-500 delay-300 hidden md:block">
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
                    
                    <h2 class="text-xl md:text-5xl lg:text-6xl font-['Crimson_Pro'] font-bold leading-tight mb-6 md:mb-10 min-h-[120px] md:min-h-auto flex items-center justify-center p-4">
                        "<?php echo $t['message']; ?>"
                    </h2>
                    
                    <div class="flex flex-col items-center">
                        <h4 class="text-lg md:text-xl font-bold font-['Inter']"><?php echo $t['name']; ?></h4>
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
            const scrollAmount = direction === 'left' ? -card.offsetWidth * 1.1 : card.offsetWidth * 1.1;
            container.scrollBy({ left: scrollAmount, behavior: 'smooth' });
        }

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


