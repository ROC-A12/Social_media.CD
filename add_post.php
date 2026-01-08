<?php
require_once 'includes/config.php';
require_once 'includes/database.php';

$db = new Database();

$stmt = $db->prepare("INSERT INTO posts (user_id, content) VALUES (1, 'Hello World! This is my first post.')");
$stmt->execute();

echo "Post added";
?>