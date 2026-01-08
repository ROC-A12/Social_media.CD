<?php
session_start();

// Jouw databasegegevens
define('BASE_URL', 'http://localhost/Social_media.CD/');
define('DB_HOST', 'localhost');
define('DB_USER', 'BDTestUser1');
define('DB_PASS', 'User1WW#43');
define('DB_NAME', 'social_media');
define('DB_CHARSET', 'utf8mb4');

// Beveiliging - foutmeldingen uitzetten in productie
ini_set('display_errors', 0);
error_reporting(0);

// Database connectie functie
function getDBConnection() {
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];
    
    try {
        $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        return $pdo;
    } catch (PDOException $e) {
        // Log de error maar toon geen gevoelige info aan gebruikers
        error_log("Database connectie fout: " . $e->getMessage());
        die("Er is een database fout opgetreden. Probeer het later opnieuw.");
    }
}

// CSRF Token beveiliging
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function validateCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}
?>