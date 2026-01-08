<?php
require_once 'includes/config.php';
require_once 'includes/database.php';

$db = new Database();

// Simulate user_id
$user_id = 1; // Assuming test user has id 1

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

echo "Posts fetched: " . $posts->num_rows . "\n";

while($post = $posts->fetch_assoc()) {
    echo "Post: " . $post['content'] . " by " . $post['username'] . "\n";
}
?>