<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

// Get Order Details from URL
$order_id = $_GET['id'] ?? 'N/A';
$order_num = $_GET['num'] ?? 'N/A';
$order = fetch_one("SELECT * FROM orders WHERE id = ?", [$order_id]);
$shipping_method = null;
if ($order && !empty($order['shipping_method_id'])) {
    $shipping_method = fetch_one("SELECT * FROM shipping_methods WHERE id = ?", [$order['shipping_method_id']]);
}

// Fetch Order Items for GA4
$order_items = [];
if ($order) {
    $order_items = fetch_all("SELECT oi.*, p.name as product_name, p.category_id FROM order_items oi JOIN products p ON oi.product_id = p.id WHERE order_id = ?", [$order['id']]);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>

<!-- Google Tag Manager -->
<script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=
'https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
})(window,document,'script','dataLayer','GTM-T3LPLX64');</script>
<!-- End Google Tag Manager -->

    <?php 
    $page_title = 'Order Confirmed - Driyum';
    include 'includes/head.php'; 
    ?>
    <script src="https://cdn.jsdelivr.net/npm/canvas-confetti@1.6.0/dist/confetti.browser.min.js"></script>
    <style>
        .premium-success-card {
            background: rgba(255, 255, 255, 0.85);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.5);
            box-shadow: 0 40px 100px rgba(0, 0, 0, 0.1);
        }
        .success-icon-wrap {
            background: linear-gradient(135deg, #19DC7E, #14B86A);
            box-shadow: 0 10px 30px rgba(25, 220, 126, 0.3);
        }
        @keyframes float-gentle {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-10px); }
        }
        .anim-float { animation: float-gentle 4s ease-in-out infinite; }
        
        /* Particle Background */
        #success-particles {
            position: fixed;
            inset: 0;
            pointer-events: none;
            z-index: 1;
        }
    </style>
</head>
<body class="bg-[#FFFEDC] min-h-screen flex items-center justify-center p-4 md:p-8 overflow-x-hidden selection:bg-[#19DC7E]/20">

<!-- Google Tag Manager (noscript) -->
<noscript><iframe src="https://www.googletagmanager.com/ns.html?id=GTM-T3LPLX64"
height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
<!-- End Google Tag Manager (noscript) -->

    <!-- ATMOSPHERIC LAYERS -->
    <div class="fixed inset-0 pointer-events-none z-0 opacity-[0.05]" style="background-image: url('https://www.transparenttextures.com/patterns/carbon-fibre.png');"></div>
    <div class="fixed top-[-20%] left-[-10%] w-[60%] h-[60%] bg-[#19DC7E]/10 rounded-full blur-[120px] pointer-events-none animate-pulse"></div>
    <div class="fixed bottom-[-20%] right-[-10%] w-[60%] h-[60%] bg-[#FFD700]/10 rounded-full blur-[120px] pointer-events-none animate-pulse" style="animation-delay: 2s"></div>

    <div class="max-w-2xl w-full relative z-10 flex flex-col items-center">
        
        <!-- Celebration Emojis -->
        <div class="absolute -top-12 -left-8 text-5xl md:text-7xl anim-float" style="animation-delay: 0.5s">🥨</div>
        <div class="absolute -top-20 -right-4 text-5xl md:text-7xl anim-float" style="animation-delay: 1.5s">✨</div>
        <div class="absolute bottom-10 -right-12 text-5xl md:text-7xl anim-float hidden md:block" style="animation-delay: 2.5s">🎁</div>

        <!-- Main Confirmation Card -->
        <div class="premium-success-card w-full rounded-[3.5rem] p-8 md:p-14 text-center relative overflow-hidden anim-up">
            
            <!-- Confetti Cannon Decor -->
            <div class="absolute top-0 inset-x-0 h-1 bg-gradient-to-r from-transparent via-[#19DC7E]/30 to-transparent"></div>

            <div class="success-icon-wrap w-24 h-24 rounded-[30%] flex items-center justify-center text-white text-5xl mx-auto mb-10 transform -rotate-12 hover:rotate-0 transition-transform duration-500">
                <i class="fas fa-check"></i>
            </div>

            <h1 class="text-4xl md:text-6xl font-['Fredoka'] font-black text-[#111827] mb-4 tracking-tight">You're Awesome!</h1>
            <p class="text-lg md:text-xl text-gray-500 font-['Outfit'] mb-10 max-w-md mx-auto leading-relaxed">
                Order <span class="text-[#004F42] font-black underline decoration-[#19DC7E] decoration-4 underline-offset-4">#<?php echo $order ? $order['order_number'] : $order_num; ?></span> is officially on its way to your cravings.
            </p>

            <!-- Delivery Tracker Visual -->
            <div class="bg-[#004F42]/5 border border-[#004F42]/10 rounded-[2.5rem] p-8 mb-10 text-left relative overflow-hidden">
                <div class="flex flex-col md:flex-row md:justify-between md:items-center gap-4 mb-8">
                    <div>
                        <span class="text-[10px] font-black uppercase tracking-[0.2em] text-[#004F42]/60 block mb-1">Estimated Delivery</span>
                        <h3 class="text-2xl font-['Crimson_Pro'] font-black text-[#004F42]">
                            <?php 
                                $created_at = $order ? strtotime($order['created_at']) : time();
                                $min_days = 4;
                                $max_days = 6;

                                if ($order) {
                                    $address_data = json_decode($order['shipping_address'], true);
                                    $pincode = $address_data['zip'] ?? '';
                                    $zone = get_shipping_zone($pincode);
                                    
                                    if ($shipping_method) {
                                        $min_days = ($zone && ($zone['min_days'] > 0 || $zone['max_days'] > 0)) ? $zone['min_days'] : ($shipping_method['min_days'] ?: 4);
                                        $max_days = ($zone && ($zone['min_days'] > 0 || $zone['max_days'] > 0)) ? $zone['max_days'] : ($shipping_method['max_days'] ?: 6);
                                    } elseif ($zone) {
                                        $min_days = $zone['min_days'] > 0 ? $zone['min_days'] : 4;
                                        $max_days = $zone['max_days'] > 0 ? $zone['max_days'] : 6;
                                    }
                                }

                                $min_date = date('M j', strtotime("+$min_days days", $created_at));
                                $max_date = date('M j', strtotime("+$max_days days", $created_at));

                                echo ($min_date === $max_date) ? $min_date : "$min_date - $max_date";
                            ?>
                        </h3>
                    </div>
                    <div class="px-4 py-2 bg-[#19DC7E]/10 rounded-full border border-[#19DC7E]/20">
                        <span class="text-[10px] font-black text-[#14B86A] uppercase tracking-wider flex items-center gap-2">
                            <span class="w-2 h-2 rounded-full bg-[#14B86A] animate-pulse"></span>
                            Shipment Originating
                        </span>
                    </div>
                </div>

                <!-- Tracker Line -->
                <div class="relative h-2.5 w-full bg-[#004F42]/10 rounded-full mb-3 overflow-hidden">
                    <div class="absolute top-0 left-0 h-full bg-[#19DC7E] w-[15%] rounded-full shadow-[0_0_15px_rgba(25,220,126,0.6)]"></div>
                </div>
                <div class="grid grid-cols-4 text-[9px] font-black uppercase tracking-widest text-[#004F42]/40">
                    <span class="text-[#14B86A]">Ordered</span>
                    <span class="text-center">Packed</span>
                    <span class="text-center">Transit</span>
                    <span class="text-right">Home</span>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <a href="track.php?id=<?php echo $order_id; ?>" class="group flex items-center justify-between bg-[#111827] text-white p-5 rounded-[20px] hover:bg-[#19DC7E] hover:text-black transition-all shadow-xl hover:-translate-y-1">
                    <span class="font-black uppercase tracking-widest text-xs">Live Tracking</span>
                    <i class="fas fa-location-arrow group-hover:rotate-45 transition-transform"></i>
                </a>
                <a href="<?php echo get_url('index'); ?>" class="group flex items-center justify-between bg-white border-2 border-gray-100 text-gray-900 p-5 rounded-[20px] hover:border-[#F67E42] hover:text-[#F67E42] transition-all shadow-sm hover:-translate-y-1">
                    <span class="font-black uppercase tracking-widest text-xs">Keep Browsing</span>
                    <i class="fas fa-shopping-bag group-hover:scale-110 transition-transform"></i>
                </a>
            </div>

            <!-- Share the Joy -->
            <div class="mt-8 md:mt-12 pt-6 md:pt-8 border-t border-gray-100 flex flex-col items-center gap-4">
                <span class="text-[10px] font-black uppercase tracking-[0.3em] text-gray-400">Share the crunch</span>
                <div class="flex gap-4">
                    <a href="#" class="w-10 h-10 rounded-full bg-gray-50 flex items-center justify-center text-gray-400 hover:bg-[#1877F2] hover:text-white transition-all"><i class="fab fa-facebook-f"></i></a>
                    <a href="#" class="w-10 h-10 rounded-full bg-gray-50 flex items-center justify-center text-gray-400 hover:bg-[#E1306C] hover:text-white transition-all"><i class="fab fa-instagram"></i></a>
                    <a href="#" class="w-10 h-10 rounded-full bg-gray-50 flex items-center justify-center text-gray-400 hover:bg-[#25D366] hover:text-white transition-all"><i class="fab fa-whatsapp"></i></a>
                </div>
            </div>

        </div>

        <!-- Support Badge -->
        <div class="mt-8 md:mt-10 flex items-center gap-4 px-6 py-3 bg-white/40 backdrop-blur-md rounded-full border border-white/50 anim-up-delayed">
            <div class="w-8 h-8 rounded-full bg-[#004F42] flex items-center justify-center text-white text-[10px]">
                <i class="fas fa-headset"></i>
            </div>
            <p class="text-[10px] md:text-[11px] font-bold text-[#004F42]/70 uppercase tracking-widest">
                Need help? <a href="mailto:support@driyum.com" class="text-[#004F42] underline font-black">Talk to us</a>
            </p>
        </div>
        
    </div>

    <script src="https://cdn.jsdelivr.net/npm/canvas-confetti@1.6.0/dist/confetti.browser.min.js"></script>
    <script>
        function triggerConfetti() {
            if (typeof confetti === 'undefined') {
                console.error('Confetti library not loaded');
                return;
            }

            // Celebration Firework - Progressive Burst
            const duration = 5000;
            const animationEnd = Date.now() + duration;
            const defaults = { startVelocity: 30, spread: 360, ticks: 60, zIndex: 1000 };

            function randomInRange(min, max) {
                return Math.random() * (max - min) + min;
            }

            const interval = setInterval(function() {
                const timeLeft = animationEnd - Date.now();

                if (timeLeft <= 0) {
                    return clearInterval(interval);
                }

                const particleCount = 20 * (timeLeft / duration);
                
                // since particles fall down, start a bit higher than random
                confetti(Object.assign({}, defaults, { 
                    particleCount, 
                    origin: { x: randomInRange(0.1, 0.3), y: Math.random() - 0.1 },
                    colors: ['#19DC7E', '#FFD700', '#F67E42']
                }));
                confetti(Object.assign({}, defaults, { 
                    particleCount, 
                    origin: { x: randomInRange(0.7, 0.9), y: Math.random() - 0.1 },
                    colors: ['#19DC7E', '#FF3E3E', '#F67E42']
                }));
            }, 250);

            // Initial Cannon Blast
            confetti({
                particleCount: 150,
                spread: 70,
                origin: { y: 0.6 },
                zIndex: 1000,
                colors: ['#19DC7E', '#FFD700', '#F67E42', '#ffffff']
            });

            // Delayed Side Blasts
            setTimeout(() => {
                confetti({
                    particleCount: 80,
                    angle: 60,
                    spread: 55,
                    origin: { x: 0, y: 0.6 },
                    zIndex: 1000,
                    colors: ['#19DC7E', '#FFD700']
                });
                confetti({
                    particleCount: 80,
                    angle: 120,
                    spread: 55,
                    origin: { x: 1, y: 0.6 },
                    zIndex: 1000,
                    colors: ['#19DC7E', '#FF3E3E']
                });
            }, 600);
        }

        window.addEventListener('load', function() {
            // GA4 Purchase Event
            <?php if($order && !empty($order_items)): ?>
            window.dataLayer = window.dataLayer || [];
            window.dataLayer.push({ ecommerce: null });
            window.dataLayer.push({
                event: "purchase",
                ecommerce: {
                    transaction_id: "<?php echo $order['order_number']; ?>",
                    value: <?php echo (float)$order['total_amount']; ?>,
                    tax: 0,
                    shipping: <?php echo (float)$order['shipping_cost']; ?>,
                    currency: "INR",
                    coupon: "<?php echo $order['coupon_code'] ?? ''; ?>",
                    items: [
                        <?php foreach($order_items as $item): ?>
                        {
                            item_id: "<?php echo $item['product_id']; ?>",
                            item_name: "<?php echo addslashes($item['product_name']); ?>",
                            price: <?php echo (float)$item['price']; ?>,
                            quantity: <?php echo (int)$item['quantity']; ?>
                        },
                        <?php endforeach; ?>
                    ]
                }
            });
            <?php endif; ?>

            triggerConfetti();
        });
        
        // Secondary trigger for safety
        setTimeout(triggerConfetti, 1000);
    </script>

</body>
</html>
