<?php
require_once 'includes/config.php';
require_once 'includes/database.php';
require_once 'includes/auth.php';

checkLogin();
$db = getDB();

$follower_id = $_SESSION['user_id'];
$following_id = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;

if ($following_id > 0 && $following_id != $follower_id) {
    // 1. Controleer of je deze persoon al volgt
    $check_stmt = $db->prepare("SELECT id FROM follows WHERE follower_id = ? AND following_id = ?");
    $check_stmt->bind_param("ii", $follower_id, $following_id);
    $check_stmt->execute();
    $result = $check_stmt->get_result();

    if ($result->num_rows > 0) {
        // JE VOLGT AL -> Dus nu ONTVOLGEN (Delete)
        $delete_stmt = $db->prepare("DELETE FROM follows WHERE follower_id = ? AND following_id = ?");
        $delete_stmt->bind_param("ii", $follower_id, $following_id);
        $delete_stmt->execute();
    } else {
        // JE VOLGT NOG NIET -> Eerst kijken of het account privé is
        $user_stmt = $db->prepare("SELECT is_private FROM users WHERE id = ?");
        $user_stmt->bind_param("i", $following_id);
        $user_stmt->execute();
        $user_data = $user_stmt->get_result()->fetch_assoc();

        if ($user_data['is_private']) {
            // Privé account: Check of er al een verzoek openstaat
            $req_check = $db->prepare("SELECT id FROM follow_requests WHERE requester_id = ? AND recipient_id = ? AND status = 'pending'");
            $req_check->bind_param("ii", $follower_id, $following_id);
            $req_check->execute();
            
            if ($req_check->get_result()->num_rows == 0) {
                // Stuur nieuw verzoek
                $req_stmt = $db->prepare("INSERT INTO follow_requests (requester_id, recipient_id, status) VALUES (?, ?, 'pending')");
                $req_stmt->bind_param("ii", $follower_id, $following_id);
                $req_stmt->execute();
            }
        } else {
            // Openbaar account: Direct volgen
            $insert_stmt = $db->prepare("INSERT INTO follows (follower_id, following_id) VALUES (?, ?)");
            $insert_stmt->bind_param("ii", $follower_id, $following_id);
            $insert_stmt->execute();
        }
    }
}

// Altijd terug naar het profiel waar je vandaan kwam
header("Location: profile.php?id=" . $following_id);
exit();