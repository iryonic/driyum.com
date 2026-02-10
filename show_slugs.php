<?php
require_once 'config/database.php';

echo "Sample Products:\n";
$products = fetch_all("SELECT id, name, slug FROM products LIMIT 5");
foreach($products as $p) {
    echo "- ID: {$p['id']}, Name: {$p['name']}, Slug: {$p['slug']}\n";
}

echo "\nSample Categories:\n";
$categories = fetch_all("SELECT id, name, slug FROM categories LIMIT 5");
foreach($categories as $c) {
    echo "- ID: {$c['id']}, Name: {$c['name']}, Slug: {$c['slug']}\n";
}


