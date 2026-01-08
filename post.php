<?php
require_once 'includes/config.php';
require_once 'includes/database.php';
require_once 'includes/auth.php';

checkLogin();

$db = new Database();
$post_id = (int)$_GET['id'];

$stmt = $db->prepare("SELECT posts.*, users.username FROM posts JOIN users ON posts.user_id = users.id WHERE posts.id = ?");
$stmt->bind_param("i", $post_id);
$stmt->execute();
$post = $stmt->get_result()->fetch_assoc();

if (!$post) {
    header("Location: index.php");
    exit();
}

// Get comments
$comments_stmt = $db->prepare("SELECT comments.*, users.username FROM comments JOIN users ON comments.user_id = users.id WHERE post_id = ? ORDER BY created_at ASC");
$comments_stmt->bind_param("i", $post_id);
$comments_stmt->execute();
$comments = $comments_stmt->get_result();
?>

<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Post</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <div class="container mt-4">
        <div class="card">
            <div class="card-body">
                <h5><?php echo htmlspecialchars($post['username']); ?></h5>
                <p><?php echo nl2br(htmlspecialchars($post['content'])); ?></p>
                <?php if($post['image_url']): ?>
                    <img src="assets/uploads/posts/<?php echo $post['image_url']; ?>" class="img-fluid">
                <?php endif; ?>
            </div>
        </div>

        <div class="mt-4">
            <h6>Comments</h6>
            <?php while($comment = $comments->fetch_assoc()): ?>
                <div class="card mb-2">
                    <div class="card-body">
                        <strong><?php echo htmlspecialchars($comment['username']); ?>:</strong>
                        <?php echo nl2br(htmlspecialchars($comment['content'])); ?>
                    </div>
                </div>
            <?php endwhile; ?>

            <form action="comment.php" method="POST" class="mt-3">
                <input type="hidden" name="post_id" value="<?php echo $post_id; ?>">
                <textarea name="content" class="form-control" placeholder="Add a comment..." required></textarea>
                <button type="submit" class="btn btn-primary mt-2">Comment</button>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>