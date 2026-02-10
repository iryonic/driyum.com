<?php
require_once 'config/database.php';

$sql_queries = [
    "CREATE TABLE IF NOT EXISTS shipping_methods (
        id INT AUTO_INCREMENT PRIMARY KEY,
        carrier_name VARCHAR(255) NOT NULL,
        display_name VARCHAR(255) NOT NULL,
        min_days INT NOT NULL,
        max_days INT NOT NULL,
        charge_type ENUM('flat', 'weight_based', 'zone_based') NOT NULL,
        status BOOLEAN DEFAULT TRUE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB",

    "CREATE TABLE IF NOT EXISTS shipping_zones (
        id INT AUTO_INCREMENT PRIMARY KEY,
        zone_name VARCHAR(100) NOT NULL,
        pincode_ranges TEXT,
        is_active BOOLEAN DEFAULT TRUE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB",

    "CREATE TABLE IF NOT EXISTS shipping_rates (
        id INT AUTO_INCREMENT PRIMARY KEY,
        method_id INT NOT NULL,
        zone_id INT NOT NULL,
        min_weight DECIMAL(10, 3) NOT NULL,
        max_weight DECIMAL(10, 3) NOT NULL,
        charge DECIMAL(10, 2) NOT NULL,
        FOREIGN KEY (method_id) REFERENCES shipping_methods(id) ON DELETE CASCADE,
        FOREIGN KEY (zone_id) REFERENCES shipping_zones(id) ON DELETE CASCADE
    ) ENGINE=InnoDB",

    "ALTER TABLE orders ADD COLUMN IF NOT EXISTS shipping_method_id INT AFTER tracking_number",
    "ALTER TABLE orders ADD COLUMN IF NOT EXISTS dispatch_date DATETIME AFTER tracking_number",
    "ALTER TABLE orders ADD COLUMN IF NOT EXISTS shipping_zone_id INT AFTER shipping_method_id"
];

foreach ($sql_queries as $sql) {
    try {
        if (mysqli_query(get_db_connection(), $sql)) {
            echo "Successfully executed: " . substr($sql, 0, 50) . "...\n";
        } else {
            echo "Error executing " . substr($sql, 0, 50) . "...: " . mysqli_error(get_db_connection()) . "\n";
        }
    } catch (Exception $e) {
        echo "Exception: " . $e->getMessage() . "\n";
    }
}

// Seed initial data if tables are empty
$methods_count_res = mysqli_query(get_db_connection(), "SELECT COUNT(*) as count FROM shipping_methods");
$methods_count = mysqli_fetch_assoc($methods_count_res)['count'];
if ($methods_count == 0) {
    mysqli_query(get_db_connection(), "INSERT INTO shipping_methods (carrier_name, display_name, min_days, max_days, charge_type) VALUES 
    ('India Post', 'Ordinary Post', 7, 10, 'weight_based'),
    ('India Post', 'Speed Post', 3, 5, 'weight_based'),
    ('India Post', 'Registered Parcel', 5, 8, 'weight_based')");
    echo "Seeded shipping methods.\n";
}

$zones_count_res = mysqli_query(get_db_connection(), "SELECT COUNT(*) as count FROM shipping_zones");
$zones_count = mysqli_fetch_assoc($zones_count_res)['count'];
if ($zones_count == 0) {
    mysqli_query(get_db_connection(), "INSERT INTO shipping_zones (zone_name, pincode_ranges) VALUES 
    ('Local', '190001-190020'), 
    ('State', '190000-199999'),
    ('National', '000000-999999')");
    echo "Seeded shipping zones.\n";
}

$rates_count_res = mysqli_query(get_db_connection(), "SELECT COUNT(*) as count FROM shipping_rates");
$rates_count = mysqli_fetch_assoc($rates_count_res)['count'];
if ($rates_count == 0) {
    // Assuming method IDs 1=Ordinary, 2=Speed, 3=Registered
    // Assuming zone IDs 1=Local, 2=State, 3=National
    mysqli_query(get_db_connection(), "INSERT INTO shipping_rates (method_id, zone_id, min_weight, max_weight, charge) VALUES 
    (1, 1, 0, 5, 20), (1, 2, 0, 5, 40), (1, 3, 0, 5, 60),
    (2, 1, 0, 5, 40), (2, 2, 0, 5, 60), (2, 3, 0, 5, 90),
    (3, 1, 0, 5, 30), (3, 2, 0, 5, 50), (3, 3, 0, 5, 75)");
    echo "Seeded shipping rates.\n";
}
?>


