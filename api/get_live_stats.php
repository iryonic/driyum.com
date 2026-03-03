<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

// Only allow admins to access this
if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$live_users = get_live_user_count(5);
$excluded_statuses = "'cancelled', 'pending', 'pending_payment'";
$sales_today = fetch_one("SELECT SUM(total) as t FROM orders WHERE DATE(created_at) = CURDATE() AND order_status NOT IN ($excluded_statuses)")['t'] ?? 0;
$pending_orders = fetch_one("SELECT COUNT(*) as c FROM orders WHERE order_status = 'pending'")['c'];

echo json_encode([
    'success' => true,
    'live_users' => $live_users,
    'sales_today' => number_format($sales_today),
    'pending_orders' => $pending_orders
]);
?>
