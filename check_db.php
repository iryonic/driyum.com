<?php
require_once 'config/database.php';
$conn = get_db_connection();
$res = $conn->query("SHOW CREATE TABLE hero_slides");
$row = $res->fetch_assoc();
echo "TABLE STRUCTURE:\n";
echo $row['Create Table'] . "\n\n";

$res = $conn->query("SELECT id, title FROM hero_slides");
echo "DATA:\n";
while($r = $res->fetch_assoc()) {
    echo "ID: {$r['id']}, Title: {$r['title']}\n";
}
?>
