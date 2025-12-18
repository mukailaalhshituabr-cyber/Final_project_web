<?php
require_once __DIR__ . '/../classes/Database.php';


class User {
    private $db;
    
    public function __construct() {
        $this->db = new Database(); 
        $this->db = Database::getInstance();
    }
    
    // Register new user
    // C:\xampp\htdocs\webtech\clothing-marketplaces\includes\classes\User.php

    public function register($data) {

        // 1️⃣ Check if username exists (Lines 14-23 - already done)
        $this->db->query("SELECT id FROM users WHERE username = :username LIMIT 1");
        $this->db->bind(':username', $data['username']);
        $this->db->execute();

        if ($this->db->rowCount() > 0) {
            return 'Username already exists.'; // Return string error for register.php handling
        }

        // 2️⃣ ADDED FIX: Check if email exists
        $this->db->query("SELECT id FROM users WHERE email = :email LIMIT 1");
        $this->db->bind(':email', $data['email']);
        $this->db->execute();

        if ($this->db->rowCount() > 0) {
            return 'The email ' . $data['email'] . ' is already registered.';
        }
        
        // 3️⃣ Insert user (The original code continues from here)
        $this->db->query("
            INSERT INTO users 
            (username, email, password, user_type, full_name, phone, address, bio)
            VALUES 
            (:username, :email, :password, :user_type, :full_name, :phone, :address, :bio)
        ");
        // ... rest of the binds and execute (your User.php line 43)

        $this->db->bind(':username', $data['username']);
        $this->db->bind(':email', $data['email']);
        $this->db->bind(':password', password_hash($data['password'], PASSWORD_DEFAULT));
        $this->db->bind(':user_type', $data['user_type']);
        $this->db->bind(':full_name', $data['full_name']);
        $this->db->bind(':phone', $data['phone'] ?? '');
        $this->db->bind(':address', $data['address'] ?? '');
        $this->db->bind(':bio', $data['bio'] ?? '');

        if ($this->db->execute()) {
            return $this->db->lastInsertId();
        }

        return false;
    }

    
    
    // Login user
    public function login($email, $password) {
        $this->db->query("SELECT * FROM users WHERE email = :email");
        $this->db->bind(':email', $email);
        
        $user = $this->db->single();
        
        if ($user && password_verify($password, $user['password'])) {
            unset($user['password']);
            return $user;
        }
        
        return false;
    }
    
    // Get user by ID
    public function getUserById($id) {
        $this->db->query("SELECT id, username, email, user_type, full_name, profile_pic, address, phone, bio, created_at 
                         FROM users WHERE id = :id");
        $this->db->bind(':id', $id);
        return $this->db->single();
    }
    
    // Get user by email
    public function getUserByEmail($email) {
        $this->db->query("SELECT id, username, email, user_type, full_name, profile_pic FROM users WHERE email = :email");
        $this->db->bind(':email', $email);
        return $this->db->single();
    }
    
    // Update profile
    public function updateProfile($id, $data) {
        // 1. SAFETY CHECK: If $data isn't an array, the script will crash.
        // This prevents the "offset of type string on string" error.
        if (!is_array($data)) {
            return false; 
        }

        $query = "UPDATE users SET 
                    full_name = :full_name, 
                    email = :email, 
                    phone = :phone, 
                    address = :address, 
                    bio = :bio";
        
        // Only update profile_pic if a new one was uploaded
        if (!empty($data['profile_pic'])) {
            $query .= ", profile_pic = :profile_pic";
        }
        
        $query .= " WHERE id = :id";
        
        $this->db->query($query);
        
        // 2. BINDING: Ensure keys match your form input names
        $this->db->bind(':full_name', $data['full_name'] ?? '');
        $this->db->bind(':email', $data['email'] ?? '');
        $this->db->bind(':phone', $data['phone'] ?? '');
        $this->db->bind(':address', $data['address'] ?? '');
        $this->db->bind(':bio', $data['bio'] ?? '');
        $this->db->bind(':id', $id);
        
        if (!empty($data['profile_pic'])) {
            $this->db->bind(':profile_pic', $data['profile_pic']);
        }
        
        return $this->db->execute();
    }

    
    
    // Update password
    public function updatePassword($id, $hashedPassword) {
        $this->db->query("UPDATE users SET password = :password WHERE id = :id");
        $this->db->bind(':password', $hashedPassword);
        $this->db->bind(':id', $id);
        return $this->db->execute();
    }
    
    // Check if email exists
    public function emailExists($email) {
        return $this->db->emailExists($email);
    }
    
    // Check if username exists
    public function usernameExists($username) {
        return $this->db->usernameExists($username);
    }
    
    // Update last login
    public function updateLastLogin($id) {
        $this->db->query("UPDATE users SET updated_at = NOW() WHERE id = :id");
        $this->db->bind(':id', $id);
        return $this->db->execute();
    }
    
    // Get total user count
    public function getTotalCount() {
        $this->db->query("SELECT COUNT(*) as count FROM users");
        $result = $this->db->single();
        return $result['count'] ?? 0;
    }
    
    // Get count by user type
    public function getCountByType($userType) {
        $this->db->query("SELECT COUNT(*) as count FROM users WHERE user_type = :user_type");
        $this->db->bind(':user_type', $userType);
        $result = $this->db->single();
        return $result['count'] ?? 0;
    }
    
    // Get recent users
    public function getRecentUsers($limit = 5) {
        $this->db->query("SELECT id, username, email, user_type, full_name, profile_pic, created_at 
                         FROM users ORDER BY created_at DESC LIMIT :limit");
        $this->db->bind(':limit', $limit);
        return $this->db->resultSet();
    }
    
    // Get all users with pagination
    public function getAllUsers($page = 1, $perPage = 10) {
        $offset = ($page - 1) * $perPage;
        $this->db->query("SELECT id, username, email, user_type, full_name, profile_pic, created_at 
                         FROM users ORDER BY created_at DESC LIMIT :limit OFFSET :offset");
        $this->db->bind(':limit', $perPage);
        $this->db->bind(':offset', $offset);
        return $this->db->resultSet();
    }
    
    // Search users
    public function searchUsers($query) {
        $this->db->query("SELECT id, username, email, user_type, full_name, profile_pic 
                         FROM users WHERE username LIKE :query OR email LIKE :query OR full_name LIKE :query 
                         LIMIT 20");
        $this->db->bind(':query', "%$query%");
        return $this->db->resultSet();
    }
    
    // Update user status (admin function)
    public function updateStatus($id, $status) {
        $this->db->query("UPDATE users SET status = :status WHERE id = :id");
        $this->db->bind(':status', $status);
        $this->db->bind(':id', $id);
        return $this->db->execute();
    }
    
    // Delete user (admin function)
    public function delete($id) {
        $this->db->query("DELETE FROM users WHERE id = :id");
        $this->db->bind(':id', $id);
        return $this->db->execute();
    }
    
    // Save password reset token
    public function saveResetToken($email, $token, $expires) {
        $this->db->query("INSERT INTO password_resets (email, token, expires_at) 
                         VALUES (:email, :token, :expires_at) 
                         ON DUPLICATE KEY UPDATE token = :token, expires_at = :expires_at");
        
        $this->db->bind(':email', $email);
        $this->db->bind(':token', $token);
        $this->db->bind(':expires_at', $expires);
        
        return $this->db->execute();
    }
    
    // Get reset token
    public function getResetToken($token) {
        $this->db->query("SELECT * FROM password_resets WHERE token = :token");
        $this->db->bind(':token', $token);
        return $this->db->single();
    }
    
    // Reset password using token
    public function resetPassword($email, $hashedPassword) {
        $this->db->query("UPDATE users SET password = :password WHERE email = :email");
        $this->db->bind(':password', $hashedPassword);
        $this->db->bind(':email', $email);
        return $this->db->execute();
    }
    
    // Delete reset token
    public function deleteResetToken($token) {
        $this->db->query("DELETE FROM password_resets WHERE token = :token");
        $this->db->bind(':token', $token);
        return $this->db->execute();
    }
    
    // Get user statistics for dashboard
    public function getUserStats($userId) {
        $stats = [];
        
        // Get user type
        $user = $this->getUserById($userId);
        $stats['user_type'] = $user['user_type'];
        
        if ($user['user_type'] == 'tailor') {
            // Tailor-specific stats
            $this->db->query("SELECT COUNT(*) as product_count FROM products WHERE tailor_id = :id");
            $this->db->bind(':id', $userId);
            $result = $this->db->single();
            $stats['product_count'] = $result['product_count'];
            
            $this->db->query("SELECT COUNT(*) as order_count FROM orders WHERE tailor_id = :id");
            $this->db->bind(':id', $userId);
            $result = $this->db->single();
            $stats['order_count'] = $result['order_count'];
            
            $this->db->query("SELECT SUM(total_amount) as revenue FROM orders WHERE tailor_id = :id AND payment_status = 'paid'");
            $this->db->bind(':id', $userId);
            $result = $this->db->single();
            $stats['revenue'] = $result['revenue'] ?? 0;
        } elseif ($user['user_type'] == 'customer') {
            // Customer-specific stats
            $this->db->query("SELECT COUNT(*) as order_count FROM orders WHERE customer_id = :id");
            $this->db->bind(':id', $userId);
            $result = $this->db->single();
            $stats['order_count'] = $result['order_count'];
            
            $this->db->query("SELECT COUNT(*) as wishlist_count FROM wishlist WHERE user_id = :id");
            $this->db->bind(':id', $userId);
            $result = $this->db->single();
            $stats['wishlist_count'] = $result['wishlist_count'];
        }
        
        return $stats;
    }
    
    // Update user online status
    public function updateOnlineStatus($userId, $status = true) {
        $this->db->query("UPDATE users SET is_online = :status, last_seen = NOW() WHERE id = :id");
        $this->db->bind(':status', $status);
        $this->db->bind(':id', $userId);
        return $this->db->execute();
    }
    
    // Get online users
    public function getOnlineUsers($excludeId = null) {
        $query = "SELECT id, username, full_name, profile_pic FROM users 
                 WHERE is_online = 1 AND last_seen > DATE_SUB(NOW(), INTERVAL 5 MINUTE)";
        
        if ($excludeId) {
            $query .= " AND id != :exclude_id";
        }
        
        $this->db->query($query);
        
        if ($excludeId) {
            $this->db->bind(':exclude_id', $excludeId);
        }
        
        return $this->db->resultSet();
    }

    public function updateTailorProfile($id, $name, $email, $phone, $address, $bio, $pic) {
        $query = "UPDATE users SET 
                full_name = :name, 
                email = :email, 
                phone = :phone, 
                address = :address, 
                bio = :bio, 
                profile_pic = :pic 
                WHERE id = :id";
        
        // Notice the change to getConnection()
        $stmt = $this->db->getConnection()->prepare($query);
        
        return $stmt->execute([
            ':name' => $name,
            ':email' => $email,
            ':phone' => $phone,
            ':address' => $address,
            ':bio' => $bio,
            ':pic' => $pic,
            ':id' => $id
        ]);
    }


}
?>