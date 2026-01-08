<?php
require_once 'includes/config.php';
require_once 'includes/database.php';
require_once 'includes/auth.php';

checkLogin();

$db = new Database();
$user_id = $_SESSION['user_id'];
$post_id = (int)$_POST['post_id'];
$content = trim($_POST['content']);

if (!empty($content)) {
    $stmt = $db->prepare("INSERT INTO comments (user_id, post_id, content) VALUES (?, ?, ?)");
    $stmt->bind_param("iis", $user_id, $post_id, $content);
    $stmt->execute();
}

header("Location: post.php?id=" . $post_id);
exit();
?>