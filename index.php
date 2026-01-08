<?php
require_once 'includes/config.php';
require_once 'includes/database.php';
require_once 'includes/auth.php';

checkLogin();

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
?>

<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Home - Social Media</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="container mt-4">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3">
                <div class="card mb-3">
                    <div class="card-body text-center">
                        <img src="assets/uploads/profile_pictures/<?php echo $_SESSION['profile_pic']; ?>" 
                             class="rounded-circle mb-2" width="100" height="100">
                        <h5><?php echo htmlspecialchars($_SESSION['username']); ?></h5>
                        <a href="profile.php?id=<?php echo $user_id; ?>" class="btn btn-sm btn-outline-primary">View Profile</a>
                    </div>
                </div>
                
                <div class="card">
                    <div class="card-header">
                        <h6>Suggestions</h6>
                    </div>
                    <div class="card-body">
                        <!-- Suggesties voor gebruikers om te volgen -->
                        <?php
                        $suggestions = $db->query("
                            SELECT users.id, users.username, users.profile_picture 
                            FROM users 
                            WHERE users.id != $user_id 
                            AND users.id NOT IN (
                                SELECT following_id FROM followers WHERE follower_id = $user_id
                            )
                            LIMIT 5
                        ");
                        
                        while($user = $suggestions->fetch_assoc()): ?>
                            <div class="d-flex align-items-center mb-2">
                                <img src="assets/uploads/profile_pictures/<?php echo $user['profile_picture']; ?>" 
                                     class="rounded-circle me-2" width="30" height="30">
                                <div class="flex-grow-1">
                                    <strong><?php echo htmlspecialchars($user['username']); ?></strong>
                                </div>
                                <a href="follow.php?user_id=<?php echo $user['id']; ?>" class="btn btn-sm btn-primary">Follow</a>
                            </div>
                        <?php endwhile; ?>
                    </div>
                </div>
            </div>
            
            <!-- Main Content -->
            <div class="col-md-6">
                <!-- Create Post -->
                <div class="card mb-3">
                    <div class="card-body">
                        <form action="create_post.php" method="POST" enctype="multipart/form-data">
                            <textarea name="content" class="form-control mb-2" placeholder="What's on your mind?" rows="3"></textarea>
                            <div class="mb-2">
                                <input type="file" name="image" class="form-control" accept="image/*">
                            </div>
                            <button type="submit" class="btn btn-primary">Post</button>
                        </form>
                    </div>
                </div>
                
                <!-- Posts Feed -->
                <?php while($post = $posts->fetch_assoc()): ?>
                    <div class="card mb-3">
                        <div class="card-body">
                            <div class="d-flex align-items-center mb-3">
                                <img src="assets/uploads/profile_pictures/<?php echo $post['profile_picture']; ?>" 
                                     class="rounded-circle me-2" width="40" height="40">
                                <div>
                                    <h6 class="mb-0"><?php echo htmlspecialchars($post['username']); ?></h6>
                                    <small class="text-muted"><?php echo date('F j, Y, g:i a', strtotime($post['created_at'])); ?></small>
                                </div>
                            </div>
                            
                            <p><?php echo nl2br(htmlspecialchars($post['content'])); ?></p>
                            
                            <?php if($post['image_url']): ?>
                                <img src="assets/uploads/posts/<?php echo $post['image_url']; ?>" class="img-fluid mb-3">
                            <?php endif; ?>
                            
                            <div class="d-flex justify-content-between">
                                <form action="like.php" method="POST" class="d-inline">
                                    <input type="hidden" name="post_id" value="<?php echo $post['id']; ?>">
                                    <button type="submit" class="btn btn-sm <?php echo $post['user_liked'] ? 'btn-danger' : 'btn-outline-danger'; ?>">
                                        Like (<?php echo $post['like_count']; ?>)
                                    </button>
                                </form>
                                
                                <a href="post.php?id=<?php echo $post['id']; ?>" class="btn btn-sm btn-outline-primary">
                                    Comment (<?php echo $post['comment_count']; ?>)
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
            
            <!-- Right Sidebar -->
            <div class="col-md-3">
                <div class="card">
                    <div class="card-header">
                        <h6>Trending</h6>
                    </div>
                    <div class="card-body">
                        <!-- Trending topics of populaire posts -->
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>