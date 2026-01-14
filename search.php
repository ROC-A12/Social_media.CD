<?php
require_once 'includes/config.php';
require_once 'includes/database.php';
require_once 'includes/auth.php';

checkLogin();

$db = new Database();
$query = isset($_GET['q']) ? trim($_GET['q']) : '';

$users = [];
if (!empty($query)) {
    $stmt = $db->prepare("SELECT id, username, profile_picture FROM users WHERE username LIKE ? LIMIT 20");
    $search = "%$query%";
    $stmt->bind_param("s", $search);
    $stmt->execute();
    $users = $stmt->get_result();
}
?>

<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Search</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <div class="container mt-4">
        <h2>Search Users</h2>
        <form method="GET" class="mb-4">
            <input type="text" name="q" class="form-control" placeholder="Search for users..." value="<?php echo htmlspecialchars($query); ?>">
            <button type="submit" class="btn btn-primary mt-2">Search</button>
        </form>

        <?php if (!empty($query)): ?>
            <h4>Results for "<?php echo htmlspecialchars($query); ?>"</h4>
            <?php while($user = $users->fetch_assoc()): ?>
                <div class="card mb-2">
                    <div class="card-body d-flex align-items-center">
                        <img src="assets/uploads/profile_pictures/<?php echo $user['profile_picture'] ?: 'default.png'; ?>" class="rounded-circle me-3" width="50" height="50">
                        <div>
                            <h6><?php echo htmlspecialchars($user['username']); ?></h6>
                            <a href="profile.php?id=<?php echo $user['id']; ?>" class="btn btn-sm btn-primary">View Profile</a>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>