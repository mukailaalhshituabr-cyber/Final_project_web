<?php
class User {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    // Register new user
    public function register($data) {
        try {
            $this->db->beginTransaction();

            // Validate required fields
            $required = ['username', 'email', 'password', 'full_name', 'user_type'];
            foreach ($required as $field) {
                if (empty($data[$field])) {
                    throw new Exception("Missing required field: " . $field);
                }
            }

            // Check if email exists
            if ($this->db->emailExists($data['email'])) {
                throw new Exception("Email already registered");
            }

            // Check if username exists
            if ($this->db->usernameExists($data['username'])) {
                throw new Exception("Username already taken");
            }

            // Validate user type
            $validTypes = ['customer', 'tailor', 'admin'];
            if (!in_array($data['user_type'], $validTypes)) {
                throw new Exception("Invalid user type");
            }

            // Hash password
            $hashedPassword = password_hash($data['password'], PASSWORD_DEFAULT);

            // Insert user
            $userData = [
                'username' => $data['username'],
                'email' => $data['email'],
                'password' => $hashedPassword,
                'user_type' => $data['user_type'],
                'full_name' => $data['full_name'],
                'phone' => $data['phone'] ?? null,
                'address' => $data['address'] ?? null,
                'bio' => $data['bio'] ?? null,
                'status' => 'active',
                'email_verified' => 0
            ];

            if ($this->db->insert('users', $userData)) {
                $userId = $this->db->lastInsertId();
                
                // Create user profile
                $profileData = [
                    'user_id' => $userId,
                    'newsletter_subscription' => 1
                ];
                $this->db->insert('user_profiles', $profileData);
                
                $this->db->commit();
                return $userId;
            }

            throw new Exception("Registration failed");
            
        } catch (Exception $e) {
            $this->db->rollBack();
            return $e->getMessage();
        }
    }

    // Login user
    public function login($email, $password) {
        try {
            $user = $this->db->getUserByEmail($email);
            
            if (!$user) {
                throw new Exception("Invalid email or password");
            }

            if ($user['status'] !== 'active') {
                throw new Exception("Account is not active");
            }

            if (!password_verify($password, $user['password'])) {
                throw new Exception("Invalid email or password");
            }

            // Update last login
            $this->updateLastLogin($user['id']);

            // Remove password from array
            unset($user['password']);
            
            return $user;
            
        } catch (Exception $e) {
            return false;
        }
    }

    // Get user by ID
    public function getUserById($id) {
        return $this->db->getUserById($id);
    }

    // Update profile
    public function updateProfile($userId, $data) {
        $allowedFields = ['full_name', 'email', 'phone', 'address', 'bio', 'profile_pic', 'specialization', 'experience'];
        
        $updateData = [];
        foreach ($allowedFields as $field) {
            if (isset($data[$field])) {
                $updateData[$field] = $data[$field];
            }
        }

        if (empty($updateData)) {
            return true;
        }

        // If email is being updated, check if it's unique
        if (isset($updateData['email'])) {
            $currentUser = $this->getUserById($userId);
            if ($currentUser['email'] !== $updateData['email']) {
                if ($this->db->emailExists($updateData['email'])) {
                    return "Email already exists";
                }
            }
        }

        return $this->db->update('users', $updateData, ['id' => $userId]);
    }

    // Update password
    public function updatePassword($userId, $currentPassword, $newPassword) {
        $user = $this->getUserById($userId);
        
        if (!$user || !password_verify($currentPassword, $user['password'])) {
            return "Current password is incorrect";
        }

        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
        return $this->db->update('users', ['password' => $hashedPassword], ['id' => $userId]);
    }

    // Update last login
    public function updateLastLogin($id) {
        return $this->db->update('users', ['last_login' => date('Y-m-d H:i:s')], ['id' => $id]);
    }

    // Get user statistics
    public function getUserStats($userId) {
        $user = $this->getUserById($userId);
        $stats = [
            'user_type' => $user['user_type'],
            'full_name' => $user['full_name'],
            'profile_pic' => $user['profile_pic']
        ];

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
            $this->db->query("SELECT AVG(r.rating) as avg_rating FROM reviews r JOIN products p ON r.product_id = p.id WHERE p.tailor_id = :id AND r.status = 'approved'");
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

            $this->db->query("SELECT SUM(total_amount) as total_spent FROM orders WHERE customer_id = :id AND payment_status = 'paid'");
            $this->db->bind(':id', $userId);
            $result = $this->db->single();
            $stats['total_spent'] = $result['total_spent'] ?? 0;
        }

        return $stats;
    }

    // Get all users with pagination (admin only)
    public function getAllUsers($page = 1, $perPage = 10, $search = '') {
        $offset = ($page - 1) * $perPage;
        
        $sql = "SELECT id, username, email, user_type, full_name, profile_pic, status, created_at FROM users";
        $where = [];
        $params = [];
        
        if ($search) {
            $where[] = "(username LIKE :search OR email LIKE :search OR full_name LIKE :search)";
            $params[':search'] = "%$search%";
        }
        
        if (!empty($where)) {
            $sql .= " WHERE " . implode(' AND ', $where);
        }
        
        $sql .= " ORDER BY created_at DESC LIMIT :limit OFFSET :offset";
        
        $this->db->query($sql);
        
        foreach ($params as $key => $value) {
            $this->db->bind($key, $value);
        }
        
        $this->db->bind(':limit', $perPage, PDO::PARAM_INT);
        $this->db->bind(':offset', $offset, PDO::PARAM_INT);
        
        return $this->db->resultSet();
    }

    // Update user status (admin function)
    public function updateStatus($id, $status) {
        $allowedStatuses = ['active', 'inactive', 'suspended'];
        if (!in_array($status, $allowedStatuses)) {
            return false;
        }

        return $this->db->update('users', ['status' => $status], ['id' => $id]);
    }

    // Password reset functions
    public function saveResetToken($email, $token, $expires) {
        return $this->db->update('users', [
            'reset_token' => $token,
            'reset_expires' => $expires
        ], ['email' => $email]);
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

        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
        
        $success = $this->db->update('users', [
            'password' => $hashedPassword,
            'reset_token' => null,
            'reset_expires' => null
        ], ['id' => $user['id']]);

        return $success ? true : 'Failed to reset password';
    }

    // Email verification
    public function saveVerificationToken($userId, $token) {
        return $this->db->update('users', ['verification_token' => $token], ['id' => $userId]);
    }

    public function verifyEmail($token) {
        $this->db->query("SELECT * FROM users WHERE verification_token = :token");
        $this->db->bind(':token', $token);
        $user = $this->db->single();
        
        if (!$user) {
            return false;
        }

        return $this->db->update('users', [
            'email_verified' => 1,
            'verification_token' => null
        ], ['id' => $user['id']]);
    }

    // Search users
    public function searchUsers($query, $limit = 20) {
        $this->db->query("
            SELECT id, username, email, user_type, full_name, profile_pic 
            FROM users 
            WHERE (username LIKE :query OR email LIKE :query OR full_name LIKE :query) 
            AND status = 'active' 
            LIMIT :limit
        ");
        $this->db->bind(':query', "%$query%");
        $this->db->bind(':limit', $limit, PDO::PARAM_INT);
        return $this->db->resultSet();
    }
}
?>