<?php
require_once 'config/database.php';
$conn = get_db_connection();
$table = 'shipping_zones';
$res = $conn->query("DESC $table");
echo "Fields in $table:\n";
while($row = $res->fetch_assoc()) {
    echo $row['Field'] . "\n";
}
echo "\n";
$table = 'shipping_methods';
$res = $conn->query("DESC $table");
echo "Fields in $table:\n";
while($row = $res->fetch_assoc()) {
    echo $row['Field'] . "\n";
}
?>
