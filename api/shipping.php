<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pincode = $_POST['pincode'] ?? '';
    
    if (empty($pincode)) {
        echo json_encode(['success' => false, 'message' => 'Pincode is required']);
        exit;
    }
    $_SESSION['shipping_zip'] = $pincode;

    $weight = get_cart_weight();
    $methods = get_shipping_methods_with_rates($pincode, $weight);

    if (empty($methods)) {
        echo json_encode(['success' => false, 'message' => 'No shipping methods available for this pincode']);
        exit;
    }

    echo json_encode(['success' => true, 'methods' => $methods, 'weight' => $weight]);
    exit;
}

// If GET, maybe for a specific product
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['pincode']) && isset($_GET['product_id'])) {
    $pincode = $_GET['pincode'];
    $product_id = (int)$_GET['product_id'];
    
    $product = get_product_by_id($product_id);
    if (!$product) {
        echo json_encode(['success' => false, 'message' => 'Product not found']);
        exit;
    }

    // Single product weight
    $weight_str = strtolower($product['weight'] ?? '0');
    $val = (float)$weight_str;
    $weight = $val;
    if (strpos($weight_str, 'g') !== false) $weight = $val / 1000;

    $methods = get_shipping_methods_with_rates($pincode, $weight);

    echo json_encode(['success' => true, 'methods' => $methods]);
    exit;
}

echo json_encode(['success' => false, 'message' => 'Invalid request']);
