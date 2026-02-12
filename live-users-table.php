<?php
require_once 'config/database.php';

$conn = get_db_connection();

$sql = "CREATE TABLE IF NOT EXISTS live_users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    session_id VARCHAR(255) NOT NULL UNIQUE,
    user_id INT NULL,
    ip_address VARCHAR(45) NULL,
    last_activity TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    current_page VARCHAR(255) NULL,
    INDEX (last_activity),
    INDEX (session_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

if ($conn->query($sql)) {
    echo "Table live_users created successfully\n";
} else {
    echo "Error creating table: " . $conn->error . "\n";
}
?>
