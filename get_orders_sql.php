<?php
require_once 'config/database.php';
$conn = get_db_connection();
$res = $conn->query("SHOW CREATE TABLE orders");
$row = $res->fetch_assoc();
file_put_contents('orders_sql.txt', $row['Create Table']);
?>
