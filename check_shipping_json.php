<?php
require 'config/database.php';
$data = [];
$res = execute_query('SELECT * FROM shipping_zones');
while($row = mysqli_fetch_assoc($res)) $data['zones'][] = $row;

$res = execute_query('SELECT * FROM shipping_methods');
while($row = mysqli_fetch_assoc($res)) $data['methods'][] = $row;

$res = execute_query('SELECT * FROM shipping_rates');
while($row = mysqli_fetch_assoc($res)) $data['rates'][] = $row;

echo json_encode($data, JSON_PRETTY_PRINT);
?>


