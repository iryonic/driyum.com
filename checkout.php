<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

// If cart is empty, redirect
if (empty($_SESSION['cart'])) {
    header("Location: " . get_url('shop.php'));
    exit;
}

// Optional Guest Checkout Logic
if (isset($_GET['mode']) && $_GET['mode'] === 'guest') {
    $_SESSION['checkout_mode'] = 'guest';
}

// If user is not logged in and not explicitly in guest mode via URL, 
// and not submitting an order, reset choice to force choice modal.
if (!is_logged_in() && !isset($_GET['mode']) && $_SERVER['REQUEST_METHOD'] !== 'POST') {
    unset($_SESSION['checkout_mode']);
}

// Require Login or Choice
$show_choice_modal = !is_logged_in() && !isset($_SESSION['checkout_mode']);
// require_login(); // Removed for Guest Checkout

$conn = get_db_connection();

// Calculate Totals and Filter Cart
$subtotal = 0;
$cart_keys = array_keys($_SESSION['cart']);

if (!empty($cart_keys)) {
    $ids = implode(',', $cart_keys);
    $products_result = $conn->query("SELECT * FROM products WHERE id IN ($ids)");
    $products_data = [];
    $valid_pids = [];
    
    while($row = $products_result->fetch_assoc()) {
        $products_data[$row['id']] = $row;
        $subtotal += $row['price'] * $_SESSION['cart'][$row['id']];
        $valid_pids[] = $row['id'];
    }

    // Remove invalid products from cart to prevent crashes later
    foreach ($_SESSION['cart'] as $pid => $qty) {
        if (!in_array($pid, $valid_pids)) {
            unset($_SESSION['cart'][$pid]);
        }
    }

    // Re-check if cart is now empty
    if (empty($_SESSION['cart'])) {
        header("Location: " . get_url('shop.php'));
        exit;
    }
} else {
    header("Location: " . get_url('shop.php'));
    exit;
}

// Coupon Logic
$coupon_discount = 0;
if (isset($_SESSION['coupon'])) {
    $coupon_val = validate_coupon($_SESSION['coupon']['code'], $subtotal);
    if ($coupon_val['valid']) {
        $coupon_discount = $coupon_val['discount'];
        $_SESSION['coupon']['discount'] = $coupon_discount; // Update discounted amount
    } else {
        unset($_SESSION['coupon']);
        $error = "Coupon removed: " . $coupon_val['message'];
    }
}

// Affiliate Logic
$affiliate_discount = 0;
$affiliate_id = null;
$affiliate_commission = 0;

if (isset($_SESSION['affiliate'])) {
    $aff_data = $_SESSION['affiliate'];
    $affiliate_id = $aff_data['id'];
    
    // Calculate 10% discount on subtotal
    $affiliate_discount = floor($subtotal * ($aff_data['discount'] / 100));
    
    // Calculate 10% commission on (subtotal - discount)
    // Commission is based on final sale price of items, usually excluding shipping/tax
    // For simplicity, commission is calculated on the discounted subtotal
    $discounted_subtotal = $subtotal - $affiliate_discount - $coupon_discount; // Stack with coupon if any
    $affiliate_commission = floor(max(0, $discounted_subtotal) * ($aff_data['commission'] / 100));
}

// Zip persistence
$zip = $_POST['zip'] ?? $_SESSION['shipping_zip'] ?? '';

$shipping = $_SESSION['shipping_cost'] ?? 0;
$shipping_method_id = $_SESSION['shipping_method_id'] ?? 0;

// Centralized Calculation Logic
function calculate_order_totals($subtotal, $discount, $aff_discount, $shipping) {
    $tax_perc = (float)get_setting('tax_percentage', 5);
    $tax_rate = $tax_perc / 100;
    
    $total_discount = $discount + $aff_discount;
    $taxable_amount = max(0, $subtotal - $total_discount + $shipping);
    $tax = ceil($taxable_amount * $tax_rate);
    $total = ($subtotal - $total_discount) + $shipping + $tax;
    return ['tax' => $tax, 'total' => $total, 'tax_rate' => $tax_perc];
}

$totals = calculate_order_totals($subtotal, $coupon_discount, $affiliate_discount, $shipping);
$tax = $totals['tax'];
$total = $totals['total'];
$tax_perc = $totals['tax_rate'];

$error = "";

// Magic Checkout: Fetch Saved Data
$user_id = is_logged_in() ? $_SESSION['user_id'] : null;
$user_data = $user_id ? fetch_one("SELECT * FROM users WHERE id = ?", [$user_id]) : null;
$saved_address = is_logged_in() ? get_user_default_address($user_id) : null;

// Form pre-fill logic
$form = [
    'email' => $user_data['email'] ?? '',
    'phone' => '',
    'first_name' => '',
    'last_name' => '',
    'address' => '',
    'city' => '',
    'state' => '',
    'zip' => $zip
];

if ($saved_address) {
    $name_parts = explode(' ', $saved_address['name'], 2);
    $form['first_name'] = $name_parts[0] ?? '';
    $form['last_name'] = $name_parts[1] ?? '';
    $form['phone'] = $saved_address['phone'];
    $form['address'] = $saved_address['address_line1'];
    $form['city'] = $saved_address['city'];
    $form['state'] = $saved_address['state'];
    if (empty($zip)) {
        $zip = $saved_address['pincode'];
        $form['zip'] = $zip;
    }
}

// Override with POST data if validation failed previously
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $form['email'] = sanitize_input($_POST['email'] ?? $form['email']);
    $form['phone'] = sanitize_input($_POST['phone'] ?? $form['phone']);
    $form['first_name'] = sanitize_input($_POST['first_name'] ?? $form['first_name']);
    $form['last_name'] = sanitize_input($_POST['last_name'] ?? $form['last_name']);
    $form['address'] = sanitize_input($_POST['address'] ?? $form['address']);
    $form['city'] = sanitize_input($_POST['city'] ?? $form['city']);
    $form['state'] = sanitize_input($_POST['state'] ?? $form['state']);
    $form['zip'] = sanitize_input($_POST['zip'] ?? $form['zip']);
}

// Handle Order Placement
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['place_order'])) {
    try {
        $conn->begin_transaction();

        $user_id = is_logged_in() ? $_SESSION['user_id'] : null;
        $method = $_POST['payment_method'] ?? 'cod';

        // Validate Payment Method Status
        if ($method === 'cod' && get_setting('payment_cod_enabled', 'on') !== 'on') {
            throw new Exception("Cash on Delivery is currently disabled. Please choose another method.");
        }
        if ($method === 'razorpay' && get_setting('payment_online_enabled', 'on') !== 'on') {
            throw new Exception("Online payment is currently disabled. Please choose another method.");
        }

        $status = 'pending';
        $order_number = 'ORD-' . strtoupper(uniqid());

        // Prepare Shipping Details
        $first_name = sanitize_input($_POST['first_name']);
        $last_name = sanitize_input($_POST['last_name']);
        $email = sanitize_input($_POST['email']);
        $phone = sanitize_input($_POST['phone']);
        $address = sanitize_input($_POST['address']);
        $city = sanitize_input($_POST['city']);
        $state = sanitize_input($_POST['state'] ?? '');
        $zip_code = sanitize_input($_POST['zip']);

        $shipping_details = json_encode([
            'name' => $first_name . ' ' . $last_name,
            'email' => $email,
            'phone' => $phone,
            'address' => $address,
            'city' => $city,
            'state' => $state,
            'zip' => $zip_code
        ]);

        // Magic Checkout: Save Address for logged in users
        if ($user_id) {
            save_user_address($user_id, [
                'name' => $first_name . ' ' . $last_name,
                'phone' => $phone,
                'address_line1' => $address,
                'address_line2' => '',
                'city' => $city,
                'state' => $state,
                'pincode' => $zip_code
            ]);
        }

        // Final Coupon Re-validation before order
        $final_coupon_discount = 0;
        if (isset($_SESSION['coupon'])) {
            $coupon_id = $_SESSION['coupon']['id'];
            $coupon_val = validate_coupon($_SESSION['coupon']['code'], $subtotal);
            if ($coupon_val['valid']) {
                $final_coupon_discount = $coupon_val['discount'];
            } else {
                throw new Exception("Coupon is no longer valid: " . $coupon_val['message']);
            }
        }
        
        // Re-calculate Affiliate in POST
        $final_affiliate_discount = 0;
        $final_affiliate_commission = 0;
        $final_affiliate_id = null;
        
        if (isset($_SESSION['affiliate'])) {
            $aff_data = $_SESSION['affiliate'];
            $final_affiliate_id = $aff_data['id'];
            $final_affiliate_discount = floor($subtotal * ($aff_data['discount'] / 100));
            
            $d_subtotal = $subtotal - $final_affiliate_discount - $final_coupon_discount;
            $final_affiliate_commission = floor(max(0, $d_subtotal) * ($aff_data['commission'] / 100));
            
            // Update Affiliate Total Earnings (Pending until order completed ideally, but simplest is tracking it now or via status changes)
            // For now, let's just record it in the order.
            if ($final_affiliate_id) {
                $conn->query("UPDATE affiliates SET total_earnings = total_earnings + $final_affiliate_commission WHERE id = $final_affiliate_id");
            }
        }

        $shipping = (float)($_POST['shipping_cost'] ?? 0);
        $shipping_method_id = $_POST['shipping_method_id'] ?? null;
        
        // Recalculate everything on server to prevent tampering
        $final_totals = calculate_order_totals($subtotal, $final_coupon_discount, $final_affiliate_discount, $shipping);
        $tax = $final_totals['tax'];
        $total = $final_totals['total'];
        
        // Persist zip to session for convenience
        $_SESSION['shipping_zip'] = sanitize_input($_POST['zip']);
        $_SESSION['shipping_cost'] = $shipping;
        $_SESSION['shipping_method_id'] = $shipping_method_id;

        $razorpay_payment_id = sanitize_input($_POST['razorpay_payment_id'] ?? null);
        $payment_status = ($method === 'razorpay') ? 'paid' : 'pending';
        
        // Final Total Discount for DB
        $total_discount_db = $final_coupon_discount + $final_affiliate_discount;

        // Insert Order
        $stmt = $conn->prepare("INSERT INTO orders (order_number, user_id, affiliate_id, subtotal, discount, affiliate_commission, shipping_cost, total, payment_method, order_status, shipping_address, shipping_method_id, razorpay_payment_id, payment_status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("siidddddsssiss", $order_number, $user_id, $final_affiliate_id, $subtotal, $total_discount_db, $final_affiliate_commission, $shipping, $total, $method, $status, $shipping_details, $shipping_method_id, $razorpay_payment_id, $payment_status);
        
        if (!$stmt->execute()) {
            throw new Exception("Failed to insert order: " . $stmt->error);
        }
        
        $order_id = $stmt->insert_id;

        // Insert Order Items
        $stmt_item = $conn->prepare("INSERT INTO order_items (order_id, product_id, quantity, price, subtotal) VALUES (?, ?, ?, ?, ?)");
        foreach ($_SESSION['cart'] as $pid => $qty) {
            if (!isset($products_data[$pid])) continue; // Skip deleted items
            $price = $products_data[$pid]['price'];
            $line_subtotal = $price * $qty;
            $stmt_item->bind_param("iiidd", $order_id, $pid, $qty, $price, $line_subtotal);
            $stmt_item->execute();
        }

        // Notify Admin of New Order
        create_admin_notification(
            "New Order #$order_number received from " . ($user_id ? $user_data['name'] : 'Guest') . " - ₹" . number_format($total, 2),
            "order",
            "orders.php?id=$order_id"
        );

        // Update product stock
        $stmt_stock = $conn->prepare("UPDATE products SET stock = stock - ? WHERE id = ?");
        foreach ($_SESSION['cart'] as $pid => $qty) {
            if (!isset($products_data[$pid])) continue;
            $stmt_stock->bind_param("ii", $qty, $pid);
            $stmt_stock->execute();
        }

        // Update Coupon Usage
        if (isset($_SESSION['coupon'])) {
            $conn->query("UPDATE coupons SET usage_count = usage_count + 1 WHERE id = $coupon_id");
        }

        // Add to Status History
        $sql_hist = "INSERT INTO order_status_history (order_id, status, notes) VALUES (?, 'pending', 'Order placed successfully')";
        execute_query($sql_hist, [$order_id]);

        $conn->commit();
        
        // Send Order Confirmation Email
        send_order_confirmation($order_id);

        // Success - Clear Cart and Coupon and Redirect
        if (is_logged_in()) {
            clear_abandoned_cart($_SESSION['user_id']);
        }
        unset($_SESSION['cart']);
        unset($_SESSION['coupon']);
        header("Location: order-success.php?id=" . $order_id . "&num=" . $order_number);
        exit;

    } catch (Exception $e) {
        $conn->rollback();
        $error = "Order failed: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php 
    $page_title = 'Secure Checkout';
    include 'includes/head.php'; 
    ?>
    <script src="https://checkout.razorpay.com/v1/checkout.js"></script>
    <style>
        .step-active { color: #24B25D; }
        .step-done { color: #24B25D; }
        .checkout-step { display: none; }
        .checkout-step.active { display: block; }
        
        .payment-radio:checked + .payment-card {
            border-color: #24B25D;
            background-color: #f0fdf4;
            transform: scale(1.02);
        }
        
        @keyframes slideIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .anim-slide { animation: slideIn 0.5s ease-out forwards; }
    </style>
</head>
<body class="bg-[#FFFEDC] font-sans">

    <!-- Premium Minimal Header -->
    <header class="bg-white/80 backdrop-blur-xl sticky top-0 z-50 border-b border-gray-100">
        <div class="container mx-auto px-6 h-20 flex items-center justify-between">
            <a href="<?php echo get_url(''); ?>" class="flex items-center gap-2">
                <img src="<?php echo get_url('./assets/images/logo.svg'); ?>" alt="Logo" class="w-20">
            </a>
            <div class="flex items-center gap-3 text-gray-400 font-bold text-xs uppercase tracking-widest">
                <i class="fas fa-lock text-[#24B25D]"></i> 
                <span class="hidden sm:inline">Secure 256-bit SSL Checkout</span>
            </div>
        </div>
    </header>

    <div class="container mx-auto px-6 py-12 max-w-6xl">
        
        <?php if($error): ?>
            <div class="mb-8 p-4 bg-red-50 border-2 border-red-100 text-red-600 rounded-3xl flex items-center gap-4 anim-slide">
                <i class="fas fa-exclamation-circle text-xl"></i>
                <p class="font-bold"><?php echo $error; ?></p>
            </div>
        <?php endif; ?>

        <div class="flex flex-col lg:flex-row gap-12 items-start">
            
            <!-- LEFT: CHECKOUT FLOW -->
            <div class="w-full lg:flex-1">
                
                <!-- Progress Indicators -->
                <div class="flex items-center gap-4 mb-10 px-2 overflow-x-auto pb-4 no-scrollbar">
                    <div class="flex items-center gap-3 shrink-0" id="indicator-1">
                        <div class="w-10 h-10 rounded-full bg-[#24B25D] text-white flex items-center justify-center font-black shadow-lg shadow-green-100">1</div>
                        <span class="font-black text-gray-900 uppercase tracking-widest text-[10px]">Shipping</span>
                    </div>
                    <div class="h-0.5 w-12 bg-gray-200 rounded-full shrink-0" id="line-1"></div>
                    <div class="flex items-center gap-3 shrink-0" id="indicator-2">
                        <div class="w-10 h-10 rounded-full bg-gray-100 text-gray-400 flex items-center justify-center font-black transition-all">2</div>
                        <span class="font-black text-gray-400 uppercase tracking-widest text-[10px]">Payment</span>
                    </div>
                    <div class="h-0.5 w-12 bg-gray-200 rounded-full shrink-0" id="line-2"></div>
                    <div class="flex items-center gap-3 shrink-0">
                        <div class="w-10 h-10 rounded-full bg-gray-100 text-gray-400 flex items-center justify-center font-black">3</div>
                        <span class="font-black text-gray-400 uppercase tracking-widest text-[10px]">Review</span>
                    </div>
                </div>

                <form method="POST" id="checkout-form">
                    <input type="hidden" name="place_order" value="1">

                    <!-- STEP 1: SHIPPING -->
                    <div id="step-1" class="checkout-step active anim-slide">
                        <div class="bg-white rounded-[40px] p-8 md:p-10 shadow-sm border border-gray-100">
                            <h2 class="text-3xl font-heading font-black text-gray-900 mb-8">Shipping Address</h2>
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div class="space-y-2">
                                    <label class="text-[10px] font-black uppercase tracking-widest text-gray-400 ml-4">Email Address</label>
                                    <input type="email" name="email" required placeholder="eg : your@email.com" value="<?php echo $form['email']; ?>" class="w-full bg-gray-50 border-2 border-transparent focus:border-[#24B25D] focus:bg-white rounded-[24px] px-6 py-4 outline-none transition-all font-bold">
                                </div>
                                <div class="space-y-2">
                                    <label class="text-[10px] font-black uppercase tracking-widest text-gray-400 ml-4">Phone Number</label>
                                    <input type="text" name="phone" required placeholder="eg : +91 00000 00000" value="<?php echo $form['phone']; ?>" class="w-full bg-gray-50 border-2 border-transparent focus:border-[#24B25D] focus:bg-white rounded-[24px] px-6 py-4 outline-none transition-all font-bold">
                                </div>
                                <div class="space-y-2">
                                    <label class="text-[10px] font-black uppercase tracking-widest text-gray-400 ml-4">First Name</label>
                                    <input type="text" name="first_name" required placeholder="eg : John" value="<?php echo $form['first_name']; ?>" class="w-full bg-gray-50 border-2 border-transparent focus:border-[#24B25D] focus:bg-white rounded-[24px] px-6 py-4 outline-none transition-all font-bold">
                                </div>
                                <div class="space-y-2">
                                    <label class="text-[10px] font-black uppercase tracking-widest text-gray-400 ml-4">Last Name</label>
                                    <input type="text" name="last_name" required placeholder=" eg : last name" value="<?php echo $form['last_name']; ?>" class="w-full bg-gray-50 border-2 border-transparent focus:border-[#24B25D] focus:bg-white rounded-[24px] px-6 py-4 outline-none transition-all font-bold">
                                </div>
                                <div class="space-y-2 md:col-span-2">
                                    <label class="text-[10px] font-black uppercase tracking-widest text-gray-400 ml-4">Delivery Address</label>
                                    <input type="text" name="address" required placeholder="eg : House No, Street, Locality" value="<?php echo $form['address']; ?>" class="w-full bg-gray-50 border-2 border-transparent focus:border-[#24B25D] focus:bg-white rounded-[24px] px-6 py-4 outline-none transition-all font-bold">
                                </div>
                                <div class="space-y-2">
                                    <label class="text-[10px] font-black uppercase tracking-widest text-gray-400 ml-4">City</label>
                                    <input type="text" name="city" required placeholder="eg  : Srinagar" value="<?php echo $form['city']; ?>" class="w-full bg-gray-50 border-2 border-transparent focus:border-[#24B25D] focus:bg-white rounded-[24px] px-6 py-4 outline-none transition-all font-bold">
                                </div>
                                <div class="space-y-2">
                                    <label class="text-[10px] font-black uppercase tracking-widest text-gray-400 ml-4">State</label>
                                    <input type="text" name="state" required placeholder="eg : J&K" value="<?php echo $form['state']; ?>" class="w-full bg-gray-50 border-2 border-transparent focus:border-[#24B25D] focus:bg-white rounded-[24px] px-6 py-4 outline-none transition-all font-bold">
                                </div>
                                <div class="space-y-2">
                                    <label class="text-[10px] font-black uppercase tracking-widest text-gray-400 ml-4">PIN Code</label>
                                    <input type="text" name="zip" id="zip_input" required placeholder="eg :190001" value="<?php echo $form['zip']; ?>" maxlength="6" oninput="fetchShippingMethods()" class="w-full bg-gray-50 border-2 border-transparent focus:border-[#24B25D] focus:bg-white rounded-[24px] px-6 py-4 outline-none transition-all font-bold">
                                </div>

                                <div class="md:col-span-2 mt-4 hidden" id="shipping-methods-container">
                                    <label class="text-[10px] font-black uppercase tracking-widest text-gray-400 ml-4 mb-3 block">Choose Shipping Method</label>
                                    <div id="shipping-methods-list" class="space-y-3">
                                        <!-- Methods will be injected here -->
                                    </div>
                                    <input type="hidden" name="shipping_method_id" id="shipping_method_id_input">
                                    <input type="hidden" name="shipping_cost" id="shipping_cost_input" value="<?php echo $shipping; ?>">
                                    <input type="hidden" name="tax" id="tax_input" value="<?php echo $tax; ?>">
                                </div>
                            </div>

                            <button type="button" onclick="goToStep(2)" class="btn-chunky bg-[#111827] text-white w-full mt-10 py-5 text-lg shadow-xl hover:bg-[#24B25D] hover:text-black">
                                Continue to Payment <i class="fas fa-arrow-right ml-2"></i>
                            </button>
                        </div>
                    </div>

                    <!-- STEP 2: PAYMENT -->
                    <div id="step-2" class="checkout-step anim-slide">
                        <div class="bg-white rounded-[40px] p-8 md:p-10 shadow-sm border border-gray-100">
                            <h2 class="text-3xl font-heading font-black text-gray-900 mb-2">Payment Method</h2>
                            <p class="text-gray-400 text-sm mb-10">All transactions are secure and encrypted.</p>
                            
                            <?php 
                            $cod_enabled = get_setting('payment_cod_enabled', 'on') === 'on';
                            $online_enabled = get_setting('payment_online_enabled', 'on') === 'on';
                            
                            if (!$cod_enabled && !$online_enabled): ?>
                                <div class="p-8 bg-red-50 rounded-[32px] border-2 border-red-100 text-center">
                                    <div class="text-4xl mb-4">⚠️</div>
                                    <h4 class="font-black text-gray-900 text-xl mb-2">No Payment Methods Available</h4>
                                    <p class="text-sm text-gray-500 font-medium">Please contact support or try again later.</p>
                                </div>
                            <?php else: ?>
                                <div class="space-y-4">
                                    <!-- COD -->
                                    <?php if ($cod_enabled): ?>
                                    <div class="relative">
                                        <input type="radio" name="payment_method" value="cod" id="pay-cod" <?php echo ($cod_enabled) ? 'checked' : ''; ?> class="hidden payment-radio">
                                        <label for="pay-cod" class="payment-card border-2 border-gray-100 rounded-[32px] p-6 flex items-center gap-6 cursor-pointer transition-all hover:border-[#24B25D]">
                                            <div class="w-16 h-16 bg-white rounded-2xl flex items-center justify-center text-3xl shadow-sm border border-gray-50">🚚</div>
                                            <div class="flex-1">
                                                <h4 class="font-black text-gray-900 text-xl font-heading">Cash on Delivery</h4>
                                                <p class="text-xs text-gray-500 font-medium">Pay when your snacks arrive.</p>
                                            </div>
                                            <div class="w-6 h-6 rounded-full border-2 border-gray-200 flex items-center justify-center">
                                                <div class="w-3 h-3 bg-[#24B25D] rounded-full opacity-0 scale-0 transition-all check-dot"></div>
                                            </div>
                                        </label>
                                    </div>
                                    <?php endif; ?>

                                    <!-- Razorpay -->
                                    <?php if ($online_enabled): ?>
                                    <div class="relative">
                                        <input type="radio" name="payment_method" value="razorpay" id="pay-razorpay" <?php echo (!$cod_enabled && $online_enabled) ? 'checked' : ''; ?> class="hidden payment-radio">
                                        <label for="pay-razorpay" class="payment-card border-2 border-gray-100 rounded-[32px] p-6 flex items-center gap-6 cursor-pointer transition-all hover:border-[#24B25D]">
                                            <div class="w-16 h-16 bg-white rounded-2xl flex items-center justify-center text-3xl shadow-sm border border-gray-50">💳</div>
                                            <div class="flex-1">
                                                <h4 class="font-black text-gray-900 text-xl font-heading">Online Payment</h4>
                                                <p class="text-xs text-gray-500 font-medium">Credit/Debit Card, UPI, Netbanking.</p>
                                            </div>
                                            <div class="w-6 h-6 rounded-full border-2 border-gray-200 flex items-center justify-center">
                                                <div class="w-3 h-3 bg-[#24B25D] rounded-full opacity-0 scale-0 transition-all check-dot"></div>
                                            </div>
                                        </label>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>

                            <div class="flex flex-col sm:flex-row gap-4 mt-12">
                                <button type="button" onclick="goToStep(1)" class="btn-chunky border-gray-100 text-gray-500 hover:text-black hover:border-black py-4 px-8">
                                    <i class="fas fa-arrow-left mr-2"></i> Back
                                </button>
                                <button type="submit" class="btn-chunky bg-[#111827] text-white flex-1 py-5 text-lg shadow-xl hover:bg-[#24B25D] hover:text-black">
                                    Finish & Place Order <i class="fas fa-check-circle ml-2"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>

            <!-- RIGHT: ORDER SUMMARY -->
            <div class="w-full lg:w-[380px] sticky top-28">
                <div class="bg-white rounded-[40px] p-8 shadow-sm border border-gray-100 overflow-hidden relative">
                    <!-- Subtle Decor -->
                    <div class="absolute -top-10 -right-10 w-40 h-40 bg-[#24B25D]/5 rounded-full blur-3xl"></div>
                    
                    <div class="flex items-center justify-between mb-8">
                        <h3 class="font-black text-xl font-heading text-gray-900">Order Items</h3>
                        <button type="button" onclick="openBrowseMoreModal()" id="btn-browse-more" class="group relative flex items-center justify-center gap-2 bg-[#24B25D]/10 hover:bg-[#24B25D] px-4 py-2.5 rounded-2xl transition-all duration-300 active:scale-95 cursor-pointer z-20">
                            <i class="fas fa-plus text-[10px] text-[#24B25D] group-hover:text-black pointer-events-none"></i>
                            <span class="text-[10px] font-black uppercase tracking-widest text-[#24B25D] group-hover:text-black pointer-events-none">Browse More</span>
                        </button>
                    </div>
                    
                    <div id="order-items-container" class="space-y-6 mb-8 max-h-[200px] overflow-y-auto pr-2 custom-scrollbar">
                        <?php foreach($products_data as $id => $p): 
                            $qty = $_SESSION['cart'][$id];
                        ?>
                        <div class="flex gap-4 items-center group mb-4">
                            <div class="w-16 h-16 bg-gray-50 rounded-2xl border border-gray-100 p-1 flex-shrink-0 relative overflow-hidden">
                                <img src="<?php echo $p['image']; ?>" class="w-full h-full object-contain rounded-xl group-hover:scale-110 transition">
                            </div>
                            <div class="flex-1">
                                <h4 class="font-bold text-sm text-gray-800 line-clamp-1 font-heading"><?php echo $p['name']; ?></h4>
                                <div class="flex items-center gap-2 mt-1">
                                    <div class="flex items-center bg-gray-50 rounded-lg p-0.5 border border-gray-100">
                                        <button onclick="updateCheckoutQty(<?php echo $id; ?>, <?php echo $qty - 1; ?>)" class="w-5 h-5 flex items-center justify-center text-[8px] text-gray-400 hover:text-black transition">-</button>
                                        <span class="text-[10px] font-black w-5 text-center"><?php echo $qty; ?></span>
                                        <button onclick="updateCheckoutQty(<?php echo $id; ?>, <?php echo $qty + 1; ?>)" class="w-5 h-5 flex items-center justify-center text-[8px] text-gray-400 hover:text-black transition">+</button>
                                    </div>
                                    <span class="text-[8px] font-black text-gray-400 uppercase tracking-tighter">@ ₹<?php echo $p['price']; ?></span>
                                </div>
                            </div>
                            <div class="text-right">
                                <span class="font-black text-gray-900 text-sm block">₹<?php echo $p['price'] * $qty; ?></span>
                                <button onclick="updateCheckoutQty(<?php echo $id; ?>, 0)" class="text-[8px] font-bold text-red-300 hover:text-red-500 uppercase tracking-widest">Remove</button>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>

                    <!-- COUPON SECTION -->
                    <div class="pt-6 border-t border-gray-100 mb-6">
                        <div class="flex sm:flex-row flex-col gap-2">
                            <input type="text" id="coupon-code" placeholder="Have a code?" class="flex-1 bg-gray-50 border-2 border-transparent focus:border-[#24B25D] focus:bg-white rounded-2xl px-4 py-3 outline-none transition-all font-bold text-sm">
                            <button type="button" onclick="applyCoupon()" class="btn-chunky bg-gray-900 text-white px-6 text-xs py-3">Apply</button>
                        </div>
                        <div id="coupon-message" class="text-[10px] font-bold mt-2 ml-2"></div>
                    </div>

                    <div class="space-y-4 pt-6 border-t border-gray-100">
                        <div class="flex justify-between items-center text-sm font-medium">
                            <span class="text-gray-400">Packs Total</span>
                            <span class="text-gray-900 font-bold" id="subtotal_display">₹<?php echo $subtotal; ?></span>
                        </div>
                        <div id="discounts-container">
                            <?php if($coupon_discount > 0): ?>
                                <div class="flex justify-between items-center text-sm font-medium text-[#24B25D]">
                                    <span>Coupon Discount (<?php echo $_SESSION['coupon']['code']; ?>)</span>
                                    <span class="font-bold">- ₹<?php echo $coupon_discount; ?></span>
                                </div>
                            <?php endif; ?>
                            
                            <?php if($affiliate_discount > 0): ?>
                                <div class="flex justify-between items-center text-sm font-medium text-[#24B25D]">
                                    <span class="flex items-center gap-1"><i class="fas fa-bolt text-[10px]"></i> Creator Discount (@<?php echo $_SESSION['affiliate']['code']; ?>)</span>
                                    <span class="font-bold">- ₹<?php echo $affiliate_discount; ?></span>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="flex justify-between items-center text-sm font-medium">
                            <span class="text-gray-400">Shipping</span>
                            <span class="text-gray-900 font-bold" id="shipping_display">
                                <?php 
                                if($shipping_method_id > 0) {
                                    echo $shipping == 0 ? '<span class="text-green-500">FREE</span>' : '₹' . $shipping;
                                } else {
                                    echo '<span class="text-gray-300">Enter Zip</span>';
                                }
                                ?>
                            </span>
                        </div>
                        <div class="flex justify-between items-center text-sm font-medium">
                            <span class="text-gray-400" id="tax_perc_label">Processing Tax (<?php echo $tax_perc; ?>%)</span>
                            <span class="text-gray-900 font-bold" id="tax_display">₹<?php echo $tax; ?></span>
                        </div>
                    </div>

                    <div class="flex justify-between items-center pt-8 mt-8 border-t-2 border-dashed border-gray-100">
                        <span class="text-gray-500 font-black uppercase text-[10px] tracking-widest">Grand Total</span>
                        <span class="text-4xl font-black font-heading text-gray-900" id="grand_total_display">₹<?php echo $total; ?></span>
                    </div>

                    <div class="mt-8 p-4 bg-green-50 rounded-[24px] border border-green-100 flex items-start gap-3">
                        <i class="fas fa-shield-alt text-[#24B25D] mt-1 text-sm"></i>
                        <p class="text-[10px] text-green-700 font-bold leading-relaxed">
                            Your payment is protected by safe browsing technology and our Freshness Guarantee.
                        </p>
                    </div>

                    <div class="mt-6 text-center">
                         <a href="<?php echo get_url('cart'); ?>" class="text-xs font-bold text-gray-400 hover:text-black transition uppercase tracking-widest flex items-center justify-center gap-2">
                             <i class="fas fa-shopping-cart text-[10px]"></i> Edit Bag
                         </a>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <!-- GHOST CHECKOUT CHOICE MODAL -->
    <?php if($show_choice_modal): ?>
    <div id="checkout-choice-overlay" class="fixed inset-0 z-[200] flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-black/60 backdrop-blur-xl"></div>
        
        <div class="relative bg-white rounded-[32px] md:rounded-[50px] w-full max-w-4xl max-h-[90vh] md:max-h-auto overflow-y-auto shadow-2xl anim-slide border-4 border-white no-scrollbar">
            <!-- Close Button (Back to Cart) -->
            <a href="<?php echo get_url('cart'); ?>" class="absolute top-4 right-5 md:top-8 md:right-8 text-gray-300 hover:text-black transition text-xl z-50 p-2">
                <i class="fas fa-times"></i>
            </a>

            <div class="flex flex-col md:flex-row min-h-full">
                
                <!-- Section 1: Member Benefit -->
                <div class="flex-1 p-6 sm:p-10 md:p-14 bg-gray-50 flex flex-col justify-center border-b md:border-b-0 md:border-r border-gray-100 text-center md:text-left">
                    <div class="w-12 h-12 sm:w-16 sm:h-16 bg-[#24B25D] rounded-2xl md:rounded-3xl flex items-center justify-center text-white text-xl sm:text-3xl shadow-lg mb-4 sm:mb-8 rotate-3 mx-auto md:mx-0">
                        <i class="fas fa-user-check"></i>
                    </div>
                    <h2 class="text-2xl sm:text-4xl font-heading font-black text-gray-900 mb-2 sm:mb-4 uppercase tracking-tight leading-none">The Member<br>Club.</h2>
                    <p class="text-gray-500 font-bold text-[10px] sm:text-sm mb-6 sm:mb-10 leading-relaxed max-w-[280px] mx-auto md:mx-0">
                        Save addresses, track every crunch, and earn 10% cash-back on every order.
                    </p>
                    
                    <div class="space-y-2 sm:space-y-4">
                        <a href="<?php echo get_url('login.php?redirect=checkout'); ?>" class="btn-chunky bg-black text-white w-full py-3 sm:py-4 text-center block hover:scale-105 transition text-[10px] sm:text-sm">
                            Login to Account
                        </a>
                        <a href="<?php echo get_url('register.php?redirect=checkout'); ?>" class="btn-chunky border-2 border-gray-200 text-gray-400 w-full py-3 sm:py-4 text-center block hover:border-black hover:text-black transition text-[10px] sm:text-sm">
                            Create New Account
                        </a>
                    </div>
                </div>

                <!-- Section 2: Guest Checkout -->
                <div class="flex-1 p-6 sm:p-10 md:p-14 bg-white flex flex-col justify-center relative overflow-hidden text-center md:text-left">
                    <!-- Decor -->
                    <div class="absolute -right-10 -top-10 w-40 h-40 bg-gray-50 rounded-full blur-3xl opacity-50 pointer-events-none"></div>
                    
                    <div class="w-12 h-12 sm:w-16 sm:h-16 bg-white border-2 border-gray-100 rounded-2xl md:rounded-3xl flex items-center justify-center text-gray-400 text-xl sm:text-3xl shadow-sm mb-4 sm:mb-8 -rotate-3 mx-auto md:mx-0">
                        <i class="fas fa-ghost"></i>
                    </div>
                    <h2 class="text-2xl sm:text-4xl font-heading font-black text-gray-900 mb-2 sm:mb-4 uppercase tracking-tight leading-none">Ghost<br>Checkout.</h2>
                    <p class="text-gray-500 font-bold text-[10px] sm:text-sm mb-6 sm:mb-10 leading-relaxed max-w-[280px] mx-auto md:mx-0">
                        In a hurry? Checkout as a guest. You can always create an account later.
                    </p>
                    
                    <a href="?mode=guest" class="btn-chunky bg-[#24B25D] text-white w-full py-4 sm:py-6 text-center block shadow-xl hover:rotate-2 hover:scale-105 transition-all text-xs sm:text-lg border-none">
                        Continue as Guest <i class="fas fa-arrow-right ml-2 text-sm opacity-50"></i>
                    </a>
                </div>

            </div>
        </div>
    </div>
    <style>
        body { overflow: <?php echo $show_choice_modal ? 'hidden' : 'auto'; ?>; } /* Lock scroll when choice is active */
    </style>
    <?php endif; ?>


    <!-- PROCESSING OVERLAY -->
    <div id="processing-overlay" class="fixed inset-0 bg-white/95 backdrop-blur-md z-[100] hidden flex-col items-center justify-center text-center">
        <div class="relative mb-10">
            <div class="w-32 h-32 border-4 border-gray-100 border-t-[#24B25D] rounded-full animate-spin"></div>
            <div class="absolute inset-0 flex items-center justify-center text-4xl">🍿</div>
        </div>
        <h2 class="text-4xl font-black font-heading text-gray-900 mb-4 anim-slide">Securing Your Snacks...</h2>
        <p class="text-gray-500 font-bold tracking-widest uppercase text-xs animate-pulse">Communicating with vault</p>
    </div>

    <!-- BROWSE MORE MODAL (PREMIUM SLIDER VERSION) -->
    <div id="browse-more-modal" class="fixed inset-0 z-[110] hidden">
        <div class="absolute inset-0 bg-black/70 backdrop-blur-md transition-opacity duration-500" onclick="closeBrowseMoreModal()"></div>
        <div class="absolute inset-0 flex items-end md:items-center justify-center p-0 md:p-4">
            <div class="bg-white rounded-t-[32px] md:rounded-[50px] w-full max-w-5xl h-[90vh] md:h-auto md:max-h-[90vh] overflow-hidden flex flex-col shadow-2xl anim-slide relative border-t-4 md:border-4 border-white">
                
                <!-- Close Button -->
                <button onclick="closeBrowseMoreModal()" class="absolute top-4 right-4 md:top-8 md:right-8 w-10 h-10 md:w-12 md:h-12 flex items-center justify-center bg-gray-50 rounded-xl md:rounded-2xl hover:bg-black hover:text-white transition-all duration-300 z-10 group">
                    <i class="fas fa-times text-gray-400 group-hover:rotate-90 transition-transform"></i>
                </button>

                <!-- Header -->
                <div class="p-6 md:p-12 border-b border-gray-100 flex flex-col md:flex-row md:items-end justify-between gap-4 md:gap-6">
                    <div>
                        <div class="flex items-center gap-3 mb-2">
                            <span class="w-10 h-1 bg-[#24B25D] rounded-full"></span>
                            <span class="text-[10px] font-black text-[#24B25D] uppercase tracking-[0.3em]">Cravings Await</span>
                        </div>
                        <h2 class="text-3xl md:text-5xl font-heading font-black text-gray-900 leading-none">Add More Snacks 🍿</h2>
                        <p class="text-gray-400 font-bold text-xs md:text-sm mt-3 md:mt-4">Don't forget these fan favorites for your journey!</p>
                    </div>
                    
                    <!-- Slider Controls - Hidden on mobile, use native touch scroll -->
                    <div class="hidden md:flex gap-3">
                        <button onclick="scrollSuggestions('left')" class="w-14 h-14 rounded-2xl bg-gray-50 border-2 border-transparent hover:border-black flex items-center justify-center text-gray-400 hover:text-black transition-all">
                            <i class="fas fa-arrow-left"></i>
                        </button>
                        <button onclick="scrollSuggestions('right')" class="w-14 h-14 rounded-2xl bg-black text-[#24B25D] flex items-center justify-center hover:scale-105 hover:rotate-3 transition-all">
                            <i class="fas fa-arrow-right"></i>
                        </button>
                    </div>
                </div>

                <!-- Slider Container -->
                <div class="flex-1 overflow-hidden p-6 md:p-12 bg-gray-50/30">
                    <div id="suggestions-slider" class="flex gap-4 md:gap-6 overflow-x-auto hide-scrollbar scroll-smooth snap-x pb-4">
                        <!-- Suggestions will be injected here -->
                    </div>
                </div>

                <!-- Footer -->
                <div class="p-6 md:p-8 border-t border-gray-100 flex flex-col sm:flex-row items-center justify-between bg-white gap-4">
                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 md:w-10 md:h-10 bg-green-50 rounded-lg md:rounded-xl flex items-center justify-center text-[#24B25D]">
                            <i class="fas fa-info-circle text-sm"></i>
                        </div>
                        <p class="text-[9px] md:text-[10px] font-bold text-gray-400 uppercase tracking-widest leading-relaxed">Adding items will automatically<br class="hidden md:block">update your order total.</p>
                    </div>
                    <button onclick="closeBrowseMoreModal()" class="btn-chunky bg-[#24B25D] text-white py-4 md:py-5 px-8 md:px-12 text-sm shadow-xl shadow-green-100 hover:rotate-2 w-full sm:w-auto">
                        Back to Checkout <i class="fas fa-arrow-right ml-2"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div id="toast-container" class="fixed bottom-10 left-1/2 -translate-x-1/2 z-[200] flex flex-col gap-3 pointer-events-none"></div>

    <script>
        function showToast(message, type = 'info') {
            const container = document.getElementById('toast-container');
            const toast = document.createElement('div');
            const colors = {
                success: 'bg-[#24B25D] text-black',
                error: 'bg-red-500 text-white',
                warning: 'bg-amber-500 text-white',
                info: 'bg-black text-white'
            };
            const icon = {
                success: 'fa-check-circle',
                error: 'fa-exclamation-circle',
                warning: 'fa-exclamation-triangle',
                info: 'fa-info-circle'
            };
            
            toast.className = `${colors[type]} px-6 py-4 rounded-[20px] shadow-2xl flex items-center gap-3 anim-up pointer-events-auto font-bold text-sm min-w-[300px] border-4 border-white/20 backdrop-blur-md`;
            toast.innerHTML = `<i class="fas ${icon[type]}"></i> <span>${message}</span>`;
            
            container.appendChild(toast);
            
            setTimeout(() => {
                toast.classList.add('opacity-0', 'translate-y-10');
                toast.style.transition = 'all 0.5s ease';
                setTimeout(() => toast.remove(), 500);
            }, 3000);
        }

        async function applyCoupon() {
            const code = document.getElementById('coupon-code').value;
            const messageEl = document.getElementById('coupon-message');
            const total = <?php echo $subtotal; ?>;

            if(!code) return;

            const formData = new FormData();
            formData.append('code', code);
            formData.append('total', total);

            const res = await fetch(BASE_URL + 'api/validate_coupon.php', { method: 'POST', body: formData });
            const data = await res.json();

            if(data.success) {
                messageEl.className = "text-[10px] font-bold mt-2 ml-2 text-green-500";
                messageEl.textContent = data.message;
                // Update summary dynamically
                refreshCheckoutSummary();
            } else {
                messageEl.className = "text-[10px] font-bold mt-2 ml-2 text-red-500";
                messageEl.textContent = data.message;
            }
        }

        function goToStep(n) {
            // Validate current step before proceeding
            if (n === 2) {
                const step1Inputs = document.querySelectorAll('#step-1 input[required]');
                let valid = true;
                step1Inputs.forEach(input => {
                    if (!input.value.trim()) {
                        input.classList.add('border-red-200', 'bg-red-50');
                        valid = false;
                    } else {
                        input.classList.remove('border-red-200', 'bg-red-50');
                    }
                });
                if (!valid) return;
            }

            // Clean up UI
            document.querySelectorAll('.checkout-step').forEach(s => s.classList.remove('active'));
            document.getElementById('step-' + n).classList.add('active');

            // Update Progress Bar
            if (n === 2) {
                document.getElementById('indicator-2').querySelector('.w-10').classList.replace('bg-gray-100', 'bg-[#24B25D]');
                document.getElementById('indicator-2').querySelector('.w-10').classList.replace('text-gray-400', 'text-white');
                document.getElementById('indicator-2').querySelector('.w-10').classList.add('shadow-lg', 'shadow-green-100');
                document.getElementById('indicator-2').querySelector('span').classList.replace('text-gray-400', 'text-gray-900');
                document.getElementById('line-1').classList.replace('bg-gray-200', 'bg-[#24B25D]');
            } else {
                document.getElementById('indicator-2').querySelector('.w-10').classList.replace('bg-[#24B25D]', 'bg-gray-100');
                document.getElementById('indicator-2').querySelector('.w-10').classList.replace('text-white', 'text-gray-400');
                document.getElementById('indicator-2').querySelector('.w-10').classList.remove('shadow-lg', 'shadow-green-100');
                document.getElementById('indicator-2').querySelector('span').classList.replace('text-gray-900', 'text-gray-400');
                document.getElementById('line-1').classList.replace('bg-[#24B25D]', 'bg-gray-200');
            }
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }

        // Flag to prevent multiple submissions
        let isProcessing = false;

        document.getElementById('checkout-form').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            if (isProcessing) return;
            isProcessing = true;

            const form = this;
            const method = form.payment_method.value;

            // Show Overlay
            const overlay = document.getElementById('processing-overlay');
            overlay.classList.remove('hidden');
            overlay.classList.add('flex');

            if (method === 'razorpay') {
                try {
                    // 1. Create Order on Server
                    const shippingCost = document.getElementById('shipping_cost_input').value;
                    const fd = new FormData();
                    fd.append('shipping_cost', shippingCost);

                    const res = await fetch(BASE_URL + 'api/razorpay.php?action=create_order', { method: 'POST', body: fd });
                    const rData = await res.json();

                    if (!rData.success) {
                        alert("Razorpay Error: " + rData.message);
                        overlay.classList.add('hidden');
                        return false;
                    }

                    // 2. Open Razorpay
                    const options = {
                        "key": rData.key,
                        "amount": rData.amount,
                        "currency": rData.currency || "INR",
                        "name": rData.name,
                        "description": rData.description,
                        "image": BASE_URL + "assets/images/logo.svg",
                        "order_id": rData.order_id,
                        "handler": async function (response){
                            // 3. Verify Payment
                            const vfd = new FormData();
                            vfd.append('razorpay_order_id', response.razorpay_order_id);
                            vfd.append('razorpay_payment_id', response.razorpay_payment_id);
                            vfd.append('razorpay_signature', response.razorpay_signature);

                            const vRes = await fetch(BASE_URL + 'api/razorpay.php?action=verify_payment', { method: 'POST', body: vfd });
                            const vData = await vRes.json();

                            if (vData.success) {
                                // Add payment ID to form and submit
                                const payIdInput = document.createElement('input');
                                payIdInput.type = 'hidden';
                                payIdInput.name = 'razorpay_payment_id';
                                payIdInput.value = response.razorpay_payment_id;
                                form.appendChild(payIdInput);
                                
                                form.submit();
                            } else {
                                alert("Verification Failed: " + vData.message);
                                overlay.classList.add('hidden');
                            }
                        },
                        "prefill": {
                            "name": form.first_name.value + ' ' + form.last_name.value,
                            "email": form.email.value,
                            "contact": form.phone.value
                        },
                        "theme": { "color": rData.color },
                        "modal": {
                            "ondismiss": function(){
                                overlay.classList.add('hidden');
                            }
                        }
                    };
                    const rzp = new Razorpay(options);
                    rzp.open();
                    return false;
                } catch (err) {
                    console.error(err);
                    alert("Something went wrong with Razorpay.");
                    overlay.classList.add('hidden');
                    return false;
                }
            } else {
                // COD or other direct methods
                form.submit();
            }
        });

        // Radio click visual enhancer
        document.querySelectorAll('.payment-radio').forEach(radio => {
            radio.addEventListener('change', () => {
                document.querySelectorAll('.check-dot').forEach(dot => dot.classList.add('opacity-0', 'scale-0'));
                if(radio.checked) {
                    radio.parentElement.querySelector('.check-dot').classList.remove('opacity-0', 'scale-0');
                }
            });
        });

        // Initialize dots for checked radios
        document.querySelectorAll('.payment-radio:checked').forEach(radio => {
             radio.parentElement.querySelector('.check-dot').classList.remove('opacity-0', 'scale-0');
        });

        async function fetchShippingMethods() {
            const zip = document.getElementById('zip_input').value;
            const container = document.getElementById('shipping-methods-container');
            const list = document.getElementById('shipping-methods-list');
            
            if (zip.length < 6) return;

            list.innerHTML = '<div class="p-4 text-center text-gray-400 font-bold"><i class="fas fa-spinner fa-spin mr-2"></i> Calculating shipping...</div>';
            container.classList.remove('hidden');

            const formData = new FormData();
            formData.append('pincode', zip);

            try {
                const res = await fetch(BASE_URL + 'api/shipping.php', { method: 'POST', body: formData });
                const data = await res.json();

                if (data.success && data.methods.length > 0) {
                    let html = '';
                    data.methods.forEach((m, idx) => {
                        html += `
                            <div class="relative">
                                <input type="radio" name="ship_method_radio" value="${m.id}" id="ship-${m.id}" ${idx === 0 ? 'checked' : ''} 
                                    onchange="selectShippingMethod(${m.id}, ${m.cost})" class="hidden peer">
                                <label for="ship-${m.id}" class="flex items-center justify-between p-4 bg-gray-50 rounded-2xl border-2 border-transparent peer-checked:border-[#24B25D] peer-checked:bg-green-50 transition-all cursor-pointer hover:bg-gray-100">
                                    <div>
                                        <p class="font-black text-xs uppercase tracking-widest text-gray-900">${m.display_name}</p>
                                        <p class="text-[10px] text-gray-500 font-medium">Estimated arrival in ${m.min_days}-${m.max_days} days</p>
                                    </div>
                                    <div class="text-right">
                                        <p class="font-black text-lg text-gray-900 leading-none">${m.cost == 0 ? '<span class="text-green-500">FREE</span>' : '₹' + m.cost}</p>
                                    </div>
                                </label>
                            </div>
                        `;
                    });
                    list.innerHTML = html;
                    // Select first by default
                    selectShippingMethod(data.methods[0].id, data.methods[0].cost);
                } else {
                    list.innerHTML = `<div class="p-4 bg-red-50 text-red-500 rounded-2xl font-bold text-xs uppercase tracking-widest">
                        <i class="fas fa-exclamation-triangle mr-2"></i> ${data.message || 'No shipping available for this zone'}
                    </div>`;
                }
            } catch (e) {
                list.innerHTML = '<div class="p-4 bg-red-50 text-red-500 rounded-2xl font-bold text-xs uppercase tracking-widest">Error fetching methods</div>';
            }
        }

        async function selectShippingMethod(id, cost) {
            // Update session via API
            const formData = new FormData();
            formData.append('cost', cost);
            formData.append('id', id);
            
            try {
                await fetch(BASE_URL + 'api/cart.php?action=set_shipping', { method: 'POST', body: formData });
                // Now refresh the entire summary which will pull the new shipping as well
                refreshCheckoutSummary();
                
                // Keep the inputs in sync for the final form submit
                document.getElementById('shipping_method_id_input').value = id;
                document.getElementById('shipping_cost_input').value = cost;
            } catch (e) {
                console.error("Shipping update failed", e);
            }
        }

        async function updateCheckoutQty(pid, qty) {
            const formData = new FormData();
            formData.append('product_id', pid);
            formData.append('quantity', qty);
            
            try {
                const res = await fetch(BASE_URL + 'api/cart.php?action=update', { method: 'POST', body: formData });
                const data = await res.json();
                if(data.success) {
                    refreshCheckoutSummary();
                }
            } catch(e) {
                console.error("Update failed", e);
            }
        }

        async function refreshCheckoutSummary() {
            try {
                const res = await fetch(BASE_URL + 'api/get_checkout_data.php');
                const result = await res.json();
                
                if (result.success) {
                    const d = result.data;
                    document.getElementById('order-items-container').innerHTML = d.items_html;
                    document.getElementById('subtotal_display').textContent = '₹' + d.subtotal;
                    document.getElementById('tax_display').textContent = '₹' + d.tax;
                    document.getElementById('grand_total_display').textContent = '₹' + d.total;
                    document.getElementById('tax_perc_label').textContent = `Processing Tax (${d.tax_perc}%)`;
                    
                    // Update Shipping Display
                    const shipEl = document.getElementById('shipping_display');
                    if (d.shipping_method_id > 0) {
                        shipEl.innerHTML = d.shipping == 0 ? '<span class="text-green-500">FREE</span>' : '₹' + d.shipping;
                    } else {
                        shipEl.innerHTML = '<span class="text-gray-300">Enter Zip</span>';
                    }
                    
                    // Update Discounts
                    let discountsHtml = '';
                    if (d.coupon_discount > 0) {
                        discountsHtml += `
                            <div class="flex justify-between items-center text-sm font-medium text-[#24B25D]">
                                <span>Coupon Discount (${d.coupon_code})</span>
                                <span class="font-bold">- ₹${d.coupon_discount}</span>
                            </div>`;
                    }
                    if (d.affiliate_discount > 0) {
                        discountsHtml += `
                            <div class="flex justify-between items-center text-sm font-medium text-[#24B25D]">
                                <span class="flex items-center gap-1"><i class="fas fa-bolt text-[10px]"></i> Creator Discount (@${d.affiliate_code})</span>
                                <span class="font-bold">- ₹${d.affiliate_discount}</span>
                            </div>`;
                    }
                    document.getElementById('discounts-container').innerHTML = discountsHtml;
                    
                    // Stock Error Warning
                    if (d.has_stock_error) {
                        showToast(d.stock_message, 'warning');
                    }

                    // Also refresh suggestions in modal to reflect new cart state
                    fetchSuggestions();
                } else if (result.message === 'Cart is empty') {
                    window.location.href = BASE_URL + 'shop';
                }
            } catch (e) {
                console.error("Summary refresh failed", e);
            }
        }

        function openBrowseMoreModal() {
            const modal = document.getElementById('browse-more-modal');
            modal.classList.remove('hidden');
            document.body.style.overflow = 'hidden';
            fetchSuggestions();
        }

        function closeBrowseMoreModal() {
            const modal = document.getElementById('browse-more-modal');
            modal.classList.add('hidden');
            document.body.style.overflow = 'auto';
        }

        function scrollSuggestions(direction) {
            const slider = document.getElementById('suggestions-slider');
            const card = slider.querySelector('.flex-none');
            if(!card) return;
            const scrollAmount = direction === 'left' ? -card.offsetWidth * 2 : card.offsetWidth * 2;
            slider.scrollBy({ left: scrollAmount, behavior: 'smooth' });
        }

        async function fetchSuggestions() {
            const slider = document.getElementById('suggestions-slider');
            if(!slider) return;
            slider.innerHTML = '<div class="w-full py-20 text-center"><i class="fas fa-spinner fa-spin text-4xl text-[#24B25D]"></i></div>';
            
            try {
                const res = await fetch(BASE_URL + 'api/get_suggestions.php');
                if (!res.ok) throw new Error('API request failed');
                const data = await res.json();
                
                if (data.success && data.products && data.products.length > 0) {
                    let html = '';
                    data.products.forEach(p => {
                        const isOutOfStock = parseInt(p.stock || 0) <= 0;
                        const isLowStock = parseInt(p.stock || 0) > 0 && parseInt(p.stock || 0) < 5;
                        const productUrl = `${BASE_URL}product/${p.slug || p.id}`;
                        
                        html += `
                            <div class="flex-none w-[180px] md:w-[220px] snap-start bg-white rounded-[24px] md:rounded-[32px] p-4 md:p-5 shadow-sm border-2 border-white hover:border-[#24B25D] hover:shadow-xl transition-all duration-300 group">
                                <div class="aspect-square bg-gray-50 rounded-xl md:rounded-2xl mb-4 md:mb-5 overflow-hidden relative ${isOutOfStock ? 'grayscale' : ''}">
                                    <img src="${BASE_URL}${p.image.replace(/^(\.\/|\/)/, '')}" class="w-full h-full object-contain group-hover:scale-110 transition duration-500" onerror="this.src='data:image/svg+xml,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 width=%22200%22 height=%22200%22%3E%3Crect fill=%22%23f3f4f6%22 width=%22200%22 height=%22200%22/%3E%3Ctext x=%2250%25%22 y=%2250%25%22 text-anchor=%22middle%22 dy=%22.3em%22 fill=%22%23d1d5db%22 font-family=%22sans-serif%22 font-size=%2224%22%3ENo Image%3C/text%3E%3C/svg%3E'">
                                    <div class="absolute inset-0 bg-black/0 group-hover:bg-black/5 transition-colors"></div>
                                    ${isOutOfStock ? '<div class="absolute inset-0 bg-white/60 flex items-center justify-center font-black text-xs text-red-500 uppercase tracking-widest">Sold Out</div>' : ''}
                                    ${isLowStock ? '<div class="absolute top-3 left-3 bg-amber-500 text-white text-[8px] font-black px-2 py-1 rounded-full uppercase tracking-tighter">Only ' + p.stock + ' Left</div>' : ''}
                                </div>
                                <h4 class="font-black text-base md:text-lg text-gray-900 mb-1 line-clamp-1 font-[\'crimson-pro\'] ${isOutOfStock ? 'opacity-50' : ''}">${p.name}</h4>
                                <div class="flex items-center justify-between mt-3 md:mt-4">
                                    <div class="flex flex-col">
                                        <span class="text-[9px] md:text-[10px] font-black text-gray-300 uppercase tracking-widest">Price</span>
                                        <span class="font-black text-lg md:text-xl text-gray-900 tracking-tighter">₹${p.price}</span>
                                    </div>
                                    <button 
                                        onclick="addToCartFromModal(${p.id}, this)" 
                                        ${isOutOfStock ? 'disabled' : ''}
                                        class="w-10 h-10 md:w-12 md:h-12 rounded-xl md:rounded-2xl ${isOutOfStock ? 'bg-gray-100 text-gray-300' : 'bg-black text-[#24B25D] hover:bg-[#24B25D] hover:text-black hover:rotate-6'} transition-all shadow-lg flex items-center justify-center">
                                        <i class="fas fa-plus text-xs md:text-base"></i>
                                    </button>
                                </div>
                            </div>
                        `;
                    });
                    slider.innerHTML = html;
                } else {
                    slider.innerHTML = '<div class="w-full py-20 text-center"><p class="text-gray-400 font-bold uppercase tracking-widest text-xs">No suggestions for now</p></div>';
                }
            } catch (e) {
                console.error("Fetch suggestions failed", e);
                slider.innerHTML = '<div class="w-full py-20 text-center"><p class="text-red-400 font-bold uppercase tracking-widest text-xs">Error loading snacks</p><button onclick="fetchSuggestions()" class="mt-4 text-xs font-black underline uppercase text-gray-500">Retry</button></div>';
            }
        }

        async function addToCartFromModal(pid, btn) {
            const icon = btn.querySelector('i');
            const originalClass = icon.className;
            icon.className = 'fas fa-spinner fa-spin';
            btn.disabled = true;

            const formData = new FormData();
            formData.append('product_id', pid);
            formData.append('quantity', 1);

            try {
                const res = await fetch(BASE_URL + 'api/cart.php?action=add', { method: 'POST', body: formData });
                const data = await res.json();
                
                if (data.success) {
                    icon.className = 'fas fa-check';
                    btn.classList.replace('bg-black', 'bg-green-500');
                    btn.classList.replace('text-[#24B25D]', 'text-white');
                    
                    // Refresh summary dynamically without closing modal
                    refreshCheckoutSummary();
                    
                    // Reset button after 2 seconds
                    setTimeout(() => {
                        icon.className = 'fas fa-plus';
                        btn.classList.replace('bg-green-500', 'bg-black');
                        btn.classList.replace('text-white', 'text-[#24B25D]');
                        btn.disabled = false;
                    }, 2000);
                } else {
                    icon.className = originalClass;
                    btn.disabled = false;
                    showToast(data.message, 'error');
                }
            } catch (e) {
                icon.className = originalClass;
                btn.disabled = false;
                showToast("System error. Please try again.", 'error');
                console.error("Add failed", e);
            }
        }
    </script>
    <script>
        // Auto-fetch shipping if zip is already filled (Magic Checkout)
        document.addEventListener('DOMContentLoaded', () => {
            const zipInput = document.getElementById('zip_input');
            if(zipInput && zipInput.value.trim().length >= 6) {
                 fetchShippingMethods();
            }
        });
    </script>

</body>
</html>


