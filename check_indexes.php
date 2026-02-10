<?php
require_once 'config/database.php';
$conn = get_db_connection();
$res = mysqli_query($conn, "SHOW INDEX FROM settings");
while($row = mysqli_fetch_assoc($res)) {
    print_r($row);
}
?>


