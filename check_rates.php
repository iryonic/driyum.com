<?php
require 'config/database.php';
$res = execute_query('SELECT * FROM shipping_rates WHERE zone_id = 1');
while($row = mysqli_fetch_assoc($res)) {
    print_r($row);
}
?>


