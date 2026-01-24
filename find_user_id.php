<?php
require_once 'config/database.php';
$conn = get_db_connection();
$res = $conn->query("SHOW TABLES");
while ($row = $res->fetch_row()) {
    $table = $row[0];
    $res2 = $conn->query("DESCRIBE `$table` COLLATE utf8mb4_unicode_ci");
    if (!$res2) {
        $res2 = $conn->query("DESCRIBE `$table` ");
    }
    while ($field = $res2->fetch_assoc()) {
        if (stripos($field['Field'], 'user_id') !== false) {
            echo "Table: $table | Field: " . $field['Field'] . " | Null: " . $field['Null'] . "\n";
        }
    }
}
?>
