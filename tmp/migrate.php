<?php
require_once __DIR__ . '/../config/database.php';
$c = get_db_connection();

// 1. Create Partners Table
$c->query("CREATE TABLE IF NOT EXISTS partners (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255),
    location VARCHAR(255),
    is_active TINYINT DEFAULT 1,
    sort_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

// 2. Add is_combo to Products
$check = $c->query("SHOW COLUMNS FROM products LIKE 'is_combo'");
if ($check->num_rows == 0) $c->query("ALTER TABLE products ADD is_combo TINYINT DEFAULT 0");

// 3. Seed Partners
$stores = [['Ecogrocery', 'Lal Nagar'], ['Basket', 'Chanapora'], ['Extracts', 'RAJBAGH'], ['Extracts', 'Peerbagh'], ['Pick N Choose', 'BAGHAT']];
foreach ($stores as $i => $s) {
    $name = $c->real_escape_string($s[0]);
    $loc = $c->real_escape_string($s[1]);
    $exists = $c->query("SELECT id FROM partners WHERE name='$name' AND location='$loc'");
    if ($exists->num_rows == 0) $c->query("INSERT INTO partners (name, location, sort_order) VALUES ('$name', '$loc', $i)");
}

echo "✓ Migration successful.";
