<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json');

$reel_id = intval($_GET['id'] ?? $_POST['id'] ?? 0);
if ($reel_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid reel ID']);
    exit;
}

try {
    if (function_exists('ensure_reels_table')) {
        ensure_reels_table();
    }
    $conn = get_db_connection();

    // 1. Increment on-site view count
    $stmt = $conn->prepare("UPDATE reels SET views_count = views_count + 1 WHERE id = ?");
    $stmt->bind_param("i", $reel_id);
    $stmt->execute();

    // 2. Fetch updated reel view data
    $stmt_get = $conn->prepare("SELECT id, views_badge, views_count FROM reels WHERE id = ?");
    $stmt_get->bind_param("i", $reel_id);
    $stmt_get->execute();
    $res = $stmt_get->get_result();
    $row = $res->fetch_assoc();

    if (!$row) {
        echo json_encode(['success' => false, 'message' => 'Reel not found']);
        exit;
    }

    // Format numbers dynamically if badge is purely numeric or empty
    function format_dynamic_views($num) {
        if ($num >= 1000000000) {
            $val = round($num / 1000000000, 1);
            return ($val == floor($val) ? (int)$val : $val) . 'B';
        }
        if ($num >= 1000000) {
            $val = round($num / 1000000, 1);
            return ($val == floor($val) ? (int)$val : $val) . 'M';
        }
        if ($num >= 1000) {
            $val = round($num / 1000, 1);
            return ($val == floor($val) ? (int)$val : $val) . 'K';
        }
        return (string)$num;
    }

    $badge = format_dynamic_views($row['views_count']);
    if ($row['views_badge'] !== $badge) {
        $stmt_up = $conn->prepare("UPDATE reels SET views_badge = ? WHERE id = ?");
        $stmt_up->bind_param("si", $badge, $reel_id);
        $stmt_up->execute();
    }

    echo json_encode([
        'success' => true,
        'reel_id' => $reel_id,
        'views_count' => intval($row['views_count']),
        'views_badge' => $badge
    ]);
} catch (Throwable $e) {
    error_log("track_reel_view error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Tracking unavailable']);
}
