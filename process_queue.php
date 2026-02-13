<?php
// process_queue.php
// Run this via cron or background process
// Usage: php process_queue.php

// Ensure we are in the right directory context if run from CLI
if (php_sapi_name() === 'cli') {
    chdir(__DIR__);
}

require_once 'config/database.php';
require_once 'includes/functions.php';

// Prevent timeout
set_time_limit(120); // 2 minutes max
ini_set('memory_limit', '1024M');

$conn = get_db_connection();

// Check for pending emails
// Limit to 5 per batch for web-triggered processing, or 50 for CLI
$limit = (php_sapi_name() === 'cli') ? 50 : 5;

// We use the column names from functions.php: recipient, subject, message, last_attempt
$sql = "SELECT id, recipient, subject, message FROM email_queue WHERE status = 'pending' AND attempts < 3 ORDER BY created_at ASC LIMIT ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $limit);
$stmt->execute();
$result = $stmt->get_result();

$processed = 0;

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $id = $row['id'];
        $to = $row['recipient'];
        $subject = $row['subject'];
        $body = $row['message'];
        
        // Try sending
        $sent = send_email($to, $subject, $body);
        
        if ($sent) {
            $update = $conn->prepare("UPDATE email_queue SET status = 'sent', last_attempt = NOW() WHERE id = ?");
            $update->bind_param("i", $id);
            $update->execute();
        } else {
            $update = $conn->prepare("UPDATE email_queue SET attempts = attempts + 1, last_attempt = NOW(), status = IF(attempts >= 3, 'failed', 'pending') WHERE id = ?");
            $update->bind_param("i", $id);
            $update->execute();
        }
        $processed++;
        
        // Slight pause to be nice to SMTP
        if (php_sapi_name() === 'cli') {
            usleep(100000); // 0.1s
        }
    }
}

// In CLI mode, we can output text. In web mode, we might want JSON or nothing.
if (php_sapi_name() === 'cli') {
    echo "Processed $processed emails.\n";
} else {
    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'processed' => $processed]);
}
?>
