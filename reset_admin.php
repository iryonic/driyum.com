<?php
require_once 'config/database.php';
require_once 'includes/functions.php';

$email = 'admin@driyum.com';
$password = 'admin123';
$hash = password_hash($password, PASSWORD_DEFAULT);

$conn = get_db_connection();

// Check if admin exists
$result = $conn->query("SELECT id FROM users WHERE email = '$email'");
if ($result->num_rows > 0) {
    // Update existing
    $stmt = $conn->prepare("UPDATE users SET password = ?, is_admin = 1 WHERE email = ?");
    $stmt->bind_param("ss", $hash, $email);
    if ($stmt->execute()) {
        echo "<h1 style='color:green'>Success!</h1>";
        echo "<p>Admin password updated to: <b>admin123</b></p>";
    } else {
        echo "Error updating: " . $conn->error;
    }
} else {
    // Create if missing
    $stmt = $conn->prepare("INSERT INTO users (name, email, phone, password, is_admin) VALUES ('Admin', ?, '9999999999', ?, 1)");
    $stmt->bind_param("ss", $email, $hash);
    if ($stmt->execute()) {
        echo "<h1 style='color:green'>Created!</h1>";
        echo "<p>Admin user created with password: <b>admin123</b></p>";
    } else {
        echo "Error creating: " . $conn->error;
    }
}

echo "<br><a href='login.php'>Go to Login</a>";
?>
