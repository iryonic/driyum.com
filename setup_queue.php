<?php
require_once 'config/database.php';

$conn = get_db_connection();

$sql = "CREATE TABLE IF NOT EXISTS email_queue (
    id INT AUTO_INCREMENT PRIMARY KEY,
    to_email VARCHAR(255) NOT NULL,
    subject VARCHAR(255) NOT NULL,
    body TEXT NOT NULL,
    status ENUM('pending', 'sent', 'failed') DEFAULT 'pending',
    attempts INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    sent_at TIMESTAMP NULL
)";

if ($conn->query($sql) === TRUE) {
    echo "Table email_queue created successfully";
} else {
    echo "Error creating table: " . $conn->error;
}

$conn->close();
?>


