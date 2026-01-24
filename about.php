<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php render_seo_tags('Our Story', 'Learn about the DRIYUM journey, how we started, and our mission to provide healthy, sun-dried Kashmiri snacks to the world.'); ?>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="<?php echo get_url('assets/css/chunky.css'); ?>">
</head>
<body class="bg-[#FFFBEB] font-['Outfit']">

    <?php include 'includes/header.php'; ?>

    <!-- HERO SECTION -->
    <section class="pt-32 pb-20 px-6 relative overflow-hidden">
        <div class="container mx-auto max-w-5xl text-center relative z-10 px-4">
            <span class="bg-[#19DC7E]/10 text-[#19DC7E] text-[10px] md:text-xs font-black px-6 py-2 rounded-full uppercase tracking-widest mb-6 inline-block anim-up">The Driyum Journey</span>
            <h1 class="text-[clamp(2.5rem,8vw,6rem)] font-['Fredoka'] font-black text-gray-900 mb-8 anim-up delay-100 leading-[1.1] tracking-tighter">Born from <span class="text-[#19DC7E]">Frustration</span>,<br>Dried to <span class="text-amber-500">Perfection</span>.</h1>
            <p class="text-lg md:text-2xl text-gray-500 font-medium max-w-3xl mx-auto anim-up delay-200 leading-relaxed px-4">We're on a mission to prove that healthy snacking shouldn't cost the earth or your health.</p>
        </div>
        
        <!-- Background Elements -->
        <div class="absolute -top-20 -left-20 w-80 h-80 bg-[#19DC7E] rounded-full blur-[120px] opacity-10"></div>
        <div class="absolute -bottom-20 -right-20 w-80 h-80 bg-amber-200 rounded-full blur-[120px] opacity-10"></div>
    </section>

    <!-- BRAND STORY SECTION -->
    <section class="py-20 px-6">
        <div class="container mx-auto max-w-6xl">
            <div class="flex flex-col md:flex-row items-center gap-16">
                <div class="w-full md:w-1/2 anim-up px-4">
                    <div class="relative group">
                        <div class="absolute -inset-4 bg-[#19DC7E]/20 rounded-[50px] blur-2xl group-hover:blur-3xl transition-all duration-500"></div>
                        <img src="<?php echo get_url('assets/images/about_story.jpg'); ?>" alt="Our Story" onerror="this.src='https://images.unsplash.com/photo-1596591606975-97ee5cef3a1e?q=80&w=1000&auto=format&fit=crop'" class="relative rounded-[40px] w-full h-[350px] md:h-[500px] object-cover shadow-2xl transform group-hover:scale-[1.02] -rotate-1 md:-rotate-2 group-hover:rotate-0 transition-all duration-700">
                    </div>
                </div>
                <div class="w-full md:w-1/2 space-y-8 anim-up delay-200">
                    <h2 class="text-4xl md:text-5xl font-['Fredoka'] font-black text-gray-900 leading-tight">The "Aha!" Moment</h2>
                    <div class="text-lg text-gray-500 leading-relaxed space-y-6 font-medium">
                        <p>Our whole journey started with a simple frustration: why were all the truly healthy snacks so expensive, while the cheap ones were loaded with processed ingredients?</p>
                        <p>It felt like we were filling our bodies with chemical experiments and guilt rather than a snack. We realized the market was missing something. So, we took matters into our own hands, deciding to create the perfect alternative.</p>
                        <p class="text-gray-900 font-bold italic border-l-4 border-[#19DC7E] pl-6">"We simply select the best fruit and gently dehydrate it. That's it!"</p>
                        <p>This pack is proof that you don't need processed prices or sky-high margins to enjoy a clean, delicious, and nutritious snack. Welcome to the way snacking was always meant to be!</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- TRADITION SECTION: HOKH SUIN -->
    <section class="py-32 px-6 bg-white rounded-[60px] md:rounded-[100px] shadow-sm my-20">
        <div class="container mx-auto max-w-6xl text-center mb-16 px-6">
             <h2 class="text-[clamp(2.5rem,7vw,5rem)] font-['Fredoka'] font-black text-gray-900 mb-6 leading-none">HOKH SUIN</h2>
             <span class="text-[#19DC7E] font-black uppercase tracking-[0.3em] text-xs md:text-sm">A piece of Kashmiri heritage</span>
        </div>

        <div class="container mx-auto max-w-6xl">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-12 md:gap-24 items-center">
                <div class="space-y-8 order-2 md:order-1 anim-up">
                    <p class="text-xl text-gray-600 font-medium leading-relaxed italic">Hokh suin by Driyum is inspired by a time when sun-drying vegetables was not a choice, but a necessity in Kashmir.</p>
                    <div class="text-gray-500 leading-relaxed space-y-6 font-medium">
                        <p>With long, harsh winters and limited access to fresh produce, families would preserve vegetables during summer so the warmth and flavour of the season could be carried into winter meals. What was once an everyday practice has now become a delicacy — a way to taste tradition, history, and memory.</p>
                        <p>As modern lifestyles take over, this practice is slowly fading. "Hokh suin by Driyum" is our effort to revive this tradition and make it accessible again.</p>
                        <div class="bg-[#FFFBEB] p-8 rounded-[40px] border border-amber-100">
                            <h4 class="font-black text-gray-900 mb-2 font-['Fredoka']">Modern Twist on Ancient Traditions</h4>
                            <p class="text-sm">Prepared from fresh vegetables, Hokh suin is traditionally sun-dried, but unlike earlier times, it is handled with modern hygiene standards and packed carefully to ensure safety, quality, and convenience.</p>
                        </div>
                    </div>
                </div>
                <div class="order-1 md:order-2 anim-up delay-200 px-4">
                    <div class="relative group">
                         <div class="absolute inset-0 bg-amber-400/20 rounded-[50px] rotate-6 scale-95 blur-xl group-hover:rotate-0 transition-transform duration-500"></div>
                         <img src="<?php echo get_url('assets/images/tradition.jpg'); ?>" alt="Heritage" onerror="this.src='https://images.unsplash.com/photo-1543362906-acfc16c623a2?q=80&w=1000&auto=format&fit=crop'" class="relative rounded-[50px] w-full h-[400px] md:h-[600px] object-cover shadow-2xl transition hover:scale-[1.01]">
                         
                         <!-- Floating Badge -->
                         <div class="absolute -bottom-10 -left-10 bg-white p-6 rounded-[30px] shadow-2xl border-4 border-[#FFFBEB] transform -rotate-6 hidden md:block">
                            <div class="text-center">
                                <span class="block text-4xl mb-1">🏺</span>
                                <span class="block font-black text-gray-900 fredoka leading-none">TRADITION</span>
                                <span class="text-[10px] text-[#19DC7E] font-black tracking-widest uppercase">REVIVED</span>
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
                        <h3 class="text-3xl font-['Fredoka'] font-black mb-4">A Quick Tip for the Perfect Meal</h3>
                        <p class="text-gray-400 text-lg max-w-3xl leading-relaxed">In dried form, the vegetables may feel hard or chewy, which is natural. Once soaked or cooked, they soften and become ready for use in rice dishes, curries, and traditional meals. It is not just dehydrated vegetables — it is a piece of Kashmiri heritage, thoughtfully prepared and delivered to your doorstep.</p>
                    </div>
                </div>
                <!-- Decor -->
                <div class="absolute -right-20 -bottom-20 w-80 h-80 bg-[#19DC7E] rounded-full blur-[120px] opacity-10"></div>
            </div>
        </div>
    </section>

    <!-- MISSION & VISION SECTION -->
    <section class="py-32 px-6">
        <div class="container mx-auto max-w-6xl">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-12">
                
                <!-- Mission Card -->
                <div class="bg-white p-12 md:p-16 rounded-[60px] border border-gray-100 shadow-sm hover:shadow-2xl hover:-translate-y-2 transition-all duration-500 group anim-up">
                    <div class="w-20 h-20 bg-[#19DC7E]/10 rounded-3xl flex items-center justify-center text-[#19DC7E] text-4xl mb-10 group-hover:scale-110 transition-transform">
                        <i class="fas fa-rocket"></i>
                    </div>
                    <h2 class="text-4xl font-['Fredoka'] font-black text-gray-900 mb-6">Our Mission</h2>
                    <p class="text-xl text-gray-500 leading-relaxed font-medium">Our mission is to make healthy snacking affordable, accessible, and convenient for everyone.</p>
                </div>

                <!-- Vision Card -->
                <div class="bg-gray-900 p-12 md:p-16 rounded-[60px] text-white shadow-2xl hover:scale-[1.02] transition-all duration-500 group anim-up delay-200">
                    <div class="w-20 h-20 bg-[#19DC7E] rounded-3xl flex items-center justify-center text-black text-4xl mb-10 group-hover:rotate-12 transition-transform shadow-lg shadow-green-500/20">
                        <i class="fas fa-eye"></i>
                    </div>
                    <h2 class="text-4xl font-['Fredoka'] font-black text-white mb-6">Our Vision</h2>
                    <p class="text-xl text-gray-400 leading-relaxed font-medium">To build Driyum into one of India’s leading healthy snacking brands, starting from Kashmir and reaching across the country.</p>
                </div>

            </div>

            <!-- Vision Pillars -->
            <div class="mt-16 grid grid-cols-2 md:grid-cols-4 gap-4 anim-up delay-400">
                <div class="bg-white p-6 rounded-[30px] border border-gray-100 text-center">
                    <span class="block text-2xl mb-2">⚡</span>
                    <span class="text-[10px] font-black uppercase tracking-widest text-[#19DC7E]">Clean Energy</span>
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
            <div class="bg-[#19DC7E] rounded-[60px] p-12 md:p-20 text-center relative overflow-hidden anim-up">
                <div class="relative z-10">
                    <h2 class="text-5xl md:text-7xl font-['Fredoka'] font-black text-black mb-8">Ready to snack better?</h2>
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
