<?php
require_once 'config/database.php';
$conn = get_db_connection();
$table = $_GET['t'] ?? 'newsletter_subscribers';
$result = $conn->query("DESCRIBE `$table` ");
while ($row = $result->fetch_assoc()) {
    echo $row['Field'] . " | " . $row['Type'] . " | " . $row['Null'] . " | " . $row['Key'] . "\n";
}
?>


