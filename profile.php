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
if ($_SERVER['REQUEST_METHOD'] == 'POST' && $user_id == $_SESSION['user_id'] && isset($_FILES['profile_picture'])) {
    if (!verifyCSRFToken($_POST['csrf_token'])) {
        die("CSRF token invalid");
    }

    if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] == 0) {
        $old_picture = $user['profile_picture'];
        $extension = pathinfo($_FILES['profile_picture']['name'], PATHINFO_EXTENSION);
        $new_filename = uniqid() . '.' . $extension;
        $target_path = 'assets/uploads/profile_pictures/' . $new_filename;

        if (move_uploaded_file($_FILES['profile_picture']['tmp_name'], $target_path)) {
            $update_stmt = $db->prepare("UPDATE users SET profile_picture = ? WHERE id = ?");
            $update_stmt->bind_param("si", $new_filename, $user_id);
            $update_stmt->execute();

            if ($old_picture != 'default.png' && !empty($old_picture) && file_exists('assets/uploads/profile_pictures/' . $old_picture)) {
                unlink('assets/uploads/profile_pictures/' . $old_picture);
            }

            $_SESSION['profile_pic'] = $new_filename;
            header("Location: profile.php?id=" . $user_id);
            exit();
        }
    }
}

// Handle bio update
if ($_SERVER['REQUEST_METHOD'] == 'POST' && $user_id == $_SESSION['user_id'] && isset($_POST['bio'])) {
    if (!verifyCSRFToken($_POST['csrf_token'])) {
        die("CSRF token invalid");
    }

    $bio = trim($_POST['bio']);
    $update_stmt = $db->prepare("UPDATE users SET text = ? WHERE id = ?");
    $update_stmt->bind_param("si", $bio, $user_id);
    $update_stmt->execute();
    header("Location: profile.php?id=" . $user_id);
    exit();
}

// Follow logica & Tellers
$is_following = false;
$has_pending_request = false;
if ($user_id != $_SESSION['user_id']) {
    $follow_check = $db->prepare("SELECT id FROM follows WHERE follower_id = ? AND following_id = ?");
    $follow_check->bind_param("ii", $_SESSION['user_id'], $user_id);
    $follow_check->execute();
    $is_following = $follow_check->get_result()->num_rows > 0;
    
    $request_check = $db->prepare("SELECT id FROM follow_requests WHERE requester_id = ? AND recipient_id = ? AND status = 'pending'");
    $request_check->bind_param("ii", $_SESSION['user_id'], $user_id);
    $request_check->execute();
    $has_pending_request = $request_check->get_result()->num_rows > 0;
}

$count_f = $db->prepare("SELECT COUNT(*) as c FROM follows WHERE following_id = ?");
$count_f->bind_param("i", $user_id);
$count_f->execute();
$followers_num = $count_f->get_result()->fetch_assoc()['c'];

$count_fg = $db->prepare("SELECT COUNT(*) as c FROM follows WHERE follower_id = ?");
$count_fg->bind_param("i", $user_id);
$count_fg->execute();
$following_num = $count_fg->get_result()->fetch_assoc()['c'];

$can_view_posts = ($user_id == $_SESSION['user_id'] || !$user['is_private'] || $is_following);

$posts = null;
if ($can_view_posts) {
    $posts_stmt = $db->prepare("SELECT * FROM posts WHERE user_id = ? AND posts_id IS NULL ORDER BY created_at DESC");
    $posts_stmt->bind_param("i", $user_id);
    $posts_stmt->execute();
    $posts = $posts_stmt->get_result();
}
?>

<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($user['username']); ?> - Profile</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <div class="container mt-4">
        <div class="row">
            <div class="col-md-4">
                <div class="card shadow-sm mb-3">
                    <div class="card-body text-center">
                        <img src="assets/uploads/profile_pictures/<?php echo $user['profile_picture'] ?: 'default.png'; ?>" 
                             class="rounded-circle mb-3 border" width="150" height="150" style="object-fit: cover;">
                        <h4><?php echo htmlspecialchars($user['username']); ?>
                            <?php if(isset($user['is_private']) && $user['is_private']): ?>
                                <span class="badge bg-warning text-dark" style="font-size: 0.5em;"><i class="fas fa-lock"></i> Privé</span>
                            <?php endif; ?>
                        </h4>

                        <div class="d-flex justify-content-center gap-2 mb-3">
                            <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#followersModal">
                                <strong><?php echo $followers_num; ?></strong> Volgers
                            </button>
                            <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#followingModal">
                                <strong><?php echo $following_num; ?></strong> Volgend
                            </button>
                        </div>

                        <p class="text-muted small"><?php echo htmlspecialchars($user['text'] ?? ''); ?></p>
                        
                        <?php if ($user_id != $_SESSION['user_id']): ?>
                            <?php if($has_pending_request): ?>
                                <button class="btn btn-sm btn-secondary w-100" disabled>Verzoek in behandeling</button>
                            <?php else: ?>
                                <a href="follow.php?user_id=<?php echo $user_id; ?>" class="btn btn-sm w-100 <?php echo $is_following ? 'btn-danger' : 'btn-primary'; ?>">
                                    <?php echo $is_following ? 'Ontvolgen' : 'Volgen'; ?>
                                </a>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <?php if ($user_id == $_SESSION['user_id']): ?>
                <div class="card shadow-sm mb-3">
                    <div class="card-body">
                        <h6>Privacy Instellingen</h6>
                        <form method="POST" action="update_privacy.php">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="is_private" onchange="this.form.submit()" <?php echo $user['is_private'] ? 'checked' : ''; ?>>
                                <label class="form-check-label small">Privé Account</label>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="card shadow-sm mb-3">
                    <div class="card-header bg-white"><h6>Volgverzoeken</h6></div>
                    <div class="card-body p-0" style="max-height: 200px; overflow-y: auto;">
                        <?php
                        $req_s = $db->prepare("SELECT fr.id, u.username, u.profile_picture FROM follow_requests fr JOIN users u ON fr.requester_id = u.id WHERE fr.recipient_id = ? AND fr.status = 'pending'");
                        $req_s->bind_param("i", $_SESSION['user_id']); $req_s->execute(); $requests = $req_s->get_result();
                        while($req = $requests->fetch_assoc()): ?>
                            <div class="d-flex align-items-center p-2 border-bottom">
                                <img src="assets/uploads/profile_pictures/<?php echo $req['profile_picture'] ?: 'default.png'; ?>" class="rounded-circle me-2" width="30" height="30">
                                <span class="small flex-grow-1"><strong><?php echo htmlspecialchars($req['username']); ?></strong></span>
                                <a href="respond_follow_request.php?request_id=<?php echo $req['id']; ?>&action=accept" class="btn btn-xs btn-success py-0 px-1 text-white">✓</a>
                                <a href="respond_follow_request.php?request_id=<?php echo $req['id']; ?>&action=reject" class="btn btn-xs btn-danger py-0 px-1 text-white ms-1">✕</a>
                            </div>
                        <?php endwhile; if($requests->num_rows == 0) echo '<p class="text-muted p-3 mb-0 small">Geen verzoeken</p>'; ?>
                    </div>
                </div>

                <div class="card shadow-sm mb-3">
                    <div class="card-body">
                        <h6>Profielfoto wijzigen</h6>
                        <form method="POST" enctype="multipart/form-data">
                            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                            <input type="file" name="profile_picture" class="form-control form-control-sm mb-2" required>
                            <button type="submit" class="btn btn-sm btn-primary w-100">Update</button>
                        </form>
                    </div>
                </div>

                <div class="card shadow-sm mb-3">
                    <div class="card-body">
                        <h6>Bio wijzigen</h6>
                        <form method="POST">
                            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                            <textarea name="bio" class="form-control form-control-sm mb-2" rows="3" placeholder="Vertel iets over jezelf..."><?php echo htmlspecialchars($user['text'] ?? ''); ?></textarea>
                            <button type="submit" class="btn btn-sm btn-primary w-100">Update Bio</button>
                        </form>
                    </div>
                </div>
                <?php endif; ?>
            </div>

            <div class="col-md-8">
                <?php if ($user_id == $_SESSION['user_id']): ?>
                    <div class="card mb-3 shadow-sm border-primary">
                        <div class="card-body">
                            <form action="create_post.php" method="POST" enctype="multipart/form-data">
                                <textarea name="content" class="form-control mb-2" placeholder="Wat wil je delen?" rows="3" required></textarea>
                                <input type="file" name="image" class="form-control form-control-sm mb-2" accept="image/*">
                                <button type="submit" class="btn btn-primary">Plaatsen</button>
                            </form>
                        </div>
                    </div>
                <?php endif; ?>

                <h3 class="mb-4">Posts</h3>
                <?php if($can_view_posts): ?>
                    <?php if($posts && $posts->num_rows > 0): ?>
                        <?php while($post = $posts->fetch_assoc()): ?>
                            <div class="card mb-3 shadow-sm">
                                <div class="card-body">
                                    <div class="d-flex align-items-center justify-content-between mb-2">
                                        <div class="d-flex align-items-center">
                                            <img src="assets/uploads/profile_pictures/<?php echo $user['profile_picture'] ?: 'default.png'; ?>" class="rounded-circle me-2" width="40" height="40" style="object-fit: cover;">
                                            <div>
                                                <h6 class="mb-0"><?php echo htmlspecialchars($user['username']); ?></h6>
                                                <small class="text-muted"><?php echo date('d-m-Y H:i', strtotime($post['created_at'])); ?></small>
                                            </div>
                                        </div>
                                        <?php if($post['user_id'] == $_SESSION['user_id']): ?>
                                            <form action="delete_post.php" method="POST">
                                                <input type="hidden" name="post_id" value="<?php echo $post['id']; ?>">
                                                <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('Verwijderen?');">Delete</button>
                                            </form>
                                        <?php endif; ?>
                                    </div>
                                    <p class="mt-2"><?php echo nl2br(htmlspecialchars($post['content'])); ?></p>
                                    <?php if(!empty($post['image'])): ?>
                                        <img src="assets/uploads/posts/<?php echo htmlspecialchars($post['image']); ?>" class="img-fluid rounded mt-2">
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    <?php else: echo '<div class="alert alert-info">Nog geen posts.</div>'; endif; ?>
                <?php else: ?>
                    <div class="card bg-light text-center p-5 shadow-sm">
                        <i class="fas fa-lock fa-3x mb-3 text-muted"></i>
                        <h5>Dit account is privé</h5>
                        <p class="text-muted">Volg deze persoon om hun posts te zien.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="modal fade" id="followersModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered modal-sm">
            <div class="modal-content">
                <div class="modal-header border-0 pb-0"><h5 class="modal-title">Volgers</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body">
                    <?php
                    $f_s = $db->prepare("SELECT u.id, u.username, u.profile_picture FROM follows f JOIN users u ON f.follower_id = u.id WHERE f.following_id = ?");
                    $f_s->bind_param("i", $user_id); $f_s->execute(); $f_res = $f_s->get_result();
                    while($f = $f_res->fetch_assoc()): ?>
                        <div class="d-flex align-items-center mb-3">
                            <img src="assets/uploads/profile_pictures/<?php echo $f['profile_picture'] ?: 'default.png'; ?>" class="rounded-circle me-2 border" width="30" height="30" style="object-fit: cover;">
                            <a href="profile.php?id=<?php echo $f['id']; ?>" class="text-decoration-none text-dark small flex-grow-1"><?php echo htmlspecialchars($f['username']); ?></a>
                            <?php if($user_id == $_SESSION['user_id']): ?>
                                <a href="remove_follower.php?follower_id=<?php echo $f['id']; ?>" class="btn btn-xs btn-outline-danger py-0" style="font-size:0.6rem" onclick="return confirm('Volger verwijderen?')">Verwijder</a>
                            <?php endif; ?>
                        </div>
                    <?php endwhile; if($f_res->num_rows == 0) echo "<small class='text-muted'>Geen volgers.</small>"; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="followingModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered modal-sm">
            <div class="modal-content">
                <div class="modal-header border-0 pb-0"><h5 class="modal-title">Volgend</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body">
                    <?php
                    $fg_s = $db->prepare("SELECT u.id, u.username, u.profile_picture FROM follows f JOIN users u ON f.following_id = u.id WHERE f.follower_id = ?");
                    $fg_s->bind_param("i", $user_id); $fg_s->execute(); $fg_res = $fg_s->get_result();
                    while($fg = $fg_res->fetch_assoc()): ?>
                        <div class="d-flex align-items-center mb-3">
                            <img src="assets/uploads/profile_pictures/<?php echo $fg['profile_picture'] ?: 'default.png'; ?>" class="rounded-circle me-2 border" width="30" height="30" style="object-fit: cover;">
                            <a href="profile.php?id=<?php echo $fg['id']; ?>" class="text-decoration-none text-dark small"><?php echo htmlspecialchars($fg['username']); ?></a>
                        </div>
                    <?php endwhile; if($fg_res->num_rows == 0) echo "<small class='text-muted'>Volgt niemand.</small>"; ?>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>