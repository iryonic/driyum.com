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
        $orderData = [
            'receipt'         => 'rcpt_' . uniqid(),
            'amount'          => round($final_total * 100), // in paise
            'currency'        => RAZORPAY_CURRENCY,
            'payment_capture' => 1 // auto capture
        ];

        $razorpayOrder = $api->order->create($orderData);

        echo json_encode([
            'success' => true,
            'order_id' => $razorpayOrder['id'],
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


