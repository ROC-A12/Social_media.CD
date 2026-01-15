<?php
require_once 'includes/config.php';

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
$conn->set_charset(DB_CHARSET);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

echo "Checking and adding database columns...<br><br>";

// Check and add privacy column to users table
$result = $conn->query("SHOW COLUMNS FROM users LIKE 'is_private'");
if ($result->num_rows == 0) {
    $sql = "ALTER TABLE users ADD COLUMN is_private BOOLEAN DEFAULT FALSE";
    if ($conn->query($sql) === TRUE) {
        echo "Column 'is_private' added to users table<br>";
    } else {
        echo "Error adding is_private: " . $conn->error . "<br>";
    }
} else {
    echo "Column 'is_private' already exists<br>";
}

// Create follow_requests table if it doesn't exist
$result = $conn->query("SHOW TABLES LIKE 'follow_requests'");
if ($result->num_rows == 0) {
    $sql = "CREATE TABLE follow_requests (
        id INT AUTO_INCREMENT PRIMARY KEY,
        requester_id INT NOT NULL,
        recipient_id INT NOT NULL,
        status ENUM('pending', 'accepted', 'rejected') DEFAULT 'pending',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (requester_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (recipient_id) REFERENCES users(id) ON DELETE CASCADE,
        UNIQUE KEY unique_request (requester_id, recipient_id)
    )";
    if ($conn->query($sql) === TRUE) {
        echo "Table 'follow_requests' created<br>";
    } else {
        echo "Error creating follow_requests: " . $conn->error . "<br>";
    }
} else {
    echo "Table 'follow_requests' already exists<br>";
}

echo "<br>Database setup complete!";
$conn->close();
?>
