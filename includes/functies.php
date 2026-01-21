<?php
include_once 'config.php';



// Beveiliging - foutmeldingen uitzetten in productie
ini_set('display_errors', 1);
error_reporting(E_ALL);

function checkLogin() {
    if(!isset($_SESSION['user_id'])) {
        header("Location: login.php");
        exit();
    }
}

function isAdmin() {
    return isset($_SESSION['is_admin']) && $_SESSION['is_admin'];
}

function sanitizeInput($input) {
    $input = trim($input);
    $input = stripslashes($input);
    $input = htmlspecialchars($input);
    return $input;
}

function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

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

function generateCSRFToken() {
    if(!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verifyCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

function toggleLike($post_id, $user_id) {
    $db = getDB();
    
    // Check if already liked
    $check_stmt = $db->prepare("SELECT id FROM likes WHERE user_id = ? AND post_id = ?");
    $check_stmt->execute([$user_id, $post_id]);

    if ($check_stmt->rowCount() > 0) {
        // Unlike
        $stmt = $db->prepare("DELETE FROM likes WHERE user_id = ? AND post_id = ?");
        $stmt->execute([$user_id, $post_id]);
    } else {
        // Like
        $stmt = $db->prepare("INSERT INTO likes (user_id, post_id) VALUES (?, ?)");
        $stmt->execute([$user_id, $post_id]);
    }
}

function toggleFollow($follower_id, $following_id) {
    $db = getDB();
    
    if ($following_id == $follower_id) return;
    
    // Check if already following
    $check_stmt = $db->prepare("SELECT id FROM follows WHERE follower_id = ? AND following_id = ?");
    $check_stmt->execute([$follower_id, $following_id]);

    if ($check_stmt->rowCount() > 0) {
        // Unfollow
        $delete_stmt = $db->prepare("DELETE FROM follows WHERE follower_id = ? AND following_id = ?");
        $delete_stmt->execute([$follower_id, $following_id]);
    } else {
        // Check if private account
        $user_stmt = $db->prepare("SELECT is_private FROM users WHERE id = ?");
        $user_stmt->execute([$following_id]);
        $user_data = $user_stmt->fetch();

        if ($user_data['is_private']) {
            // Private account: Check if request already exists
            $req_check = $db->prepare("SELECT id FROM follow_requests WHERE requester_id = ? AND recipient_id = ? AND status = 'pending'");
            $req_check->execute([$follower_id, $following_id]);
            
            if ($req_check->rowCount() == 0) {
                // Send new request
                $req_stmt = $db->prepare("INSERT INTO follow_requests (requester_id, recipient_id, status) VALUES (?, ?, 'pending')");
                $req_stmt->execute([$follower_id, $following_id, 'pending']);
            }
        } else {
            // Public account: Follow directly
            $insert_stmt = $db->prepare("INSERT INTO follows (follower_id, following_id) VALUES (?, ?)");
            $insert_stmt->execute([$follower_id, $following_id]);
        }
    }
}

function deletePost($post_id, $user_id) {
    $db = getDB();
    
    // Check ownership
    $stmt = $db->prepare("SELECT user_id FROM posts WHERE id = ?");
    $stmt->execute([$post_id]);
    $post = $stmt->fetch();

    if (!$post || $post['user_id'] != $user_id) {
        return false;
    }

    // Delete likes and comments
    $db->query("DELETE FROM likes WHERE post_id = ? OR post_id IN (SELECT id FROM posts WHERE posts_id = ?)", [$post_id, $post_id]);
    $db->query("DELETE FROM posts WHERE id = ? OR posts_id = ?", [$post_id, $post_id]);
    
    return true;
}

function addComment($post_id, $user_id, $content) {
    $db = getDB();
    
    if (!empty($content)) {
        $stmt = $db->prepare("INSERT INTO posts (user_id, posts_id, content) VALUES (?, ?, ?)");
        $stmt->execute([$user_id, $post_id, $content]);
        return true;
    }
    return false;
}

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

function updatePrivacy($user_id, $is_private) {
    $db = getDB();
    
    $stmt = $db->prepare("UPDATE users SET is_private = ? WHERE id = ?");
    $stmt->execute([$is_private, $user_id]);
}

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
        // Delete request after action
        $stmt = $db->prepare("DELETE FROM follow_requests WHERE id = ?");
        $stmt->execute([$request_id]);
    }
}

function removeFollower($follower_id, $my_id) {
    $db = getDB();
    
    $stmt = $db->prepare("DELETE FROM follows WHERE follower_id = ? AND following_id = ?");
    $stmt->execute([$follower_id, $my_id]);
}