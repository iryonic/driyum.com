<?php
require 'config/database.php';
$res = execute_query('SELECT DISTINCT zone_id FROM shipping_rates');
while($row = mysqli_fetch_assoc($res)) {
    echo "Zone ID: {$row['zone_id']}\n";
}
?>
