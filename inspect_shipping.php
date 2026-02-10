<?php
require_once 'config/database.php';
$conn = get_db_connection();

$tables = ['shipping_zones', 'shipping_methods', 'shipping_rates'];
foreach ($tables as $table) {
    echo "--- $table ---\n";
    $res = $conn->query("DESC $table");
    while($row = $res->fetch_assoc()) {
        echo $row['Field'] . " (" . $row['Type'] . ")\n";
    }
    echo "\nData:\n";
    $res = $conn->query("SELECT * FROM $table");
    while($row = $res->fetch_assoc()) {
        echo "ID: " . $row['id'] . " | " . (isset($row['zone_name']) ? $row['zone_name'] : (isset($row['display_name']) ? $row['display_name'] : '')) . " | Pincodes: " . ($row['pincode_ranges'] ?? 'N/A') . "\n";
    }
    echo "\n\n";
}


