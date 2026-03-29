<?php
require_once __DIR__ . '/../config/database.php';
$conn = get_db_connection();

// 1. Get all active images from the database
$res = $conn->query("SELECT image, image_tablet, image_mobile FROM hero_slides");
$active_files = [];
while ($row = $res->fetch_assoc()) {
    if ($row['image']) $active_files[] = basename($row['image']);
    if ($row['image_tablet']) $active_files[] = basename($row['image_tablet']);
    if ($row['image_mobile']) $active_files[] = basename($row['image_mobile']);
}

// 2. Scan the uploads directory
$upload_dir = __DIR__ . '/../assets/images/uploads/';
$files = scandir($upload_dir);
$deleted_count = 0;
$freed_space = 0;

foreach ($files as $file) {
    if ($file === '.' || $file === '..') continue;
    
    // Only target files starting with slide_ (to avoid deleting product/other images)
    if (strpos($file, 'slide_') === 0 || strpos($file, 'vid_thumb_') === 0) {
        if (!in_array($file, $active_files)) {
            $path = $upload_dir . $file;
            $freed_space += filesize($path);
            unlink($path);
            $deleted_count++;
        }
    }
}

echo "Cleaned up $deleted_count orphaned slider files.\n";
echo "Freed up " . round($freed_space / 1024 / 1024, 2) . " MB.\n";
?>
