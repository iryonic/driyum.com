<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

if (!isset($_GET['id']) || !is_admin()) {
    exit;
}

$id = (int)$_GET['id'];
mark_notification_read($id);
echo json_encode(['success' => true]);
?>
