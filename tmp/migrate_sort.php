<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once __DIR__ . '/../config/database.php';

$conn = get_db_connection();

function migrate_table($table) {
    global $conn;
    echo "Processing table: $table...\n";

    // 1. Add Index (ignoring duplicates)
    try {
        @$conn->query("ALTER TABLE $table ADD INDEX idx_sort_order (sort_order)");
        echo "Index added successfully (or already exists).\n";
    } catch (Exception $e) {
        echo "Note: " . $e->getMessage() . "\n";
    }

    // 2. Rebalance (Unique Sequential Order)
    $res = $conn->query("SELECT id FROM $table ORDER BY sort_order ASC, id ASC");
    $count = 1;
    while ($row = $res->fetch_assoc()) {
        $id = $row['id'];
        $conn->query("UPDATE $table SET sort_order = $count WHERE id = $id");
        $count++;
    }
    echo "Table $table rebalanced with unique sequential orders (1-".($count-1).").\n\n";
}

migrate_table('hero_slides');
migrate_table('partners');
migrate_table('trust_badges');

echo "Indexing and Rebalancing Complete!";
?>
