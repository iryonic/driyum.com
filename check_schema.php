<?php
require 'config/database.php';
$res = execute_query('DESCRIBE shipping_zones');
if (!$res) die("Query failed: " . mysqli_error(get_db_connection()));
while($row = mysqli_fetch_assoc($res)) {
    echo $row['Field'] . "\n";
}
echo "---\n";
$res = execute_query('SELECT * FROM shipping_zones');
while($row = mysqli_fetch_assoc($res)) {
    print_r($row);
}
?>


