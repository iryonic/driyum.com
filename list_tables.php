<?php
require 'config/database.php';
$res = get_db_connection()->query("SHOW TABLES");
while($row = $res->fetch_array()) {
    echo $row[0] . "\n";
}
?>
