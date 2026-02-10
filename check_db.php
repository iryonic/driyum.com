<?php
require_once 'config/database.php';

$conn = get_db_connection();
$result = mysqli_query($conn, "SHOW TABLES LIKE 'settings'");
if (mysqli_num_rows($result) > 0) {
    echo "Table 'settings' exists.\n";
    $res = mysqli_query($conn, "DESCRIBE settings");
    while($row = mysqli_fetch_assoc($res)) {
        print_r($row);
    }
} else {
    echo "Table 'settings' DOES NOT EXIST.\n";
}
?>


