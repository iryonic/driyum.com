<?php
require_once 'config/database.php';

$conn = get_db_connection();
$result = mysqli_query($conn, "SHOW TABLES");
$broken_tables = [];

while ($row = mysqli_fetch_row($result)) {
    $table = $row[0];
    try {
        $q = mysqli_query($conn, "SELECT 1 FROM `$table` LIMIT 1");
        if ($q === false) {
            $broken_tables[] = $table . " (Error: " . mysqli_error($conn) . ")";
        } else {
            mysqli_free_result($q);
        }
    } catch (Throwable $e) {
        $broken_tables[] = $table . " (Exception: " . $e->getMessage() . ")";
    }
}

if (empty($broken_tables)) {
    echo "All tables are healthy!\n";
} else {
    echo "Broken tables:\n";
    foreach ($broken_tables as $table) {
        echo "- $table\n";
    }
}
