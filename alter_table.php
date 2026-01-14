<?php
require_once 'includes/config.php';

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
$conn->set_charset(DB_CHARSET);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Check if image_url column exists
$result = $conn->query("SHOW COLUMNS FROM posts LIKE 'image_url'");

if ($result->num_rows == 0) {
    // Add image_url column if it doesn't exist
    $sql = "ALTER TABLE posts ADD COLUMN image_url VARCHAR(255)";
    if ($conn->query($sql) === TRUE) {
        echo "Column 'image_url' added successfully<br>";
    } else {
        echo "Error adding column: " . $conn->error . "<br>";
    }
} else {
    echo "Column 'image_url' already exists<br>";
}

// Check if posts_id column exists
$result = $conn->query("SHOW COLUMNS FROM posts LIKE 'posts_id'");

if ($result->num_rows == 0) {
    // Add posts_id column if it doesn't exist
    $sql = "ALTER TABLE posts ADD COLUMN posts_id INT";
    if ($conn->query($sql) === TRUE) {
        echo "Column 'posts_id' added successfully<br>";
    } else {
        echo "Error adding column: " . $conn->error . "<br>";
    }
} else {
    echo "Column 'posts_id' already exists<br>";
}

// Show current table structure
echo "<br>Current posts table structure:<br>";
$result = $conn->query("DESCRIBE posts");
while ($row = $result->fetch_assoc()) {
    echo $row['Field'] . " - " . $row['Type'] . "<br>";
}

$conn->close();
?>
