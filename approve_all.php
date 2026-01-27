p<?php
require_once 'config/database.php';
$conn = get_db_connection();
$conn->query("UPDATE reviews SET is_approved = 1");
echo "All reviews approved.";
unlink(__FILE__);
?>
