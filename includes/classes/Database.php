<?php
// File: includes/classes/Database.php
require_once __DIR__ . '/../../config.php';

class Database {
    private $host = DB_HOST;
    private $user = DB_USER;
    private $pass = DB_PASS;
    private $dbname = DB_NAME;
    
    private $connection;
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

    // Core Query Method
    public function query($sql) {
        $stmt = $this->db->pdo->prepare($query);
    }

    // Bind values
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

    // Execute the prepared statement
    public function execute() {
        if (!$this->stmt) return false; // Safety check
        return $this->stmt->execute();
    }

    // Add this alias so both names work!
    public function fetchAll() {
        return $this->resultSet();
    }
    // Inside Database.php

    public function resultSet() {
        if (!$this->stmt) {
            // This prevents the "on null" error if query() wasn't called
            return []; 
        }
        $this->execute();
        return $this->stmt->fetchAll();
    }

    public function single() {
        if (!$this->stmt) {
            return null;
        }
        $this->execute();
        return $this->stmt->fetch();
    }

    // Get row count
    public function rowCount() {
        return $this->stmt->rowCount();
    }

    // Get last inserted ID
    public function lastInsertId() {
        return $this->connection->lastInsertId();
    }

    // --- Helper Logic moved from User class to prevent circular errors ---
    public function emailExists($email) {
        $this->query("SELECT id FROM users WHERE email = :email");
        $this->bind(':email', $email);
        $this->execute();
        return $this->rowCount() > 0;
    }
}