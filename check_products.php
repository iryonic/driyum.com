<?php
require 'config/database.php';
$res = execute_query('DESCRIBE products');
while($row = mysqli_fetch_assoc($res)) {
    echo $row['Field'] . " (" . $row['Type'] . ")\n";
}
?>
