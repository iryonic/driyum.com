<?php
require_once 'config/database.php';
try {
    $conn = get_db_connection();
    echo "--- TABLE STRUCTURE ---\n";
    $res = mysqli_query($conn, "SHOW CREATE TABLE available_at");
    if ($res) {
        $row = mysqli_fetch_assoc($res);
        echo $row['Create Table'] . ";\n\n";
    }

    echo "--- TABLE DATA ---\n";
    $res = mysqli_query($conn, "SELECT * FROM available_at");
    if ($res) {
        while ($row = mysqli_fetch_assoc($res)) {
            $keys = array_keys($row);
            $values = array_map(function($v) use ($conn) {
                return $v === null ? "NULL" : "'" . mysqli_real_escape_string($conn, $v) . "'";
            }, array_values($row));
            echo "INSERT INTO available_at (" . implode(', ', $keys) . ") VALUES (" . implode(', ', $values) . ");\n";
        }
    }
} catch (Exception $e) {
    echo $e->getMessage();
}
