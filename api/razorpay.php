<?php
session_start();
require_once '../config/database.php';
require_once '../config/payment.php';
require_once '../vendor/autoload.php';

use Razorpay\Api\Api;

header('Content-Type: application/json');

$action = $_GET['action'] ?? '';

if ($action === 'create_order') {
    // Recalculate Total on Server to prevent tampering
    require_once '../includes/functions.php';
    $conn = get_db_connection();
    
    $subtotal = 0;
    $ids = implode(',', array_keys($_SESSION['cart']));
    $products = fetch_all("SELECT id, price FROM products WHERE id IN ($ids)");
    foreach($products as $p) {
        $subtotal += $p['price'] * $_SESSION['cart'][$p['id']];
    }

    $coupon_discount = 0;
    if (isset($_SESSION['coupon'])) {
        $coupon_val = validate_coupon($_SESSION['coupon']['code'], $subtotal);
        if ($coupon_val['valid']) {
            $coupon_discount = $coupon_val['discount'];
        }
    }

    $shipping = (float)($_POST['shipping_cost'] ?? 0);
    $tax_perc = (float)get_setting('tax_percentage', 5);
    $tax_rate = $tax_perc / 100;
    
    $taxable_amount = max(0, $subtotal - $coupon_discount + $shipping);
    $tax = ceil($taxable_amount * $tax_rate);
    $final_total = ($subtotal - $coupon_discount) + $shipping + $tax;

    $api = new Api(RAZORPAY_KEY_ID, RAZORPAY_KEY_SECRET);

    try {
        $first_name = sanitize_input($_POST['first_name'] ?? '');
        $last_name = sanitize_input($_POST['last_name'] ?? '');
        $email = sanitize_input($_POST['email'] ?? '');
        $phone = sanitize_input($_POST['phone'] ?? '');
        $address = sanitize_input($_POST['address'] ?? '');
        $city = sanitize_input($_POST['city'] ?? '');
        $state = sanitize_input($_POST['state'] ?? '');
        $zip_code = sanitize_input($_POST['zip'] ?? '');
        $shipping_method_id = (int)($_POST['shipping_method_id'] ?? 0);

        $order_number = 'ORD-' . strtoupper(uniqid());
        $user_id = is_logged_in() ? $_SESSION['user_id'] : null;

        $shipping_details = json_encode([
            'name' => $first_name . ' ' . $last_name,
            'email' => $email,
            'phone' => $phone,
            'address' => $address,
            'city' => $city,
            'state' => $state,
            'zip' => $zip_code
        ]);

        $final_affiliate_id = null;
        $final_affiliate_commission = 0;
        $final_affiliate_discount = 0;
        if (isset($_SESSION['affiliate'])) {
            $aff_data = $_SESSION['affiliate'];
            $final_affiliate_id = $aff_data['id'];
            $final_affiliate_discount = floor($subtotal * ($aff_data['discount'] / 100));
            $d_subtotal = $subtotal - $final_affiliate_discount - $coupon_discount;
            $final_affiliate_commission = floor(max(0, $d_subtotal) * ($aff_data['commission'] / 100));
        }

        $total_discount_db = $coupon_discount + $final_affiliate_discount;

        $stmt = $conn->prepare("INSERT INTO orders (order_number, user_id, affiliate_id, subtotal, discount, affiliate_commission, shipping_cost, total, payment_method, order_status, shipping_address, shipping_method_id, payment_status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'razorpay', 'pending_payment', ?, ?, 'pending')");
        $stmt->bind_param("siidddddsi", $order_number, $user_id, $final_affiliate_id, $subtotal, $total_discount_db, $final_affiliate_commission, $shipping, $final_total, $shipping_details, $shipping_method_id);
        
        if (!$stmt->execute()) {
            throw new Exception("Draft order creation failed: " . $stmt->error);
        }
        $internal_order_id = $stmt->insert_id;

        // Insert Items
        foreach($products as $p) {
            $pid = $p['id'];
            $qty = $_SESSION['cart'][$pid];
            $price = $p['price'];
            $line_subtotal = $price * $qty;
            execute_query("INSERT INTO order_items (order_id, product_id, quantity, price, subtotal) VALUES (?, ?, ?, ?, ?)", [$internal_order_id, $pid, $qty, $price, $line_subtotal]);
        }

        $orderData = [
            'receipt'         => $order_number,
            'amount'          => round($final_total * 100),
            'currency'        => RAZORPAY_CURRENCY,
            'payment_capture' => 1,
            'notes'           => [
                'internal_order_id' => $internal_order_id,
                'order_number' => $order_number
            ]
        ];

        $razorpayOrder = $api->order->create($orderData);

        echo json_encode([
            'success' => true,
            'order_id' => $razorpayOrder['id'],
            'internal_id' => $internal_order_id,
            'amount' => $orderData['amount'],
            'currency' => RAZORPAY_CURRENCY,
            'key' => RAZORPAY_KEY_ID,
            'name' => RAZORPAY_COMPANY_NAME,
            'description' => RAZORPAY_DESCRIPTION,
            'color' => RAZORPAY_THEME_COLOR
        ]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
} elseif ($action === 'verify_payment') {
    $api = new Api(RAZORPAY_KEY_ID, RAZORPAY_KEY_SECRET);

    $success = true;
    $error = "Payment Verification Failed";

    if (empty($_POST['razorpay_payment_id']) === false) {
        try {
            $attributes = [
                'razorpay_order_id' => $_POST['razorpay_order_id'],
                'razorpay_payment_id' => $_POST['razorpay_payment_id'],
                'razorpay_signature' => $_POST['razorpay_signature']
            ];

            $api->utility->verifyPaymentSignature($attributes);
        } catch(Exception $e) {
            $success = false;
            $error = 'Razorpay Error : ' . $e->getMessage();
        }
    } else {
        $success = false;
    }

    if ($success) {
        $_SESSION['payment_verified'] = true;
        $_SESSION['razorpay_payment_id'] = $_POST['razorpay_payment_id'];
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => $error]);
    }
}
?>


