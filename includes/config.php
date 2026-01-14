<?php
session_start();

// Jouw databasegegevens
define('BASE_URL', 'http://localhost/Social_media.CD/');
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'social_media');
define('DB_CHARSET', 'utf8mb4');

// Beveiliging - foutmeldingen uitzetten in productie
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Helper functie voor database verbinding
function getDB() {
    static $db = null;
    if ($db === null) {
        require_once __DIR__ . '/database.php';
        $db = new Database();
    }
    return $db;
}
?>

