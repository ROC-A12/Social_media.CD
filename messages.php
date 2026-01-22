<?php
require_once 'includes/functies.php';

checkLogin();

$db = getDB();
$user_id = $_SESSION['user_id'];

// Verzendbericht verwerken
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['recipient_id']) && isset($_POST['content'])) {
    if (!verifyCSRFToken($_POST['csrf_token'])) {
        die('CSRF token invalid');
    }
    $recipient = (int)$_POST['recipient_id'];
    $content = trim($_POST['content']);
    // valideer dat ontvanger bestaat en niet jezelf is
    $r_stmt = $db->prepare("SELECT id FROM users WHERE id = ?");
    $r_stmt->execute([$recipient]);
    $r_user = $r_stmt->fetch();
    if ($r_user && $recipient !== $user_id && $content !== '') {
        sendMessage($user_id, $recipient, $content);
    }
    header('Location: messages.php?user=' . $recipient);
    exit();
}

$conversations = getConversations($user_id);

$selected_user = isset($_GET['user']) ? (int)$_GET['user'] : null;
$messages = [];
$selected_user_info = null;
if ($selected_user) {
    $su_stmt = $db->prepare("SELECT id, username, profile_picture FROM users WHERE id = ?");
    $su_stmt->execute([$selected_user]);
    $selected_user_info = $su_stmt->fetch();
    if (!$selected_user_info) {
            // ongeldige gebruiker, selectie negeren
        $selected_user = null;
    } else {
        $messages = getMessagesBetween($user_id, $selected_user);
        // Markeer inkomende berichten van geselecteerde gebruiker als gelezen
        markMessagesRead($selected_user, $user_id);
    }
}

?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Berichten</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .conversation-list { max-height: 70vh; overflow-y: auto; }
        .chat-box { max-height: 65vh; overflow-y: auto; }
        .msg-me { text-align: right; }
        .msg-them { text-align: left; }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <div class="container mt-4">
        <div class="row">
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header"><strong>Gesprekken</strong></div>
                    <div class="list-group list-group-flush conversation-list">
                        <?php if(empty($conversations)): ?>
                            <div class="p-3 text-muted">Nog geen gesprekken</div>
                        <?php else: ?>
                            <?php foreach($conversations as $c):
                                $u = $c['user'];
                                $last = $c['last'];
                                $unread = $c['unread'];
                            ?>
                            <a href="messages.php?user=<?php echo $u['id']; ?>" class="list-group-item list-group-item-action d-flex justify-content-between align-items-start <?php echo ($selected_user==$u['id'])?'active':''; ?>">
                                <div class="d-flex align-items-center">
                                    <img src="assets/uploads/profile_pictures/<?php echo $u['profile_picture'] ?: 'default.png'; ?>" class="rounded-circle me-2" width="45" height="45">
                                    <div>
                                        <div><strong><?php echo htmlspecialchars($u['username']); ?></strong></div>
                                        <small class="text-truncate" style="max-width:180px; display:block;"><?php echo isset($last['content'])? htmlspecialchars($last['content']) : ''; ?></small>
                                    </div>
                                </div>
                                <?php if($unread): ?><span class="badge bg-danger rounded-pill"><?php echo $unread; ?></span><?php endif; ?>
                            </a>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="col-md-8">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <div>
                            <?php if($selected_user && $selected_user_info): ?>
                                <div class="d-flex align-items-center">
                                    <img src="assets/uploads/profile_pictures/<?php echo $selected_user_info['profile_picture'] ?: 'default.png'; ?>" class="rounded-circle me-2" width="45" height="45">
                                    <strong><?php echo htmlspecialchars($selected_user_info['username']); ?></strong>
                                </div>
                            <?php else: ?>
                                <strong>Kies een gesprek</strong>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="card-body chat-box">
                        <?php if($selected_user): ?>
                            <?php if(empty($messages)): ?>
                                <div class="text-muted">Geen berichten in dit gesprek.</div>
                            <?php else: ?>
                                <?php foreach($messages as $m): ?>
                                    <?php if($m['sender_id'] == $user_id): ?>
                                        <div class="mb-2 msg-me"><div class="badge bg-primary text-wrap p-2"><?php echo nl2br(htmlspecialchars($m['content'])); ?></div><small class="text-muted d-block"><?php echo formatDateDutch($m['created_at']); ?></small></div>
                                    <?php else: ?>
                                        <div class="mb-2 msg-them"><div class="badge bg-light text-dark p-2"><?php echo nl2br(htmlspecialchars($m['content'])); ?></div><small class="text-muted d-block"><?php echo formatDateDutch($m['created_at']); ?></small></div>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        <?php else: ?>
                            <div class="text-muted">Selecteer links een gesprek of zoek een gebruiker via het profiel.</div>
                        <?php endif; ?>
                    </div>

                    <?php if($selected_user): ?>
                    <div class="card-footer">
                        <form method="POST">
                            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                            <input type="hidden" name="recipient_id" value="<?php echo $selected_user; ?>">
                            <div class="input-group">
                                <input type="text" name="content" class="form-control" placeholder="Stuur een bericht..." required>
                                <button class="btn btn-primary" type="submit">Verzenden</button>
                            </div>
                        </form>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
