<?php
require_once 'includes/config.php';
require_once 'includes/database.php';

$db = new Database();

// Create a test user
$username = 'testuser';
$email = 'test@example.com';
$password = password_hash('password', PASSWORD_DEFAULT);

$stmt = $db->prepare("INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, 'admin')");
if (!$stmt) {
    echo "Prepare failed: " . $db->getError();
    exit;
}
$stmt->bind_param("sss", $username, $email, $password);

if ($stmt->execute()) {
    echo "Test user created successfully. Email: test@example.com, Password: password";
} else {
    echo "Error creating user: " . $stmt->error;
}
?>