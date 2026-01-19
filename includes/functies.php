<?php
session_start();

// Jouw databasegegevens
define('BASE_URL', 'http://localhost/social-media-site/');
define('DB_HOST', 'localhost');
define('DB_USER', 'BDTestUser1');
define('DB_PASS', 'User1WW#43');
define('DB_NAME', 'social_media');
define('DB_CHARSET', 'utf8mb4');;

// Beveiliging - foutmeldingen uitzetten in productie
ini_set('display_errors', 1);
error_reporting(E_ALL);

class Database {
    private $host = DB_HOST;
    private $user = DB_USER;
    private $pass = DB_PASS;
    private $dbname = DB_NAME;
    private $charset = DB_CHARSET;
    
    private $conn;
    private $error;
    
    public function __construct() {
        $this->connect();
    }
    
    private function connect() {
        $this->conn = new mysqli($this->host, $this->user, $this->pass, $this->dbname);
        
        if ($this->conn->connect_error) {
            $this->error = "Database connectie mislukt: " . $this->conn->connect_error;
            error_log($this->error);
            die("Database connectie mislukt. Controleer uw instellingen.");
        }
        
        $this->conn->set_charset($this->charset);
        $this->conn->query("SET sql_mode=''");
    }
    
    public function getConnection() {
        return $this->conn;
    }
    
    public function prepare($sql) {
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            error_log("Prepare fout: " . $this->conn->error . " SQL: " . $sql);
            die("Database query fout.");
        }
        return $stmt;
    }
    
    public function query($sql, $params = []) {
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            error_log("Query fout: " . $this->conn->error);
            return false;
        }
        if ($params) {
            $types = '';
            foreach ($params as $param) {
                if (is_int($param)) $types .= 'i';
                elseif (is_float($param)) $types .= 'd';
                else $types .= 's';
            }
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        return $stmt;
    }
    
    public function fetch($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        return $stmt ? $stmt->get_result()->fetch_assoc() : false;
    }
    
    public function fetchAll($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        return $stmt ? $stmt->get_result()->fetch_all(MYSQLI_ASSOC) : false;
    }
    
    public function insert($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        return $stmt ? $this->conn->insert_id : false;
    }
    
    public function execute($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        return $stmt ? $stmt->affected_rows : false;
    }
    
    public function lastInsertId() {
        return $this->conn->insert_id;
    }
    
    public function beginTransaction() {
        return $this->conn->begin_transaction();
    }
    
    public function commit() {
        return $this->conn->commit();
    }
    
    public function rollback() {
        return $this->conn->rollback();
    }
    
    public function getError() {
        return $this->error;
    }
}

// Helper functie voor database verbinding
function getDB() {
    static $db = null;
    if ($db === null) {
        $db = new Database();
    }
    return $db;
}

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
    $check_stmt->bind_param("ii", $user_id, $post_id);
    $check_stmt->execute();
    $check_stmt->store_result();

    if ($check_stmt->num_rows > 0) {
        // Unlike
        $stmt = $db->prepare("DELETE FROM likes WHERE user_id = ? AND post_id = ?");
        $stmt->bind_param("ii", $user_id, $post_id);
        $stmt->execute();
    } else {
        // Like
        $stmt = $db->prepare("INSERT INTO likes (user_id, post_id) VALUES (?, ?)");
        $stmt->bind_param("ii", $user_id, $post_id);
        $stmt->execute();
    }
}

function toggleFollow($follower_id, $following_id) {
    $db = getDB();
    
    if ($following_id == $follower_id) return;
    
    // Check if already following
    $check_stmt = $db->prepare("SELECT id FROM follows WHERE follower_id = ? AND following_id = ?");
    $check_stmt->bind_param("ii", $follower_id, $following_id);
    $check_stmt->execute();
    $result = $check_stmt->get_result();

    if ($result->num_rows > 0) {
        // Unfollow
        $delete_stmt = $db->prepare("DELETE FROM follows WHERE follower_id = ? AND following_id = ?");
        $delete_stmt->bind_param("ii", $follower_id, $following_id);
        $delete_stmt->execute();
    } else {
        // Check if private account
        $user_stmt = $db->prepare("SELECT is_private FROM users WHERE id = ?");
        $user_stmt->bind_param("i", $following_id);
        $user_stmt->execute();
        $user_data = $user_stmt->get_result()->fetch_assoc();

        if ($user_data['is_private']) {
            // Private account: Check if request already exists
            $req_check = $db->prepare("SELECT id FROM follow_requests WHERE requester_id = ? AND recipient_id = ? AND status = 'pending'");
            $req_check->bind_param("ii", $follower_id, $following_id);
            $req_check->execute();
            
            if ($req_check->get_result()->num_rows == 0) {
                // Send new request
                $req_stmt = $db->prepare("INSERT INTO follow_requests (requester_id, recipient_id, status) VALUES (?, ?, 'pending')");
                $req_stmt->bind_param("ii", $follower_id, $following_id);
                $req_stmt->execute();
            }
        } else {
            // Public account: Follow directly
            $insert_stmt = $db->prepare("INSERT INTO follows (follower_id, following_id) VALUES (?, ?)");
            $insert_stmt->bind_param("ii", $follower_id, $following_id);
            $insert_stmt->execute();
        }
    }
}

function deletePost($post_id, $user_id) {
    $db = getDB();
    
    // Check ownership
    $stmt = $db->prepare("SELECT user_id FROM posts WHERE id = ?");
    $stmt->bind_param("i", $post_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $post = $result->fetch_assoc();

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
        $stmt->bind_param("iis", $user_id, $post_id, $content);
        $stmt->execute();
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
        $stmt->bind_param("iss", $user_id, $content, $image_url);
        $stmt->execute();
        return true;
    }
    return false;
}

function updatePrivacy($user_id, $is_private) {
    $db = getDB();
    
    $stmt = $db->prepare("UPDATE users SET is_private = ? WHERE id = ?");
    $stmt->bind_param("ii", $is_private, $user_id);
    $stmt->execute();
}

function respondFollowRequest($request_id, $action, $recipient_id) {
    $db = getDB();
    
    $stmt = $db->prepare("SELECT requester_id, recipient_id FROM follow_requests WHERE id = ? AND recipient_id = ?");
    $stmt->bind_param("ii", $request_id, $recipient_id);
    $stmt->execute();
    $req = $stmt->get_result()->fetch_assoc();

    if ($req) {
        if ($action == 'accept') {
            $stmt = $db->prepare("INSERT IGNORE INTO follows (follower_id, following_id) VALUES (?, ?)");
            $stmt->bind_param("ii", $req['requester_id'], $req['recipient_id']);
            $stmt->execute();
        }
        // Delete request after action
        $stmt = $db->prepare("DELETE FROM follow_requests WHERE id = ?");
        $stmt->bind_param("i", $request_id);
        $stmt->execute();
    }
}

function removeFollower($follower_id, $my_id) {
    $db = getDB();
    
    $stmt = $db->prepare("DELETE FROM follows WHERE follower_id = ? AND following_id = ?");
    $stmt->bind_param("ii", $follower_id, $my_id);
    $stmt->execute();
}