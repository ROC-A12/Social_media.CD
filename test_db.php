<?php
require_once 'includes/config.php';
require_once 'includes/database.php';

$db = new Database();

$result = $db->query('SELECT COUNT(*) as count FROM users');
$row = $result->get_result()->fetch_assoc();
echo "Users count: " . $row['count'];
?>