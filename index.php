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
    $page_description = "Experience the crunch of 100% natural, sun-dried healthy snacks. Absolutely no added sugar, 100% guilt-free snacking.";
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
<body class="bg-[#FFFBEB]">

    <?php include 'includes/header.php'; ?>

    <?php
    $hero = fetch_one("SELECT * FROM homepage_sections WHERE section_name = 'hero'");
    if(!$hero) {
        $hero = [
            'heading' => "YOUR NEW \nHEALTHY HABIT.",
            'subheading' => "Absolutely No Sugar. 100% Guilt-Free.",
            'cta_text' => "Start Crunching",
            'cta_link' => "shop.php",
            'media_url' => "assets/images/hero.jpg"
        ];
    }
    // Parse headline to add style to the second line or specific words
    $head_parts = explode("\n", $hero['heading']);
    $main_head = $head_parts[0];
    $sub_head = isset($head_parts[1]) ? $head_parts[1] : '';
    ?>
    <!-- ENHANCED CHUNKY HERO -->
    <section class="relative pt-12 pb-28 px-4 text-center overflow-hidden">
        
        <!-- Animated Background Elements -->
        <div class="absolute top-20 left-10 w-32 h-32 bg-yellow-300 rounded-full blur-3xl opacity-30 animate-pulse"></div>
        <div class="absolute bottom-40 right-10 w-40 h-40 bg-[#19DC7E] rounded-full blur-[80px] opacity-20 animate-bounce-slow"></div>

        <!-- FUN FLOATING FRUITS 🍎 -->
        <div class="absolute inset-0 overflow-hidden pointer-events-none select-none z-0">
            <!-- Top Left -->
            <div class="absolute top-10 left-[10%] text-6xl opacity-20 animate-bounce duration-[3000ms] rotate-12 drop-shadow-lg hidden sm:block">🍎</div>
            <div class="absolute top-40 left-[5%] text-4xl opacity-15 animate-ping duration-[4000ms] hidden sm:block">🍃</div>
            <div class="absolute top-60 left-[20%] text-5xl opacity-20 animate-bounce duration-[3500ms] -rotate-6 hidden lg:block">🍓</div>
            
            <!-- Bottom Left -->
            <div class="absolute bottom-20 left-[15%] text-7xl opacity-20 animate-bounce duration-[4000ms] -rotate-12 blur-[1px] hidden sm:block">🍑</div>
            <div class="absolute bottom-40 left-[25%] text-4xl opacity-15 animate-spin-slow duration-[12s] hidden lg:block">🥝</div>
            
            <!-- Top Right -->
            <div class="absolute top-20 right-[10%] text-5xl opacity-20 animate-bounce duration-[3500ms] rotate-[20deg] hidden sm:block">🌰</div>
            <div class="absolute top-1/2 right-[5%] text-4xl opacity-10 animate-spin-slow duration-[10s] hidden sm:block">🍇</div>
            <div class="absolute top-32 right-[20%] text-6xl opacity-20 animate-bounce duration-[4200ms] rotate-12 hidden lg:block">🥭</div>
            
            <!-- Bottom Right -->
            <div class="absolute bottom-32 right-[15%] text-6xl opacity-20 animate-bounce duration-[4500ms] -rotate-[15deg] blur-[1px] hidden sm:block">🍒</div>
            <div class="absolute bottom-10 right-[25%] text-5xl opacity-15 animate-bounce duration-[3800ms] rotate-6 hidden sm:block">🍊</div>
            
            <!-- Center area drift -->
            <div class="absolute top-1/4 left-1/4 text-3xl opacity-10 animate-pulse hidden md:block">✨</div>
            <div class="absolute bottom-1/4 right-1/4 text-3xl opacity-10 animate-pulse delay-700 hidden md:block">✨</div>
        </div>

        <div class="container mx-auto max-w-5xl relative z-10">
            
            <!-- Badge -->
            <div class="inline-flex items-center gap-2 bg-white border border-gray-100 rounded-full px-5 py-2 shadow-lg mb-8 transform rotate-[-3deg] hover:rotate-0 transition duration-300 cursor-default">
                <span class="w-3 h-3 rounded-full bg-[#19DC7E] animate-pulse"></span>
                <span class="text-sm font-black font-['Outfit'] uppercase tracking-widest text-gray-400">Fresh Drop 2026</span>
            </div>

            <!-- Headline -->
            <h1 class="text-4xl sm:text-6xl md:text-8xl lg:text-9xl font-['Fredoka'] font-black leading-[0.9] text-gray-900 mb-6 tracking-tight relative">
                <?php echo htmlspecialchars($main_head); ?> <br>
                <?php if($sub_head): ?>
                <span class="relative inline-block text-transparent bg-clip-text bg-gradient-to-r from-[#19DC7E] to-[#0ea5e9]">
                    <?php echo htmlspecialchars($sub_head); ?>
                    <!-- Underline Squiggle -->
                    <svg class="absolute w-full h-4 -bottom-1 left-0 text-[#19DC7E]" viewBox="0 0 100 10" preserveAspectRatio="none">
                         <path d="M0 5 Q 50 10 100 5" stroke="currentColor" stroke-width="8" fill="none" class="opacity-30" />
                    </svg>
                </span>
                <?php endif; ?>
            </h1>

            <p class="text-xl md:text-2xl text-gray-400 font-['Outfit'] font-medium mb-10 max-w-2xl mx-auto leading-relaxed">
                <span class="text-gray-900 font-bold"><?php echo htmlspecialchars($hero['subheading']); ?></span>
            </p>

            <!-- Buttons -->
            <div class="flex flex-col sm:flex-row gap-4 justify-center mb-16 relative z-20">
                <a href="<?php echo get_url('shop'); ?>" class="btn-chunky bg-black text-white text-lg md:text-xl px-10 py-5 rounded-[20px] shadow-2xl hover:scale-110 hover:-rotate-3 hover:bg-[#19DC7E] hover:text-black transition-all duration-300 border-none">
                    <?php echo htmlspecialchars($hero['cta_text']); ?>
                </a>
                <a href="#video_brand_story" class="hidden sm:flex w-16 h-16 rounded-[20px] bg-white text-gray-900 border-2 border-gray-100 items-center justify-center text-xl shadow-lg hover:border-[#19DC7E] hover:text-[#19DC7E] hover:rotate-12 transition-all duration-300">
                    <i class="fas fa-play ml-1"></i>
                </a>
            </div>

            <!-- 3D HERO IMAGE COMPOSITION -->
            <div class="relative max-w-4xl mx-auto group perspective-[1000px]">
                
                <!-- Main Image Container -->
                <div class="relative bg-white p-1 md:p-2 rounded-[2.5rem] md:rounded-[3.5rem] shadow-[0_50px_100px_rgba(0,0,0,0.15)] transform rotate-[-2deg] group-hover:rotate-0 transition-all duration-700 ease-out z-10 border-4 border-white ring-4 ring-gray-50">
                    <img src="<?php echo !empty($hero['media_url']) ? get_url(ltrim($hero['media_url'], '/')) : get_url('assets/images/hero.jpg'); ?>" class="rounded-[2.2rem] md:rounded-[3rem] w-full h-[280px] md:h-[450px] object-cover transform scale-100 group-hover:scale-105 transition duration-[1.5s]">
                    
                    <!-- Overlay Gradient -->
                    <div class="absolute inset-0 rounded-[2.2rem] md:rounded-[3rem] bg-gradient-to-t from-black/20 to-transparent pointer-events-none"></div>
                </div>

                <!-- Floating Elements (Stickers) -->
                <div class="absolute -top-6 -left-4 md:-top-10 md:-left-10 z-20 animate-bounce-slow">
                     <div class="bg-[#19DC7E] text-white font-black font-['Fredoka'] text-sm md:text-3xl px-4 py-2 md:px-6 md:py-4 rounded-[1.5rem] md:rounded-[2rem] shadow-xl transform -rotate-12 border-2 md:border-4 border-white">
                        100% NATURAL 🌿
                     </div>
                </div>

                <div class="absolute -bottom-8 -right-4 md:-bottom-10 md:-right-5 z-20">
                     <div class="bg-yellow-400 text-black font-black font-['Fredoka'] text-xs md:text-2xl px-4 py-6 md:px-6 md:py-8 rounded-full shadow-xl transform rotate-12 border-2 md:border-4 border-white flex flex-col items-center leading-none group-hover:rotate-[20deg] transition duration-500">
                        <span>ZERO</span>
                        <span class="text-[8px] md:text-sm">JUNK</span>
                     </div>
                </div>

                <!-- Glass Stats Card -->
                <div class="absolute bottom-10 left-10 md:left-10 bg-white/80 backdrop-blur-md p-4 rounded-2xl shadow-lg border border-white/50 z-30 transform translate-y-4 group-hover:translate-y-0 transition duration-500 hidden md:flex items-center gap-3">
                    <div class="flex -space-x-2">
                        <img src="https://i.pravatar.cc/100?img=5" class="w-8 h-8 rounded-full border-2 border-white">
                        <img src="https://i.pravatar.cc/100?img=8" class="w-8 h-8 rounded-full border-2 border-white">
                        <img src="https://i.pravatar.cc/100?img=12" class="w-8 h-8 rounded-full border-2 border-white">
                    </div>
                    <div>
                        <div class="flex text-yellow-500 text-[10px]">
                            <i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i>
                        </div>
                        <span class="text-xs font-bold text-gray-800">5k+ Reviews</span>
                    </div>
                </div>

            </div>

        </div>
    </section>


    <?php
    $active_sale = fetch_one("SELECT * FROM sale_countdowns WHERE is_active = 1 LIMIT 1");
    if($active_sale):
    ?>
    <section class="py-0 px-4 -mt-8 md:-mt-16 relative z-20 mb-12 md:mb-20">
        <div class="container mx-auto">
            <div class="bg-black text-white rounded-[32px] md:rounded-[48px] p-6 md:p-12 shadow-[0_40px_80px_rgba(0,0,0,0.4)] flex flex-col lg:flex-row items-center justify-between gap-8 md:gap-12 border-4 border-[#19DC7E] overflow-hidden relative">
                
                <!-- Background Glitch Effect -->
                <div class="absolute inset-0 bg-[url('<?php echo get_url('assets/images/noise.png'); ?>')] opacity-20 pointer-events-none mix-blend-overlay"></div>
                <div class="absolute -right-20 -top-20 w-64 h-64 bg-[#19DC7E] rounded-full blur-[100px] opacity-20 animate-pulse"></div>

                <div class="text-center lg:text-left relative z-10 lg:max-w-md">
                    <span class="inline-block bg-[#19DC7E] text-black font-black uppercase text-[10px] md:text-xs px-4 py-1.5 rounded-full mb-4 animate-bounce">Limited Time Offer</span>
                    <h2 class="text-2xl md:text-4xl lg:text-5xl font-['Fredoka'] font-black leading-tight mb-2"><?php echo htmlspecialchars($active_sale['title']); ?></h2>
                    <p class="text-gray-400 font-['Outfit'] text-sm md:text-base opacity-80">Don't miss out on these crunch-tastic deals!</p>
                </div>

                <div class="flex flex-col sm:flex-row items-center gap-8 relative z-10 w-full lg:w-auto">
                    <!-- Timer Grid -->
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 md:gap-4 text-center w-full sm:w-auto" id="sale-timer" data-end="<?php echo $active_sale['end_date']; ?>">
                        <div class="bg-white/10 backdrop-blur-md rounded-2xl md:rounded-3xl p-4 md:p-6 border border-white/10 min-w-[75px] md:min-w-[100px]">
                            <div class="text-2xl md:text-4xl font-black font-['Fredoka'] leading-none mb-1" id="days">00</div>
                            <div class="text-[9px] md:text-xs uppercase text-[#19DC7E] font-black tracking-widest">Days</div>
                        </div>
                        <div class="bg-white/10 backdrop-blur-md rounded-2xl md:rounded-3xl p-4 md:p-6 border border-white/10 min-w-[75px] md:min-w-[100px]">
                            <div class="text-2xl md:text-4xl font-black font-['Fredoka'] leading-none mb-1" id="hours">00</div>
                            <div class="text-[9px] md:text-xs uppercase text-[#19DC7E] font-black tracking-widest">Hrs</div>
                        </div>
                        <div class="bg-white/10 backdrop-blur-md rounded-2xl md:rounded-3xl p-4 md:p-6 border border-white/10 min-w-[75px] md:min-w-[100px]">
                            <div class="text-2xl md:text-4xl font-black font-['Fredoka'] leading-none mb-1" id="minutes">00</div>
                            <div class="text-[9px] md:text-xs uppercase text-[#19DC7E] font-black tracking-widest">Mins</div>
                        </div>
                        <div class="bg-[#19DC7E] text-black rounded-2xl md:rounded-3xl p-4 md:p-6 border border-[#19DC7E] min-w-[75px] md:min-w-[100px] shadow-[0_0_40px_rgba(25,220,126,0.4)]">
                            <div class="text-2xl md:text-4xl font-black font-['Fredoka'] leading-none mb-1" id="seconds">00</div>
                            <div class="text-[9px] md:text-xs uppercase font-black tracking-widest">Secs</div>
                        </div>
                    </div>

                    <!-- CTA Button -->
                    <div class="w-full sm:w-auto">
                        <a href="<?php echo get_url('shop'); ?>" class="btn-chunky w-full sm:w-auto bg-white text-black font-black uppercase text-sm px-8 py-5 rounded-2xl hover:bg-[#19DC7E] hover:scale-105 transition-all duration-300 shadow-2xl group flex items-center justify-center gap-3">
                            Shop Sale <i class="fas fa-shopping-bag group-hover:animate-bounce"></i>
                        </a>
                    </div>
                </div>

            </div>
        </div>
        <script>
        (function() {
            const timer = document.getElementById('sale-timer');
            if(!timer) return;
            
            // Standardize date for cross-browser support (replace space with T)
            const dateStr = timer.dataset.end.replace(' ', 'T');
            const endDate = new Date(dateStr).getTime();
            
            if (isNaN(endDate)) {
                console.error("Countdown Date Invalid:", timer.dataset.end);
                return;
            }

            const daysEl = document.getElementById('days');
            const hoursEl = document.getElementById('hours');
            const minsEl = document.getElementById('minutes');
            const secsEl = document.getElementById('seconds');
            
            const update = () => {
                const now = new Date().getTime();
                const distance = endDate - now;
                
                if (distance < 0) {
                    timer.innerHTML = '<div class="col-span-full text-center py-4 text-2xl md:text-3xl font-black text-[#19DC7E] animate-bounce uppercase tracking-widest">Sale has Ended!</div>';
                    clearInterval(timerInterval);
                    return;
                }

                const d = Math.floor(distance / (1000 * 60 * 60 * 24));
                const h = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
                const m = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
                const s = Math.floor((distance % (1000 * 60)) / 1000);

                if(daysEl) daysEl.innerText = d.toString().padStart(2, '0');
                if(hoursEl) hoursEl.innerText = h.toString().padStart(2, '0');
                if(minsEl) minsEl.innerText = m.toString().padStart(2, '0');
                if(secsEl) secsEl.innerText = s.toString().padStart(2, '0');
            };
            
            const timerInterval = setInterval(update, 1000);
            update();
        })();
        </script>
    </section>
    <?php endif; ?>

    <!-- CATEGORIES CAROUSEL (Scroll Snap) -->
    <section class="py-16 anim-up delay-200 overflow-hidden">
        <div class="container mx-auto px-6 mb-8 flex justify-between items-end">
            <div>
                <span class="text-[#19DC7E] font-bold tracking-wider uppercase text-sm mb-2 block">Browse by Vibe</span>
                <h2 class="text-3xl md:text-5xl font-bold text-gray-900">Find Your Crunch</h2>
            </div>

            <!-- Category Slider Controls -->
            <div class="hidden md:flex gap-3">
                <button onclick="scrollCategories('left')" class="w-16 h-16 rounded-[24px] bg-white border-3 border-gray-100 flex items-center justify-center text-gray-400 hover:border-black hover:text-black hover:rotate-[-5deg] transition-all shadow-sm active:scale-90">
                    <i class="fas fa-arrow-left text-xl"></i>
                </button>
                <button onclick="scrollCategories('right')" class="w-16 h-16 rounded-[24px] bg-black text-[#19DC7E] flex items-center justify-center hover:scale-105 hover:rotate-[5deg] transition-all shadow-2xl active:scale-90">
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
                    <h3 class="text-xl md:text-4xl font-['Fredoka'] font-black <?php echo $s['text']; ?> leading-none mb-1 md:mb-2 drop-shadow-sm card-title"><?php echo $c['name']; ?></h3>
                    <p class="<?php echo $s['text']; ?>/80 font-['Outfit'] font-bold text-[10px] md:text-sm tracking-wide flex items-center gap-2">
                        <span class="w-1.5 h-1.5 md:w-2 md:h-2 rounded-full bg-current animate-pulse"></span>
                        <?php echo $c['product_count']; ?> Varieties
                    </p>
                </div>
                
                <!-- Main Image - Floating 3D Effect -->
                <?php if($c['image']): ?>
                    <div class="absolute inset-0 flex items-end justify-center z-10 perspective-[1000px]">
                         <img src="<?php echo $c['image']; ?>" class="w-64 h-64 object-contain transform translate-y-8 scale-95 group-hover:translate-y-0 group-hover:scale-110 group-hover:rotate-3 transition duration-700 ease-out drop-shadow-2xl brightness-105">
                    </div>
                <?php else: ?>
                    <!-- Fallback Emoji Art -->
                    <div class="absolute bottom-0 left-1/2 -translate-x-1/2 text-[12rem] transform translate-y-10 group-hover:translate-y-0 group-hover:scale-110 group-hover:rotate-6 transition duration-700 opacity-90 filter drop-shadow-2xl grayscale-[0.2] group-hover:grayscale-0">
                        <?php echo $s['emoji']; ?>
                    </div>
                <?php endif; ?>

                <!-- Floating Background Particles (Drifting) -->
                 <div class="absolute top-10 right-10 text-4xl opacity-20 transform animate-bounce duration-[3000ms] z-0 select-none pointer-events-none mix-blend-multiply">
                    <?php echo $s['emoji']; ?>
                 </div>
                 <div class="absolute top-1/2 left-4 text-2xl opacity-10 transform animate-ping duration-[2000ms] z-0 select-none pointer-events-none">
                    ⚪
                 </div>

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
        <div class="container mx-auto px-6">
            <div class="flex flex-col md:flex-row justify-between items-start md:items-end mb-16 gap-6">
                <div>
                    <span class="text-[#19DC7E] font-black tracking-[0.2em] uppercase text-xs mb-3 block font-['Outfit']">Fresh From The Farm</span>
                    <h2 class="text-4xl md:text-7xl font-['Fredoka'] font-black text-gray-900 leading-none">New Drops <span class="text-[#19DC7E]">🔥</span></h2>
                </div>
                <!-- Slider Controls -->
                <div class="flex gap-3">
                    <button onclick="scrollProducts('left')" class="w-16 h-16 rounded-[24px] bg-white border-3 border-gray-100 flex items-center justify-center text-gray-400 hover:border-black hover:text-black hover:rotate-[-5deg] transition-all shadow-sm active:scale-90">
                        <i class="fas fa-arrow-left text-xl"></i>
                    </button>
                    <button onclick="scrollProducts('right')" class="w-16 h-16 rounded-[24px] bg-black text-[#19DC7E] flex items-center justify-center hover:scale-105 hover:rotate-[5deg] transition-all shadow-2xl active:scale-90">
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
                                
                                <img src="<?php echo get_url($p['image']); ?>" class="w-[85%] h-[85%] object-contain transform group-hover:scale-110 group-hover:-rotate-6 group-hover:-translate-y-4 transition duration-700 ease-out z-10 filter drop-shadow-[0_10px_10px_rgba(0,0,0,0.05)] group-hover:drop-shadow-[0_30px_30px_rgba(0,0,0,0.1)] <?php echo $p['stock'] <= 0 ? 'grayscale' : ''; ?>">
                                
                                <!-- Badges -->
                                <div class="absolute top-6 left-6 flex flex-col gap-2 z-20 items-start">
                                    <?php if(isset($p['is_new']) && $p['is_new']): ?>
                                        <span class="bg-[#19DC7E] text-black text-[10px] font-black px-4 py-1.5 rounded-full shadow-lg shadow-green-200 uppercase tracking-widest backdrop-blur-md transform group-hover:scale-110 transition-transform">NEW ✨</span>
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
                                <button onclick="event.preventDefault(); toggleWishlist(<?php echo $p['id']; ?>, this)" class="absolute top-5 right-5 w-12 h-12 bg-white rounded-[18px] flex items-center justify-center shadow-lg <?php echo $is_wishlisted ? 'active text-red-500' : 'text-gray-300'; ?> hover:text-red-500 hover:scale-110 transition-all duration-300 z-20 group/heart active:scale-90 border border-gray-50">
                                    <i class="<?php echo $is_wishlisted ? 'fas' : 'far'; ?> fa-heart group-hover/heart:animate-bounce"></i>
                                </button>
                            </div>

                            <!-- Content -->
                            <div class="px-6 pb-2 relative">
                                <div class="flex flex-col gap-1">
                                    <div class="flex items-center gap-2">
                                        <span class="w-2 h-2 rounded-full bg-[#19DC7E] opacity-50"></span>
                                        <p class="text-gray-400 text-[10px] font-black uppercase tracking-[0.2em] font-['Outfit']">Premium Select</p>
                                    </div>
                                    <h3 class="text-2xl font-black font-['Fredoka'] text-gray-900 group-hover:text-black transition leading-tight py-1 card-title"><?php echo $p['name']; ?></h3>
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
                                    <span class="text-xl md:text-3xl font-black text-gray-900 font-['Fredoka'] tracking-tighter">₹<?php echo $p['price']; ?></span>
                                 </div>

                                 <div class="flex gap-1 md:gap-2">
                                    <!-- Quick Buy -->
                                    <button onclick="event.stopPropagation(); quickBuy(<?php echo $p['id']; ?>, this)" 
                                        <?php echo $p['stock'] <= 0 ? 'disabled' : ''; ?>
                                        class="w-10 h-10 md:w-14 md:h-14 rounded-xl md:rounded-2xl <?php echo $p['stock'] <= 0 ? 'bg-gray-100 text-gray-300' : 'bg-amber-50 text-amber-500 hover:bg-amber-400 hover:text-white hover:scale-105 active:scale-95'; ?> flex items-center justify-center transition-all duration-300 group/btn" title="Flash Buy">
                                        <i class="fas fa-bolt text-sm md:text-xl group-hover/btn:animate-pulse"></i>
                                    </button>
                                    <!-- Add Cart -->
                                    <button onclick="event.stopPropagation(); addToCart(<?php echo $p['id']; ?>, this)" 
                                        <?php echo $p['stock'] <= 0 ? 'disabled' : ''; ?>
                                        class="w-10 h-10 md:w-14 md:h-14 rounded-xl md:rounded-2xl <?php echo $p['stock'] <= 0 ? 'bg-gray-100 text-gray-300' : 'bg-black text-[#19DC7E] hover:bg-[#19DC7E] hover:text-black hover:scale-105 active:scale-95'; ?> flex items-center justify-center transition-all duration-300 group/btn">
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
            'subheading' => "Experience the journey of our sun-dried treats. No machines, just sunshine and mountain air.",
            'media_url' => "assets/images/hero.jpg",
            'video_url' => "#"
        ];
    }
    ?>
    <section class="py-10 px-4 mb-20 md:mb-0" id="video_brand_story">
        <div class="container mx-auto">
            <div class="relative w-full rounded-[40px] overflow-hidden shadow-2xl group cursor-pointer aspect-[16/10] md:aspect-video bg-black">
                
                <!-- Video Placeholder (Dynamic Image) -->
                <img src="<?php echo $vid_sec['media_url']; ?>" class="w-full h-full object-cover opacity-60 group-hover:opacity-40 transition duration-700 transform group-hover:scale-105">
                
                <!-- Play Button (Glassmorphism) -->
                <button onclick="openVideoModal('<?php echo $vid_sec['video_url']; ?>')" class="absolute inset-0 flex items-center justify-center z-30 w-full h-full cursor-pointer focus:outline-none">
                    <div class="w-16 h-16 md:w-24 md:h-24 bg-white/10 backdrop-blur-md rounded-full border border-white/30 flex items-center justify-center group-hover:scale-110 transition duration-500 shadow-[0_0_50px_rgba(25,220,126,0.5)] group-hover:bg-[#19DC7E] group-hover:border-transparent">
                        <i class="fas fa-play text-2xl md:text-4xl text-white ml-2"></i>
                    </div>
                </button>

                <!-- Text Overlay -->
                <div class="absolute bottom-0 left-0 w-full p-6 md:p-12 bg-gradient-to-t from-black via-black/60 to-transparent z-20 pointer-events-none">
                    <div class="max-w-3xl pointer-events-auto">
                        <div class="inline-block bg-[#19DC7E] text-black text-[10px] md:text-xs font-bold px-3 py-1 rounded-full mb-3 md:mb-4 uppercase tracking-widest transform translate-y-4 opacity-0 group-hover:translate-y-0 group-hover:opacity-100 transition duration-500 delay-100">
                            Watch Brand Story
                        </div>
                        <h2 class="text-2xl md:text-5xl font-['Fredoka'] font-bold text-white mb-2 md:mb-4 leading-tight transform translate-y-4 group-hover:translate-y-0 transition duration-500 delay-200">
                            <?php echo nl2br(htmlspecialchars($vid_sec['heading'])); ?>
                        </h2>
                        <p class="text-gray-200 font-['Outfit'] text-sm md:text-xl max-w-xl opacity-0 group-hover:opacity-100 transform translate-y-4 group-hover:translate-y-0 transition duration-500 delay-300 hidden md:block">
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
            <div class="absolute top-[-20%] left-[-10%] w-[300px] md:w-[500px] h-[300px] md:h-[500px] bg-[#19DC7E] rounded-full blur-[100px] md:blur-[150px] animate-pulse"></div>
            <div class="absolute bottom-[-20%] right-[-10%] w-[300px] md:w-[500px] h-[300px] md:h-[500px] bg-blue-600 rounded-full blur-[100px] md:blur-[150px] animate-pulse" style="animation-delay: 2s;"></div>
        </div>

        <div class="container mx-auto px-6 relative z-10">
            
            <div class="text-center mb-10 md:mb-16">
                 <div class="inline-flex items-center gap-2 border border-white/20 rounded-full px-4 py-2 bg-white/5 backdrop-blur-md mb-6">
                     <i class="fas fa-heart text-[#19DC7E]"></i>
                     <span class="text-[10px] md:text-xs font-bold tracking-[0.2em] uppercase">Wall of Love</span>
                 </div>
            </div>

            <div class="relative max-w-4xl mx-auto text-center" id="review-slider">
                
                <!-- Review Items -->
                <?php foreach($testimonials as $k => $t): ?>
                <div class="review-slide absolute inset-0 transition-opacity duration-700 ease-[cubic-bezier(0.23,1,0.32,1)] <?php echo $k===0 ? 'opacity-100 relative' : 'opacity-0 absolute pointer-events-none'; ?>" data-index="<?php echo $k; ?>">
                    
                    <div class="mb-6 md:mb-8 text-[#19DC7E] text-xl md:text-2xl flex justify-center gap-1 md:gap-2">
                         <?php for($i=0; $i<$t['rating']; $i++) echo '<i class="fas fa-star"></i>'; ?>
                    </div>
                    
                    <h2 class="text-xl md:text-5xl lg:text-6xl font-['Fredoka'] font-bold leading-tight mb-6 md:mb-10 min-h-[120px] md:min-h-auto flex items-center justify-center p-4">
                        "<?php echo $t['message']; ?>"
                    </h2>
                    
                    <div class="flex flex-col items-center">
                        <h4 class="text-lg md:text-xl font-bold font-['Outfit']"><?php echo $t['name']; ?></h4>
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
                    <button onclick="prevReview()" class="w-12 h-12 md:w-14 md:h-14 rounded-full border border-white/20 flex items-center justify-center hover:bg-white hover:text-black transition duration-300 group active:scale-95">
                        <i class="fas fa-arrow-left text-lg md:text-xl group-hover:-translate-x-1 transition-transform"></i>
                    </button>
                    
                    <!-- Indicators (Visible on Desktop/Tablet, Smaller on Mobile) -->
                    <div class="flex gap-2 md:gap-3">
                        <?php foreach($testimonials as $k => $t): ?>
                        <button onclick="goToReview(<?php echo $k; ?>)" class="w-8 h-1 md:w-12 md:h-1 rounded-full bg-white/20 hover:bg-[#19DC7E] transition-all duration-300 review-dot <?php echo $k===0 ? 'bg-[#19DC7E]' : ''; ?>" data-index="<?php echo $k; ?>"></button>
                        <?php endforeach; ?>
                    </div>

                    <button onclick="nextReview()" class="w-12 h-12 md:w-14 md:h-14 rounded-full border border-white/20 flex items-center justify-center hover:bg-white hover:text-black transition duration-300 group active:scale-95">
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

            dots.forEach(d => d.classList.remove('bg-[#19DC7E]'));
            dots[index].classList.add('bg-[#19DC7E]');
            
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
