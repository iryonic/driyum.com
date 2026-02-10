<?php
require 'config/database.php';
$rates = fetch_all("SELECT * FROM shipping_rates");
echo json_encode($rates, JSON_PRETTY_PRINT);
?>


