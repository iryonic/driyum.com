<?php
require_once 'config/database.php';
$conn = get_db_connection();
$result = mysqli_query($conn, "DESCRIBE partners");
while ($row = mysqli_fetch_assoc($result)) {
    print_r($row);
}
