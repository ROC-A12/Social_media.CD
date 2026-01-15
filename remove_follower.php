<?php
require_once 'includes/config.php';
require_once 'includes/database.php';
require_once 'includes/auth.php';

checkLogin();
$db = getDB();

if (isset($_GET['follower_id'])) {
    $follower_id = (int)$_GET['follower_id'];
    $my_id = $_SESSION['user_id'];

    // Verwijder de relatie waar IK de gevolgde ben en de ander de volger
    $stmt = $db->prepare("DELETE FROM follows WHERE follower_id = ? AND following_id = ?");
    $stmt->bind_param("ii", $follower_id, $my_id);
    $stmt->execute();
}

// Stuur terug naar je eigen profiel
header("Location: profile.php?id=" . $_SESSION['user_id']);
exit();