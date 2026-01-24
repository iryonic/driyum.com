<?php
require_once 'config/database.php';
$conn = get_db_connection();
$result = $conn->query("DESCRIBE orders");
$output = "";
while ($row = $result->fetch_assoc()) {
    $output .= implode(" | ", $row) . "\n";
}
file_put_contents('schema_dump.txt', $output);
echo "Schema dumped to schema_dump.txt\n";
?>
