<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

if (empty($_SESSION['cart'])) {
    header("Location: " . get_url('shop.php'));
    exit;
}

// Calculate Total
$subtotal = 0;
$ids = implode(',', array_keys($_SESSION['cart']));
$products = fetch_all("SELECT * FROM products WHERE id IN ($ids)");
foreach ($products as $p) $subtotal += $p['price'] * $_SESSION['cart'][$p['id']];
$shipping = ($subtotal >= 500) ? 0 : 50;
$tax = ceil($subtotal * 0.05);
$total = $subtotal + $shipping + $tax;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php 
    $page_title = 'Secure Payment';
    include 'includes/head.php'; 
    ?>
</head>
<body class="bg-[#F8FAFC]">

    <!-- Header -->
    <header class="bg-white border-b border-gray-100 py-4 mb-8">
        <div class="container mx-auto px-4 flex justify-between items-center">
            <div class="flex items-center gap-2">
                 <i class="fas fa-lock text-[#19DC7E] text-2xl"></i>
                 <span class="font-bold font-['Fredoka'] text-gray-800 text-lg">Secure Gateway</span>
            </div>
            <div class="text-sm text-gray-400 font-['Outfit']">ID: <?php echo uniqid('TRX-'); ?></div>
        </div>
    </header>

    <div class="container mx-auto px-4 max-w-4xl">
        <div class="flex flex-col md:flex-row gap-8">
            
            <!-- LEFT: PAYMENT METHODS -->
            <div class="flex-1 space-y-6">
                <h1 class="text-2xl font-bold font-['Fredoka'] mb-6">Select Payment Method</h1>
                
                <form action="<?php echo get_url('place-order'); ?>" method="POST" id="payment-form">
                    <input type="hidden" name="total" value="<?php echo $total; ?>">

                    <div class="space-y-4">
                        <!-- UPI -->
                        <label class="payment-option card-chunky p-6 flex items-center gap-4 cursor-pointer hover:border-[#19DC7E] active-border transition bg-white relative overflow-hidden group">
                            <input type="radio" name="method" value="upi" class="w-5 h-5 text-[#19DC7E] focus:ring-[#19DC7E]">
                            <div class="w-12 h-12 bg-gray-50 rounded-xl flex items-center justify-center text-xl"><i class="fas fa-mobile-alt"></i></div>
                            <div class="flex-1">
                                <h3 class="font-bold text-gray-900">UPI / QR Code</h3>
                                <p class="text-xs text-gray-500">GooglePay, PhonePe, Paytm</p>
                            </div>
                            <i class="fas fa-check-circle text-[#19DC7E] opacity-0 group-hover:opacity-100 transition"></i>
                        </label>

                        <!-- CARD -->
                        <label class="payment-option card-chunky p-6 flex items-center gap-4 cursor-pointer hover:border-[#19DC7E] transition bg-white relative overflow-hidden group">
                            <input type="radio" name="method" value="card" class="w-5 h-5 text-[#19DC7E] focus:ring-[#19DC7E]">
                            <div class="w-12 h-12 bg-gray-50 rounded-xl flex items-center justify-center text-xl"><i class="fas fa-credit-card"></i></div>
                            <div class="flex-1">
                                <h3 class="font-bold text-gray-900">Credit / Debit Card</h3>
                                <p class="text-xs text-gray-500">Visa, Mastercard, RuPay</p>
                            </div>
                        </label>

                        <!-- COD -->
                        <label class="payment-option card-chunky p-6 flex items-center gap-4 cursor-pointer hover:border-[#19DC7E] transition bg-white relative overflow-hidden group">
                            <input type="radio" name="method" value="cod" checked class="w-5 h-5 text-[#19DC7E] focus:ring-[#19DC7E]">
                            <div class="w-12 h-12 bg-green-50 rounded-xl flex items-center justify-center text-xl text-[#19DC7E]"><i class="fas fa-money-bill-wave"></i></div>
                            <div class="flex-1">
                                <h3 class="font-bold text-gray-900">Cash on Delivery</h3>
                                <p class="text-xs text-gray-500">Pay when you receive</p>
                            </div>
                        </label>
                    </div>

                    <button type="button" onclick="processPayment()" class="btn-chunky btn-primary w-full mt-8 py-4 text-lg shadow-xl hover:scale-[1.02] flex items-center justify-center gap-3">
                        Pay ₹<?php echo $total; ?> <i class="fas fa-lock text-sm opacity-70"></i>
                    </button>
                </form>

                <p class="text-center text-xs text-gray-400 mt-4"><i class="fas fa-shield-alt"></i> PCI DSS Compliant Secure Transaction</p>
            </div>

            <!-- RIGHT: SUMMARY -->
            <div class="w-full md:w-80">
                <div class="bg-gray-100 rounded-[2rem] p-6 sticky top-24">
                    <h3 class="font-bold text-gray-500 uppercase text-xs tracking-wider mb-4">Amount to Pay</h3>
                    <div class="text-4xl font-black font-['Fredoka'] text-gray-900 mb-6">₹<?php echo $total; ?></div>
                    
                    <div class="space-y-4 border-t border-gray-200 pt-4">
                        <div class="flex justify-between text-sm text-gray-600">
                            <span>Order Value</span>
                            <span>₹<?php echo $subtotal; ?></span>
                        </div>
                        <div class="flex justify-between text-sm text-gray-600">
                            <span>Shipping</span>
                            <span><?php echo $shipping == 0 ? 'FREE' : '₹'.$shipping; ?></span>
                        </div>
                         <div class="flex justify-between text-sm text-gray-600">
                            <span>Tax</span>
                            <span>₹<?php echo $tax; ?></span>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <!-- Processing Modal -->
    <div id="processing-modal" class="fixed inset-0 bg-white/95 z-[100] hidden flex-col items-center justify-center text-center">
        <div class="w-24 h-24 border-4 border-[#19DC7E] border-t-transparent rounded-full animate-spin mb-8"></div>
        <h2 class="text-3xl font-bold font-['Fredoka'] text-gray-900 mb-2">Processing Payment</h2>
        <p class="text-gray-500">Please do not close this window...</p>
    </div>

    <script>
    function processPayment() {
        // Show Processing Overlay
        const modal = document.getElementById('processing-modal');
        modal.classList.remove('hidden');
        modal.classList.add('flex');

        // Simulate Network Delay -> Then Submit
        setTimeout(() => {
            document.getElementById('payment-form').submit();
        }, 2500);
    }
    </script>

</body>
</html>
