<?php
require_once 'config/database.php';
$conn = get_db_connection();
$res = $conn->query("SHOW TRIGGERS LIKE 'orders'");
$triggers = [];
while ($row = $res->fetch_assoc()) {
    $triggers[] = $row;
}
echo json_encode($triggers, JSON_PRETTY_PRINT);
?>
