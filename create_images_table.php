<?php
require_once 'config/database.php';

$sql = "CREATE TABLE IF NOT EXISTS product_images (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    image_path VARCHAR(500) NOT NULL,
    sort_order INT DEFAULT 0,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
)";

$conn = get_db_connection();
if ($conn->query($sql)) {
    echo "<h1>Product Images Table Created!</h1>";
} else {
    echo "Error: " . $conn->error;
}
?>


