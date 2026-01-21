<?php
require_once 'includes/functies.php';

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
$conn->set_charset(DB_CHARSET);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$sql = "CREATE TABLE IF NOT EXISTS messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sender_id INT NOT NULL,
    recipient_id INT NOT NULL,
    content TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    is_read TINYINT(1) DEFAULT 0,
    INDEX(sender_id),
    INDEX(recipient_id),
    FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (recipient_id) REFERENCES users(id) ON DELETE CASCADE
)
ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
";

if ($conn->query($sql) === TRUE) {
    echo "Table 'messages' created or already exists.\n";
} else {
    echo "Error creating table: " . $conn->error . "\n";
}

$conn->close();

?>
