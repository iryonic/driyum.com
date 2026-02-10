<?php
require 'config/database.php';
$data = [
    'zones' => fetch_all("SELECT * FROM shipping_zones"),
    'methods' => fetch_all("SELECT * FROM shipping_methods"),
    'rates' => fetch_all("SELECT * FROM shipping_rates")
];
file_put_contents('shipping_dump.json', json_encode($data, JSON_PRETTY_PRINT));
echo "Dumped to shipping_dump.json";
?>


