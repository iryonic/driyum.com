<?php
require 'config/database.php';
$conn = get_db_connection();

$res = $conn->query("SELECT id, ingredients, nutritional_info FROM products");
while($row = $res->fetch_assoc()) {
    $id = $row['id'];
    $ing = $row['ingredients'];
    $nut = $row['nutritional_info'];
    
    $update = false;
    if (strpos($ing, '\"') !== false) {
        $ing = stripslashes($ing);
        $update = true;
    }
    if (strpos($nut, '\"') !== false) {
        $nut = stripslashes($nut);
        $update = true;
    }
    
    if ($update) {
        $stmt = $conn->prepare("UPDATE products SET ingredients=?, nutritional_info=? WHERE id=?");
        $stmt->bind_param("ssi", $ing, $nut, $id);
        $stmt->execute();
        echo "Cleaned ID: $id\n";
    }
}
echo "Cleanup complete.\n";
?>


