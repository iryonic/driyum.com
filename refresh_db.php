<?php
require_once 'config/database.php';
$conn = get_db_connection();
$conn->query("SET FOREIGN_KEY_CHECKS=0");
$conn->query("ALTER TABLE orders DROP FOREIGN KEY orders_ibfk_1");
$conn->query("ALTER TABLE orders MODIFY user_id INT NULL");
$conn->query("ALTER TABLE orders ADD CONSTRAINT orders_ibfk_1 FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE");
$conn->query("SET FOREIGN_KEY_CHECKS=1");
echo "FKeys and nullability refreshed.\n";
?>


