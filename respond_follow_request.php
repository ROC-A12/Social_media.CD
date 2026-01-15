<?php
require_once 'includes/config.php';
require_once 'includes/database.php';
require_once 'includes/auth.php';

checkLogin();
$db = getDB();
$request_id = (int)$_GET['request_id'];
$action = $_GET['action'];

// Zoek het verzoek op
$stmt = $db->prepare("SELECT requester_id, recipient_id FROM follow_requests WHERE id = ? AND recipient_id = ?");
$stmt->bind_param("ii", $request_id, $_SESSION['user_id']);
$stmt->execute();
$req = $stmt->get_result()->fetch_assoc();

if ($req) {
    if ($action == 'accept') {
        $stmt = $db->prepare("INSERT IGNORE INTO follows (follower_id, following_id) VALUES (?, ?)");
        $stmt->bind_param("ii", $req['requester_id'], $req['recipient_id']);
        $stmt->execute();
    }
    // Verwijder request na actie
    $stmt = $db->prepare("DELETE FROM follow_requests WHERE id = ?");
    $stmt->bind_param("i", $request_id);
    $stmt->execute();
}

header("Location: profile.php?id=" . $_SESSION['user_id']);