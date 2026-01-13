<?php
require_once 'includes/config.php';
require_once 'includes/database.php';
require_once 'includes/auth.php';

checkLogin();

$db = new Database();
$user_id = $_SESSION['user_id'];
$follow_user_id = (int)$_GET['user_id'];

if ($follow_user_id != $user_id) {
    // Check if already following.
    $check_stmt = $db->prepare("SELECT id FROM followers WHERE follower_id = ? AND following_id = ?");
    $check_stmt->bind_param("ii", $user_id, $follow_user_id);
    $check_stmt->execute();
    $check_stmt->store_result();

    if ($check_stmt->num_rows == 0) {
        // Follow
        $stmt = $db->prepare("INSERT INTO followers (follower_id, following_id) VALUES (?, ?)");
        $stmt->bind_param("ii", $user_id, $follow_user_id);
        $stmt->execute();
    }
}

header("Location: " . $_SERVER['HTTP_REFERER']);
exit();
?>