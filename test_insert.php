<?php
require_once 'config/database.php';
$conn = get_db_connection();
$conn->begin_transaction();
try {
    $sql = "INSERT INTO orders (order_number, user_id, subtotal, total, payment_method, shipping_address) VALUES ('TEST-GUEST-1', NULL, 100, 100, 'cod', 'test address')";
    if ($conn->query($sql)) {
        echo "SUCCESS: Guest order inserted.\n";
    } else {
        echo "ERROR: " . $conn->error . "\n";
    }
    $conn->rollback();
} catch (Exception $e) {
    echo "EXCEPTION: " . $e->getMessage() . "\n";
}
?>


