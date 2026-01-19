<?php
require_once 'includes/functies.php';

checkLogin();

// Handle like action
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['post_id'])) {
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

// Haal posts op van alle openbare accounts en accounts die je volgt
$stmt = $db->prepare("
    SELECT posts.*, users.username, users.profile_picture,
           (SELECT COUNT(*) FROM likes WHERE likes.post_id = posts.id) as like_count,
           EXISTS(SELECT 1 FROM likes WHERE likes.post_id = posts.id AND likes.user_id = ?) as user_liked
    FROM posts 
    JOIN users ON posts.user_id = users.id
    WHERE posts.posts_id IS NULL AND (posts.user_id IN (
        SELECT following_id FROM follows WHERE follower_id = ?
    ) OR posts.user_id = ?)
    ORDER BY posts.created_at DESC
    LIMIT 50
");
$stmt->bind_param("iii", $user_id, $user_id, $user_id);
$stmt->execute();
$posts = $stmt->get_result();
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
                    <div class="card-header">
                        <h6>Discover Users</h6>
                    </div>
                    <div class="card-body">
                        <?php
                        $suggestions_stmt = $db->prepare("
                            SELECT users.id, users.username, users.profile_picture,
                                   EXISTS(SELECT 1 FROM follows WHERE follower_id = ? AND following_id = users.id) as is_following
                            FROM users 
                            WHERE users.id != ? 
                            AND users.id NOT IN (
                                SELECT following_id FROM follows WHERE follower_id = ?
                            )
                            LIMIT 8
                        ");
                        $suggestions_stmt->bind_param("iii", $user_id, $user_id, $user_id);
                        $suggestions_stmt->execute();
                        $suggestions = $suggestions_stmt->get_result();
                        
                        while($user = $suggestions->fetch_assoc()): ?>
                            <div class="d-flex align-items-center mb-3 pb-2 border-bottom">
                                <a href="profile.php?id=<?php echo $user['id']; ?>" class="text-decoration-none">
                                    <img src="assets/uploads/profile_pictures/<?php echo $user['profile_picture'] ?: 'default.png'; ?>" 
                                         class="rounded-circle me-2" width="35" height="35">
                                </a>
                                <div class="flex-grow-1 min-width-0">
                                    <a href="profile.php?id=<?php echo $user['id']; ?>" class="text-decoration-none text-dark">
                                        <strong><?php echo htmlspecialchars($user['username']); ?></strong>
                                    </a>
                                </div>
                                <a href="?follow=<?php echo $user['id']; ?>" class="btn btn-sm btn-primary">Follow</a>
                            </div>
                        <?php endwhile; ?>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6">
                <h4 class="mb-4">Posts</h4>
                
                <?php if($posts->num_rows > 0): ?>
                    <?php while($post = $posts->fetch_assoc()): ?>
                        <div class="card mb-3">
                            <div class="card-body">
                                <div class="d-flex align-items-center justify-content-between mb-3">
                                    <div class="d-flex align-items-center">
                                        <a href="profile.php?id=<?php echo $post['user_id']; ?>" class="text-decoration-none">
                                            <img src="assets/uploads/profile_pictures/<?php echo $post['profile_picture'] ?: 'default.png'; ?>" 
                                                 class="rounded-circle me-2" width="40" height="40">
                                        </a>
                                        <div>
                                            <a href="profile.php?id=<?php echo $post['user_id']; ?>" class="text-decoration-none text-dark">
                                                <h6 class="mb-0"><?php echo htmlspecialchars($post['username']); ?></h6>
                                            </a>
                                            <small class="text-muted"><?php echo date('F j, Y, g:i a', strtotime($post['created_at'])); ?></small>
                                        </div>
                                    </div>
                                    <?php if($post['user_id'] == $user_id): ?>
                                        <form action="" method="POST" class="d-inline">
                                            <input type="hidden" name="post_id" value="<?php echo $post['id']; ?>">
                                            <input type="hidden" name="delete" value="1">
                                            <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('Weet je zeker dat je deze post wilt verwijderen?');">
                                                Delete
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                                
                                <p><?php echo nl2br(htmlspecialchars($post['content'])); ?></p>
                                
                                <?php if(!empty($post['image'])): ?>
                                    <img src="assets/uploads/posts/<?php echo htmlspecialchars($post['image']); ?>" class="img-fluid mt-3 mb-3" style="max-height: 400px; width: auto;">
                                <?php endif; ?>
                                
                                <div class="d-flex justify-content-between mt-3">
                                    <form action="" method="POST" class="d-inline">
                                        <input type="hidden" name="post_id" value="<?php echo $post['id']; ?>">
                                        <button type="submit" class="btn btn-sm <?php echo $post['user_liked'] ? 'btn-danger' : 'btn-outline-danger'; ?>">
                                            Like (<?php echo $post['like_count']; ?>)
                                        </button>
                                    </form>
                                    
                                    <a href="post.php?id=<?php echo $post['id']; ?>" class="btn btn-sm btn-outline-primary">
                                        Reply
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="alert alert-info">No posts to explore. Follow more users to see their posts!</div>
                <?php endif; ?>
            </div>
            
            <div class="col-md-3">
                <div class="card">
                    <div class="card-header">
                        <h6>Popular Users</h6>
                    </div>
                    <div class="card-body">
                        <?php
                        $popular_stmt = $db->prepare("
                            SELECT users.id, users.username, users.profile_picture,
                                   (SELECT COUNT(*) FROM follows WHERE following_id = users.id) as follower_count
                            FROM users 
                            ORDER BY follower_count DESC
                            LIMIT 5
                        ");
                        $popular_stmt->execute();
                        $popular = $popular_stmt->get_result();
                        
                        while($user = $popular->fetch_assoc()): ?>
                            <div class="d-flex align-items-center mb-2 pb-2 border-bottom">
                                <a href="profile.php?id=<?php echo $user['id']; ?>">
                                    <img src="assets/uploads/profile_pictures/<?php echo $user['profile_picture'] ?: 'default.png'; ?>" 
                                         class="rounded-circle me-2" width="35" height="35">
                                </a>
                                <div>
                                    <a href="profile.php?id=<?php echo $user['id']; ?>" class="text-decoration-none text-dark">
                                        <strong><?php echo htmlspecialchars($user['username']); ?></strong>
                                    </a>
                                    <br><small class="text-muted"><?php echo $user['follower_count']; ?> followers</small>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>