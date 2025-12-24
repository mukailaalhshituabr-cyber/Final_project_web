<?php
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
    
    // USER METHODS (matching your users table exactly)
    public function getUserById($id) {
        $this->query("SELECT * FROM users WHERE id = :id AND status = 'active'");
        $this->bind(':id', $id);
        return $this->single();
    }
    
    public function getUserByEmail($email) {
        $this->query("SELECT * FROM users WHERE email = :email AND status = 'active'");
        $this->bind(':email', $email);
        return $this->single();
    }
    
    public function getUserByUsername($username) {
        $this->query("SELECT * FROM users WHERE username = :username AND status = 'active'");
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
    
    // PRODUCT METHODS (matching your products table)
    public function getFeaturedProducts($limit = 8) {
        $this->query("
            SELECT p.*, u.full_name as tailor_name 
            FROM products p 
            JOIN users u ON p.tailor_id = u.id 
            WHERE p.featured = 1 
            AND p.status = 'active'
            AND u.status = 'active'
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
            AND u.status = 'active'
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
            AND p.status IN ('active', 'draft')
            AND u.status = 'active'
        ");
        $this->bind(':id', $id);
        return $this->single();
    }
    
    // CATEGORY METHODS (matching your categories table)
    public function getCategories() {
        $this->query("SELECT * FROM categories WHERE is_active = 1 ORDER BY display_order ASC, name ASC");
        return $this->resultSet();
    }
    
    public function getCategoryBySlug($slug) {
        $this->query("SELECT * FROM categories WHERE slug = :slug AND is_active = 1");
        $this->bind(':slug', $slug);
        return $this->single();
    }
    
    // ORDER METHODS (matching your orders table)
    public function getOrderById($id) {
        $this->query("
            SELECT o.*, 
                   c.full_name as customer_name, c.email as customer_email,
                   t.full_name as tailor_name, t.email as tailor_email
            FROM orders o
            LEFT JOIN users c ON o.customer_id = c.id
            LEFT JOIN users t ON o.tailor_id = t.id
            WHERE o.id = :id
        ");
        $this->bind(':id', $id);
        return $this->single();
    }
    
    public function getRecentOrders($limit = 10) {
        $this->query("
            SELECT o.*, 
                   c.full_name as customer_name,
                   t.full_name as tailor_name
            FROM orders o
            LEFT JOIN users c ON o.customer_id = c.id
            LEFT JOIN users t ON o.tailor_id = t.id
            ORDER BY o.created_at DESC 
            LIMIT :limit
        ");
        $this->bind(':limit', $limit, PDO::PARAM_INT);
        return $this->resultSet();
    }
    
    public function getOrdersByCustomer($customer_id, $limit = 20) {
        $this->query("
            SELECT o.*, t.full_name as tailor_name
            FROM orders o
            LEFT JOIN users t ON o.tailor_id = t.id
            WHERE o.customer_id = :customer_id
            ORDER BY o.created_at DESC
            LIMIT :limit
        ");
        $this->bind(':customer_id', $customer_id);
        $this->bind(':limit', $limit, PDO::PARAM_INT);
        return $this->resultSet();
    }
    
    public function getOrdersByTailor($tailor_id, $limit = 20) {
        $this->query("
            SELECT o.*, c.full_name as customer_name
            FROM orders o
            LEFT JOIN users c ON o.customer_id = c.id
            WHERE o.tailor_id = :tailor_id
            ORDER BY o.created_at DESC
            LIMIT :limit
        ");
        $this->bind(':tailor_id', $tailor_id);
        $this->bind(':limit', $limit, PDO::PARAM_INT);
        return $this->resultSet();
    }
    
    // CART METHODS (matching your cart table)
    public function getCartItems($user_id) {
        $this->query("
            SELECT c.*, p.title, p.price, p.images, p.stock_quantity,
                   u.full_name as tailor_name
            FROM cart c
            JOIN products p ON c.product_id = p.id
            JOIN users u ON p.tailor_id = u.id
            WHERE c.user_id = :user_id
            AND p.status = 'active'
            ORDER BY c.created_at DESC
        ");
        $this->bind(':user_id', $user_id);
        return $this->resultSet();
    }
    
    public function getCartCount($user_id) {
        $this->query("
            SELECT COUNT(*) as count 
            FROM cart c
            JOIN products p ON c.product_id = p.id
            WHERE c.user_id = :user_id 
            AND p.status = 'active'
        ");
        $this->bind(':user_id', $user_id);
        $result = $this->single();
        return $result['count'] ?? 0;
    }
    
    // WISHLIST METHODS (matching your wishlist table)
    public function getWishlistItems($user_id) {
        $this->query("
            SELECT w.*, p.title, p.price, p.images, p.status,
                   u.full_name as tailor_name
            FROM wishlist w
            JOIN products p ON w.product_id = p.id
            JOIN users u ON p.tailor_id = u.id
            WHERE w.user_id = :user_id
            ORDER BY w.created_at DESC
        ");
        $this->bind(':user_id', $user_id);
        return $this->resultSet();
    }
    
    public function getWishlistCount($user_id) {
        $this->query("SELECT COUNT(*) as count FROM wishlist WHERE user_id = :user_id");
        $this->bind(':user_id', $user_id);
        $result = $this->single();
        return $result['count'] ?? 0;
    }
    
    // REVIEW METHODS (matching your reviews table)
    public function getProductReviews($product_id, $status = 'approved') {
        $this->query("
            SELECT r.*, u.full_name as reviewer_name, u.profile_pic
            FROM reviews r
            JOIN users u ON r.user_id = u.id
            WHERE r.product_id = :product_id 
            AND r.status = :status
            ORDER BY r.created_at DESC
        ");
        $this->bind(':product_id', $product_id);
        $this->bind(':status', $status);
        return $this->resultSet();
    }
    
    // MESSAGE METHODS (matching your messages table)
    public function getUnreadMessageCount($user_id) {
        $this->query("SELECT COUNT(*) as count FROM messages WHERE receiver_id = :user_id AND is_read = 0");
        $this->bind(':user_id', $user_id);
        $result = $this->single();
        return $result['count'] ?? 0;
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
    
    // STATISTICS METHODS
    public function getTotalUsers() {
        $this->query("SELECT COUNT(*) as count FROM users WHERE status = 'active'");
        $result = $this->single();
        return $result['count'] ?? 0;
    }
    
    public function getTotalUsersByType($user_type) {
        $this->query("SELECT COUNT(*) as count FROM users WHERE user_type = :user_type AND status = 'active'");
        $this->bind(':user_type', $user_type);
        $result = $this->single();
        return $result['count'] ?? 0;
    }
    
    public function getTotalProducts() {
        $this->query("SELECT COUNT(*) as count FROM products WHERE status = 'active'");
        $result = $this->single();
        return $result['count'] ?? 0;
    }
    
    public function getTotalOrders() {
        $this->query("SELECT COUNT(*) as count FROM orders");
        $result = $this->single();
        return $result['count'] ?? 0;
    }
    
    public function getTotalRevenue() {
        $this->query("SELECT SUM(total_amount) as total FROM orders WHERE payment_status = 'paid'");
        $result = $this->single();
        return $result['total'] ?? 0;
        //done
    }
}
?>



<?php
/*// File: includes/classes/Database.php

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