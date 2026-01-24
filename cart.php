<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

// Handle Quantity Updates
if (isset($_POST['update_qty'])) {
    $pid = (int)$_POST['product_id'];
    $qty = (int)$_POST['quantity'];
    if ($qty <= 0) unset($_SESSION['cart'][$pid]);
    else $_SESSION['cart'][$pid] = $qty;
    header("Location: " . get_url('cart'));
    exit;
}

// Handle Remove
if (isset($_GET['remove'])) {
    $pid = (int)$_GET['remove'];
    unset($_SESSION['cart'][$pid]);
    header("Location: " . get_url('cart'));
    exit;
}

// Fetch Cart Data
$products = [];
$subtotal = 0;
if (!empty($_SESSION['cart'])) {
    $ids = implode(',', array_keys($_SESSION['cart']));
    $products = fetch_all("SELECT * FROM products WHERE id IN ($ids)");
}

// Free Shipping Logic
$shipping_threshold = (float)get_setting('free_shipping_threshold', 500);

// Coupon Logic
$coupon_discount = 0;
if (isset($_SESSION['coupon'])) {
    // We need subtotal to validate coupon
    $temp_subtotal = 0;
    if (!empty($_SESSION['cart'])) {
        foreach ($products as $p) {
            $temp_subtotal += $p['price'] * $_SESSION['cart'][$p['id']];
        }
    }
    
    $coupon_val = validate_coupon($_SESSION['coupon']['code'], $temp_subtotal);
    if ($coupon_val['valid']) {
        $coupon_discount = $coupon_val['discount'];
        $_SESSION['coupon']['discount'] = $coupon_discount;
    } else {
        unset($_SESSION['coupon']);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php 
    $page_title = 'Your Bag';
    include 'includes/head.php'; 
    ?>

</head>
<body class="bg-[#FFFBEB]">

    <?php include 'includes/header.php'; ?>

    <div class="container mx-auto px-4 py-12">
        <h1 class="text-4xl md:text-5xl font-['Fredoka'] font-bold mb-8 text-center text-gray-900">Your Stash 🧺</h1>

        <?php if (empty($products)): ?>
            <div class="max-w-md mx-auto text-center py-20 bg-white rounded-[3rem] shadow-sm border-2 border-dashed border-gray-200 anim-up">
                <div class="text-8xl mb-6 animate-bounce">🛒</div>
                <h2 class="text-2xl font-bold font-['Fredoka'] text-gray-400 mb-4">Empty Bag? Tragic.</h2>
                <p class="text-gray-500 mb-8 px-8">Our snacks are lonely. Give them a home.</p>
                <a href="<?php echo get_url('shop'); ?>" class="btn-chunky btn-primary shadow-lg">Stock Up Now</a>
            </div>
        <?php else: ?>
            <div class="flex flex-col lg:flex-row gap-8 items-start">
                
                <!-- Cart Items -->
                <div class="flex-1 space-y-6 w-full">
                    
                    <!-- Free Shipping Tracker -->
                     <?php 
                        foreach ($products as $p) $subtotal += $p['price'] * $_SESSION['cart'][$p['id']];
                        $progress = min(100, ($subtotal / $shipping_threshold) * 100);
                        $remaining = $shipping_threshold - $subtotal;
                    ?>
                    <div class="bg-white p-6 rounded-[2rem] border border-gray-100 shadow-sm mb-4">
                        <?php if ($remaining > 0): ?>
                            <p class="font-['Fredoka'] font-bold text-gray-700 mb-2">
                                Add <span class="text-[#19DC7E]">₹<?php echo $remaining; ?></span> more for <span class="text-[#19DC7E]">FREE Shipping!</span> 🚚
                            </p>
                        <?php else: ?>
                            <p class="font-['Fredoka'] font-bold text-[#19DC7E] mb-2">
                                🎉 You've unlocked FREE Shipping!
                            </p>
                        <?php endif; ?>
                    </div>

                    <?php 
                    // Reset Subtotal for Loop display
                    $subtotal = 0; 
                    foreach ($products as $p): 
                        $qty = $_SESSION['cart'][$p['id']];
                        $line_total = $p['price'] * $qty;
                        $subtotal += $line_total;
                    ?>
                    <div class="card-chunky flex flex-col sm:flex-row items-center gap-6 p-6 anim-up hover:border-[#19DC7E] transition">
                        <!-- Image -->
                        <div class="w-full sm:w-32 h-32 bg-gray-50 rounded-2xl flex-shrink-0 relative overflow-hidden">
                            <img src="<?php echo get_url(ltrim($p['image'], './')); ?>" class="w-full h-full object-cover">
                        </div>

                        <!-- Details -->
                        <div class="flex-1 text-center sm:text-left">
                            <a href="<?php echo product_url($p['slug']); ?>" class="font-bold text-xl font-['Fredoka'] text-gray-900 hover:text-[#19DC7E] transition"><?php echo $p['name']; ?></a>
                            <p class="text-gray-500 text-sm font-['Outfit'] mb-2"><?php echo $p['weight']; ?></p>
                            <div class="text-gray-400 font-bold text-sm">Unit: ₹<?php echo $p['price']; ?></div>
                        </div>

                        <!-- Quantity Actions -->
                        <div class="flex items-center gap-4 bg-gray-50 rounded-full px-4 py-2 border border-gray-200">
                            <form action="" method="POST" class="flex items-center">
                                <input type="hidden" name="product_id" value="<?php echo $p['id']; ?>">
                                <input type="hidden" name="update_qty" value="1">
                                <button type="submit" name="quantity" value="<?php echo $qty - 1; ?>" class="w-8 h-8 flex items-center justify-center text-gray-500 hover:text-black hover:bg-white hover:rounded-full hover:shadow-sm transition">
                                    <i class="fas fa-minus"></i>
                                </button>
                                <span class="font-bold w-10 text-center font-['Fredoka'] text-lg"><?php echo $qty; ?></span>
                                <button type="submit" name="quantity" value="<?php echo $qty + 1; ?>" class="w-8 h-8 flex items-center justify-center text-gray-500 hover:text-black hover:bg-white hover:rounded-full hover:shadow-sm transition">
                                    <i class="fas fa-plus"></i>
                                </button>
                            </form>
                        </div>

                        <!-- Total & Remove -->
                        <div class="text-right flex flex-col items-end gap-2 w-full sm:w-auto">
                            <span class="font-black text-2xl font-['Fredoka'] text-gray-900">₹<?php echo $line_total; ?></span>
                            <a href="?remove=<?php echo $p['id']; ?>" class="text-red-400 hover:text-red-600 text-xs font-bold uppercase tracking-wider bg-red-50 hover:bg-red-100 px-3 py-1 rounded-full transition"><i class="fas fa-trash mr-1"></i> Remove</a>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>

                <!-- Summary Sidebar -->
                <div class="w-full lg:w-96 flex-shrink-0">
                    <div class="card-chunky p-8 sticky top-24 bg-white border-2 border-gray-100">
                        <h3 class="font-bold text-2xl font-['Fredoka'] mb-6">Order Summary</h3>
                        
                        <!-- Coupon -->
                        <div class="mb-6">
                            <?php if (isset($_SESSION['coupon'])): ?>
                                <div class="bg-green-50 border-2 border-green-100 rounded-2xl p-4 flex items-center justify-between">
                                    <div>
                                        <p class="text-[10px] font-black uppercase tracking-widest text-green-600 mb-1">Coupon Applied</p>
                                        <h4 class="font-black text-gray-900 fredoka"><?php echo $_SESSION['coupon']['code']; ?></h4>
                                    </div>
                                    <button onclick="removeCoupon()" class="w-8 h-8 bg-white text-red-500 rounded-full flex items-center justify-center shadow-sm hover:bg-red-50 transition">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </div>
                            <?php else: ?>
                                <div class="flex gap-2">
                                    <input type="text" id="coupon-code" placeholder="Promo Code" class="input-chunky text-sm py-2 px-4 border-2 bg-gray-50 focus:bg-white flex-1">
                                    <button onclick="applyCoupon()" class="btn-chunky bg-black text-white px-4 py-2 text-xs">Apply</button>
                                </div>
                                <div id="coupon-message" class="text-[10px] font-bold mt-2 ml-2"></div>
                            <?php endif; ?>
                        </div>

                        <div class="space-y-4 mb-6 text-gray-600 font-['Outfit']">
                            <div class="flex justify-between">
                                <span>Subtotal</span>
                                <span class="font-bold text-gray-900">₹<?php echo $subtotal; ?></span>
                            </div>
                            <!-- Pincode Checker in Cart -->
                            <div class="pt-4 border-t border-gray-100">
                                <label class="text-[10px] font-black uppercase tracking-widest text-gray-400 mb-2 block">Shipping Estimate</label>
                                <div class="flex gap-2">
                                    <input type="text" id="cart_pincode" placeholder="Pincode" class="flex-1 bg-gray-50 border-none rounded-xl px-4 py-2 text-sm font-bold focus:ring-1 focus:ring-[#19DC7E]">
                                    <button onclick="checkCartShipping()" class="bg-gray-900 text-white px-4 py-2 rounded-xl text-[10px] font-black uppercase tracking-widest hover:bg-[#19DC7E] hover:text-black transition-all">Check</button>
                                </div>
                                <div id="cart_shipping_result" class="mt-4 hidden space-y-2"></div>
                            </div>
                            <?php if($coupon_discount > 0): ?>
                                <div class="flex justify-between text-[#19DC7E]">
                                    <span>Discount (<?php echo $_SESSION['coupon']['code']; ?>)</span>
                                    <span class="font-bold">- ₹<?php echo $coupon_discount; ?></span>
                                </div>
                            <?php endif; ?>

                            <div class="flex justify-between text-[#19DC7E]">
                                <span>Shipping</span>
                                <span class="font-bold"><?php echo $remaining <= 0 ? 'FREE' : '₹50'; ?></span>
                            </div>
                            <?php 
                                $tax_perc = (float)get_setting('tax_percentage', 5);
                                $shipping_cost = ($remaining <= 0 ? 0 : 50);
                                $taxable_amount = max(0, $subtotal - $coupon_discount + $shipping_cost);
                                $tax_amount = ceil($taxable_amount * ($tax_perc / 100));
                            ?>
                            <div class="flex justify-between">
                                <span>Tax (<?php echo $tax_perc; ?>%)</span>
                                <span class="font-bold text-gray-900">₹<?php echo $tax_amount; ?></span>
                            </div>
                        </div>

                        <div class="border-t-2 border-dashed border-gray-200 pt-6 mb-8">
                            <div class="flex justify-between text-3xl font-black font-['Fredoka'] mb-2">
                                <span>Total</span>
                                <span id="cart-total-display">₹<?php echo ($subtotal - $coupon_discount) + $shipping_cost + $tax_amount; ?></span>
                            </div>
                            <p class="text-right text-xs text-gray-400">Including all taxes</p>
                        </div>

                        <a href="<?php echo get_url('checkout'); ?>" class="btn-chunky btn-primary w-full text-lg shadow-xl py-4 flex items-center justify-center gap-3 group">
                            Checkout Now <i class="fas fa-arrow-right group-hover:translate-x-1 transition"></i>
                        </a>
                        
                        <div class="mt-6 flex flex-col gap-3 text-center">
                            <p class="text-xs text-gray-400"><i class="fas fa-lock text-[#19DC7E]"></i> 256-bit Secure SSL Payment</p>
                            <img src="<?php echo get_url('assets/images/razorpay-icon.png'); ?>" class="h-12 mx-auto opacity-50 grayscale hover:grayscale-0 transition">
                        </div>
                    </div>
                </div>

            </div>
        <?php endif; ?>
    </div>

    <script>
    async function applyCoupon() {
        const code = document.getElementById('coupon-code').value;
        const messageEl = document.getElementById('coupon-message');
        const total = <?php echo $subtotal; ?>;

        if(!code) return;

        messageEl.className = "text-[10px] font-bold mt-2 ml-2 text-gray-400";
        messageEl.textContent = "Validating...";

        const formData = new FormData();
        formData.append('code', code);
        formData.append('total', total);

        try {
            const res = await fetch('<?php echo get_url('api/validate_coupon.php'); ?>', { method: 'POST', body: formData });
            const data = await res.json();

            if(data.success) {
                messageEl.className = "text-[10px] font-bold mt-2 ml-2 text-[#19DC7E]";
                messageEl.textContent = data.message;
                setTimeout(() => location.reload(), 800);
            } else {
                messageEl.className = "text-[10px] font-bold mt-2 ml-2 text-red-500";
                messageEl.textContent = data.message;
            }
        } catch(e) {
            messageEl.textContent = "Error applying coupon";
        }
    }

    async function removeCoupon() {
        await fetch('<?php echo get_url('api/cart.php'); ?>?action=remove_coupon', { method: 'POST' });
        location.reload();
    }

    async function checkCartShipping() {
        const pincode = document.getElementById('cart_pincode').value;
        const resultDiv = document.getElementById('cart_shipping_result');
        
        if(!pincode || pincode.length < 6) {
            showToast('Please enter a valid pincode', 'warning');
            return;
        }

        resultDiv.innerHTML = '<div class="flex items-center gap-3 text-gray-400 font-bold"><i class="fas fa-spinner fa-spin"></i> Checking...</div>';
        resultDiv.classList.remove('hidden');

        try {
            const response = await fetch(`<?php echo get_url('api/shipping.php'); ?>?pincode=${pincode}`);
            const data = await response.json();

            if(data.success && data.methods.length > 0) {
                let html = '<div class="space-y-4 pt-4 border-t border-gray-50">';
                data.methods.forEach(m => {
                    html += `
                        <div class="bg-green-50/50 p-3 rounded-xl border border-green-100 flex items-center justify-between group hover:bg-green-50 transition-colors">
                            <div>
                                <p class="font-black text-[8px] uppercase tracking-widest text-green-700">${m.display_name}</p>
                                <p class="text-[8px] text-gray-500 font-medium">Delivered in ${m.min_days}-${m.max_days} days</p>
                            </div>
                            <div class="text-right">
                                <p class="font-black text-sm text-gray-900 leading-none">₹${m.cost}</p>
                            </div>
                        </div>
                    `;
                });
                html += '</div>';
                resultDiv.innerHTML = html;
            } else {
                resultDiv.innerHTML = `<div class="p-4 bg-red-50 text-red-500 rounded-xl text-[10px] font-black uppercase tracking-widest"><i class="fas fa-exclamation-triangle mr-2"></i> ${data.message || 'Delivery not available'}</div>`;
            }
        } catch(e) {
            resultDiv.innerHTML = '<div class="p-4 bg-red-50 text-red-500 rounded-xl text-[10px] font-black uppercase tracking-widest">Error checking delivery</div>';
        }
    }
    </script>

    <?php include 'includes/footer.php'; ?>
</body>
</html>
