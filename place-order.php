<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

// Verification
if (empty($_SESSION['cart']) || empty($_SESSION['checkout_data'])) {
    header("Location: " . get_url('shop.php'));
    exit;
}

$conn = get_db_connection();

try {
    $conn->begin_transaction();

    // 1. Calculate Finals
    $subtotal = 0;
    $ids = implode(',', array_keys($_SESSION['cart']));
    $products_result = $conn->query("SELECT * FROM products WHERE id IN ($ids)");
    $products = [];
    while($row = $products_result->fetch_assoc()) {
        $products[$row['id']] = $row;
        $subtotal += $row['price'] * $_SESSION['cart'][$row['id']];
    }

    $shipping = ($subtotal >= 500) ? 0 : 50;
    $tax = ceil($subtotal * 0.05);
    $total = $subtotal + $shipping + $tax;

    // 2. Prepare User Data
    if (!isset($_SESSION['user_id'])) {
        header("Location: " . get_url('login.php')); // Should not happen if checkout required login
        exit;
    }
    $user_id = $_SESSION['user_id'];
    
    $checkout = $_SESSION['checkout_data'];
    $method = $_POST['method'] ?? 'cod';
    $status = 'pending';
    $order_number = 'ORD-' . strtoupper(uniqid());

    // Combine address parts into one string for storage
    $full_shipping_details = json_encode([
        'name' => $checkout['first_name'] . ' ' . $checkout['last_name'],
        'email' => $checkout['email'],
        'phone' => $checkout['phone'],
        'address' => $checkout['address'],
        'city' => $checkout['city'],
        'zip' => $checkout['zip']
    ]);

    // 3. Insert Order
    // Columns: order_number, user_id, subtotal, shipping_cost, total, payment_method, order_status, shipping_address
    $stmt = $conn->prepare("INSERT INTO orders (order_number, user_id, subtotal, shipping_cost, total, payment_method, order_status, shipping_address) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    
    $stmt->bind_param("sidddsss", $order_number, $user_id, $subtotal, $shipping, $total, $method, $status, $full_shipping_details);
    $stmt->execute();
    $order_id = $stmt->insert_id;

    // 4. Insert Order Items
    $stmt_item = $conn->prepare("INSERT INTO order_items (order_id, product_id, quantity, price, subtotal) VALUES (?, ?, ?, ?, ?)");
    foreach ($_SESSION['cart'] as $pid => $qty) {
        $price = $products[$pid]['price'];
        $line_subtotal = $price * $qty;
        $stmt_item->bind_param("iiidd", $order_id, $pid, $qty, $price, $line_subtotal);
        $stmt_item->execute();
    }

    $conn->commit();

    // 5. Success
    unset($_SESSION['cart']);
    unset($_SESSION['checkout_data']);
    
    header("Location: order-success.php?id=" . $order_id);

} catch (Exception $e) {
    $conn->rollback();
    die("Order failed: " . $e->getMessage());
}
?>


