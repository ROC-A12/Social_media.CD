<?php
session_start();

define('BASE_URL', 'http://localhost/Social_media.CD/');
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'social_media');
define('DB_CHARSET', 'utf8mb4');

ini_set('display_errors', 1);
error_reporting(E_ALL);

/**
 * Maakt een PDO database verbinding aan en geeft deze terug.
 */
function getDB() {
    static $pdo = null;

    if ($pdo === null) {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ];
            
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            error_log("Verbindingsfout: " . $e->getMessage());
            die("Er is een probleem met de database verbinding.");
        }
    }

    return $pdo;
}
?>