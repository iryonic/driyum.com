<?php
require 'config/database.php';
$conn = get_db_connection();
$sql = "UPDATE products SET weight = '0.500' WHERE weight IS NULL OR weight = '' OR weight = '0'";
if ($conn->query($sql)) {
    echo "Updated " . $conn->affected_rows . " products with default weight.\n";
} else {
    echo "Error: " . $conn->error . "\n";
}
?>
