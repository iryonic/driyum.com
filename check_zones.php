<?php
require 'config/database.php';
$res = execute_query('SELECT id, zone_name, is_active, pincode_ranges FROM shipping_zones');
while($row = mysqli_fetch_assoc($res)) {
    echo "ID: " . $row['id'] . " | Name: " . $row['zone_name'] . " | Active: " . $row['is_active'] . " | Ranges: " . $row['pincode_ranges'] . "\n";
}
?>


