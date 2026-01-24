<?php
require 'config/database.php';
$res = execute_query('SELECT id, carrier_name, status FROM shipping_methods');
while($row = mysqli_fetch_assoc($res)) {
    echo "ID: {$row['id']} | Carrier: {$row['carrier_name']} | Status: {$row['status']}\n";
}
?>
