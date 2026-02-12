<?php
/**
 * DRIYUM - Activity Tracker
 * Tracks live users by session id and last activity timestamp.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/database.php';

function track_user_activity() {
    $conn = get_db_connection();
    
    $session_id = session_id();
    if (!$session_id) return;
    
    $user_id = $_SESSION['user_id'] ?? null;
    $ip_address = $_SERVER['REMOTE_ADDR'] ?? null;
    $current_page = $_SERVER['REQUEST_URI'] ?? null;
    
    // Cleanup old sessions (e.g., older than 30 minutes) to keep table small
    $conn->query("DELETE FROM live_users WHERE last_activity < (NOW() - INTERVAL 30 MINUTE)");
    
    $stmt = $conn->prepare("INSERT INTO live_users (session_id, user_id, ip_address, current_page, last_activity) 
                            VALUES (?, ?, ?, ?, CURRENT_TIMESTAMP) 
                            ON DUPLICATE KEY UPDATE 
                            user_id = VALUES(user_id), 
                            ip_address = VALUES(ip_address), 
                            current_page = VALUES(current_page), 
                            last_activity = CURRENT_TIMESTAMP");
                            
    if ($stmt) {
        $stmt->bind_param("siss", $session_id, $user_id, $ip_address, $current_page);
        $stmt->execute();
        $stmt->close();
    }
}

// Only track if not an admin page or if you want to track admins too (usually not)
if (strpos($_SERVER['REQUEST_URI'], '/admin/') === false) {
    track_user_activity();
}
?>
