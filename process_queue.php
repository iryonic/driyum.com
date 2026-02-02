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
set_time_limit(0);
ini_set('memory_limit', '512M');

$conn = get_db_connection();

// Check for pending emails
// Limit to 50 per batch to prevent overwhelming the server/SMTP limits
$limit = 50;
$sql = "SELECT * FROM email_queue WHERE status = 'pending' AND attempts < 3 ORDER BY created_at ASC LIMIT ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $limit);
$stmt->execute();
$result = $stmt->get_result();

$processed = 0;

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $id = $row['id'];
        $to = $row['to_email'];
        $subject = $row['subject'];
        $body = $row['body'];
        
        // Try sending
        $sent = send_email($to, $subject, $body);
        
        if ($sent) {
            $update = $conn->prepare("UPDATE email_queue SET status = 'sent', sent_at = NOW() WHERE id = ?");
            $update->bind_param("i", $id);
            $update->execute();
        } else {
            $update = $conn->prepare("UPDATE email_queue SET attempts = attempts + 1, status = IF(attempts >= 3, 'failed', 'pending') WHERE id = ?");
            $update->bind_param("i", $id);
            $update->execute();
        }
        $processed++;
        
        // Slight pause to be nice to SMTP
        usleep(100000); // 0.1s
    }
}

$conn->close();

echo "Processed $processed emails.\n";
?>
