<?php
require_once 'config/database.php';
require_once 'includes/functions.php';

echo "<h2>Driyum System Test</h2>";

// 1. Test Database
try {
    $conn = get_db_connection();
    echo "✅ Database connection successful.<br>";
} catch (Exception $e) {
    echo "❌ Database connection failed: " . $e->getMessage() . "<br>";
}

// 2. Test Email Function (Mocking a test mail)
echo "<h3>Testing Email System...</h3>";
$to = "test@example.com";
$subject = "Driyum System Test Mail";
$message = "<h1>Test Success!</h1><p>Your SMTP/PHPMailer system is correctly configured.</p>";

$start = microtime(true);
$result = send_email($to, $subject, $message);
$end = microtime(true);
$time = round($end - $start, 2);

if ($result) {
    echo "✅ Email function returned TRUE (Check mail_log.txt or your inbox).<br>";
    echo "⏱️ Time taken: $time seconds.<br>";
} else {
    echo "❌ Email function returned FALSE. Check PHP error logs.<br>";
}

// 3. Check for PHPMailer
if (class_exists('PHPMailer\PHPMailer\PHPMailer')) {
    echo "✅ PHPMailer class is loaded.<br>";
} else {
    echo "❌ PHPMailer class NOT found. Check vendor/autoload.php.<br>";
}

echo "<hr>";
echo "<b>Note:</b> If you are on localhost, this will log to mail_log.txt even if it fails to actually send an email to the internet.";
