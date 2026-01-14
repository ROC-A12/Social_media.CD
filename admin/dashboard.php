<?php
require_once '../includes/config.php';
require_once '../includes/database.php';
require_once '../includes/auth.php';

// Check admin rechten
if(!isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
    header("Location: ../index.php");
    exit();
}

$db = getDB();

// Statistieken
$total_users = $db->query("SELECT COUNT(*) as count FROM users")->fetch_assoc()['count'];
$total_posts = $db->query("SELECT COUNT(*) as count FROM posts")->fetch_assoc()['count'];
$total_comments = $db->query("SELECT COUNT(*) as count FROM comments")->fetch_assoc()['count'];
?>

<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <?php include '../includes/admin_header.php'; ?>
    
    <div class="container-fluid mt-3">
        <h2>Admin Dashboard</h2>
        
        <div class="row mt-4">
            <div class="col-md-3">
                <div class="card text-white bg-primary mb-3">
                    <div class="card-body">
                        <h5 class="card-title">Total Users</h5>
                        <p class="card-text display-4"><?php echo $total_users; ?></p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-white bg-success mb-3">
                    <div class="card-body">
                        <h5 class="card-title">Total Posts</h5>
                        <p class="card-text display-4"><?php echo $total_posts; ?></p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-white bg-warning mb-3">
                    <div class="card-body">
                        <h5 class="card-title">Total Comments</h5>
                        <p class="card-text display-4"><?php echo $total_comments; ?></p>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="row mt-4">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5>Recent Users</h5>
                    </div>
                    <div class="card-body">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Username</th>
                                    <th>Email</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $users = $db->query("SELECT * FROM users ORDER BY created_at DESC LIMIT 5");
                                while($user = $users->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo $user['id']; ?></td>
                                    <td><?php echo htmlspecialchars($user['username']); ?></td>
                                    <td><?php echo htmlspecialchars($user['email']); ?></td>
                                    <td>
                                        <a href="users.php?action=block&id=<?php echo $user['id']; ?>" 
                                           class="btn btn-sm btn-danger">Block</a>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5>Recent Posts</h5>
                    </div>
                    <div class="card-body">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Content</th>
                                    <th>User</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $posts = $db->query("
                                    SELECT posts.*, users.username 
                                    FROM posts 
                                    JOIN users ON posts.user_id = users.id 
                                    ORDER BY posts.created_at DESC 
                                    LIMIT 5
                                ");
                                while($post = $posts->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo $post['id']; ?></td>
                                    <td><?php echo substr(htmlspecialchars($post['content']), 0, 50); ?>...</td>
                                    <td><?php echo htmlspecialchars($post['username']); ?></td>
                                    <td>
                                        <a href="posts.php?action=delete&id=<?php echo $post['id']; ?>" 
                                           class="btn btn-sm btn-danger">Delete</a>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>