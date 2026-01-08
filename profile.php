<?php
require_once 'includes/config.php';
require_once 'includes/database.php';
require_once 'includes/auth.php';

checkLogin();

$db = new Database();

$user_id = isset($_GET['id']) ? (int)$_GET['id'] : $_SESSION['user_id'];

$stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

if (!$user) {
    header("Location: index.php");
    exit();
}

// Get user's posts
$posts_stmt = $db->prepare("SELECT * FROM posts WHERE user_id = ? ORDER BY created_at DESC");
$posts_stmt->bind_param("i", $user_id);
$posts_stmt->execute();
$posts = $posts_stmt->get_result();
?>

<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($user['username']); ?> - Profile</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <div class="container mt-4">
        <div class="row">
            <div class="col-md-4">
                <div class="card">
                    <div class="card-body text-center">
                        <img src="assets/uploads/profile_pictures/<?php echo $user['profile_picture']; ?>" 
                             class="rounded-circle mb-3" width="150" height="150">
                        <h4><?php echo htmlspecialchars($user['username']); ?></h4>
                        <p><?php echo htmlspecialchars($user['bio'] ?? ''); ?></p>
                    </div>
                </div>
            </div>
            <div class="col-md-8">
                <h3>Posts</h3>
                <?php while($post = $posts->fetch_assoc()): ?>
                    <div class="card mb-3">
                        <div class="card-body">
                            <p><?php echo nl2br(htmlspecialchars($post['content'])); ?></p>
                            <?php if($post['image_url']): ?>
                                <img src="assets/uploads/posts/<?php echo $post['image_url']; ?>" class="img-fluid">
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>