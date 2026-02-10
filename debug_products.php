<?php
require_once 'config/database.php';
$res = mysqli_query(get_db_connection(), "DESCRIBE products");
while($row = mysqli_fetch_assoc($res)) {
    print_r($row);
}


