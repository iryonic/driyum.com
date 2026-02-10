<?php
require_once 'config/database.php';
$conn = get_db_connection();
$sql = "ALTER TABLE orders MODIFY user_id INT NULL";
if ($conn->query($sql)) {
    echo "SUCCESS: Column user_id is now nullable.\n";
} else {
    echo "ERROR: " . $conn->error . "\n";
}

// Also check for foreign key constraints that might prevent this
$res = $conn->query("SHOW CREATE TABLE orders");
$row = $res->fetch_assoc();
echo "Table Structure:\n" . $row['Create Table'] . "\n";
?>


