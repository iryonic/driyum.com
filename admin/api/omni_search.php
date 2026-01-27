<?php
require_once '../../config/database.php';
require_once '../../includes/functions.php';

if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$q = $_GET['q'] ?? '';
if (strlen($q) < 2) {
    echo json_encode([]);
    exit;
}

$results = [];
$limit = 5;

// 1. Search Products
$products = fetch_all("SELECT id, name, image, price FROM products WHERE name LIKE ? OR id = ? LIMIT $limit", ["%$q%", (int)$q]);
foreach ($products as $p) {
    $results[] = [
        'type' => 'product',
        'id' => $p['id'],
        'title' => $p['name'],
        'subtitle' => '₹' . number_format($p['price']),
        'url' => 'product_form.php?id=' . $p['id'],
        'image' => $p['image']
    ];
}

// 2. Search Orders
$orders = fetch_all("SELECT id, order_number, total, order_status FROM orders WHERE order_number LIKE ? OR id = ? LIMIT $limit", ["%$q%", (int)$q]);
foreach ($orders as $o) {
    $results[] = [
        'type' => 'order',
        'id' => $o['id'],
        'title' => 'Order #' . ($o['order_number'] ?: $o['id']),
        'subtitle' => '₹' . number_format($o['total']) . ' • ' . strtoupper($o['order_status']),
        'url' => 'orders.php?id=' . $o['id'],
        'icon' => 'fas fa-shipping-fast'
    ];
}

// 3. Search Customers
$users = fetch_all("SELECT id, name, email FROM users WHERE name LIKE ? OR email LIKE ? LIMIT $limit", ["%$q%", "%$q%"]);
foreach ($users as $u) {
    $results[] = [
        'type' => 'customer',
        'id' => $u['id'],
        'title' => $u['name'],
        'subtitle' => $u['email'],
        'url' => 'users.php?q=' . urlencode($u['email']),
        'icon' => 'fas fa-user'
    ];
}

header('Content-Type: application/json');
echo json_encode($results);
