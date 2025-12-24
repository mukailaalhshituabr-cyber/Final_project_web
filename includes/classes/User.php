<?php
require_once __DIR__ . '/Database.php';

class User {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    // Register new user (matching your users table exactly)
    public function register($data) {
        try {
            $this->db->beginTransaction();
            
            // Check if email already exists
            if ($this->emailExists($data['email'])) {
                return "Email already registered";
            }
            
            // Check if username already exists
            if ($this->usernameExists($data['username'])) {
                return "Username already taken";
            }
            
            // Validate user_type against your enum
            $allowedTypes = ['customer', 'tailor', 'admin'];
            if (!in_array($data['user_type'], $allowedTypes)) {
                return "Invalid user type";
            }
            
            // Insert user - matching your users table structure exactly
            $sql = "INSERT INTO users (
                username, email, password, user_type, full_name, 
                phone, address, bio, experience, specialization, 
                status, email_verified
            ) VALUES (
                :username, :email, :password, :user_type, :full_name,
                :phone, :address, :bio, :experience, :specialization,
                'active', 0
            )";
            
            $this->db->query($sql);
            $this->db->bind(':username', $data['username']);
            $this->db->bind(':email', $data['email']);
            $this->db->bind(':password', password_hash($data['password'], PASSWORD_DEFAULT));
            $this->db->bind(':user_type', $data['user_type']);
            $this->db->bind(':full_name', $data['full_name']);
            $this->db->bind(':phone', $data['phone'] ?? null);
            $this->db->bind(':address', $data['address'] ?? null);
            $this->db->bind(':bio', $data['bio'] ?? null);
            $this->db->bind(':experience', $data['experience'] ?? null);
            $this->db->bind(':specialization', $data['specialization'] ?? null);
            
            if ($this->db->execute()) {
                $userId = $this->db->lastInsertId();
                $this->createUserProfile($userId);
                $this->db->commit();
                return $userId;
            }
            
            $this->db->rollBack();
            return "Registration failed";
            
        } catch (Exception $e) {
            $this->db->rollBack();
            return "Database error: " . $e->getMessage();
        }
    }
    
    // Create user profile in user_profiles table
    private function createUserProfile($userId) {
        $sql = "INSERT INTO user_profiles (user_id, newsletter_subscription, created_at) 
                VALUES (:user_id, 1, NOW())";
        $this->db->query($sql);
        $this->db->bind(':user_id', $userId);
        return $this->db->execute();
    }
    
    // Login user
    public function login($email, $password) {
        $this->db->query("SELECT * FROM users WHERE email = :email AND status = 'active'");
        $this->db->bind(':email', $email);
        
        $user = $this->db->single();
        
        if ($user && password_verify($password, $user['password'])) {
            $this->updateLastLogin($user['id']);
            unset($user['password']);
            return $user;
        }
        
        return false;
    }
    
    // Get user by ID
    public function getUserById($id) {
        $this->db->query("SELECT * FROM users WHERE id = :id");
        $this->db->bind(':id', $id);
        return $this->db->single();
    }
    
    // Get user by email
    public function getUserByEmail($email) {
        $this->db->query("SELECT * FROM users WHERE email = :email");
        $this->db->bind(':email', $email);
        return $this->db->single();
    }
    
    // Update profile (matching your users table structure)
    public function updateProfile($userId, $data) {
        try {
            $sql = "UPDATE users SET 
                full_name = :full_name,
                phone = :phone,
                address = :address,
                bio = :bio,
                experience = :experience,
                specialization = :specialization,
                profile_pic = :profile_pic,
                updated_at = NOW()
                WHERE id = :id";
            
            $this->db->query($sql);
            $this->db->bind(':id', $userId);
            $this->db->bind(':full_name', $data['full_name']);
            $this->db->bind(':phone', $data['phone'] ?? null);
            $this->db->bind(':address', $data['address'] ?? null);
            $this->db->bind(':bio', $data['bio'] ?? null);
            $this->db->bind(':experience', $data['experience'] ?? null);
            $this->db->bind(':specialization', $data['specialization'] ?? null);
            $this->db->bind(':profile_pic', $data['profile_pic'] ?? null);
            
            return $this->db->execute();
        } catch (Exception $e) {
            error_log("Profile update error: " . $e->getMessage());
            return false;
        }
    }
    
    // Update password
    public function updatePassword($userId, $currentPassword, $newPassword) {
        $user = $this->getUserById($userId);
        
        if (!$user || !password_verify($currentPassword, $user['password'])) {
            return 'Current password is incorrect';
        }
        
        $this->db->query("UPDATE users SET password = :password, updated_at = NOW() WHERE id = :id");
        $this->db->bind(':password', password_hash($newPassword, PASSWORD_DEFAULT));
        $this->db->bind(':id', $userId);
        
        return $this->db->execute() ? true : 'Failed to update password';
    }
    
    // Check if email exists
    public function emailExists($email) {
        $this->db->query("SELECT id FROM users WHERE email = :email");
        $this->db->bind(':email', $email);
        $this->db->execute();
        return $this->db->rowCount() > 0;
    }
    
    // Check if username exists
    public function usernameExists($username) {
        $this->db->query("SELECT id FROM users WHERE username = :username");
        $this->db->bind(':username', $username);
        $this->db->execute();
        return $this->db->rowCount() > 0;
    }
    
    // Update last login
    public function updateLastLogin($id) {
        $this->db->query("UPDATE users SET last_login = NOW(), updated_at = NOW() WHERE id = :id");
        $this->db->bind(':id', $id);
        return $this->db->execute();
    }
    
    // Get user statistics
    public function getUserStats($userId) {
        $stats = [];
        $user = $this->getUserById($userId);
        
        if (!$user) return $stats;
        
        $stats['user_type'] = $user['user_type'];
        
        if ($user['user_type'] == 'tailor') {
            // Products count
            $this->db->query("SELECT COUNT(*) as count FROM products WHERE tailor_id = :id AND status = 'active'");
            $this->db->bind(':id', $userId);
            $result = $this->db->single();
            $stats['product_count'] = $result['count'] ?? 0;
            
            // Orders count
            $this->db->query("SELECT COUNT(*) as count FROM orders WHERE tailor_id = :id");
            $this->db->bind(':id', $userId);
            $result = $this->db->single();
            $stats['order_count'] = $result['count'] ?? 0;
            
            // Revenue
            $this->db->query("SELECT SUM(total_amount) as revenue FROM orders WHERE tailor_id = :id AND payment_status = 'paid'");
            $this->db->bind(':id', $userId);
            $result = $this->db->single();
            $stats['revenue'] = $result['revenue'] ?? 0;
            
            // Average rating
            $this->db->query("SELECT AVG(r.rating) as avg_rating FROM reviews r 
                             JOIN products p ON r.product_id = p.id 
                             WHERE p.tailor_id = :id AND r.status = 'approved'");
            $this->db->bind(':id', $userId);
            $result = $this->db->single();
            $stats['avg_rating'] = $result['avg_rating'] ?? 0;
            
        } elseif ($user['user_type'] == 'customer') {
            // Orders count
            $this->db->query("SELECT COUNT(*) as count FROM orders WHERE customer_id = :id");
            $this->db->bind(':id', $userId);
            $result = $this->db->single();
            $stats['order_count'] = $result['count'] ?? 0;
            
            // Wishlist count
            $this->db->query("SELECT COUNT(*) as count FROM wishlist WHERE user_id = :id");
            $this->db->bind(':id', $userId);
            $result = $this->db->single();
            $stats['wishlist_count'] = $result['wishlist_count'] ?? 0;
            
            // Total spent
            $this->db->query("SELECT SUM(total_amount) as total_spent FROM orders WHERE customer_id = :id AND payment_status = 'paid'");
            $this->db->bind(':id', $userId);
            $result = $this->db->single();
            $stats['total_spent'] = $result['total_spent'] ?? 0;
        }
        
        return $stats;
    }
    
    // Password reset methods
    public function saveResetToken($email, $token, $expires) {
        $this->db->query("UPDATE users SET reset_token = :token, reset_expires = :expires WHERE email = :email");
        $this->db->bind(':token', $token);
        $this->db->bind(':expires', $expires);
        $this->db->bind(':email', $email);
        return $this->db->execute();
    }
    
    public function getResetToken($token) {
        $this->db->query("SELECT * FROM users WHERE reset_token = :token AND reset_expires > NOW()");
        $this->db->bind(':token', $token);
        return $this->db->single();
    }
    
    public function resetPassword($token, $newPassword) {
        $user = $this->getResetToken($token);
        if (!$user) {
            return 'Invalid or expired token';
        }
        
        $this->db->query("UPDATE users SET password = :password, reset_token = NULL, reset_expires = NULL WHERE id = :id");
        $this->db->bind(':password', password_hash($newPassword, PASSWORD_DEFAULT));
        $this->db->bind(':id', $user['id']);
        
        return $this->db->execute() ? true : 'Failed to reset password';
    }
    
    // Admin methods
    public function getAllUsers($page = 1, $perPage = 10) {
        $offset = ($page - 1) * $perPage;
        $this->db->query("SELECT id, username, email, user_type, full_name, profile_pic, status, created_at 
                         FROM users ORDER BY created_at DESC LIMIT :limit OFFSET :offset");
        $this->db->bind(':limit', $perPage, PDO::PARAM_INT);
        $this->db->bind(':offset', $offset, PDO::PARAM_INT);
        return $this->db->resultSet();
    }
    
    public function updateStatus($id, $status) {
        $allowed_statuses = ['active', 'inactive', 'suspended'];
        if (!in_array($status, $allowed_statuses)) {
            return false;
        }
        
        $this->db->query("UPDATE users SET status = :status, updated_at = NOW() WHERE id = :id");
        $this->db->bind(':status', $status);
        $this->db->bind(':id', $id);
        return $this->db->execute();
    }

    // Get total users count (for admin)
    public function getTotalUsersCount() {
        $this->db->query("SELECT COUNT(*) as count FROM users");
        $result = $this->db->single();
        return $result['count'] ?? 0;
    }

    // Get recent users (for admin)
    public function getRecentUsers($limit = 5) {
        $this->db->query("
            SELECT id, username, email, user_type, full_name, profile_pic, created_at 
            FROM users 
            ORDER BY created_at DESC 
            LIMIT :limit
        ");
        $this->db->bind(':limit', $limit, PDO::PARAM_INT);
        return $this->db->resultSet();
    }

    // Get users by type
    public function getUsersByType($type, $limit = null) {
        $sql = "SELECT * FROM users WHERE user_type = :user_type ORDER BY created_at DESC";
        
        if ($limit) {
            $sql .= " LIMIT :limit";
        }
        
        $this->db->query($sql);
        $this->db->bind(':user_type', $type);
        
        if ($limit) {
            $this->db->bind(':limit', $limit, PDO::PARAM_INT);
        }
        
        return $this->db->resultSet();
    }
}
?>



<?php
/*require_once __DIR__ . '/../classes/Database.php';

class User {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    // Register new user
    /*public function register($data) {
        // Validate required fields
        $required = ['username', 'email', 'password', 'user_type', 'full_name'];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                return "Missing required field: $field";
            }
        }
        
        // Check if username exists
        $this->db->query("SELECT id FROM users WHERE username = :username LIMIT 1");
        $this->db->bind(':username', $data['username']);
        $this->db->execute();
        
        if ($this->db->rowCount() > 0) {
            return 'Username already exists.';
        }
        
        // Check if email exists
        $this->db->query("SELECT id FROM users WHERE email = :email LIMIT 1");
        $this->db->bind(':email', $data['email']);
        $this->db->execute();
        
        if ($this->db->rowCount() > 0) {
            return 'The email ' . $data['email'] . ' is already registered.';
        }
        
        // Insert user
        $sql = "INSERT INTO users (username, email, password, user_type, full_name, phone, address, bio, status) 
                VALUES (:username, :email, :password, :user_type, :full_name, :phone, :address, :bio, 'active')";
        
        $this->db->query($sql);
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
    }*
    
    // Login user
    public function login($email, $password) {
        $this->db->query("SELECT * FROM users WHERE email = :email AND status = 'active'");
        $this->db->bind(':email', $email);
        
        $user = $this->db->single();
        
        if ($user && password_verify($password, $user['password'])) {
            // Update last login
            $this->updateLastLogin($user['id']);
            
            // Remove password from user array
            unset($user['password']);
            return $user;
        }
        
        return false;
    }
    
    // Get user by ID
    public function getUserById($id) {
        $this->db->query("SELECT * FROM users WHERE id = :id");
        $this->db->bind(':id', $id);
        return $this->db->single();
    }
    
    // Get user by email
    public function getUserByEmail($email) {
        $this->db->query("SELECT * FROM users WHERE email = :email");
        $this->db->bind(':email', $email);
        return $this->db->single();
    }
    
    /* Update profile
    public function updateProfile($userId, $data) {
        if (!is_array($data)) {
            return false;
        }
        
        // Build query dynamically based on provided fields
        $fields = [];
        $bindings = [':id' => $userId];
        
        if (isset($data['full_name'])) {
            $fields[] = 'full_name = :full_name';
            $bindings[':full_name'] = $data['full_name'];
        }
        
        if (isset($data['email'])) {
            // Check if email is being changed and if it already exists
            $currentUser = $this->getUserById($userId);
            if ($currentUser['email'] !== $data['email']) {
                if ($this->emailExists($data['email'])) {
                    return 'Email already exists';
                }
            }
            $fields[] = 'email = :email';
            $bindings[':email'] = $data['email'];
        }
        
        if (isset($data['phone'])) {
            $fields[] = 'phone = :phone';
            $bindings[':phone'] = $data['phone'];
        }
        
        if (isset($data['address'])) {
            $fields[] = 'address = :address';
            $bindings[':address'] = $data['address'];
        }
        
        if (isset($data['bio'])) {
            $fields[] = 'bio = :bio';
            $bindings[':bio'] = $data['bio'];
        }
        
        if (isset($data['profile_pic']) && !empty($data['profile_pic'])) {
            $fields[] = 'profile_pic = :profile_pic';
            $bindings[':profile_pic'] = $data['profile_pic'];
        }
        
        if (empty($fields)) {
            return true; // Nothing to update
        }
        
        $sql = "UPDATE users SET " . implode(', ', $fields) . " WHERE id = :id";
        $this->db->query($sql);
        
        foreach ($bindings as $key => $value) {
            $this->db->bind($key, $value);
        }
        
        return $this->db->execute();
    }*

    public function updateProfile($userId, $data) {
        try {
            $this->db->query("UPDATE users SET 
                full_name = :full_name,
                phone = :phone,
                address = :address,
                bio = :bio,
                profile_pic = :profile_pic,
                updated_at = NOW()
                WHERE id = :id");
            
            $this->db->bind(':id', $userId);
            $this->db->bind(':full_name', $data['full_name']);
            $this->db->bind(':phone', $data['phone'] ?? null);
            $this->db->bind(':address', $data['address'] ?? null);
            $this->db->bind(':bio', $data['bio'] ?? null);
            $this->db->bind(':profile_pic', $data['profile_pic'] ?? null);
            
            return $this->db->execute();
        } catch (Exception $e) {
            error_log("Profile update error: " . $e->getMessage());
            return false;
        }
    }
    
    // Update password
    public function updatePassword($userId, $currentPassword, $newPassword) {
        $user = $this->getUserById($userId);
        
        if (!$user || !password_verify($currentPassword, $user['password'])) {
            return 'Current password is incorrect';
        }
        
        $this->db->query("UPDATE users SET password = :password WHERE id = :id");
        $this->db->bind(':password', password_hash($newPassword, PASSWORD_DEFAULT));
        $this->db->bind(':id', $userId);
        
        return $this->db->execute() ? true : 'Failed to update password';
    }
    
    // Check if email exists
    public function emailExists($email) {
        $this->db->query("SELECT id FROM users WHERE email = :email");
        $this->db->bind(':email', $email);
        $this->db->execute();
        return $this->db->rowCount() > 0;
    }
    
    // Check if username exists
    public function usernameExists($username) {
        $this->db->query("SELECT id FROM users WHERE username = :username");
        $this->db->bind(':username', $username);
        $this->db->execute();
        return $this->db->rowCount() > 0;
    }
    
    // Update last login
    public function updateLastLogin($id) {
        $this->db->query("UPDATE users SET last_login = NOW(), updated_at = NOW() WHERE id = :id");
        $this->db->bind(':id', $id);
        return $this->db->execute();
    }
    
    // Get total user count
    public function getTotalCount() {
        $this->db->query("SELECT COUNT(*) as count FROM users WHERE status = 'active'");
        $result = $this->db->single();
        return $result['count'] ?? 0;
    }
    
    // Get count by user type
    public function getCountByType($userType) {
        $this->db->query("SELECT COUNT(*) as count FROM users WHERE user_type = :user_type AND status = 'active'");
        $this->db->bind(':user_type', $userType);
        $result = $this->db->single();
        return $result['count'] ?? 0;
    }
    
    // Get recent users
    public function getRecentUsers($limit = 5) {
        $this->db->query("SELECT id, username, email, user_type, full_name, profile_pic, created_at 
                         FROM users WHERE status = 'active' 
                         ORDER BY created_at DESC LIMIT :limit");
        $this->db->bind(':limit', $limit, PDO::PARAM_INT);
        return $this->db->resultSet();
    }
    
    // Get all users with pagination (admin only)
    public function getAllUsers($page = 1, $perPage = 10) {
        $offset = ($page - 1) * $perPage;
        $this->db->query("SELECT id, username, email, user_type, full_name, profile_pic, status, created_at 
                         FROM users ORDER BY created_at DESC LIMIT :limit OFFSET :offset");
        $this->db->bind(':limit', $perPage, PDO::PARAM_INT);
        $this->db->bind(':offset', $offset, PDO::PARAM_INT);
        return $this->db->resultSet();
    }
    
    // Search users
    public function searchUsers($query) {
        $this->db->query("SELECT id, username, email, user_type, full_name, profile_pic 
                         FROM users WHERE (username LIKE :query OR email LIKE :query OR full_name LIKE :query) 
                         AND status = 'active' LIMIT 20");
        $this->db->bind(':query', "%$query%");
        return $this->db->resultSet();
    }
    
    // Update user status (admin function)
    public function updateStatus($id, $status) {
        $allowed_statuses = ['active', 'inactive', 'suspended'];
        if (!in_array($status, $allowed_statuses)) {
            return false;
        }
        
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
    
    // Save password reset token (using your users table structure)
    public function saveResetToken($email, $token, $expires) {
        $this->db->query("UPDATE users SET reset_token = :token, reset_expires = :expires WHERE email = :email");
        $this->db->bind(':token', $token);
        $this->db->bind(':expires', $expires);
        $this->db->bind(':email', $email);
        return $this->db->execute();
    }
    
    // Get reset token
    public function getResetToken($token) {
        $this->db->query("SELECT * FROM users WHERE reset_token = :token AND reset_expires > NOW()");
        $this->db->bind(':token', $token);
        return $this->db->single();
    }
    
    // Reset password using token
    public function resetPassword($token, $newPassword) {
        $user = $this->getResetToken($token);
        if (!$user) {
            return 'Invalid or expired token';
        }
        
        $this->db->query("UPDATE users SET password = :password, reset_token = NULL, reset_expires = NULL WHERE id = :id");
        $this->db->bind(':password', password_hash($newPassword, PASSWORD_DEFAULT));
        $this->db->bind(':id', $user['id']);
        
        return $this->db->execute() ? true : 'Failed to reset password';
    }
    
    // Get user statistics for dashboard
    public function getUserStats($userId) {
        $stats = [];
        $user = $this->getUserById($userId);
        
        if (!$user) {
            return $stats;
        }
        
        $stats['user_type'] = $user['user_type'];
        
        if ($user['user_type'] == 'tailor') {
            // Tailor-specific stats
            $this->db->query("SELECT COUNT(*) as product_count FROM products WHERE tailor_id = :id AND status = 'active'");
            $this->db->bind(':id', $userId);
            $result = $this->db->single();
            $stats['product_count'] = $result['product_count'] ?? 0;
            
            $this->db->query("SELECT COUNT(*) as order_count FROM orders WHERE tailor_id = :id AND status != 'cancelled'");
            $this->db->bind(':id', $userId);
            $result = $this->db->single();
            $stats['order_count'] = $result['order_count'] ?? 0;
            
            $this->db->query("SELECT SUM(total_amount) as revenue FROM orders WHERE tailor_id = :id AND payment_status = 'paid'");
            $this->db->bind(':id', $userId);
            $result = $this->db->single();
            $stats['revenue'] = $result['revenue'] ?? 0;
            
            // Get average rating
            $this->db->query("SELECT AVG(r.rating) as avg_rating FROM reviews r 
                             JOIN products p ON r.product_id = p.id 
                             WHERE p.tailor_id = :id AND r.status = 'approved'");
            $this->db->bind(':id', $userId);
            $result = $this->db->single();
            $stats['avg_rating'] = $result['avg_rating'] ?? 0;
            
        } elseif ($user['user_type'] == 'customer') {
            // Customer-specific stats
            $this->db->query("SELECT COUNT(*) as order_count FROM orders WHERE customer_id = :id");
            $this->db->bind(':id', $userId);
            $result = $this->db->single();
            $stats['order_count'] = $result['order_count'] ?? 0;
            
            $this->db->query("SELECT COUNT(*) as wishlist_count FROM wishlist WHERE user_id = :id");
            $this->db->bind(':id', $userId);
            $result = $this->db->single();
            $stats['wishlist_count'] = $result['wishlist_count'] ?? 0;
            
            $this->db->query("SELECT COUNT(*) as review_count FROM reviews WHERE user_id = :id AND status = 'approved'");
            $this->db->bind(':id', $userId);
            $result = $this->db->single();
            $stats['review_count'] = $result['review_count'] ?? 0;
        }
        
        return $stats;
    }
    
    // Update user profile as tailor (special method)
    public function updateTailorProfile($userId, $data) {
        $allowedFields = ['business_name', 'description', 'specialization', 'experience_years', 
                         'hourly_rate', 'portfolio_images', 'services_offered', 'availability'];
        
        $fields = [];
        $bindings = [':user_id' => $userId];
        
        foreach ($allowedFields as $field) {
            if (isset($data[$field])) {
                $fields[] = "$field = :$field";
                $bindings[":$field"] = $data[$field];
            }
        }
        
        if (empty($fields)) {
            return false;
        }
        
        // Check if tailor profile exists
        $this->db->query("SELECT id FROM tailors WHERE user_id = :user_id");
        $this->db->bind(':user_id', $userId);
        $this->db->execute();
        
        if ($this->db->rowCount() > 0) {
            // Update existing
            $sql = "UPDATE tailors SET " . implode(', ', $fields) . " WHERE user_id = :user_id";
        } else {
            // Insert new
            $sql = "INSERT INTO tailors (user_id, " . implode(', ', array_keys($data)) . ") 
                    VALUES (:user_id, " . implode(', ', array_keys($bindings)) . ")";
        }
        
        $this->db->query($sql);
        
        foreach ($bindings as $key => $value) {
            $this->db->bind($key, $value);
        }
        
        return $this->db->execute();
    }
    
    // Get tailor profile
    public function getTailorProfile($userId) {
        $this->db->query("SELECT * FROM tailors WHERE user_id = :user_id");
        $this->db->bind(':user_id', $userId);
        return $this->db->single();
    }
    
    // Verify email
    public function verifyEmail($userId) {
        $this->db->query("UPDATE users SET email_verified = 1, verification_token = NULL WHERE id = :id");
        $this->db->bind(':id', $userId);
        return $this->db->execute();
    }
    
    // Save verification token
    public function saveVerificationToken($userId, $token) {
        $this->db->query("UPDATE users SET verification_token = :token WHERE id = :id");
        $this->db->bind(':token', $token);
        $this->db->bind(':id', $userId);
        return $this->db->execute();
    }
    
    // Get user by verification token
    public function getUserByVerificationToken($token) {
        $this->db->query("SELECT * FROM users WHERE verification_token = :token");
        $this->db->bind(':token', $token);
        return $this->db->single();
    }

    // In your upload function, add this check:
    public function uploadProfilePicture($file) {
        $upload_dir = '../../assets/images/avatars/';
        
        // Ensure directory exists
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        // Check if writable
        if (!is_writable($upload_dir)) {
            // Try to change permissions
            chmod($upload_dir, 0777);
            
            if (!is_writable($upload_dir)) {
                return [
                    'success' => false,
                    'message' => 'Upload directory is not writable. Please check permissions.'
                ];
            }
        }
        
        // Continue with your upload logic...
    }

    public function register($data) {
        try {
            $this->db->beginTransaction();
            
            // Check if email already exists
            $this->db->query("SELECT id FROM users WHERE email = :email");
            $this->db->bind(':email', $data['email']);
            if ($this->db->single()) {
                return "Email already registered";
            }
            
            // Check if username already exists
            $this->db->query("SELECT id FROM users WHERE username = :username");
            $this->db->bind(':username', $data['username']);
            if ($this->db->single()) {
                return "Username already taken";
            }
            
            // Insert user
            $this->db->query("INSERT INTO users (username, email, password, full_name, user_type, phone, address, bio) 
                            VALUES (:username, :email, :password, :full_name, :user_type, :phone, :address, :bio)");
            
            $this->db->bind(':username', $data['username']);
            $this->db->bind(':email', $data['email']);
            $this->db->bind(':password', password_hash($data['password'], PASSWORD_DEFAULT));
            $this->db->bind(':full_name', $data['full_name']);
            $this->db->bind(':user_type', $data['user_type']);
            $this->db->bind(':phone', $data['phone'] ?? null);
            $this->db->bind(':address', $data['address'] ?? null);
            $this->db->bind(':bio', $data['bio'] ?? null);
            
            if ($this->db->execute()) {
                $userId = $this->db->lastInsertId();
                $this->db->commit();
                return $userId;
            }
            
            $this->db->rollBack();
            return "Registration failed";
            
        } catch (Exception $e) {
            $this->db->rollBack();
            return "Database error: " . $e->getMessage();
        }
    }

}
?>


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
*/