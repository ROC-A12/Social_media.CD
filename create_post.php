<?php
require_once 'includes/config.php';
require_once 'includes/database.php';
require_once 'includes/auth.php';

checkLogin();

$db = new Database();
$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $content = trim($_POST['content']);
    $image_url = null;

    if (!empty($_FILES['image']['name'])) {
        $target_dir = "assets/uploads/posts/";
        $target_file = $target_dir . basename($_FILES["image"]["name"]);
        move_uploaded_file($_FILES["image"]["tmp_name"], $target_file);
        $image_url = basename($_FILES["image"]["name"]);
    }

    if (!empty($content) || $image_url) {
        $stmt = $db->prepare("INSERT INTO posts (user_id, content, image_url) VALUES (?, ?, ?)");
        $stmt->bind_param("iss", $user_id, $content, $image_url);
        $stmt->execute();
    }

    header("Location: index.php");
    exit();
}
?>