<?php
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

    /**
     * Core Query Method
     * This prepares the SQL statement using the PDO connection
     */
    public function query($sql) {
        // FIXED: Use $this->connection and the correct parameter $sql
        $this->stmt = $this->connection->prepare($sql);
    }

    /**
     * Bind values to the prepared statement
     */
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

    /**
     * Execute the prepared statement
     */
    public function execute() {
        if (!$this->stmt) return false;
        return $this->stmt->execute();
    }

    /**
     * Get result set as array of objects
     */
    public function resultSet() {
        $this->execute();
        return $this->stmt->fetchAll();
    }

    /**
     * Alias for resultSet
     */
    public function fetchAll() {
        return $this->resultSet();
    }

    /**
     * Get single record as object
     */
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

    /**
     * Added for compatibility: Returns raw PDO connection
     */
    public function getConnection() {
        return $this->connection;
    }

    public function emailExists($email) {
        $this->query("SELECT id FROM users WHERE email = :email");
        $this->bind(':email', $email);
        $this->execute();
        return $this->rowCount() > 0;
    }
}