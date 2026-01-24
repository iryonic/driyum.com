<?php
require_once 'config/database.php';
$conn = get_db_connection();
$res = $conn->query("DESC products");
while($row = $res->fetch_assoc()) {
    echo $row['Field'] . "\n";
}
?>
