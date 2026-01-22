<?php
require_once 'includes/functies.php';

// Zoekpagina voor gebruikers
// Simpele LIKE-zoekopdracht op gebruikersnaam (limiet 20 resultaten)
checkLogin();

$db = getDB();
$query = isset($_GET['q']) ? trim($_GET['q']) : '';

$users = [];
if (!empty($query)) {
    // Bereid zoekquery voor en gebruik wildcard
    $stmt = $db->prepare("SELECT id, username, profile_picture FROM users WHERE username LIKE ? LIMIT 20");
    $search = "%$query%";
    $stmt->execute([$search]);
    $users = $stmt->fetchAll();
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
        <h2>Zoek gebruikers</h2>
        <form method="GET" class="mb-4">
            <input type="text" name="q" class="form-control" placeholder="Zoek naar gebruikers..." value="<?php echo htmlspecialchars($query); ?>">
            <button type="submit" class="btn btn-primary mt-2">Zoeken</button>
        </form>

        <?php if (!empty($query)): ?>
            <h4>Resultaten voor "<?php echo htmlspecialchars($query); ?>"</h4>
            <?php foreach($users as $user): ?>
                <div class="card mb-2">
                    <div class="card-body d-flex align-items-center">
                        <img src="assets/uploads/profile_pictures/<?php echo $user['profile_picture'] ?: 'default.png'; ?>" class="rounded-circle me-3" width="50" height="50">
                        <div>
                            <h6><?php echo htmlspecialchars($user['username']); ?></h6>
                            <a href="profile.php?id=<?php echo $user['id']; ?>" class="btn btn-sm btn-primary">Bekijk profiel</a>
                            <a href="messages.php?user=<?php echo $user['id']; ?>" class="btn btn-sm btn-outline-secondary ms-2">Bericht</a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>