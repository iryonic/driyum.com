y <?php
require_once 'config/database.php';
$conn = get_db_connection();

echo "Starting Database Repair...\n";

// 1. Give unique IDs to any rows that have ID 0 or duplicates
$res = $conn->query("SELECT * FROM hero_slides");
$rows = [];
while($row = $res->fetch_assoc()) {
    $rows[] = $row;
}

$conn->query("DELETE FROM hero_slides"); // Clear it temporarily

foreach($rows as $index => $row) {
    $new_id = $index + 1;
    $columns = array_keys($row);
    $placeholders = array_fill(0, count($columns), '?');
    $sql = "INSERT INTO hero_slides (" . implode(',', $columns) . ") VALUES (" . implode(',', $placeholders) . ")";
    $row['id'] = $new_id;
    
    $stmt = $conn->prepare($sql);
    $types = str_repeat('s', count($row)); // simplified for repair
    $values = array_values($row);
    $stmt->bind_param($types, ...$values);
    $stmt->execute();
    echo "Re-inserted row with new ID: $new_id\n";
}

// 2. Add Primary Key and Auto Increment
echo "Adding Primary Key and Auto Increment...\n";
$conn->query("ALTER TABLE hero_slides ADD PRIMARY KEY (id)");
$conn->query("ALTER TABLE hero_slides MODIFY id INT(11) NOT NULL AUTO_INCREMENT");

// 3. Fix image paths if they are wrong
echo "Fixing image paths...\n";
$conn->query("UPDATE hero_slides SET image = REPLACE(image, 'assets/images/uploads/', 'assets/images/hero/') WHERE image NOT LIKE 'assets/images/hero/%'");
$conn->query("UPDATE hero_slides SET image_tablet = REPLACE(image_tablet, 'assets/images/uploads/', 'assets/images/hero/') WHERE image_tablet NOT LIKE 'assets/images/hero/%'");
$conn->query("UPDATE hero_slides SET image_mobile = REPLACE(image_mobile, 'assets/images/uploads/', 'assets/images/hero/') WHERE image_mobile NOT LIKE 'assets/images/hero/%'");

echo "Database Repair Complete!\n";
?>
