<?php
require_once 'config/database.php';
$conn = get_db_connection();
$conn->query("SET FOREIGN_KEY_CHECKS=0");
$conn->query("ALTER TABLE orders MODIFY user_id INT NULL");
$conn->query("SET FOREIGN_KEY_CHECKS=1");
echo "Done ensuring user_id is nullable.\n";
?>


