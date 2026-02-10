<?php
require_once 'config/database.php';
$conn = get_db_connection();

$queries = [
    "ALTER TABLE hero_slides ADD COLUMN show_title TINYINT(1) DEFAULT 1 AFTER subtitle",
    "ALTER TABLE hero_slides ADD COLUMN show_subtitle TINYINT(1) DEFAULT 1 AFTER show_title",
    "ALTER TABLE hero_slides ADD COLUMN show_cta TINYINT(1) DEFAULT 1 AFTER cta_link"
];

foreach ($queries as $sql) {
    if ($conn->query($sql)) {
        echo "Executed: $sql\n";
    } else {
        echo "Error: " . $conn->error . "\n";
    }
}
?>
