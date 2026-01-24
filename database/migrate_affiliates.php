<?php
require_once __DIR__ . '/../config/database.php';

$conn = get_db_connection();

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// 1. Create Affiliates Table
$sql1 = "CREATE TABLE IF NOT EXISTS affiliates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    code VARCHAR(50) UNIQUE NOT NULL,
    commission_rate DECIMAL(5,2) DEFAULT 10.00,
    discount_percentage DECIMAL(5,2) DEFAULT 10.00,
    bank_details TEXT,
    is_approved BOOLEAN DEFAULT FALSE,
    status ENUM('active', 'suspended') DEFAULT 'active',
    total_earnings DECIMAL(10, 2) DEFAULT 0.00,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_code (code),
    INDEX idx_user (user_id)
) ENGINE=InnoDB;";

if (mysqli_query($conn, $sql1)) {
    echo "Affiliates table created successfully.<br>";
} else {
    echo "Error creating table: " . mysqli_error($conn) . "<br>";
}

// 2. Update Orders Table
$checkCol = mysqli_query($conn, "SHOW COLUMNS FROM orders LIKE 'affiliate_id'");
if (mysqli_num_rows($checkCol) == 0) {
    $sql2 = "ALTER TABLE orders 
             ADD COLUMN affiliate_id INT NULL AFTER user_id,
             ADD COLUMN affiliate_commission DECIMAL(10, 2) DEFAULT 0.00 AFTER total,
             ADD FOREIGN KEY (affiliate_id) REFERENCES affiliates(id) ON DELETE SET NULL;";
    
    if (mysqli_query($conn, $sql2)) {
        echo "Orders table updated successfully.<br>";
    } else {
        echo "Error updating orders: " . mysqli_error($conn) . "<br>";
    }
} else {
    echo "Orders table already has affiliate columns.<br>";
}

echo "Migration Complete!";
?>
