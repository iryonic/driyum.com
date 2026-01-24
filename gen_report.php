<?php
require_once 'config/database.php';
$conn = get_db_connection();
$tables_res = $conn->query("SHOW TABLES");
$report = "";
while($t_row = $tables_res->fetch_row()){
    $table = $t_row[0];
    $cols_res = $conn->query("DESCRIBE `$table` ");
    while($c_row = $cols_res->fetch_assoc()){
        if(strpos($c_row['Field'], 'user_id') !== false){
            $report .= "Table: $table | Column: " . $c_row['Field'] . " | Null: " . $c_row['Null'] . "\n";
        }
    }
}
file_put_contents('user_id_report.txt', $report);
?>
