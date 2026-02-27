<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

// Strict Admin Check
if (!isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
    die("Unauthorized");
}

$conn = get_db_connection();

$ids = isset($_GET['ids']) ? $_GET['ids'] : null;
$q = isset($_GET['q']) ? sanitize_input($_GET['q']) : null;
$status_filter = isset($_GET['status_filter']) ? sanitize_input($_GET['status_filter']) : null;
$user_id = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;
$all = isset($_GET['all']) ? (int)$_GET['all'] : 0;

$where_clause = "WHERE 1=1";
$params = [];

if ($ids) {
    $id_array = array_map('intval', explode(',', $ids));
    if (!empty($id_array)) {
        $placeholders = implode(',', array_fill(0, count($id_array), '?'));
        $where_clause .= " AND o.id IN ($placeholders)";
        $params = array_merge($params, $id_array);
    }
} else if ($all) {
    if ($q) {
        $where_clause .= " AND (o.order_number LIKE ? OR o.shipping_address LIKE ?)";
        $params[] = "%$q%";
        $params[] = "%$q%";
    }
    if ($status_filter) {
        $where_clause .= " AND o.order_status = ?";
        $params[] = $status_filter;
    }
    if ($user_id) {
        $where_clause .= " AND o.user_id = ?";
        $params[] = $user_id;
    }
} else {
    die("No data to export");
}

$query = "SELECT o.*, u.name as user_name, u.email as user_email, u.phone as user_phone 
          FROM orders o 
          LEFT JOIN users u ON o.user_id = u.id 
          $where_clause 
          ORDER BY o.created_at DESC";

$orders = fetch_all($query, $params);

if (empty($orders)) {
    die("No orders found for export.");
}

// CSV Headers
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=orders_export_' . date('Y-m-d_His') . '.csv');

$output = fopen('php://output', 'w');

// Header row
fputcsv($output, ['Order ID', 'Order Number', 'Date', 'Customer Name', 'Email', 'Phone', 'Address', 'Total Value', 'Status', 'Payment Method', 'Tracking Number']);

foreach ($orders as $row) {
    $address = json_decode($row['shipping_address'] ?? '{}', true);
    
    // Fallback to shipping address if user info is missing (guests)
    $email = $row['user_email'] ?: ($address['email'] ?? 'N/A');
    $phone = $row['user_phone'] ?: ($address['phone'] ?? 'N/A');
    $customer_name = $row['user_name'] ?: ($address['name'] ?? 'Guest');
    
    $full_address = ($address['address'] ?? '') . ', ' . ($address['city'] ?? '') . ', ' . ($address['state'] ?? '') . ' - ' . ($address['zip'] ?? '');

    fputcsv($output, [
        $row['id'],
        $row['order_number'],
        $row['created_at'],
        $customer_name,
        $email,
        $phone,
        $full_address,
        $row['total'],
        strtoupper($row['order_status']),
        $row['payment_method'],
        $row['tracking_number'] ?? 'N/A'
    ]);
}

fclose($output);
exit;
