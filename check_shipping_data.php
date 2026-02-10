<?php
require 'config/database.php';
echo "SHIPPING ZONES:\n";
$res = execute_query('SELECT * FROM shipping_zones');
while($row = mysqli_fetch_assoc($res)) {
    print_r($row);
}
echo "\nSHIPPING METHODS:\n";
$res = execute_query('SELECT * FROM shipping_methods');
while($row = mysqli_fetch_assoc($res)) {
    print_r($row);
}
echo "\nSHIPPING RATES COUNT:\n";
$res = execute_query('SELECT COUNT(*) as count FROM shipping_rates');
$row = mysqli_fetch_assoc($res);
echo $row['count'] . "\n";
?>


