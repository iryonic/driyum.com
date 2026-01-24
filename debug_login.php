<?php
require_once 'config/database.php';
require_once 'includes/functions.php';

$email = 'admin@driyum.com';
$input_pass = 'admin123';

echo "<h2>Debug Login for: $email</h2>";

// 1. Fetch User directly
$conn = get_db_connection();
$result = $conn->query("SELECT * FROM users WHERE email = '$email'");
$user = $result->fetch_assoc();

if (!$user) {
    die("User NOT FOUND in DB!");
}

echo "<h3>Database Record:</h3>";
echo "ID: " . $user['id'] . "<br>";
echo "Email: " . $user['email'] . "<br>";
echo "Stored Hash: <div style='background:#eee; padding:5px; font-family:monospace;'>" . $user['password'] . "</div>";
echo "Hash Length: " . strlen($user['password']) . " chars<br>";

// 2. Generate New Hash
$new_hash = password_hash($input_pass, PASSWORD_DEFAULT);
echo "<h3>New Hash (Generated Just Now):</h3>";
echo "Hash: <div style='background:#eee; padding:5px; font-family:monospace;'>" . $new_hash . "</div>";

// 3. Verify
echo "<h3>Verification Test:</h3>";
$check = password_verify($input_pass, $user['password']);

if ($check) {
    echo "<h1 style='color:green'>MATCH! password_verify() returned TRUE.</h1>";
    echo "The login script should work. If it fails there, check for strict whitespace trimming.";
} else {
    echo "<h1 style='color:red'>FAIL! password_verify() returned FALSE.</h1>";
    echo "This means the stored hash does not match 'admin123'.";
    
    // Attempt fix
    echo "<h3>Attempting Auto-Fix...</h3>";
    $fix_stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
    $fix_stmt->bind_param("si", $new_hash, $user['id']);
    if ($fix_stmt->execute()) {
        echo "Updated DB with new hash. <a href="<?php echo get_url('debug_login'); ?>">Reload to test match</a>";
    }
}
?>
