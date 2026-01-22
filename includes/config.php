<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

    define('BASE_URL', 'http://localhost/social-media-site/');
    define('DB_HOST', 'localhost');
    define('DB_USER', 'BDTestUser1');
    define('DB_PASS', 'User1WW#43');
    define('DB_NAME', 'social_media');
    define('DB_CHARSET', 'utf8mb4');

ini_set('display_errors', 1);
error_reporting(E_ALL);

class Database {
    private $pdo;
    private $error;
    
    public function __construct() {
        $this->connect();
    }
    
    private function connect() {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ];
            
            $this->pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            $this->error = "Database connectie mislukt: " . $e->getMessage();
            error_log($this->error);
            die("Database connectie mislukt. Controleer uw instellingen.");
        }
    }
    
    public function getConnection() {
        return $this->pdo;
    }
    
    public function prepare($sql) {
        try {
            return $this->pdo->prepare($sql);
        } catch (PDOException $e) {
            $msg = "Prepare fout: " . $e->getMessage() . " SQL: " . $sql;
            error_log($msg);
            // Tijdelijk gedetailleerde fout tonen voor debugging
            die("Database query fout: " . htmlspecialchars($e->getMessage()));
        }
    }
    
    public function query($sql, $params = []) {
        $stmt = $this->prepare($sql);
        if ($params) {
            $stmt->execute($params);
        } else {
            $stmt->execute();
        }
        return $stmt;
    }
    
    public function fetch($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        return $stmt->fetch();
    }
    
    public function fetchAll($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        return $stmt->fetchAll();
    }
    
    public function insert($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        return $this->pdo->lastInsertId();
    }
    
    public function execute($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        return $stmt->rowCount();
    }
    
    public function lastInsertId() {
        return $this->pdo->lastInsertId();
    }
    
    public function beginTransaction() {
        return $this->pdo->beginTransaction();
    }
    
    public function commit() {
        return $this->pdo->commit();
    }
    
    public function rollback() {
        return $this->pdo->rollback();
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

?>