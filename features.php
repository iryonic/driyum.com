<!DOCTYPE html>
<html lang="en">
<head>
    <?php 
    $page_title = 'Platform Features';
    $page_description = 'Explore the unique features of the DRIYUM platform, from its premium design to its advanced eCommerce capabilities.';
    include 'includes/head.php'; 
    ?>
</head>
<body class="bg-gradient-mesh min-h-screen">
    
    <!-- Hero Header -->
    <div class="relative overflow-hidden bg-gradient-to-br from-gray-900 via-gray-800 to-primary-dark text-white py-20">
        <div class="absolute inset-0 opacity-10">
            <div class="absolute top-0 left-0 w-96 h-96 bg-primary rounded-full blur-3xl"></div>
            <div class="absolute bottom-0 right-0 w-96 h-96 bg-blue-500 rounded-full blur-3xl"></div>
        </div>
        
        <div class="container mx-auto px-4 relative z-10">
            <div class="max-w-4xl mx-auto text-center">
                <div class="inline-flex items-center gap-2 bg-primary/20 backdrop-blur-sm px-6 py-2 rounded-full mb-6 border border-primary/30">
                    <span class="w-2 h-2 bg-primary rounded-full animate-pulse"></span>
                    <span class="text-sm font-medium">Ultra-Premium eCommerce Platform</span>
                </div>
                
                <h1 class="text-5xl md:text-7xl font-bold mb-6 gradient-text">
                    DRIYUM Platform
                </h1>
                <p class="text-xl md:text-2xl text-gray-300 mb-4">
                    Next-Generation Food eCommerce Experience
                </p>
                <p class="text-lg text-gray-400 mb-8">
                    Built with cutting-edge technologies & premium design patterns
                </p>
                
                <div class="flex flex-wrap justify-center gap-4 mb-12">
                    <div class="glass-dark px-6 py-3 rounded-xl">
                        <div class="text-2xl font-bold text-primary" data-counter="100">0</div>
                        <div class="text-sm text-gray-300">% Dynamic</div>
                    </div>
                    <div class="glass-dark px-6 py-3 rounded-xl">
                        <div class="text-2xl font-bold text-primary" data-counter="20">0</div>
                        <div class="text-sm text-gray-300">Database Tables</div>
                    </div>
                    <div class="glass-dark px-6 py-3 rounded-xl">
                        <div class="text-2xl font-bold text-primary" data-counter="50">0</div>
                        <div class="text-sm text-gray-300">+ Features</div>
                    </div>
                </div>
                
                <div class="flex flex-wrap justify-center gap-4">
                    <a href="/" class="magnetic-btn bg-primary hover:bg-primary-dark text-white px-8 py-4 rounded-xl font-semibold inline-flex items-center gap-2 transition">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
                        </svg>
                        View Homepage
                    </a>
                    <a href="<?php echo get_url('/test'); ?>" class="glass-dark hover:bg-white/10 text-white px-8 py-4 rounded-xl font-semibold inline-flex items-center gap-2 transition">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        System Status
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Features Grid -->
    <div class="container mx-auto px-4 py-16">
        
        <!-- Core Features -->
        <div class="mb-16 scroll-reveal">
            <h2 class="text-4xl font-bold text-center mb-12 gradient-text">🎯 Core Platform Features</h2>
            <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
                
                <div class="product-card-premium p-6 hover-lift">
                    <div class="w-14 h-14 bg-primary/10 rounded-2xl flex items-center justify-center mb-4">
                        <svg class="w-8 h-8 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold mb-2">Ultra-Fast Performance</h3>
                    <p class="text-gray-600 mb-4">AJAX-powered interactions, lazy loading, optimized queries, and instant page transitions</p>
                    <div class="flex flex-wrap gap-2">
                        <span class="text-xs bg-primary/10 text-primary px-3 py-1 rounded-full">AJAX</span>
                        <span class="text-xs bg-primary/10 text-primary px-3 py-1 rounded-full">Lazy Load</span>
                    </div>
                </div>
                
                <div class="product-card-premium p-6 hover-lift">
                    <div class="w-14 h-14 bg-blue-500/10 rounded-2xl flex items-center justify-center mb-4">
                        <svg class="w-8 h-8 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold mb-2">Mobile-First Design</h3>
                    <p class="text-gray-600 mb-4">App-like experience with bottom navigation, swipe gestures, and touch-optimized UI</p>
                    <div class="flex flex-wrap gap-2">
                        <span class="text-xs bg-blue-500/10 text-blue-500 px-3 py-1 rounded-full">Responsive</span>
                        <span class="text-xs bg-blue-500/10 text-blue-500 px-3 py-1 rounded-full">PWA-Ready</span>
                    </div>
                </div>
                
                <div class="product-card-premium p-6 hover-lift">
                    <div class="w-14 h-14 bg-purple-500/10 rounded-2xl flex items-center justify-center mb-4">
                        <svg class="w-8 h-8 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold mb-2">Enterprise Security</h3>
                    <p class="text-gray-600 mb-4">CSRF protection, XSS prevention, SQL injection guards, password hashing</p>
                    <div class="flex flex-wrap gap-2">
                        <span class="text-xs bg-purple-500/10 text-purple-500 px-3 py-1 rounded-full">CSRF</span>
                        <span class="text-xs bg-purple-500/10 text-purple-500 px-3 py-1 rounded-full">Encrypted</span>
                    </div>
                </div>
                
                <div class="product-card-premium p-6 hover-lift">
                    <div class="w-14 h-14 bg-orange-500/10 rounded-2xl flex items-center justify-center mb-4">
                        <svg class="w-8 h-8 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path>
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold mb-2">Smart Shopping Cart</h3>
                    <p class="text-gray-600 mb-4">AJAX cart updates, real-time inventory, coupon system, stock validation</p>
                    <div class="flex flex-wrap gap-2">
                        <span class="text-xs bg-orange-500/10 text-orange-500 px-3 py-1 rounded-full">Real-time</span>
                        <span class="text-xs bg-orange-500/10 text-orange-500 px-3 py-1 rounded-full">Coupons</span>
                    </div>
                </div>
                
                <div class="product-card-premium p-6 hover-lift">
                    <div class="w-14 h-14 bg-green-500/10 rounded-2xl flex items-center justify-center mb-4">
                        <svg class="w-8 h-8 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold mb-2">Advanced Search</h3>
                    <p class="text-gray-600 mb-4">Full-screen modal, real-time results, category filters, search history</p>
                    <div class="flex flex-wrap gap-2">
                        <span class="text-xs bg-green-500/10 text-green-500 px-3 py-1 rounded-full">Instant</span>
                        <span class="text-xs bg-green-500/10 text-green-500 px-3 py-1 rounded-full">Smart</span>
                    </div>
                </div>
                
                <div class="product-card-premium p-6 hover-lift">
                    <div class="w-14 h-14 bg-red-500/10 rounded-2xl flex items-center justify-center mb-4">
                        <svg class="w-8 h-8 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"></path>
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold mb-2">Wishlist System</h3>
                    <p class="text-gray-600 mb-4">Save favorites, quick add, sync across devices, email notifications</p>
                    <div class="flex flex-wrap gap-2">
                        <span class="text-xs bg-red-500/10 text-red-500 px-3 py-1 rounded-full">Synced</span>
                        <span class="text-xs bg-red-500/10 text-red-500 px-3 py-1 rounded-full">Saved</span>
                    </div>
                </div>
                
            </div>
        </div>
        
        <!-- UI/UX Features -->
        <div class="mb-16 scroll-reveal">
            <h2 class="text-4xl font-bold text-center mb-12 gradient-text">✨ Premium UI/UX Features</h2>
            <div class="grid md:grid-cols-2 gap-8">
                
                <div class="glass p-8 rounded-3xl hover-lift">
                    <h3 class="text-2xl font-bold mb-4 flex items-center gap-3">
                        <span class="text-3xl">🎨</span>
                        Visual Excellence
                    </h3>
                    <ul class="space-y-3 text-gray-700">
                        <li class="flex items-center gap-2">
                            <svg class="w-5 h-5 text-primary flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                            Clean and responsive typography and layout
                        </li>
                        <li class="flex items-center gap-2">
                            <svg class="w-5 h-5 text-primary flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                            Optimized image loading and performance
                        </li>
                    </ul>
                </div>
                
                <div class="glass p-8 rounded-3xl hover-lift">
                    <h3 class="text-2xl font-bold mb-4 flex items-center gap-3">
                        <span class="text-3xl">⚡</span>
                        Micro-Interactions
                    </h3>
                    <ul class="space-y-3 text-gray-700">
                        <li class="flex items-center gap-2">
                            <svg class="w-5 h-5 text-primary flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                            Scroll reveal animations
                        </li>
                        <li class="flex items-center gap-2">
                            <svg class="w-5 h-5 text-primary flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                            Magnetic button hover effects
                        </li>
                        <li class="flex items-center gap-2">
                            <svg class="w-5 h-5 text-primary flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                            Ripple click effects
                        </li>
                        <li class="flex items-center gap-2">
                            <svg class="w-5 h-5 text-primary flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                            Shimmer hover animations
                        </li>
                        <li class="flex items-center gap-2">
                            <svg class="w-5 h-5 text-primary flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                            Parallax scrolling
                        </li>
                        <li class="flex items-center gap-2">
                            <svg class="w-5 h-5 text-primary flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                            Haptic feedback (mobile)
                        </li>
                    </ul>
                </div>
                
            </div>
        </div>
        
        <!-- Tech Stack -->
        <div class="mb-16 scroll-reveal">
            <h2 class="text-4xl font-bold text-center mb-12 gradient-text">🛠️ Technology Stack</h2>
            <div class="grid md:grid-cols-4 gap-6">
                <div class="text-center p-6 glass rounded-2xl hover-scale">
                    <div class="text-4xl mb-3">🐘</div>
                    <h4 class="font-bold mb-2">PHP 8.2</h4>
                    <p class="text-sm text-gray-600">Procedural backend</p>
                </div>
                <div class="text-center p-6 glass rounded-2xl hover-scale">
                    <div class="text-4xl mb-3">🗄️</div>
                    <h4 class="font-bold mb-2">MySQL</h4>
                    <p class="text-sm text-gray-600">Relational database</p>
                </div>
                <div class="text-center p-6 glass rounded-2xl hover-scale">
                    <div class="text-4xl mb-3">🎨</div>
                    <h4 class="font-bold mb-2">Tailwind CSS</h4>
                    <p class="text-sm text-gray-600">Utility-first styling</p>
                </div>
                <div class="text-center p-6 glass rounded-2xl hover-scale">
                    <div class="text-4xl mb-3">⚡</div>
                    <h4 class="font-bold mb-2">Vanilla JS</h4>
                    <p class="text-sm text-gray-600">ES6+ Modern JS</p>
                </div>
            </div>
        </div>
        
        <!-- CTA Section -->
        <div class="text-center scroll-reveal">
            <div class="glass-dark p-12 rounded-3xl max-w-3xl mx-auto">
                <h2 class="text-3xl font-bold text-white mb-4">Ready to Experience Excellence?</h2>
                <p class="text-gray-300 mb-8">Explore the full platform with all premium features</p>
                <div class="flex flex-wrap justify-center gap-4">
                    <a href="/" class="magnetic-btn bg-primary hover:bg-primary-dark text-white px-10 py-4 rounded-xl font-semibold inline-flex items-center gap-2">
                        Launch Platform
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"></path>
                        </svg>
                    </a>
                    <a href="/README.md" class="glass hover:bg-white/10 text-white px-10 py-4 rounded-xl font-semibold">
                        View Documentation
                    </a>
                </div>
            </div>
        </div>
        
    </div>
    

    <script>
        console.log('🚀 DRIYUM Features Page Loaded');
    </script>
</body>
</html>


