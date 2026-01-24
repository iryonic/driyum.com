<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$code = sanitize_input($_POST['code'] ?? '');
$total = (float)($_POST['total'] ?? 0);

if (empty($code)) {
    echo json_encode(['success' => false, 'message' => 'Please enter a coupon code']);
    exit;
}

$result = validate_coupon($code, $total);

if ($result['valid']) {
    // Save to session for use in checkout
    $_SESSION['coupon'] = [
        'id' => $result['coupon']['id'],
        'code' => $result['coupon']['code'],
        'discount' => $result['discount']
    ];
    
    echo json_encode([
        'success' => true,
        'message' => 'Coupon applied successfully!',
        'discount' => $result['discount'],
        'new_total' => $total - $result['discount']
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => $result['message']
    ]);
}
