<?php
require_once 'includes/functies.php';

// Zorg dat er een sessie-user_id is voor testing
if (session_status() === PHP_SESSION_NONE) session_start();
$_SESSION['user_id'] = $_SESSION['user_id'] ?? 1;

try {
    $convs = getConversations($_SESSION['user_id']);
    echo "Conversations count: " . count($convs) . "\n";
    foreach ($convs as $c) {
        echo "Partner: " . ($c['user']['username'] ?? $c['user']['id']) . " Unread: " . $c['unread'] . " Last: " . ($c['last']['content'] ?? '') . "\n";
    }
    if (isset($convs[0])) {
        $pid = $convs[0]['user']['id'];
        $msgs = getMessagesBetween($_SESSION['user_id'], $pid);
        echo "Messages with {$pid}: " . count($msgs) . "\n";
    }
} catch (Exception $e) {
    echo "Exception: " . $e->getMessage() . "\n";
}

?>
