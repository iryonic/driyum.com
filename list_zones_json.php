<?php
require 'config/database.php';
$zones = fetch_all("SELECT id, zone_name, pincode_ranges FROM shipping_zones");
echo json_encode($zones, JSON_PRETTY_PRINT);
?>
