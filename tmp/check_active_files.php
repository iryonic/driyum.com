<?php
require_once __DIR__ . '/../config/database.php';
$conn = get_db_connection();
$res = $conn->query("SELECT image, image_tablet, image_mobile FROM hero_slides");
$active_files = [];
while ($row = $res->fetch_assoc()) {
    if ($row['image']) $active_files[] = $row['image'];
    if ($row['image_tablet']) $active_files[] = $row['image_tablet'];
    if ($row['image_mobile']) $active_files[] = $row['image_mobile'];
}
echo implode("\n", $active_files);
?>
