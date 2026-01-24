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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Confirmed! - DRIYUM</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/chunky.css">
    <script src="https://cdn.jsdelivr.net/npm/canvas-confetti@1.6.0/dist/confetti.browser.min.js"></script>
</head>
<body class="bg-[#FFFBEB] flex items-center justify-center min-h-screen p-4 overflow-hidden">

    <div class="max-w-xl w-full text-center relative z-10">
        
        <!-- Success Card -->
        <div class="bg-white rounded-[3rem] p-8 md:p-12 shadow-2xl border-2 border-green-50 relative anim-up">
            <div class="w-24 h-24 bg-[#19DC7E] rounded-full flex items-center justify-center text-white text-5xl mx-auto mb-8 shadow-lg animate-[bounce_1s_infinite]">
                <i class="fas fa-check"></i>
            </div>

            <h1 class="text-4xl md:text-5xl font-['Fredoka'] font-bold text-gray-900 mb-4">You're Awesome!</h1>
            <p class="text-xl text-gray-500 font-['Outfit'] mb-8">Your order <span class="text-black font-bold">#<?php echo $order_num; ?></span> has been placed successfully.</p>

            <div class="bg-gray-50 rounded-2xl p-6 mb-8 text-left">
                <div class="flex justify-between items-center mb-4">
                    <span class="text-gray-500 text-sm uppercase font-bold">Estimated Delivery</span>
                    <span class="text-gray-900 font-bold">
                        <?php 
                            if ($shipping_method) {
                                $created_at = strtotime($order['created_at']);
                                echo date('M j', strtotime("+{$shipping_method['min_days']} days", $created_at)) . " - " . date('M j', strtotime("+{$shipping_method['max_days']} days", $created_at));
                            } else {
                                echo date('M j', strtotime('+4 days')) . " - " . date('M j', strtotime('+6 days'));
                            }
                        ?>
                    </span>
                </div>
                <div class="w-full bg-gray-200 rounded-full h-2">
                    <div class="bg-[#19DC7E] h-2 rounded-full w-[20%]"></div>
                </div>
                <div class="flex justify-between text-xs text-gray-400 mt-2">
                    <span>Ordered</span>
                    <span>Packed</span>
                    <span>Shipped</span>
                    <span>Delivered</span>
                </div>
            </div>

            <div class="flex flex-col gap-3">
                <a href="track.php?id=<?php echo $order_id; ?>" class="btn-chunky btn-primary w-full py-4 text-lg shadow-lg">Track Order</a>
                <a href="<?php echo get_url('index'); ?>" class="btn-chunky btn-outline w-full py-4 border-none text-gray-500 hover:text-black">Continue Shopping</a>
            </div>
            
            <!-- Decor -->
            <div class="absolute -top-6 -right-6 text-6xl rotate-12">🎉</div>
            <div class="absolute -bottom-6 -left-6 text-6xl rotate-[-12deg]">📦</div>
        </div>
        
    </div>

    <!-- Background Decor -->
    <div class="fixed top-20 left-20 w-32 h-32 bg-[#FFD700] rounded-full blur-3xl opacity-20 animate-pulse"></div>
    <div class="fixed bottom-20 right-20 w-40 h-40 bg-[#19DC7E] rounded-full blur-3xl opacity-20 animate-pulse"></div>

    <script>
        // Trigger Massive Confetti
        window.onload = function() {
            const duration = 3000;
            const end = Date.now() + duration;

            (function frame() {
                confetti({
                    particleCount: 5,
                    angle: 60,
                    spread: 55,
                    origin: { x: 0 },
                    colors: ['#19DC7E', '#FFD700', '#FF6B6B']
                });
                confetti({
                    particleCount: 5,
                    angle: 120,
                    spread: 55,
                    origin: { x: 1 },
                    colors: ['#19DC7E', '#FFD700', '#FF6B6B']
                });

                if (Date.now() < end) {
                    requestAnimationFrame(frame);
                }
            }());
        };
    </script>

</body>
</html>
