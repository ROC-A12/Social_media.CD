<?php
include_once 'config.php';


// ini_set('display_errors', 1);
// error_reporting(E_ALL);

/**
 * Controleer login
 * - Redirect naar `login.php` als er geen actieve gebruiker is
 */
function checkLogin() {
    // Zorg dat er een geldige sessie is; anders doorsturen naar inloggen
    if(!isset($_SESSION['user_id'])) {
        header("Location: login.php");
        exit();
    }
}

/**
 * Sanitize input
 * - Verwijdert onnodige whitespace en escapt speciale tekens
 */
function sanitizeInput($input) {
    $input = trim($input);
    $input = stripslashes($input);
    $input = htmlspecialchars($input);
    return $input;
}

// Validatie- en helperfuncties hierboven: gebruik deze voor alle gebruikersinvoer

/**
 * E-mail validatie
 * - Retourneert gefilterde waarde of false
 */
function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

/**
 * Valideer afbeelding
 * - Controleert MIME-type en maximale bestandsgrootte
 */
function validateImage($file) {
    $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
    $max_size = 5 * 1024 * 1024; // 5MB
    
    if(!in_array($file['type'], $allowed_types)) {
        return false;
    }
    
    if($file['size'] > $max_size) {
        return false;
    }
    
    return true;
}

/**
 * Genereer CSRF-token
 * - Bewaart token in sessie en retourneert deze
 */
function generateCSRFToken() {
    if(!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verifieer CSRF-token
 * - Beveiliging tegen CSRF door vergelijking met sessie-token
 */
function verifyCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Formatteer datum in Nederlands
 * - `short=true` geeft korte notatie (bv. "21 jan 14:26")
 * - `short=false` geeft lange notatie (bv. "21 januari 2026, 14:26")
 */
function formatDateDutch($datetime, $short = true) {
    if (empty($datetime)) return '';
    try {
        $dt = new DateTime($datetime);
    } catch (Exception $e) {
        return $datetime;
    }

    if (class_exists('IntlDateFormatter')) {
        if ($short) {
            // korte weergave: 21 jan 14:26
            $fmt = new IntlDateFormatter('nl_NL', IntlDateFormatter::MEDIUM, IntlDateFormatter::SHORT, 'Europe/Amsterdam', IntlDateFormatter::GREGORIAN);
            $fmt->setPattern('d MMM HH:mm');
            $res = $fmt->format($dt);
            // Verwijder ongewenste punten uit afkortingen (bv. "jan.")
            $res = str_replace('.', '', $res);
            return $res;
        } else {
            // lange weergave: 21 januari 2026, 14:26
            $fmt = new IntlDateFormatter('nl_NL', IntlDateFormatter::LONG, IntlDateFormatter::SHORT, 'Europe/Amsterdam', IntlDateFormatter::GREGORIAN);
            $fmt->setPattern('d MMMM yyyy, HH:mm');
            return $fmt->format($dt);
        }
    } else {
        // Fallback zonder strftime() (deprecated): bouw Nederlandse maandnamen handmatig
        if ($short) {
            $months_short = [1 => 'jan', 'feb', 'mrt', 'apr', 'mei', 'jun', 'jul', 'aug', 'sep', 'okt', 'nov', 'dec'];
            $day = $dt->format('j');
            $month = (int)$dt->format('n');
            $time = $dt->format('H:i');
            return sprintf('%d %s %s', $day, $months_short[$month], $time);
        } else {
            $months = [1 => 'januari', 'februari', 'maart', 'april', 'mei', 'juni', 'juli', 'augustus', 'september', 'oktober', 'november', 'december'];
            $day = $dt->format('j');
            $month = (int)$dt->format('n');
            $year = $dt->format('Y');
            $time = $dt->format('H:i');
            return sprintf('%d %s %s, %s', $day, $months[$month], $year, $time);
        }
    }
}

/**
 * Toggle like
 * - Voegt een like toe of verwijdert deze als al geliked
 */
function toggleLike($post_id, $user_id) {
    $db = getDB();
    
    // Controleer of de gebruiker het bericht al geliked heeft
    $check_stmt = $db->prepare("SELECT id FROM likes WHERE user_id = ? AND post_id = ?");
    $check_stmt->execute([$user_id, $post_id]);

    if ($check_stmt->rowCount() > 0) {
        // Verwijder like
        $stmt = $db->prepare("DELETE FROM likes WHERE user_id = ? AND post_id = ?");
        $stmt->execute([$user_id, $post_id]);
    } else {
        // Liken
        $stmt = $db->prepare("INSERT INTO likes (user_id, post_id) VALUES (?, ?)");
        $stmt->execute([$user_id, $post_id]);
    }
}

/**
 * Toggle follow
 * - Volg of ontvolg een gebruiker
 * - Bij privé-accounts wordt een follow_request aangemaakt
 */
function toggleFollow($follower_id, $following_id) {
    $db = getDB();
    
    if ($following_id == $follower_id) return;
    
    // Controleer of de relatie al bestaat (volgen/ontvolgen)
    $check_stmt = $db->prepare("SELECT id FROM follows WHERE follower_id = ? AND following_id = ?");
    $check_stmt->execute([$follower_id, $following_id]);

    if ($check_stmt->rowCount() > 0) {
        // Ontvolgen
        $delete_stmt = $db->prepare("DELETE FROM follows WHERE follower_id = ? AND following_id = ?");
        $delete_stmt->execute([$follower_id, $following_id]);
    } else {
        // Controleer of de doelgebruiker een privé-account heeft
        $user_stmt = $db->prepare("SELECT is_private FROM users WHERE id = ?");
        $user_stmt->execute([$following_id]);
        $user_data = $user_stmt->fetch();

        if ($user_data['is_private']) {
            // Privé-account: controleer of verzoek al bestaat
            $req_check = $db->prepare("SELECT id FROM follow_requests WHERE requester_id = ? AND recipient_id = ? AND status = 'pending'");
            $req_check->execute([$follower_id, $following_id]);
            
            if ($req_check->rowCount() == 0) {
                // Verstuur nieuw follow-verzoek (status = pending)
                $req_stmt = $db->prepare("INSERT INTO follow_requests (requester_id, recipient_id, status) VALUES (?, ?, ?)");
                $req_stmt->execute([$follower_id, $following_id, 'pending']);
            }
        } else {
            // Openbaar account: direct volgen
            $insert_stmt = $db->prepare("INSERT INTO follows (follower_id, following_id) VALUES (?, ?)");
            $insert_stmt->execute([$follower_id, $following_id]);
        }
    }
}

/**
 * Verwijder post
 * - Controleert eigendom en verwijdert post + gerelateerde likes/reacties
 */
function deletePost($post_id, $user_id) {
    $db = getDB();
    
    // Controleer of de ingelogde gebruiker eigenaar is van de post
    $stmt = $db->prepare("SELECT user_id FROM posts WHERE id = ?");
    $stmt->execute([$post_id]);
    $post = $stmt->fetch();

    if (!$post || $post['user_id'] != $user_id) {
        return false;
    }

    // Verwijder alle gekoppelde likes en reacties bij deze post
    $db->query("DELETE FROM likes WHERE post_id = ? OR post_id IN (SELECT id FROM posts WHERE posts_id = ?)", [$post_id, $post_id]);
    $db->query("DELETE FROM posts WHERE id = ? OR posts_id = ?", [$post_id, $post_id]);
    
    return true;
}

/**
 * Voeg reactie toe
 * - Slaat reactie op als `posts` record met `posts_id` verwijzing
 */
function addComment($post_id, $user_id, $content) {
    $db = getDB();
    
    if (!empty($content)) {
        // Voeg reply toe als een post met posts_id verwijzing
        $stmt = $db->prepare("INSERT INTO posts (user_id, posts_id, content) VALUES (?, ?, ?)");
        $stmt->execute([$user_id, $post_id, $content]);
        return true;
    }
    return false;
}

/**
 * Maak nieuwe post
 * - Ondersteunt optionele afbeelding upload
 */
function createPost($user_id, $content, $image_file = null) {
    $db = getDB();
    
    $image_url = null;
    if ($image_file && !empty($image_file['name'])) {
        $target_dir = "assets/uploads/posts/";
        
        if (!is_dir($target_dir)) {
            mkdir($target_dir, 0777, true);
        }
        
        $filename = time() . '_' . basename($image_file["name"]);
        $target_file = $target_dir . $filename;
        
        if (move_uploaded_file($image_file["tmp_name"], $target_file)) {
            $image_url = $filename;
        } else {
            error_log("Failed to upload file: " . $image_file["error"]);
        }
    }

    if (!empty($content) || $image_url) {
        $stmt = $db->prepare("INSERT INTO posts (user_id, content, image) VALUES (?, ?, ?)");
        $stmt->execute([$user_id, $content, $image_url]);
        return true;
    }
    return false;
}

/**
 * Update privacy-instelling
 * - Zet profiel op privé (1) of openbaar (0)
 */
function updatePrivacy($user_id, $is_private) {
    $db = getDB();
    
    $stmt = $db->prepare("UPDATE users SET is_private = ? WHERE id = ?");
    $stmt->execute([$is_private, $user_id]);
}

/**
 * Behandel follow-verzoek
 * - Accepteer of weiger verzoek en werk data bij
 */
function respondFollowRequest($request_id, $action, $recipient_id) {
    $db = getDB();
    
    $stmt = $db->prepare("SELECT requester_id, recipient_id FROM follow_requests WHERE id = ? AND recipient_id = ?");
    $stmt->execute([$request_id, $recipient_id]);
    $req = $stmt->fetch();

    if ($req) {
        if ($action == 'accept') {
            $stmt = $db->prepare("INSERT IGNORE INTO follows (follower_id, following_id) VALUES (?, ?)");
            $stmt->execute([$req['requester_id'], $req['recipient_id']]);
        }
        // Verwijder verzoek na actie
        $stmt = $db->prepare("DELETE FROM follow_requests WHERE id = ?");
        $stmt->execute([$request_id]);
    }
}

/**
 * Verwijder volger
 * - Verwijdert een follower record voor deze gebruiker
 */
function removeFollower($follower_id, $my_id) {
    $db = getDB();
    
    $stmt = $db->prepare("DELETE FROM follows WHERE follower_id = ? AND following_id = ?");
    $stmt->execute([$follower_id, $my_id]);
}

// --- Functies voor directe berichten ---
/**
 * Verstuur bericht
 * - Slaat een nieuw chatbericht op tussen twee gebruikers
 */
function sendMessage($from_id, $to_id, $content) {
    $db = getDB();
    $stmt = $db->prepare("INSERT INTO messages (sender_id, recipient_id, content) VALUES (?, ?, ?)");
    $stmt->execute([$from_id, $to_id, $content]);
}

/**
 * Haal gesprekken op
 * - Geeft een lijst met gesprekspartners, laatste bericht en ongelezen aantal
 */
function getConversations($user_id) {
    $db = getDB();
    $partners_stmt = $db->prepare("SELECT DISTINCT IF(sender_id = ?, recipient_id, sender_id) AS partner_id FROM messages WHERE sender_id = ? OR recipient_id = ?");
    $partners_stmt->execute([$user_id, $user_id, $user_id]);
    $partners = $partners_stmt->fetchAll();

    $result = [];
    foreach ($partners as $p) {
        $pid = $p['partner_id'];

        $last_stmt = $db->prepare("SELECT * FROM messages WHERE (sender_id = ? AND recipient_id = ?) OR (sender_id = ? AND recipient_id = ?) ORDER BY created_at DESC LIMIT 1");
        $last_stmt->execute([$user_id, $pid, $pid, $user_id]);
        $last = $last_stmt->fetch();

        $unread_stmt = $db->prepare("SELECT COUNT(*) as c FROM messages WHERE sender_id = ? AND recipient_id = ? AND is_read = 0");
        $unread_stmt->execute([$pid, $user_id]);
        $unread = $unread_stmt->fetch()['c'];

        $user_stmt = $db->prepare("SELECT id, username, profile_picture FROM users WHERE id = ?");
        $user_stmt->execute([$pid]);
        $user = $user_stmt->fetch();

        $result[] = ['user' => $user, 'last' => $last, 'unread' => $unread];
    }

    // Sorteer op laatste bericht tijd aflopend
    usort($result, function($a, $b) {
        $ta = isset($a['last']['created_at']) ? strtotime($a['last']['created_at']) : 0;
        $tb = isset($b['last']['created_at']) ? strtotime($b['last']['created_at']) : 0;
        return $tb <=> $ta;
    });

    return $result;
}

/**
 * Haal berichten tussen twee gebruikers
 * - Retourneert alle berichten chronologisch
 */
function getMessagesBetween($user1, $user2) {
    $db = getDB();
    $stmt = $db->prepare("SELECT m.*, u.username, u.profile_picture FROM messages m JOIN users u ON u.id = m.sender_id WHERE (m.sender_id = ? AND m.recipient_id = ?) OR (m.sender_id = ? AND m.recipient_id = ?) ORDER BY m.created_at ASC");
    $stmt->execute([$user1, $user2, $user2, $user1]);
    return $stmt->fetchAll();
}

/**
 * Markeer berichten als gelezen
 * - Zet `is_read` voor berichten van `from_id` naar `to_id`
 */
function markMessagesRead($from_id, $to_id) {
    $db = getDB();
    $stmt = $db->prepare("UPDATE messages SET is_read = 1 WHERE sender_id = ? AND recipient_id = ?");
    $stmt->execute([$from_id, $to_id]);
}