<?php
header('Content-Type: application/json');
require_once '../config/database.php';
require_once '../includes/functions.php';

// Handle POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');

    // Basic validation
    if (empty($email)) {
        echo json_encode(['success' => false, 'message' => 'Please enter your email address.']);
        exit;
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['success' => false, 'message' => 'Please enter a valid email address.']);
        exit;
    }

    $conn = get_db_connection();

    // Check if duplicate
    $stmt = $conn->prepare("SELECT id FROM newsletter_subscribers WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        echo json_encode(['success' => false, 'message' => 'You are already subscribed!']);
        exit;
    }

    // Insert new subscriber
    $insert_stmt = $conn->prepare("INSERT INTO newsletter_subscribers (email) VALUES (?)");
    $insert_stmt->bind_param("s", $email);

    if ($insert_stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Thanks for subscribing! Welcome to the club. 💚']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Something went wrong. Please try again.']);
    }

    $conn->close();
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
}
?>


