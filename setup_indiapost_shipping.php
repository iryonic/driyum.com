<?php
require_once 'config/database.php';
$conn = get_db_connection();

echo "Starting India Post Advanced Setup...\n";

// 1. Deactivate generic India Post if it exists to avoid confusion
$conn->query("UPDATE shipping_methods SET status = 0 WHERE display_name = 'India Post'");

// 2. Setup Methods
$methods = [
    [
        'key' => 'india_post_speed',
        'display' => 'India Post (Speed Post)',
        'carrier' => 'India Post'
    ],
    [
        'key' => 'india_post_normal',
        'display' => 'India Post (Normal Delivery)',
        'carrier' => 'India Post'
    ]
];

$method_ids = [];
foreach ($methods as $m) {
    $m_key = $m['key'];
    $m_display = $m['display'];
    $m_carrier = $m['carrier'];
    
    $existing = $conn->query("SELECT id FROM shipping_methods WHERE display_name = '$m_display'")->fetch_assoc();
    if ($existing) {
        $mid = $existing['id'];
        $conn->query("UPDATE shipping_methods SET status = 1, charge_type = 'weight_based' WHERE id = $mid");
        $method_ids[$m_key] = $mid;
    } else {
        $conn->query("INSERT INTO shipping_methods (carrier_name, display_name, charge_type, status) VALUES ('$m_carrier', '$m_display', 'weight_based', 1)");
        $method_ids[$m_key] = $conn->insert_id;
    }
}

// 3. Setup Zones
$zones_data = [
    [
        'name' => 'Srinagar Local',
        'pincodes' => '190001-190035',
        'min_days' => 0,
        'max_days' => 2,
        'method_key' => 'india_post_speed',
        'rates' => [
            ['min' => 0, 'max' => 0.250, 'cost' => 30],
            ['min' => 0.251, 'max' => 0.500, 'cost' => 35],
            ['min' => 0.501, 'max' => 1.000, 'cost' => 50]
        ]
    ],
    [
        'name' => 'Outside Srinagar',
        'pincodes' => '180001-189999,190036-194999',
        'min_days' => 3,
        'max_days' => 4,
        'method_key' => 'india_post_normal',
        'rates' => [
            ['min' => 0, 'max' => 0.500, 'cost' => 50],
            ['min' => 0.501, 'max' => 1.000, 'cost' => 65]
        ]
    ],
    [
        'name' => 'Outside State',
        'pincodes' => '110001-179999,200000-999999',
        'min_days' => 5,
        'max_days' => 7,
        'method_key' => 'india_post_normal',
        'rates' => [
            ['min' => 0, 'max' => 0.500, 'cost' => 60],
            ['min' => 0.501, 'max' => 1.000, 'cost' => 100]
        ]
    ]
];

foreach ($zones_data as $z) {
    $z_name = $z['name'];
    $z_pincodes = $z['pincodes'];
    $min_d = $z['min_days'];
    $max_d = $z['max_days'];
    $mid = $method_ids[$z['method_key']];

    $existing_zone = $conn->query("SELECT id FROM shipping_zones WHERE zone_name = '$z_name'")->fetch_assoc();
    if ($existing_zone) {
        $zid = $existing_zone['id'];
        $conn->query("UPDATE shipping_zones SET pincode_ranges = '$z_pincodes', min_days = $min_d, max_days = $max_d, is_active = 1 WHERE id = $zid");
    } else {
        $conn->query("INSERT INTO shipping_zones (zone_name, pincode_ranges, min_days, max_days, is_active) VALUES ('$z_name', '$z_pincodes', $min_d, $max_d, 1)");
        $zid = $conn->insert_id;
    }

    // Clear old rates for this zid + ALL India Post methods just in case
    $all_mids = implode(',', array_values($method_ids));
    $conn->query("DELETE FROM shipping_rates WHERE zone_id = $zid AND method_id IN ($all_mids)");

    foreach ($z['rates'] as $r) {
        $min_w = $r['min'];
        $max_w = $r['max'];
        $cost = $r['cost'];
        $conn->query("INSERT INTO shipping_rates (method_id, zone_id, min_weight, max_weight, charge) VALUES ($mid, $zid, $min_w, $max_w, $cost)");
    }
}

echo "Advanced Shipping Setup Complete!\n";
?>


