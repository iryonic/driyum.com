<?php
require_once 'config/database.php';
$conn = get_db_connection();
$tables_res = mysqli_query($conn, "SHOW TABLES");
$results = [];
while ($row = mysqli_fetch_row($tables_res)) {
    $table = $row[0];
    $res = mysqli_query($conn, "DESCRIBE `$table` ");
    $has_pk = false;
    $has_ai = false;
    while ($field = mysqli_fetch_assoc($res)) {
        if ($field['Key'] === 'PRI') $has_pk = true;
        if (strpos($field['Extra'], 'auto_increment') !== false) $has_ai = true;
    }
    $results[] = "$table | PK: " . ($has_pk ? "YES" : "NO") . " | AI: " . ($has_ai ? "YES" : "NO");
}
file_put_contents('db_audit_log.txt', implode("\n", $results));
?>
