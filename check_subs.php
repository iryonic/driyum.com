<?php
require 'config/database.php';
$res = get_db_connection()->query("DESCRIBE newsletter_subscribers");
while($row = $res->fetch_assoc()) {
    echo $row['Field'] . " (" . $row['Type'] . ")\n";
}
?>


