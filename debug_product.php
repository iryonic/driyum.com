<?php
require 'config/database.php';
$p = fetch_one("SELECT id, name, ingredients, nutritional_info FROM products WHERE ingredients LIKE '[%' OR nutritional_info LIKE '{%' LIMIT 5");
if ($p) {
    echo "ID: " . $p['id'] . "\n";
    echo "NAME: " . $p['name'] . "\n";
    echo "INGREDIENTS RAW: " . $p['ingredients'] . "\n";
    echo "NUTRITION RAW: " . $p['nutritional_info'] . "\n";
    
    $decoded_ing = json_decode($p['ingredients'], true);
    echo "JSON DECODE ING success: " . (json_last_error() === JSON_ERROR_NONE ? 'YES' : 'NO') . "\n";
    
    $decoded_nut = json_decode($p['nutritional_info'], true);
    echo "JSON DECODE NUT success: " . (json_last_error() === JSON_ERROR_NONE ? 'YES' : 'NO') . "\n";
} else {
    echo "No matching products found.\n";
}
?>
