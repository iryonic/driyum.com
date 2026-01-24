<?php
require_once 'config/database.php';
$conn = get_db_connection();
$stmt = $conn->prepare("INSERT INTO orders (order_number, user_id, subtotal, total, payment_method, order_status, shipping_address) VALUES (?, ?, ?, ?, ?, ?, ?)");
$num = 'TEST-GUEST-2';
$uid = null;
$sub = 100.0;
$tot = 100.0;
$meth = 'cod';
$stat = 'pending';
$addr = 'test address';
$stmt->bind_param("siidsss", $num, $uid, $sub, $tot, $meth, $stat, $addr);
if ($stmt->execute()) {
    echo "SUCCESS: Prepared statement worked with NULL user_id.\n";
} else {
    echo "ERROR: " . $stmt->error . "\n";
}
?>
