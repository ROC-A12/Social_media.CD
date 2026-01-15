<?php
require_once 'includes/config.php';
require_once 'includes/database.php';
require_once 'includes/auth.php';

checkLogin();

$db = getDB();
$user_id = $_SESSION['user_id'];
$post_id = (int)$_POST['post_id'];

// Controleer of de post van de huidige gebruiker is
$stmt = $db->prepare("SELECT user_id FROM posts WHERE id = ?");
$stmt->bind_param("i", $post_id);
$stmt->execute();
$result = $stmt->get_result();
$post = $result->fetch_assoc();

if (!$post || $post['user_id'] != $user_id) {
    header("Location: index.php");
    exit();
}

// Verwijder de post en alle reactions (likes en comments)
$db->query("DELETE FROM likes WHERE post_id = ? OR post_id IN (SELECT id FROM posts WHERE posts_id = ?)", [$post_id, $post_id]);
$db->query("DELETE FROM posts WHERE id = ? OR posts_id = ?", [$post_id, $post_id]);

// Redirect terug naar vorige pagina of index
$referer = $_SERVER['HTTP_REFERER'] ?? 'index.php';
header("Location: $referer");
exit();
?>
