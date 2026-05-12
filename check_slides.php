<?php
require_once 'config/database.php';
$res = $conn->query("SELECT id, title, image, image_tablet, image_mobile FROM hero_slides");
echo "HERO SLIDES DATA:\n";
while($row = $res->fetch_assoc()) {
    print_r($row);
}
?>
