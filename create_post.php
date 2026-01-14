<?php
require_once 'includes/config.php';
require_once 'includes/database.php';
require_once 'includes/auth.php';

checkLogin();

$db = getDB();
$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $content = trim($_POST['content']);
    $image_url = null;

    if (!empty($_FILES['image']['name'])) {
        $target_dir = "assets/uploads/posts/";
        
        // Zorg dat de directory bestaat
        if (!is_dir($target_dir)) {
            mkdir($target_dir, 0777, true);
        }
        
        $filename = time() . '_' . basename($_FILES["image"]["name"]);
        $target_file = $target_dir . $filename;
        
        if (move_uploaded_file($_FILES["image"]["tmp_name"], $target_file)) {
            $image_url = $filename;
        } else {
            error_log("Failed to upload file: " . $_FILES["image"]["error"]);
        }
    }

    if (!empty($content) || $image_url) {
        $stmt = $db->prepare("INSERT INTO posts (user_id, content, image) VALUES (?, ?, ?)");
        $stmt->bind_param("iss", $user_id, $content, $image_url);
        $stmt->execute();
    }

    header("Location: index.php");
    exit();
}
?>