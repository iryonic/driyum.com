<?php
// Test file to verify setup
echo "<!DOCTYPE html><html><head><title>Driyum Test</title></head><body>";
echo "<h1 style='color: #2BB35C; font-family: Arial;'>✅ DRIYUM Platform Status</h1>";

// Test 1: PHP Version
echo "<h2>1. PHP Version</h2>";
echo "<p>PHP Version: <strong>" . phpversion() . "</strong> ✅</p>";

// Test 2: Database Connection
echo "<h2>2. Database Connection</h2>";
try {
    $conn = mysqli_connect('localhost', 'root', '', 'driyum_db');
    if ($conn) {
        echo "<p style='color: green;'>✅ Database Connected Successfully!</p>";
        
        // Test queries
        $result = mysqli_query($conn, "SELECT COUNT(*) as count FROM products");
        $row = mysqli_fetch_assoc($result);
        echo "<p>Products in DB: <strong>" . $row['count'] . "</strong></p>";
        
        $result = mysqli_query($conn, "SELECT COUNT(*) as count FROM categories");
        $row = mysqli_fetch_assoc($result);
        echo "<p>Categories in DB: <strong>" . $row['count'] . "</strong></p>";
        
        mysqli_close($conn);
    } else {
        echo "<p style='color: red;'>❌ Database Connection Failed: " . mysqli_connect_error() . "</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error: " . $e->getMessage() . "</p>";
}

// Test 3: Required Files
echo "<h2>3. Required Files</h2>";
$files = [
    'config/database.php',
    'includes/functions.php',
    'includes/header.php',
    'includes/footer.php',
    'assets/css/style.css',
    'assets/js/main.js'
];

foreach ($files as $file) {
    if (file_exists($file)) {
        echo "<p style='color: green;'>✅ {$file}</p>";
    } else {
        echo "<p style='color: red;'>❌ {$file} - NOT FOUND</p>";
    }
}

// Test 4: Session
echo "<h2>4. Session Test</h2>";
session_start();
$_SESSION['test'] = 'working';
if (isset($_SESSION['test'])) {
    echo "<p style='color: green;'>✅ Sessions are working!</p>";
} else {
    echo "<p style='color: red;'>❌ Sessions not working</p>";
}

echo "<hr>";
echo "<h2>✅ Setup Complete!</h2>";
echo "<p><a href="<?php echo get_url('index'); ?>" style='background: #2BB35C; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Go to Homepage →</a></p>";

echo "</body></html>";
?>
