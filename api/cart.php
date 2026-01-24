<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

$action = $_GET['action'] ?? '';

if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

try {
    // --- ADD ITEM ---
    if ($action === 'add') {
        $product_id = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;
        $quantity = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 1;

        if ($product_id > 0) {
            $product = fetch_one("SELECT name, price, image, stock FROM products WHERE id = ?", [$product_id]);
            if (!$product) throw new Exception("Product not found");

            $current_in_cart = isset($_SESSION['cart'][$product_id]) ? $_SESSION['cart'][$product_id] : 0;
            $new_quantity = $current_in_cart + $quantity;

            if ($new_quantity > $product['stock']) {
                $available = $product['stock'] - $current_in_cart;
                throw new Exception("Only " . max(0, $product['stock']) . " items available in stock.");
            }

            $_SESSION['cart'][$product_id] = $new_quantity;
            sync_cart_to_db();
            
            echo json_encode([
                'success' => true, 
                'message' => 'Added to cart!',
                'cart_count' => array_sum($_SESSION['cart']),
                'product' => $product
            ]);
        } else {
            throw new Exception("Invalid product ID");
        }
    } 
    // --- UPDATE QUANTITY ---
    elseif ($action === 'update') {
        $product_id = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;
        $quantity = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 0;

        if ($product_id > 0) {
            if ($quantity <= 0) {
                unset($_SESSION['cart'][$product_id]);
            } else {
                $product = fetch_one("SELECT stock FROM products WHERE id = ?", [$product_id]);
                if ($quantity > $product['stock']) {
                    throw new Exception("Only " . $product['stock'] . " items available in stock.");
                }
                $_SESSION['cart'][$product_id] = $quantity;
            }
            sync_cart_to_db();
            echo json_encode(['success' => true, 'message' => 'Cart updated']);
        }
    }
    // --- GET COUNT ---
    elseif ($action === 'get_count') {
        echo json_encode(['success' => true, 'count' => array_sum($_SESSION['cart'])]);
    }
    // --- GET ITEMS (SIDEBAR/CART) ---
    elseif ($action === 'get_items') {
        if (empty($_SESSION['cart'])) {
            echo json_encode(['success' => true, 'items' => [], 'subtotal' => 0]);
            exit;
        }

        $ids = array_keys($_SESSION['cart']);
        $placeholders = str_repeat('?,', count($ids) - 1) . '?';
        $sql = "SELECT id, name, price, image, weight FROM products WHERE id IN ($placeholders)";
        $products = fetch_all($sql, $ids);
        
        $cart_items = [];
        $subtotal = 0;
        
        foreach ($products as $p) {
            $qty = $_SESSION['cart'][$p['id']];
            $total_price = $p['price'] * $qty;
            $subtotal += $total_price;
            
            $cart_items[] = [
                'id' => $p['id'],
                'name' => $p['name'],
                'price' => $p['price'],
                'image' => $p['image'],
                'weight' => $p['weight'],
                'quantity' => $qty,
                'total' => $total_price
            ];
        }
        
        echo json_encode([
            'success' => true, 
            'items' => $cart_items, 
            'subtotal' => $subtotal,
            'count' => array_sum($_SESSION['cart'])
        ]);
    }
    // --- REMOVE COUPON ---
    elseif ($action === 'remove_coupon') {
        unset($_SESSION['coupon']);
        echo json_encode(['success' => true, 'message' => 'Coupon removed']);
    }
    // --- SET SHIPPING ---
    elseif ($action === 'set_shipping') {
        $cost = isset($_POST['cost']) ? (float)$_POST['cost'] : 0;
        $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
        $_SESSION['shipping_cost'] = $cost;
        $_SESSION['shipping_method_id'] = $id;
        echo json_encode(['success' => true, 'message' => 'Shipping set']);
    }
    else {
        throw new Exception("Invalid action");
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
