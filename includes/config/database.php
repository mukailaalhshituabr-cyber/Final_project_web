<?php
/*
// ============================================
// DATABASE CLASS - COMPLETE VERSION
// ============================================

// File: includes/classes/Database.php
require_once __DIR__ . '/../../config.php';
class Database {
    private $host = DB_HOST;
    private $user = DB_USER;
    private $pass = DB_PASS;
    private $dbname = DB_NAME;
    
    private $connection;
    private $stmt;
    
    public function __construct() {
        $dsn = "mysql:host={$this->host};dbname={$this->dbname};charset=utf8mb4";
        
        $options = [
            PDO::ATTR_PERSISTENT => true,
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ];
        
        try {
            $this->connection = new PDO($dsn, $this->user, $this->pass, $options);
        } catch(PDOException $e) {
            die("Connection failed: " . $e->getMessage());
        }
    }
    
    public function query($sql) {
        $this->stmt = $this->connection->prepare($sql);
    }
    
    public function bind($param, $value, $type = null) {
        if (is_null($type)) {
            switch (true) {
                case is_int($value):
                    $type = PDO::PARAM_INT;
                    break;
                case is_bool($value):
                    $type = PDO::PARAM_BOOL;
                    break;
                case is_null($value):
                    $type = PDO::PARAM_NULL;
                    break;
                default:
                    $type = PDO::PARAM_STR;
            }
        }
        $this->stmt->bindValue($param, $value, $type);
    }
    
    public function execute() {
        try {
            return $this->stmt->execute();
        } catch (PDOException $e) {
            // Re-throw with cleaner message
            throw new Exception("Database error: " . $e->getMessage());
        }
    }
    

    public static function fetch($sql, $params = []) {
        $db = self::getInstance();
        $db->query($sql);
        
        foreach ($params as $key => $value) {
            // Handle both 0-indexed arrays and associative arrays
            $p = is_int($key) ? $key + 1 : $key;
            $db->bind($p, $value);
        }
        
        $db->execute();
        return $db->stmt->fetch();
    }

    
    public static function fetchAll($sql, $params = []) {
        $db = self::getInstance();
        $db->query($sql);
        
        foreach ($params as $key => $value) {
            $p = is_int($key) ? $key + 1 : $key;
            $db->bind($p, $value);
        }
        
        $db->execute();
        return $db->stmt->fetchAll();
    }

    public function resultSet() {
        $this->execute();
        return $this->stmt->fetchAll();
    }
    
    public function single() {
        $this->execute();
        return $this->stmt->fetch();
    }
    
    public function rowCount() {
        return $this->stmt->rowCount();
    }
    
    public function lastInsertId() {
        return $this->connection->lastInsertId();
    }
}
?>*/