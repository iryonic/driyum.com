<?php
// Diagnostics Tool
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>System Diagnostics</h1>";

// 1. PHP Info partial
echo "<h3>PHP Version: " . phpversion() . "</h3>";

// 2. Database Check
require_once 'config/database.php';
try {
    $conn = get_db_connection();
    echo "<p style='color:green'>✅ Database Connection Successful</p>";
    echo "<p>Host: " . DB_HOST . "</p>";
    echo "<p>User: " . DB_USER . "</p>";
    
    // Check tables
    $tables = ['products', 'orders', 'users', 'abandoned_carts'];
    foreach($tables as $t) {
        $res = mysqli_query($conn, "SELECT 1 FROM $t LIMIT 1");
        if($res) echo "<p style='color:green'>✅ Table '$t' exists</p>";
        else echo "<p style='color:red'>❌ Table '$t' MISSING or Error: " . mysqli_error($conn) . "</p>";
    }
} catch (Throwable $e) {
    echo "<p style='color:red'>❌ Database Connection Failed: " . $e->getMessage() . "</p>";
}

// 3. Session Check
if (session_status() === PHP_SESSION_NONE) session_start();
$_SESSION['test_key'] = 'working';
echo "<p>Session Status: " . (session_status() === PHP_SESSION_ACTIVE ? 'Active' : 'Inactive') . "</p>";
echo "<p>Session ID: " . session_id() . "</p>";
echo "<p>Test Key: " . ($_SESSION['test_key'] ?? 'Not Set') . "</p>";

// 4. URL Check
require_once 'includes/functions.php';
echo "<p>Calculated BASE_URL: " . (defined('BASE_URL') ? BASE_URL : 'NotDefined') . "</p>";
echo "<p>Example URL (shop): " . get_url('shop') . "</p>";
?>
