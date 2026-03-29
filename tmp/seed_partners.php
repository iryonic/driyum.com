<?php
require_once 'config/database.php';
$conn = get_db_connection();

echo "Seeding default Retail Partners...\n";

$partners = [
    ['name' => 'Ecogrocery', 'location' => 'LAL NAGAR'],
    ['name' => 'Basket', 'location' => 'CHANAPORA'],
    ['name' => 'Extracts', 'location' => 'RAJBAGH'],
    ['name' => 'Extracts', 'location' => 'PEERBAGH'],
    ['name' => 'Pick N Choose', 'location' => 'BAGHAT']
];

foreach ($partners as $i => $p) {
    // Check if partner already exists by name & location
    $exists = fetch_one("SELECT id FROM partners WHERE name=? AND location=?", [$p['name'], $p['location']]);
    if (!$exists) {
        $stmt = $conn->prepare("INSERT INTO partners (name, location, sort_order) VALUES (?, ?, ?)");
        $stmt->bind_param("ssi", $p['name'], $p['location'], $i);
        if ($stmt->execute()) {
            echo "✓ Added partner: {$p['name']} ({$p['location']})\n";
        }
    } else {
        echo "• Skipping: {$p['name']} ({$p['location']}) - already exists.\n";
    }
}

echo "Seeding Complete.\n";
