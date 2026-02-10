<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

if (!is_logged_in()) {
    echo json_encode(['success' => false, 'message' => 'Login required']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$action = $input['action'] ?? '';
$product_id = $input['product_id'] ?? null;
$user_id = $_SESSION['user_id'];

switch ($action) {
    case 'add':
        if (!$product_id) {
            echo json_encode(['success' => false, 'message' => 'Product ID required']);
            exit;
        }
        
        $sql = "INSERT INTO wishlist (user_id, product_id) VALUES (?, ?) ON DUPLICATE KEY UPDATE created_at = NOW()";
        execute_query($sql, [$user_id, $product_id]);
        
        echo json_encode(['success' => true, 'message' => 'Added to wishlist']);
        break;
        
    case 'remove':
        if (!$product_id) {
            echo json_encode(['success' => false, 'message' => 'Product ID required']);
            exit;
        }
        
        $sql = "DELETE FROM wishlist WHERE user_id = ? AND product_id = ?";
        execute_query($sql, [$user_id, $product_id]);
        
        echo json_encode(['success' => true, 'message' => 'Removed from wishlist']);
        break;
        
    case 'get':
        $sql = "SELECT p.* FROM products p 
                INNER JOIN wishlist w ON w.product_id = p.id 
                WHERE w.user_id = ? AND p.is_active = 1";
        $products = fetch_all($sql, [$user_id]);
        
        echo json_encode(['success' => true, 'products' => $products]);
        break;
        
    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
}


