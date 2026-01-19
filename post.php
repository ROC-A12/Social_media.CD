<?php
require_once 'includes/functies.php';

checkLogin();

// Handle like and comment actions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['post_id'])) {
        if (isset($_POST['delete'])) {
            deletePost((int)$_POST['post_id'], $_SESSION['user_id']);
            header("Location: index.php");
            exit();
        } elseif (isset($_POST['content'])) {
            // Comment
            addComment((int)$_POST['post_id'], $_SESSION['user_id'], trim($_POST['content']));
        } else {
            // Like
            toggleLike((int)$_POST['post_id'], $_SESSION['user_id']);
        }
        header("Location: " . $_SERVER['HTTP_REFERER']);
        exit();
    }
}

$db = getDB();
$user_id = $_SESSION['user_id'];
$post_id = (int)$_GET['id'];

$stmt = $db->prepare("SELECT posts.*, users.username, users.profile_picture,
           (SELECT COUNT(*) FROM likes WHERE likes.post_id = posts.id) as like_count,
           EXISTS(SELECT 1 FROM likes WHERE likes.post_id = posts.id AND likes.user_id = ?) as user_liked
    FROM posts JOIN users ON posts.user_id = users.id WHERE posts.id = ?");
$stmt->bind_param("ii", $user_id, $post_id);
$stmt->execute();
$post = $stmt->get_result()->fetch_assoc();

if (!$post) {
    header("Location: index.php");
    exit();
}

// Get comments/replies with like counts
$comments_stmt = $db->prepare("SELECT posts.*, users.username, users.profile_picture,
           (SELECT COUNT(*) FROM likes WHERE likes.post_id = posts.id) as like_count,
           EXISTS(SELECT 1 FROM likes WHERE likes.post_id = posts.id AND likes.user_id = ?) as user_liked
    FROM posts JOIN users ON posts.user_id = users.id WHERE posts.posts_id = ? ORDER BY posts.created_at ASC");
$comments_stmt->bind_param("ii", $user_id, $post_id);
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
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <div class="d-flex align-items-center">
                        <img src="assets/uploads/profile_pictures/<?php echo $post['profile_picture'] ?: 'default.png'; ?>" 
                             class="rounded-circle me-2" width="40" height="40">
                        <div>
                            <h6 class="mb-0"><?php echo htmlspecialchars($post['username']); ?></h6>
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
                    <img src="assets/uploads/posts/<?php echo htmlspecialchars($post['image']); ?>" class="img-fluid mb-3">
                <?php endif; ?>
                <form action="" method="POST" class="d-inline">
                    <input type="hidden" name="post_id" value="<?php echo $post['id']; ?>">
                    <button type="submit" class="btn btn-sm <?php echo $post['user_liked'] ? 'btn-danger' : 'btn-outline-danger'; ?>">
                        Like (<?php echo $post['like_count']; ?>)
                    </button>
                </form>
            </div>
        </div>

        <div class="mt-4">
            <h6>Replies</h6>
            <?php while($comment = $comments->fetch_assoc()): ?>
                <div class="card mb-2">
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <div class="d-flex align-items-center">
                                <img src="assets/uploads/profile_pictures/<?php echo $comment['profile_picture'] ?: 'default.png'; ?>" 
                                     class="rounded-circle me-2" width="30" height="30">
                                <div>
                                    <strong><?php echo htmlspecialchars($comment['username']); ?></strong>
                                    <small class="text-muted ms-2"><?php echo date('F j, Y, g:i a', strtotime($comment['created_at'])); ?></small>
                                </div>
                            </div>
                            <?php if($comment['user_id'] == $user_id): ?>
                                <form action="" method="POST" class="d-inline">
                                    <input type="hidden" name="post_id" value="<?php echo $comment['id']; ?>">
                                    <input type="hidden" name="delete" value="1">
                                    <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('Weet je zeker dat je deze reply wilt verwijderen?');">
                                        Delete
                                    </button>
                                </form>
                            <?php endif; ?>
                        </div>
                        <p><?php echo nl2br(htmlspecialchars($comment['content'])); ?></p>
                        <?php if(!empty($comment['image'])): ?>
                            <img src="assets/uploads/posts/<?php echo htmlspecialchars($comment['image']); ?>" class="img-fluid mb-2" style="max-height: 250px;">
                        <?php endif; ?>
                        <form action="" method="POST" class="d-inline">
                            <input type="hidden" name="post_id" value="<?php echo $comment['id']; ?>">
                            <button type="submit" class="btn btn-sm <?php echo $comment['user_liked'] ? 'btn-danger' : 'btn-outline-danger'; ?>">
                                Like (<?php echo $comment['like_count']; ?>)
                            </button>
                        </form>
                    </div>
                </div>
            <?php endwhile; ?>

            <form action="" method="POST" class="mt-3">
                <input type="hidden" name="post_id" value="<?php echo $post_id; ?>">
                <textarea name="content" class="form-control mb-2" placeholder="Add a reply..." rows="2" required></textarea>
                <button type="submit" class="btn btn-primary">Reply</button>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>