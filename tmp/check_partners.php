<?php
require_once 'config/database.php';
$conn = get_db_connection();
$result = mysqli_query($conn, "DESCRIBE partners");
if(!$result) {
    echo "ERROR: Table 'partners' does not exist.\n";
    exit;
}
while ($row = mysqli_fetch_assoc($result)) {
    echo "Field: " . $row['Field'] . " | Type: " . $row['Type'] . "\n";
}
