<?php
require_once 'config/database.php';
require_once 'includes/functions.php';

$conn = get_db_connection();

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Check if is_combo column exists
$check = $conn->query("SHOW COLUMNS FROM products LIKE 'is_combo'");
if ($check && $check->num_rows == 0) {
    echo "Adding is_combo column...\n";
    $sql = "ALTER TABLE products ADD COLUMN is_combo TINYINT(1) DEFAULT 0 AFTER is_featured";
    if ($conn->query($sql)) {
        echo "Successfully added is_combo column.\n";
    } else {
        echo "Error: " . $conn->error . "\n";
    }
} else {
    echo "is_combo already exists.\n";
}

// Update existing combos
echo "Auto-marking existing combos...\n";
$conn->query("UPDATE products SET is_combo = 1 WHERE name LIKE '%Combo%' OR name LIKE '%Pack%' OR description LIKE '%Combo%' OR description LIKE '%Pack%'");
echo "Finished.\n";
?>
