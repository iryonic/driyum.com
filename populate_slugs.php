<?php
require_once 'config/database.php';

function create_slug($text) {
    if (empty($text)) return 'n-a';
    $text = preg_replace('~[^\pL\d]+~u', '-', $text);
    $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);
    $text = preg_replace('~[^-\w]+~', '', $text);
    $text = trim($text, '-');
    $text = preg_replace('~-+~', '-', $text);
    $text = strtolower($text);
    return $text;
}

echo "Checking products...\n";
$products = fetch_all("SELECT id, name, slug FROM products");
foreach ($products as $p) {
    if (empty($p['slug'])) {
        $slug = create_slug($p['name']);
        // Check uniqueness
        $check = fetch_one("SELECT id FROM products WHERE slug = ? AND id != ?", [$slug, $p['id']]);
        if ($check) {
            $slug .= '-' . $p['id'];
        }
        execute_query("UPDATE products SET slug = ? WHERE id = ?", [$slug, $p['id']]);
        echo "Updated product {$p['id']}: {$p['name']} -> $slug\n";
    }
}

echo "\nChecking categories...\n";
$categories = fetch_all("SELECT id, name, slug FROM categories");
foreach ($categories as $c) {
    if (empty($c['slug'])) {
        $slug = create_slug($c['name']);
        // Check uniqueness
        $check = fetch_one("SELECT id FROM categories WHERE slug = ? AND id != ?", [$slug, $c['id']]);
        if ($check) {
            $slug .= '-' . $c['id'];
        }
        execute_query("UPDATE categories SET slug = ? WHERE id = ?", [$slug, $c['id']]);
        echo "Updated category {$c['id']}: {$c['name']} -> $slug\n";
    }
}

echo "\nDone!\n";


