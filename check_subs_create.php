<?php
require 'config/database.php';
$res = get_db_connection()->query("SHOW CREATE TABLE newsletter_subscribers");
$row = $res->fetch_array();
echo $row[1];
?>
