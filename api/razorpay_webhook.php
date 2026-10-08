<?php
// Razorpay Webhook Handler
require_once '../config/database.php';
require_once '../config/payment.php';
require_once '../includes/functions.php';
require_once '../vendor/autoload.php';

use Razorpay\Api\Api;
use Razorpay\Api\Errors\SignatureVerificationError;

$post_data = file_get_contents('php://input');
$data = json_decode($post_data, true);

if (!$data || !isset($data['event'])) {
    http_response_code(400);
    exit;
}

// Verification (Optional: If WEBHOOK_SECRET is defined)
if (defined('RAZORPAY_WEBHOOK_SECRET') && !empty(RAZORPAY_WEBHOOK_SECRET)) {
    $api = new Api(RAZORPAY_KEY_ID, RAZORPAY_KEY_SECRET);
    try {
        $sig = $_SERVER['HTTP_X_RAZORPAY_SIGNATURE'];
        $api->utility->verifyWebhookSignature($post_data, $sig, RAZORPAY_WEBHOOK_SECRET);
    } catch (SignatureVerificationError $e) {
        http_response_code(400);
        exit;
    }
}

$conn = get_db_connection();

if ($data['event'] === 'payment.captured') {
    $payment = $data['payload']['payment']['entity'];
    $razorpay_order_id = $payment['order_id'];
    $razorpay_payment_id = $payment['id'];
    
    // We stored our internal order id in notes
    $internal_order_id = $payment['notes']['internal_order_id'] ?? null;
    $order_number = $payment['notes']['order_number'] ?? null;

    if ($internal_order_id) {
        // Find the order
        $order = fetch_one("SELECT id, order_status FROM orders WHERE id = ?", [$internal_order_id]);
        
        if ($order && $order['order_status'] === 'pending_payment') {
            // Update to Paid
            execute_query("UPDATE orders SET razorpay_payment_id = ?, payment_status = 'paid', order_status = 'pending' WHERE id = ?", [$razorpay_payment_id, $internal_order_id]);
            
            // Decrement Stock for Order Items
            $items = fetch_all("SELECT product_id, quantity FROM order_items WHERE order_id = ?", [$internal_order_id]);
            foreach ($items as $item) {
                execute_query("UPDATE products SET stock = GREATEST(0, stock - ?) WHERE id = ?", [(int)$item['quantity'], (int)$item['product_id']]);
            }

            // Add history
            execute_query("INSERT INTO order_status_history (order_id, status, notes) VALUES (?, 'pending', 'Order confirmed via Webhook')", [$internal_order_id]);
            
            // Send email non-blocking
            try {
                send_order_confirmation($internal_order_id);
            } catch (Throwable $e) {
                error_log("Webhook order confirmation email failed: " . $e->getMessage());
            }
            
            // Log for admin
            create_admin_notification("Payment Captured for Order #$order_number via Webhook", 'order', "orders.php?id=$internal_order_id");
        }
    }
}

http_response_code(200);
echo json_encode(['success' => true]);
