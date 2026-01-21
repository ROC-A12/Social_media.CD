<?php
require_once 'includes/functies.php';

checkLogin();

// Handle like and delete actions
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['post_id'])) {
    if (!verifyCSRFToken($_POST['csrf_token'])) {
        die("CSRF token invalid");
    }
    
    if (isset($_POST['delete'])) {
        deletePost((int)$_POST['post_id'], $_SESSION['user_id']);
    } else {
        toggleLike((int)$_POST['post_id'], $_SESSION['user_id']);
    }
    header("Location: " . $_SERVER['HTTP_REFERER']);
    exit();
}

// Handle follow action
if (isset($_GET['follow'])) {
    toggleFollow($_SESSION['user_id'], (int)$_GET['follow']);
    header("Location: index.php");
    exit();
}

$db = getDB();
$user_id = $_SESSION['user_id'];

// SQL Query met privacy filter (PDO)
$sql = "
    SELECT posts.*, users.username, users.profile_picture,
           (SELECT COUNT(*) FROM likes WHERE likes.post_id = posts.id) as like_count,
           EXISTS(SELECT 1 FROM likes WHERE likes.post_id = posts.id AND likes.user_id = ?) as user_liked
    FROM posts
    JOIN users ON posts.user_id = users.id
    WHERE posts.posts_id IS NULL
    AND (users.is_private = 0 OR posts.user_id = ? OR posts.user_id IN (
        SELECT following_id FROM follows WHERE follower_id = ?
    ))
    ORDER BY posts.created_at DESC
    LIMIT 50
";
$posts = $db->query($sql, [$user_id, $user_id, $user_id])->fetchAll();
?>

<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Home - Social Media</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="container mt-4">
        <div class="row">
            <div class="col-md-3">
                <div class="card mb-3">
                    <div class="card-body text-center">
                        <img src="assets/uploads/profile_pictures/<?php echo $_SESSION['profile_pic'] ?: 'default.png'; ?>" 
                             class="rounded-circle mb-2" width="100" height="100">
                        <h5><?php echo htmlspecialchars($_SESSION['username']); ?></h5>
                        <a href="profile.php?id=<?php echo $user_id; ?>" class="btn btn-sm btn-outline-primary">View Profile</a>
                    </div>
                </div>
                
                <div class="card">
                    <div class="card-header"><h6>Discover Users</h6></div>
                    <div class="card-body">
                        <?php
                        $sugg_sql = "SELECT id, username, profile_picture FROM users WHERE id != ? AND is_private = 0 AND id NOT IN (SELECT following_id FROM follows WHERE follower_id = ?) LIMIT 5";
                        $sugg_res = $db->query($sugg_sql, [$user_id, $user_id])->fetchAll();
                        foreach($sugg_res as $user): ?>
                            <div class="d-flex align-items-center mb-3 pb-2 border-bottom">
                                <a href="profile.php?id=<?php echo $user['id']; ?>">
                                    <img src="assets/uploads/profile_pictures/<?php echo $user['profile_picture'] ?: 'default.png'; ?>" class="rounded-circle me-2" width="35" height="35">
                                </a>
                                <div class="flex-grow-1"><strong><?php echo htmlspecialchars($user['username']); ?></strong></div>
                                <a href="index.php?follow=<?php echo $user['id']; ?>" class="btn btn-sm btn-primary">Follow</a>
                                <a href="messages.php?user=<?php echo $user['id']; ?>" class="btn btn-sm btn-outline-secondary ms-2">Bericht</a>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6">
                <h4 class="mb-4">Posts</h4>
                <?php if(count($posts) > 0): ?>
                    <?php foreach($posts as $post): ?>
                        <div class="card mb-3">
                            <div class="card-body">
                                <div class="d-flex align-items-center justify-content-between mb-3">
                                    <div class="d-flex align-items-center">
                                        <a href="profile.php?id=<?php echo $post['user_id']; ?>">
                                            <img src="assets/uploads/profile_pictures/<?php echo $post['profile_picture'] ?: 'default.png'; ?>" class="rounded-circle me-2" width="40" height="40">
                                        </a>
                                        <div>
                                            <h6 class="mb-0"><?php echo htmlspecialchars($post['username']); ?></h6>
                                            <small class="text-muted"><?php echo date('F j, Y, g:i a', strtotime($post['created_at'])); ?></small>
                                        </div>
                                    </div>
                                    <?php if($post['user_id'] == $user_id): ?>
                                        <form action="" method="POST" class="d-inline">
                                            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                            <input type="hidden" name="post_id" value="<?php echo $post['id']; ?>">
                                            <input type="hidden" name="delete" value="1">
                                            <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('Weet je zeker?');">Delete</button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                                
                                <p><?php echo nl2br(htmlspecialchars($post['content'])); ?></p>
                                
                                <?php 
                                $img_file = !empty($post['image']) ? $post['image'] : (!empty($post['image_url']) ? $post['image_url'] : '');
                                if(!empty($img_file)): ?>
                                    <img src="assets/uploads/posts/<?php echo htmlspecialchars($img_file); ?>" class="img-fluid mt-3 mb-3" style="max-height: 400px; width: auto;">
                                <?php endif; ?>
                                
                                <div class="d-flex justify-content-between mt-3">
                                    <form action="" method="POST" class="d-inline">
                                        <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                        <input type="hidden" name="post_id" value="<?php echo $post['id']; ?>">
                                        <button type="submit" class="btn btn-sm <?php echo $post['user_liked'] ? 'btn-danger' : 'btn-outline-danger'; ?>">
                                            Like (<?php echo $post['like_count']; ?>)
                                        </button>
                                    </form>
                                    <a href="post.php?id=<?php echo $post['id']; ?>" class="btn btn-sm btn-outline-primary">Reply</a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            
            <div class="col-md-3">
                <div class="card">
                    <div class="card-header"><h6>Popular Users</h6></div>
                    <div class="card-body">
                        <?php
                        $pop_sql = "SELECT id, username, profile_picture, (SELECT COUNT(*) FROM follows WHERE following_id = users.id) as f_count FROM users WHERE is_private = 0 ORDER BY f_count DESC LIMIT 5";
                        $pop_res = $db->query($pop_sql)->fetchAll();
                        foreach($pop_res as $p_user): ?>
                            <div class="d-flex align-items-center mb-2 pb-2 border-bottom">
                                <a href="profile.php?id=<?php echo $p_user['id']; ?>">
                                    <img src="assets/uploads/profile_pictures/<?php echo $p_user['profile_picture'] ?: 'default.png'; ?>" class="rounded-circle me-2" width="35" height="35">
                                </a>
                                <div>
                                    <strong><?php echo htmlspecialchars($p_user['username']); ?></strong>
                                    <br><small class="text-muted"><?php echo $p_user['f_count']; ?> followers</small>
                                </div>
                                <div class="ms-2">
                                    <a href="messages.php?user=<?php echo $p_user['id']; ?>" class="btn btn-sm btn-outline-secondary">Bericht</a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>