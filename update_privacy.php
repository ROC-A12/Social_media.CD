<?php
require_once 'includes/config.php';
require_once 'includes/database.php';
require_once 'includes/auth.php';

checkLogin();
$db = getDB();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $is_private = isset($_POST['is_private']) ? 1 : 0;
    $stmt = $db->prepare("UPDATE users SET is_private = ? WHERE id = ?");
    $stmt->bind_param("ii", $is_private, $_SESSION['user_id']);
    $stmt->execute();
}

header("Location: profile.php?id=" . $_SESSION['user_id']);