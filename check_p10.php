<?php
require_once 'config/database.php';
$p = fetch_one("SELECT * FROM products WHERE id = 10");
if ($p) {
    echo "Product 10 exists: " . $p['name'] . "\n";
} else {
    echo "Product 10 does NOT exist\n";
}

$all = fetch_all("SELECT id, name FROM products");
echo "\nAll Products:\n";
foreach($all as $item) {
    echo "{$item['id']} | {$item['name']}\n";
}
?>
