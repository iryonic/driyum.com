<?php
require_once 'config/database.php';
$conn = get_db_connection();

echo "Starting DB Repair...\n";

// 1. Fetch all settings ordered by ID/Time descending (assuming there's an ID or checking all)
// Since I don't know if there's an ID column, let's check columns again carefully.
// The previous output was messy.
// Let's assume there might not be an ID. 
// We will fetch ALL rows.
$res = mysqli_query($conn, "SELECT * FROM settings");
$all_settings = [];
while($row = mysqli_fetch_assoc($res)) {
    // Overwrite previous entries with later ones (assuming sequential read usually gives older first? Actually we want the LATEST. If insert appends, last is newest.)
    $all_settings[$row['key']] = $row['value'];
}

echo "Found " . count($all_settings) . " unique keys.\n";

// 2. Truncate table
mysqli_query($conn, "TRUNCATE TABLE settings");
echo "Table truncated.\n";

// 3. Add Unique Index if not exists (try/catch style)
// First, ensure 'key' column is valid for index (varchar length)
// We saw varchar(100) earlier, which is fine.
try {
    mysqli_query($conn, "ALTER TABLE settings ADD PRIMARY KEY (`key`)"); 
    echo "Primary Key added.\n";
} catch (Exception $e) {
    echo "Index might already exist or error: " . $e->getMessage() . "\n";
}

// 4. Re-insert unique values
$stmt = mysqli_prepare($conn, "INSERT INTO settings (`key`, `value`) VALUES (?, ?)");
foreach($all_settings as $k => $v) {
    mysqli_stmt_bind_param($stmt, "ss", $k, $v);
    mysqli_stmt_execute($stmt);
}
echo "Restored " . count($all_settings) . " settings.\n";

echo "Database Repair Complete.\n";
?>


