<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

// Exclude items already in cart
$exclude_ids = !empty($_SESSION['cart']) ? array_keys($_SESSION['cart']) : [];
$where_clause = "WHERE stock > 0 AND is_active = 1";

if (!empty($exclude_ids)) {
    $ids_str = implode(',', array_map('intval', $exclude_ids));
    $where_clause .= " AND id NOT IN ($ids_str)";
}

$sql = "SELECT id, name, price, image, description, slug, stock FROM products $where_clause ORDER BY RAND() LIMIT 8";
$products = fetch_all($sql);

echo json_encode(['success' => true, 'products' => $products]);


