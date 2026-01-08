<?php
require_once 'includes/config.php';
require_once 'includes/database.php';
require_once 'includes/auth.php';

checkLogin();

$db = new Database();
$user_id = $_SESSION['user_id'];
$post_id = (int)$_POST['post_id'];

// Check if already liked
$check_stmt = $db->prepare("SELECT id FROM likes WHERE user_id = ? AND post_id = ?");
$check_stmt->bind_param("ii", $user_id, $post_id);
$check_stmt->execute();
$check_stmt->store_result();

if ($check_stmt->num_rows > 0) {
    // Unlike
    $stmt = $db->prepare("DELETE FROM likes WHERE user_id = ? AND post_id = ?");
    $stmt->bind_param("ii", $user_id, $post_id);
    $stmt->execute();
} else {
    // Like
    $stmt = $db->prepare("INSERT INTO likes (user_id, post_id) VALUES (?, ?)");
    $stmt->bind_param("ii", $user_id, $post_id);
    $stmt->execute();
}

header("Location: " . $_SERVER['HTTP_REFERER']);
exit();
?>