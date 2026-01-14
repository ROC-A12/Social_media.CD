<?php
require_once 'includes/config.php';
require_once 'includes/database.php';
require_once 'includes/auth.php';

checkLogin();

$db = getDB();

$user_id = isset($_GET['id']) ? (int)$_GET['id'] : $_SESSION['user_id'];

$stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

if (!$user) {
    header("Location: index.php");
    exit();
}

// Handle profile picture upload
if ($_SERVER['REQUEST_METHOD'] == 'POST' && $user_id == $_SESSION['user_id']) {
    if (!verifyCSRFToken($_POST['csrf_token'])) {
        die("CSRF token invalid");
    }

    if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] == 0) {
        if (validateImage($_FILES['profile_picture'])) {
            $old_picture = $user['profile_picture'];
            $extension = pathinfo($_FILES['profile_picture']['name'], PATHINFO_EXTENSION);
            $new_filename = uniqid() . '.' . $extension;
            $target_path = 'assets/uploads/profile_pictures/' . $new_filename;

            if (move_uploaded_file($_FILES['profile_picture']['tmp_name'], $target_path)) {
                // Update database
                $update_stmt = $db->prepare("UPDATE users SET profile_picture = ? WHERE id = ?");
                $update_stmt->bind_param("si", $new_filename, $user_id);
                $update_stmt->execute();

                // Delete old file if not default
                if ($old_picture != 'default.png' && file_exists('assets/uploads/profile_pictures/' . $old_picture)) {
                    unlink('assets/uploads/profile_pictures/' . $old_picture);
                }

                // Update session
                $_SESSION['profile_pic'] = $new_filename;

                header("Location: profile.php?id=" . $user_id);
                exit();
            } else {
                $error = "Failed to upload image.";
            }
        } else {
            $error = "Invalid image file.";
        }
    }
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
                        <img src="assets/uploads/profile_pictures/<?php echo $user['profile_picture'] ?: 'default.png'; ?>" 
                             class="rounded-circle mb-3" width="150" height="150">
                        <h4><?php echo htmlspecialchars($user['username']); ?></h4>
                        <p><?php echo htmlspecialchars($user['bio'] ?? ''); ?></p>
                    </div>
                </div>
                <?php if ($user_id == $_SESSION['user_id']): ?>
                <div class="card mt-3">
                    <div class="card-body">
                        <h5>Update Profile Picture</h5>
                        <?php if (isset($error)): ?>
                            <div class="alert alert-danger"><?php echo $error; ?></div>
                        <?php endif; ?>
                        <form method="POST" enctype="multipart/form-data">
                            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                            <div class="mb-3">
                                <input type="file" name="profile_picture" accept="image/*" class="form-control" required>
                            </div>
                            <button type="submit" class="btn btn-primary">Update Picture</button>
                        </form>
                    </div>
                </div>
                <?php endif; ?>
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