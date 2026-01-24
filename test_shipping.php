<?php
require 'config/database.php';
require 'includes/functions.php';

$test_pincode = '190001';
$zone = get_shipping_zone($test_pincode);
echo "TEST PINCODE: $test_pincode\n";
if ($zone) {
    echo "ZONE FOUND: " . $zone['zone_name'] . " (ID: " . $zone['id'] . ")\n";
    $weight = 0.5;
    $methods = get_shipping_methods_with_rates($test_pincode, $weight);
    echo "METHODS FOUND: " . count($methods) . "\n";
    foreach($methods as $m) {
        echo "- " . $m['display_name'] . ": ₹" . $m['cost'] . "\n";
    }
} else {
    echo "NO ZONE FOUND\n";
}
?>
