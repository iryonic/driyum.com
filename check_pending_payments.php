<?php
require_once 'config/database.php';

$conn = get_db_connection();
$query = "SELECT order_number, total, payment_method, order_status, payment_status, created_at, razorpay_payment_id 
          FROM orders 
          WHERE order_status = 'pending_payment' OR payment_status = 'pending' 
          ORDER BY created_at DESC";

$result = $conn->query($query);
$orders = [];
while ($row = $result->fetch_assoc()) {
    $orders[] = $row;
}

header('Content-Type: application/json');
echo json_encode($orders, JSON_PRETTY_PRINT);
?>
