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
$all = isset($_GET['all']) ? (int)$_GET['all'] : 0;
$q = isset($_GET['q']) ? sanitize_input($_GET['q']) : null;

$query = "SELECT * FROM newsletter_subscribers";
$params = [];

if ($ids) {
    // Sanitize IDs - ensure they are integers separated by commas
    $id_array = array_map('intval', explode(',', $ids));
    if (empty($id_array)) die("No valid IDs");
    $placeholders = implode(',', array_fill(0, count($id_array), '?'));
    $query .= " WHERE id IN ($placeholders)";
    $params = $id_array;
} else if ($all) {
    if ($q) {
        $query .= " WHERE email LIKE ?";
        $params[] = "%$q%";
    }
} else {
    die("No data to export");
}

$query .= " ORDER BY subscribed_at DESC";
$subscribers = fetch_all($query, $params);

if (empty($subscribers)) {
    die("No subscribers found for export.");
}

// CSV Headers
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=subscribers_' . date('Y-m-d') . '.csv');

$output = fopen('php://output', 'w');

// Header row
fputcsv($output, ['ID', 'Email', 'Active', 'Subscribed At']);

foreach ($subscribers as $row) {
    fputcsv($output, [
        $row['id'],
        $row['email'],
        $row['is_active'] ? 'Yes' : 'No',
        $row['subscribed_at']
    ]);
}

fclose($output);
exit;
?>
