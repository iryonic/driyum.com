<?php
require 'config/database.php';
$res = execute_query('SELECT id, zone_name, pincode_ranges FROM shipping_zones WHERE id IN (7, 8, 9, 1)');
while($row = mysqli_fetch_assoc($res)) {
    echo "ID: {$row['id']} | Name: {$row['zone_name']} | Ranges: {$row['pincode_ranges']}\n";
}
?>
