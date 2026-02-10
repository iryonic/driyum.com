<?php
/**
 * DATABASE REPAIR SCRIPT
 * Fixes missing Primary Keys and Auto-Increment columns
 * specifically for reviews and admin_notifications
 */
require_once 'config/database.php';

echo "<pre>";
echo "Starting Database Repair...\n";

$tables_to_fix = ['reviews', 'admin_notifications'];
$conn = get_db_connection();

foreach ($tables_to_fix as $table) {
    echo "\nProcessing table: $table\n";
    
    // Check if table exists
    $check = $conn->query("SHOW TABLES LIKE '$table'");
    if ($check->num_rows == 0) {
        echo "❌ Table $table does not exist. Skipping.\n";
        continue;
    }

    // Check current structure
    $res = $conn->query("DESCRIBE $table");
    $has_pk = false;
    $has_ai = false;
    while ($row = $res->fetch_assoc()) {
        if ($row['Field'] == 'id') {
            if ($row['Key'] == 'PRI') $has_pk = true;
            if (strpos($row['Extra'], 'auto_increment') !== false) $has_ai = true;
        }
    }

    if ($has_ai) {
        echo "✅ Table $table already has Auto-Increment. No fix needed.\n";
    } else {
        echo "🔧 Fixing $table...\n";
        
        // Step 1: In case there are multiple rows with ID 0, we should reorganize them
        $conn->query("SET @count = 0;");
        $conn->query("UPDATE `$table` SET `id` = (@count := @count + 1);");
        
        // Step 2: Add Primary Key and Auto-Increment
        // If it already has PRI but no AI, we just modify the column
        if ($has_pk) {
            $alter_sql = "ALTER TABLE `$table` MODIFY `id` INT(11) NOT NULL AUTO_INCREMENT";
        } else {
            $alter_sql = "ALTER TABLE `$table` MODIFY `id` INT(11) NOT NULL PRIMARY KEY AUTO_INCREMENT";
        }
        
        if ($conn->query($alter_sql)) {
            echo "✨ Table $table successfully repaired!\n";
        } else {
            echo "❌ Error repairing $table: " . $conn->error . "\n";
            
            // Fallback: If it fails because multiple primary keys exist (rare), try just modifying it
            $fallback_sql = "ALTER TABLE `$table` MODIFY `id` INT(11) NOT NULL AUTO_INCREMENT";
            if ($conn->query($fallback_sql)) {
                 echo "✨ Table $table successfully repaired (via fallback)!\n";
            }
        }
    }
}

echo "\n--- ADDITIONAL CHECKS ---\n";

// Check if is_approved exists in reviews
$res = $conn->query("DESCRIBE reviews");
$has_is_approved = false;
while($row = $res->fetch_assoc()) {
    if($row['Field'] == 'is_approved') $has_is_approved = true;
}
if (!$has_is_approved) {
    echo "🔧 Adding is_approved to reviews table...\n";
    if($conn->query("ALTER TABLE reviews ADD COLUMN is_approved TINYINT(1) DEFAULT 1 AFTER comment")) {
        echo "✅ added is_approved column.\n";
    }
}

echo "\nRepair complete. Please test the review submission now.\n";
echo "</pre>";
?>


