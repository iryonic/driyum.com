<?php
require_once '../config/database.php';
require_once '../includes/functions.php';

// Turn off error reporting to avoid breaking JSON
error_reporting(0);
ini_set('display_errors', 0);

header('Content-Type: application/json');

$query = $_GET['q'] ?? '';

try {
    if (strlen($query) < 2) {
        echo json_encode(['success' => false, 'results' => []]);
        exit;
    }

    // Search by name or description
    $sql = "SELECT id, name, price, image, slug FROM products 
            WHERE name LIKE ? OR description LIKE ? 
            LIMIT 10";
    $term = "%$query%";
    $products = fetch_all($sql, [$term, $term]);

    echo json_encode(['success' => true, 'results' => $products]);

} catch (Throwable $e) {
    // Return valid JSON error
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}


