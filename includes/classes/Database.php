
UPDATED includes/classes/Database.php (with your DB structure):
php
<?php
// File: includes/classes/Database.php

require_once __DIR__ . '/../../config.php';

class Database {
    private static $instance = null;
    private $connection;
    private $stmt;
    
    private function __construct() {
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
        
        $options = [
            PDO::ATTR_PERSISTENT => true,
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
        ];
        
        try {
            $this->connection = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            $this->handleException($e);
        }
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function handleException($e) {
        if (defined('DEBUG_MODE') && DEBUG_MODE) {
            die('Database Connection Error: ' . $e->getMessage());
        } else {
            error_log('Database Error: ' . $e->getMessage());
            die('A database error occurred. Please try again later.');
        }
    }
    
    // Query methods
    public function query($sql) {
        $this->stmt = $this->connection->prepare($sql);
        return $this;
    }
    
    public function bind($param, $value, $type = null) {
        if (is_null($type)) {
            switch (true) {
                case is_int($value): $type = PDO::PARAM_INT; break;
                case is_bool($value): $type = PDO::PARAM_BOOL; break;
                case is_null($value): $type = PDO::PARAM_NULL; break;
                default: $type = PDO::PARAM_STR;
            }
        }
        $this->stmt->bindValue($param, $value, $type);
        return $this;
    }
    
    public function execute() {
        try {
            return $this->stmt->execute();
        } catch (PDOException $e) {
            $this->handleException($e);
            return false;
        }
    }
    
    public function resultSet() {
        $this->execute();
        return $this->stmt->fetchAll();
    }
    
    public function fetchAll() {
        return $this->resultSet();
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
    
    public function getConnection() {
        return $this->connection;
    }
    
    // USER METHODS (for your users table)
    public function getUserById($id) {
        $this->query("SELECT * FROM users WHERE id = :id");
        $this->bind(':id', $id);
        return $this->single();
    }
    
    public function getUserByEmail($email) {
        $this->query("SELECT * FROM users WHERE email = :email");
        $this->bind(':email', $email);
        return $this->single();
    }
    
    public function getUserByUsername($username) {
        $this->query("SELECT * FROM users WHERE username = :username");
        $this->bind(':username', $username);
        return $this->single();
    }
    
    public function emailExists($email) {
        $this->query("SELECT id FROM users WHERE email = :email");
        $this->bind(':email', $email);
        $this->execute();
        return $this->rowCount() > 0;
    }
    
    public function usernameExists($username) {
        $this->query("SELECT id FROM users WHERE username = :username");
        $this->bind(':username', $username);
        $this->execute();
        return $this->rowCount() > 0;
    }
    
    // PRODUCT METHODS (for your products table)
    public function getFeaturedProducts($limit = 8) {
        $this->query("
            SELECT p.*, u.full_name as tailor_name 
            FROM products p 
            JOIN users u ON p.tailor_id = u.id 
            WHERE p.featured = 1 
            AND p.status = 'active'
            ORDER BY p.created_at DESC 
            LIMIT :limit
        ");
        $this->bind(':limit', $limit, PDO::PARAM_INT);
        return $this->resultSet();
    }
    
    public function getProductsByCategory($category, $limit = 12) {
        $this->query("
            SELECT p.*, u.full_name as tailor_name 
            FROM products p 
            JOIN users u ON p.tailor_id = u.id 
            WHERE p.category = :category 
            AND p.status = 'active'
            ORDER BY p.created_at DESC 
            LIMIT :limit
        ");
        $this->bind(':category', $category);
        $this->bind(':limit', $limit, PDO::PARAM_INT);
        return $this->resultSet();
    }
    
    public function getProductById($id) {
        $this->query("
            SELECT p.*, u.full_name as tailor_name, u.profile_pic as tailor_avatar 
            FROM products p 
            JOIN users u ON p.tailor_id = u.id 
            WHERE p.id = :id
        ");
        $this->bind(':id', $id);
        return $this->single();
    }
    
    // CATEGORY METHODS
    public function getCategories() {
        $this->query("SELECT * FROM categories WHERE is_active = 1 ORDER BY display_order");
        return $this->resultSet();
    }
    
    // TRANSACTION METHODS
    public function beginTransaction() {
        return $this->connection->beginTransaction();
    }
    
    public function commit() {
        return $this->connection->commit();
    }
    
    public function rollBack() {
        return $this->connection->rollBack();
    }
    
    // GENERIC METHODS
    public function insert($table, $data) {
        $columns = implode(', ', array_keys($data));
        $placeholders = ':' . implode(', :', array_keys($data));
        
        $sql = "INSERT INTO {$table} ({$columns}) VALUES ({$placeholders})";
        $this->query($sql);
        
        foreach ($data as $key => $value) {
            $this->bind(':' . $key, $value);
        }
        
        return $this->execute();
    }
    
    public function update($table, $data, $where) {
        $set = '';
        foreach ($data as $key => $value) {
            $set .= "{$key} = :{$key}, ";
        }
        $set = rtrim($set, ', ');
        
        $whereClause = '';
        $whereParams = [];
        foreach ($where as $key => $value) {
            $whereClause .= "{$key} = :where_{$key} AND ";
            $whereParams['where_' . $key] = $value;
        }
        $whereClause = rtrim($whereClause, ' AND ');
        
        $sql = "UPDATE {$table} SET {$set} WHERE {$whereClause}";
        $this->query($sql);
        
        // Bind data values
        foreach ($data as $key => $value) {
            $this->bind(':' . $key, $value);
        }
        
        // Bind where values
        foreach ($whereParams as $key => $value) {
            $this->bind(':' . $key, $value);
        }
        
        return $this->execute();
    }
    
    public function delete($table, $where) {
        $whereClause = '';
        foreach ($where as $key => $value) {
            $whereClause .= "{$key} = :{$key} AND ";
        }
        $whereClause = rtrim($whereClause, ' AND ');
        
        $sql = "DELETE FROM {$table} WHERE {$whereClause}";
        $this->query($sql);
        
        foreach ($where as $key => $value) {
            $this->bind(':' . $key, $value);
        }
        
        return $this->execute();
    }
}
?>






<?php
/*
// File: includes/classes/Database.php
require_once __DIR__ . '/../../config.php';

class Database {
    private $host = DB_HOST;
    private $user = DB_USER;
    private $pass = DB_PASS;
    private $dbname = DB_NAME;
    
    private $connection; // This is your PDO object
    private $stmt;
    private static $instance = null;

    public function __construct() {
        $dsn = 'mysql:host=' . $this->host . ';dbname=' . $this->dbname . ';charset=utf8mb4';
        $options = [
            PDO::ATTR_PERSISTENT => true,
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ];

        try {
            $this->connection = new PDO($dsn, $this->user, $this->pass, $options);
        } catch(PDOException $e) {
            die('Database connection failed: ' . $e->getMessage());
        }
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new Database();
        }
        return self::$instance;
    }

    public function query($sql) {
        // FIXED: Use $this->connection and the correct parameter $sql
        $this->stmt = $this->connection->prepare($sql);
    }

    public function bind($param, $value, $type = null) {
        if (is_null($type)) {
            switch (true) {
                case is_int($value): $type = PDO::PARAM_INT; break;
                case is_bool($value): $type = PDO::PARAM_BOOL; break;
                case is_null($value): $type = PDO::PARAM_NULL; break;
                default: $type = PDO::PARAM_STR;
            }
        }
        $this->stmt->bindValue($param, $value, $type);
    }

    public function execute() {
        if (!$this->stmt) return false;
        return $this->stmt->execute();
    }

    public function resultSet() {
        $this->execute();
        return $this->stmt->fetchAll();
    }

    public function fetchAll() {
        return $this->resultSet();
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

    
    public function getConnection() {
        return $this->connection;
    }

    public function emailExists($email) {
        $this->query("SELECT id FROM users WHERE email = :email");
        $this->bind(':email', $email);
        $this->execute();
        return $this->rowCount() > 0;
    }
}*/