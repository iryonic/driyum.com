<?php
require_once 'config/database.php';
$conn = get_db_connection();
$result = $conn->query("DESCRIBE order_status_history");
if (!$result) {
    echo "NO TABLE";
    exit;
}
while ($row = $result->fetch_assoc()) {
    echo $row['Field'] . " | " . $row['Null'] . "\n";
}
?>


