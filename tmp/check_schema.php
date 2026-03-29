<?php
require_once __DIR__ . '/../config/database.php';
$conn = get_db_connection();
$res = $conn->query("DESCRIBE hero_slides");
while ($r = $res->fetch_assoc()) {
    echo $r['Field'] . " - " . $r['Type'] . "\n";
}
?>
