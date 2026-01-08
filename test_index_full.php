<?php
require_once 'includes/config.php';
require_once 'includes/database.php';

// Simulate login
$_SESSION['user_id'] = 1;
$_SESSION['username'] = 'testuser';
$_SESSION['profile_pic'] = 'default.png';

$db = new Database();
$user_id = $_SESSION['user_id'];

// Haal posts op van gebruikers die je volgt
$stmt = $db->prepare("
    SELECT posts.*, users.username, users.profile_picture,
           (SELECT COUNT(*) FROM likes WHERE likes.post_id = posts.id) as like_count,
           (SELECT COUNT(*) FROM comments WHERE comments.post_id = posts.id) as comment_count,
           EXISTS(SELECT 1 FROM likes WHERE likes.post_id = posts.id AND likes.user_id = ?) as user_liked
    FROM posts 
    JOIN users ON posts.user_id = users.id
    WHERE posts.user_id IN (
        SELECT following_id FROM followers WHERE follower_id = ?
    ) OR posts.user_id = ?
    ORDER BY posts.created_at DESC
    LIMIT 20
");
$stmt->bind_param("iii", $user_id, $user_id, $user_id);
$stmt->execute();
$posts = $stmt->get_result();

echo "<!DOCTYPE html><html><head><title>Test Index</title></head><body>";
echo "<h1>Home - Social Media</h1>";
echo "<p>Posts: " . $posts->num_rows . "</p>";

while($post = $posts->fetch_assoc()) {
    echo "<div><h3>" . htmlspecialchars($post['username']) . "</h3><p>" . nl2br(htmlspecialchars($post['content'])) . "</p></div>";
}

echo "</body></html>";
?>