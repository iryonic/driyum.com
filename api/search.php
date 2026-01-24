<?php
require_once '../config/database.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

$query = $_GET['q'] ?? '';

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
