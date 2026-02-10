<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php 
    $page_title = 'Our Sacred Story';
    $page_description = "Discover the heritage of Kashmiri Hokh Suin and our mission to redefine snacking. Real ingredients, mountain-fresh perfection, and a modern twist on ancient Kashmiri traditions.";
    include 'includes/head.php'; 
    ?>
</head>
<body class="bg-[#FFFEDC] font-['Inter']">

    <?php include 'includes/header.php'; ?>

    <!-- HERO SECTION -->
    <section class="pt-32 pb-20 px-6 relative overflow-hidden">
        <div class="container mx-auto max-w-5xl text-center relative z-10 px-4">
            <span class="bg-[#24B25D]/10 text-[#24B25D] text-[10px] md:text-xs font-black px-6 py-2 rounded-full uppercase tracking-widest mb-6 inline-block anim-up"><?php echo get_setting('about_hero_subtitle', 'The Driyum Journey'); ?></span>
            <h1 class="text-[clamp(2.5rem,8vw,6rem)] font-['Crimson_Pro'] font-black text-gray-900 mb-8 anim-up delay-100 leading-[1.1] tracking-tighter"><?php echo get_setting('about_hero_title', 'Born from <span class="text-[#24B25D]">Frustration</span>,<br>Dried to <span class="text-amber-500">Perfection</span>.'); ?></h1>
            <p class="text-lg md:text-2xl text-gray-500 font-medium max-w-3xl mx-auto anim-up delay-200 leading-relaxed px-4"><?php echo get_setting('about_hero_desc', "We're on a mission to prove that healthy snacking shouldn't cost the earth or your health."); ?></p>
        </div>
        
        <!-- Background Elements -->
        <div class="absolute -top-20 -left-20 w-80 h-80 bg-[#24B25D] rounded-full blur-[120px] opacity-10"></div>
        <div class="absolute -bottom-20 -right-20 w-80 h-80 bg-amber-200 rounded-full blur-[120px] opacity-10"></div>
    </section>

    <!-- BRAND STORY SECTION -->
    <section class="py-20 px-6">
        <div class="container mx-auto max-w-6xl">
            <div class="flex flex-col md:flex-row items-center gap-16">
                <div class="w-full md:w-1/2 anim-up px-4">
                    <div class="relative group">
                        <div class="absolute -inset-4 bg-[#24B25D]/20 rounded-[50px] blur-2xl group-hover:blur-3xl transition-all duration-500"></div>
                        <img src="<?php echo get_url(get_setting('about_story_image', 'assets/images/about_story.jpg')); ?>" alt="Our Story" onerror="this.src='./assets/images/about_story.jpg'" class="relative rounded-[40px] w-full h-[350px] md:h-[500px] object-cover shadow-2xl transform group-hover:scale-[1.02] -rotate-1 md:-rotate-2 group-hover:rotate-0 transition-all duration-700">
                    </div>
                </div>
                <div class="w-full md:w-1/2 space-y-8 anim-up delay-200">
                    <h2 class="text-4xl md:text-5xl font-['Crimson_Pro'] font-black text-gray-900 leading-tight"><?php echo get_setting('about_story_title', 'The "Aha!" Moment'); ?></h2>
                    <div class="text-lg text-gray-500 leading-relaxed space-y-6 font-medium">
                        <?php 
                        $story_text = get_setting('about_story_text', "Our whole journey started with a simple frustration: why were all the truly healthy snacks so expensive, while the cheap ones were loaded with processed ingredients?\n\nIt felt like we were filling our bodies with chemical experiments and guilt rather than a snack. We realized the market was missing something. So, we took matters into our own hands, deciding to create the perfect alternative.");
                        echo nl2br(htmlspecialchars($story_text)); 
                        ?>
                        <p class="text-gray-900 font-bold italic border-l-4 border-[#24B25D] pl-6"><?php echo get_setting('about_story_quote', '"We simply select the best fruit and gently dehydrate it. That\'s it!"'); ?></p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- TRADITION SECTION: HOKH SUIN -->
    <section class="py-32 px-6 bg-white rounded-[60px] md:rounded-[100px] shadow-sm my-20">
        <div class="container mx-auto max-w-6xl text-center mb-16 px-6">
             <h2 class="text-[clamp(2.5rem,7vw,5rem)] font-['Crimson_Pro'] font-black text-gray-900 mb-6 leading-none"><?php echo get_setting('about_tradition_title', 'HOKH SUIN'); ?></h2>
             <span class="text-[#24B25D] font-black uppercase tracking-[0.3em] text-xs md:text-sm">A piece of Kashmiri heritage</span>
        </div>

        <div class="container mx-auto max-w-6xl">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-12 md:gap-24 items-center">
                <div class="space-y-8 order-2 md:order-1 anim-up">
                    <div class="text-gray-500 leading-relaxed space-y-6 font-medium">
                        <?php 
                        $trad_text = get_setting('about_tradition_text', "With long, harsh winters and limited access to fresh produce, families would preserve vegetables during summer so the warmth and flavour of the season could be carried into winter meals. What was once an everyday practice has now become a delicacy — a way to taste tradition, history, and memory.\n\nAs modern lifestyles take over, this practice is slowly fading. \"Hokh suin by Driyum\" is our effort to revive this tradition and make it accessible again.");
                        echo nl2br(htmlspecialchars($trad_text));
                        ?>
                        <div class="bg-[#FFFEDC] p-8 rounded-[40px] border border-amber-100">
                            <h4 class="font-black text-gray-900 mb-2 font-['Crimson_Pro']"><?php echo get_setting('about_twist_title', 'Modern Twist on Ancient Traditions'); ?></h4>
                            <p class="text-sm"><?php echo get_setting('about_twist_text', 'Prepared from fresh vegetables, Hokh suin is traditionally dehydrated, but unlike earlier times, it is handled with modern hygiene standards and packed carefully to ensure safety, quality, and convenience.'); ?></p>
                        </div>
                    </div>
                </div>
                <div class="order-1 md:order-2 anim-up delay-200 px-4">
                    <div class="relative group">
                         <div class="absolute inset-0 bg-amber-400/20 rounded-[50px] rotate-6 scale-95 blur-xl group-hover:rotate-0 transition-transform duration-500"></div>
                         <img src="<?php echo get_url(get_setting('about_tradition_image', 'assets/images/tradition.jpg')); ?>" alt="Heritage" onerror="this.src='./assets/images/tradition.jpg'" class="relative rounded-[50px] w-full h-[400px] md:h-[600px] object-cover shadow-2xl transition hover:scale-[1.01]">
                         
                         <!-- Floating Badge -->
                         <div class="absolute -bottom-10 -left-10 bg-white p-6 rounded-[30px] shadow-2xl border-4 border-[#FFFEDC] transform -rotate-6 hidden md:block">
                            <div class="text-center">
                                <span class="block text-4xl mb-1">🏺</span>
                                <span class="block font-black text-gray-900 crimson-pro leading-none">TRADITION</span>
                                <span class="text-[10px] text-[#24B25D] font-black tracking-widest uppercase">REVIVED</span>
                            </div>
                         </div>
                    </div>
                </div>
            </div>

            <!-- Cooking Note -->
            <div class="mt-24 p-10 md:p-16 bg-gray-900 text-white rounded-[60px] relative overflow-hidden anim-up">
                <div class="relative z-10 flex flex-col md:flex-row items-center gap-12">
                    <div class="text-6xl">🥘</div>
                    <div>
                        <h3 class="text-3xl font-['Crimson_Pro'] font-black mb-4"><?php echo get_setting('about_tip_title', 'A Quick Tip for the Perfect Meal'); ?></h3>
                        <p class="text-gray-400 text-lg max-w-3xl leading-relaxed"><?php echo get_setting('about_tip_text', 'In dried form, the vegetables may feel hard or chewy, which is natural. Once soaked or cooked, they soften and become ready for use in rice dishes, curries, and traditional meals. It is not just dehydrated vegetables — it is a piece of Kashmiri heritage, thoughtfully prepared and delivered to your doorstep.'); ?></p>
                    </div>
                </div>
                <!-- Decor -->
                <div class="absolute -right-20 -bottom-20 w-80 h-80 bg-[#24B25D] rounded-full blur-[120px] opacity-10"></div>
            </div>
        </div>
    </section>

    <!-- MISSION & VISION SECTION -->
    <section class="py-32 px-6">
        <div class="container mx-auto max-w-6xl">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-12">
                
                <!-- Mission Card -->
                <div class="bg-white p-12 md:p-16 rounded-[60px] border border-gray-100 shadow-sm hover:shadow-2xl hover:-translate-y-2 transition-all duration-500 group anim-up">
                    <div class="w-20 h-20 bg-[#24B25D]/10 rounded-3xl flex items-center justify-center text-[#24B25D] text-4xl mb-10 group-hover:scale-110 transition-transform">
                        <i class="fas fa-rocket"></i>
                    </div>
                    <h2 class="text-4xl font-['Crimson_Pro'] font-black text-gray-900 mb-6">Our Mission</h2>
                    <p class="text-xl text-gray-500 leading-relaxed font-medium"><?php echo get_setting('about_mission', 'Our mission is to make healthy snacking affordable, accessible, and convenient for everyone.'); ?></p>
                </div>

                <!-- Vision Card -->
                <div class="bg-gray-900 p-12 md:p-16 rounded-[60px] text-white shadow-2xl hover:scale-[1.02] transition-all duration-500 group anim-up delay-200">
                    <div class="w-20 h-20 bg-[#24B25D] rounded-3xl flex items-center justify-center text-black text-4xl mb-10 group-hover:rotate-12 transition-transform shadow-lg shadow-green-500/20">
                        <i class="fas fa-eye"></i>
                    </div>
                    <h2 class="text-4xl font-['Crimson_Pro'] font-black text-white mb-6">Our Vision</h2>
                    <p class="text-xl text-gray-400 leading-relaxed font-medium"><?php echo get_setting('about_vision', 'To build Driyum into one of India’s leading healthy snacking brands, starting from Kashmir and reaching across the country.'); ?></p>
                </div>

            </div>

            <!-- Vision Pillars -->
            <div class="mt-16 grid grid-cols-2 md:grid-cols-4 gap-4 anim-up delay-400">
                <div class="bg-white p-6 rounded-[30px] border border-gray-100 text-center">
                    <span class="block text-2xl mb-2">⚡</span>
                    <span class="text-[10px] font-black uppercase tracking-widest text-[#24B25D]">Clean Energy</span>
                </div>
                <div class="bg-white p-6 rounded-[30px] border border-gray-100 text-center">
                    <span class="block text-2xl mb-2">🌿</span>
                    <span class="text-[10px] font-black uppercase tracking-widest text-orange-500">Real Ingredients</span>
                </div>
                <div class="bg-white p-6 rounded-[30px] border border-gray-100 text-center">
                    <span class="block text-2xl mb-2">🧠</span>
                    <span class="text-[10px] font-black uppercase tracking-widest text-blue-500">Smart Snacking</span>
                </div>
                <div class="bg-white p-6 rounded-[30px] border border-gray-100 text-center">
                    <span class="block text-2xl mb-2">🏔️</span>
                    <span class="text-[10px] font-black uppercase tracking-widest text-indigo-500">Kashmiri Origins</span>
                </div>
            </div>

            <div class="mt-20 text-center max-w-3xl mx-auto anim-up delay-500">
                <p class="text-lg text-gray-500 font-medium leading-relaxed">We want to promote healthier eating habits by replacing unhealthy packaged snacks with natural alternatives, ideal for everyday snacking, trekking, fitness enthusiasts, athletes, and post-workout nutrition.</p>
            </div>
        </div>
    </section>

    <!-- JOIN THE MOVEMENT CTA -->
    <section class="py-20 px-6">
        <div class="container mx-auto max-w-6xl">
            <div class="bg-[#24B25D] rounded-[60px] p-12 md:p-20 text-center relative overflow-hidden anim-up">
                <div class="relative z-10">
                    <h2 class="text-5xl md:text-7xl font-['Crimson_Pro'] font-black text-black mb-8">Ready to snack better?</h2>
                    <a href="<?php echo get_url('shop'); ?>" class="btn-chunky bg-black text-white px-12 py-6 text-xl shadow-2xl hover:bg-white hover:text-black transition-all">Explore the Collection</a>
                </div>
                
                <!-- Decor -->
                <div class="absolute top-0 right-0 w-64 h-64 bg-white/20 rounded-full -mr-32 -mt-32 blur-[80px]"></div>
                <div class="absolute bottom-0 left-0 w-64 h-64 bg-black/5 rounded-full -ml-32 -mb-32 blur-[80px]"></div>
            </div>
        </div>
    </section>

    <?php include 'includes/footer.php'; ?>
</body>
</html>


