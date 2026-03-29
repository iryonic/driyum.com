<?php
require_once 'config/database.php';
$conn = get_db_connection();

echo "Starting Partners Table Migration...\n";

// 1. Create table if not exists
$create_query = "CREATE TABLE IF NOT EXISTS partners (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    location VARCHAR(255) NOT NULL,
    logo VARCHAR(255) DEFAULT NULL,
    is_active TINYINT(1) DEFAULT 1,
    sort_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

if (mysqli_query($conn, $create_query)) {
    echo "✓ Table 'partners' ensured.\n";
} else {
    echo "✗ Error creating table: " . mysqli_error($conn) . "\n";
}

// 2. Ensure all columns exist (in case table existed but was different)
$cols = [
    'location' => "ALTER TABLE partners ADD COLUMN location VARCHAR(255) AFTER name",
    'logo' => "ALTER TABLE partners ADD COLUMN logo VARCHAR(255) DEFAULT NULL AFTER location",
    'is_active' => "ALTER TABLE partners ADD COLUMN is_active TINYINT(1) DEFAULT 1",
    'sort_order' => "ALTER TABLE partners ADD COLUMN sort_order INT DEFAULT 0"
];

foreach ($cols as $col => $sql) {
    $check = mysqli_query($conn, "SHOW COLUMNS FROM partners LIKE '$col'");
    if (mysqli_num_rows($check) == 0) {
        if (mysqli_query($conn, $sql)) {
            echo "✓ Column '$col' added.\n";
        } else {
            echo "✗ Error adding column '$col': " . mysqli_error($conn) . "\n";
        }
    }
}

echo "Migration Complete.\n";
