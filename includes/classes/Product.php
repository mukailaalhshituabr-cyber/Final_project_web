<?php
require_once __DIR__ . '/../classes/Database.php';

class Product {
    private $db;
    
    public function __construct() {
        $this->db = new Database();
    }
    
    // Add new product
    public function add($data) {
        $this->db->query("INSERT INTO products (tailor_id, title, description, price, category, material, size, color, is_customizable, images, stock) 
                         VALUES (:tailor_id, :title, :description, :price, :category, :material, :size, :color, :is_customizable, :images, :stock)");
        
        $this->db->bind(':tailor_id', $data['tailor_id']);
        $this->db->bind(':title', $data['title']);
        $this->db->bind(':description', $data['description']);
        $this->db->bind(':price', $data['price']);
        $this->db->bind(':category', $data['category']);
        $this->db->bind(':material', $data['material'] ?? null);
        $this->db->bind(':size', $data['size'] ?? null);
        $this->db->bind(':color', $data['color'] ?? null);
        $this->db->bind(':is_customizable', $data['is_customizable'] ?? 0);
        $this->db->bind(':images', $data['images']);
        $this->db->bind(':stock', $data['stock'] ?? 1);
        
        if ($this->db->execute()) {
            return $this->db->lastInsertId();
        }
        return false;
    }
    
    // Update product
    public function update($id, $data) {
        $query = "UPDATE products SET title = :title, description = :description, price = :price, 
                  category = :category, material = :material, size = :size, color = :color, 
                  is_customizable = :is_customizable, stock = :stock, status = :status";
        
        if (isset($data['images'])) {
            $query .= ", images = :images";
        }
        
        $query .= " WHERE id = :id";
        
        $this->db->query($query);
        
        $this->db->bind(':title', $data['title']);
        $this->db->bind(':description', $data['description']);
        $this->db->bind(':price', $data['price']);
        $this->db->bind(':category', $data['category']);
        $this->db->bind(':material', $data['material'] ?? null);
        $this->db->bind(':size', $data['size'] ?? null);
        $this->db->bind(':color', $data['color'] ?? null);
        $this->db->bind(':is_customizable', $data['is_customizable'] ?? 0);
        $this->db->bind(':stock', $data['stock'] ?? 1);
        $this->db->bind(':status', $data['status'] ?? 'active');
        $this->db->bind(':id', $id);
        
        if (isset($data['images'])) {
            $this->db->bind(':images', $data['images']);
        }
        
        return $this->db->execute();
    }
    
    // Delete product
    public function delete($id) {
        $this->db->query("DELETE FROM products WHERE id = :id");
        $this->db->bind(':id', $id);
        return $this->db->execute();
    }
    
    // Get product by ID
    public function getById($id) {
        $this->db->query("SELECT p.*, u.full_name as tailor_name, u.profile_pic as tailor_pic 
                         FROM products p 
                         JOIN users u ON p.tailor_id = u.id 
                         WHERE p.id = :id");
        $this->db->bind(':id', $id);
        return $this->db->single();
    }
    
    // Get products by tailor
    public function getTailorProducts($tailorId) {
        $this->db->query("SELECT * FROM products WHERE tailor_id = :tailor_id ORDER BY created_at DESC");
        $this->db->bind(':tailor_id', $tailorId);
        return $this->db->resultSet();
    }
    
    // Get all products with filters
    public function getAll($filters = [], $page = 1, $perPage = 12) {
        $where = "WHERE p.status = 'active'";
        $params = [];
        
        if (!empty($filters['category'])) {
            $where .= " AND p.category = :category";
            $params[':category'] = $filters['category'];
        }
        
        if (!empty($filters['min_price'])) {
            $where .= " AND p.price >= :min_price";
            $params[':min_price'] = $filters['min_price'];
        }
        
        if (!empty($filters['max_price'])) {
            $where .= " AND p.price <= :max_price";
            $params[':max_price'] = $filters['max_price'];
        }
        
        if (!empty($filters['tailor_id'])) {
            $where .= " AND p.tailor_id = :tailor_id";
            $params[':tailor_id'] = $filters['tailor_id'];
        }
        
        if (!empty($filters['is_customizable'])) {
            $where .= " AND p.is_customizable = 1";
        }
        
        $offset = ($page - 1) * $perPage;
        
        $query = "SELECT p.*, u.full_name as tailor_name 
                 FROM products p 
                 JOIN users u ON p.tailor_id = u.id 
                 $where 
                 ORDER BY p.created_at DESC 
                 LIMIT :limit OFFSET :offset";
        
        $this->db->query($query);
        
        foreach ($params as $key => $value) {
            $this->db->bind($key, $value);
        }
        
        $this->db->bind(':limit', $perPage);
        $this->db->bind(':offset', $offset);
        
        return $this->db->resultSet();
    }
    
    // Get featured products
    public function getFeatured($limit = 8) {
        $this->db->query("SELECT p.*, u.full_name as tailor_name 
                         FROM products p 
                         JOIN users u ON p.tailor_id = u.id 
                         WHERE p.status = 'active' 
                         ORDER BY p.rating DESC, p.review_count DESC 
                         LIMIT :limit");
        $this->db->bind(':limit', $limit);
        return $this->db->resultSet();
    }
    
    // Search products
    public function search($query, $filters = [], $page = 1, $perPage = 12) {
        $where = "WHERE p.status = 'active' AND (p.title LIKE :query OR p.description LIKE :query OR p.category LIKE :query)";
        $params = [':query' => "%$query%"];
        
        // Add filters
        if (!empty($filters['category'])) {
            $where .= " AND p.category = :category";
            $params[':category'] = $filters['category'];
        }
        
        if (!empty($filters['min_price'])) {
            $where .= " AND p.price >= :min_price";
            $params[':min_price'] = $filters['min_price'];
        }
        
        if (!empty($filters['max_price'])) {
            $where .= " AND p.price <= :max_price";
            $params[':max_price'] = $filters['max_price'];
        }
        
        $offset = ($page - 1) * $perPage;
        
        $query = "SELECT p.*, u.full_name as tailor_name 
                 FROM products p 
                 JOIN users u ON p.tailor_id = u.id 
                 $where 
                 ORDER BY p.created_at DESC 
                 LIMIT :limit OFFSET :offset";
        
        $this->db->query($query);
        
        foreach ($params as $key => $value) {
            $this->db->bind($key, $value);
        }
        
        $this->db->bind(':limit', $perPage);
        $this->db->bind(':offset', $offset);
        
        return $this->db->resultSet();
    }
    
    // Get related products
    public function getRelated($productId, $category, $limit = 4) {
        $this->db->query("SELECT p.*, u.full_name as tailor_name 
                         FROM products p 
                         JOIN users u ON p.tailor_id = u.id 
                         WHERE p.category = :category AND p.id != :id AND p.status = 'active' 
                         ORDER BY RAND() 
                         LIMIT :limit");
        $this->db->bind(':category', $category);
        $this->db->bind(':id', $productId);
        $this->db->bind(':limit', $limit);
        return $this->db->resultSet();
    }
    
    // Get product reviews
    public function getReviews($productId) {
        $this->db->query("SELECT r.*, u.full_name, u.profile_pic 
                         FROM reviews r 
                         JOIN users u ON r.customer_id = u.id 
                         WHERE r.product_id = :product_id 
                         ORDER BY r.created_at DESC");
        $this->db->bind(':product_id', $productId);
        return $this->db->resultSet();
    }
    
    // Add review
    public function addReview($userId, $productId, $orderId, $rating, $comment) {
        $this->db->query("INSERT INTO reviews (product_id, customer_id, order_id, rating, comment) 
                         VALUES (:product_id, :customer_id, :order_id, :rating, :comment)");
        
        $this->db->bind(':product_id', $productId);
        $this->db->bind(':customer_id', $userId);
        $this->db->bind(':order_id', $orderId);
        $this->db->bind(':rating', $rating);
        $this->db->bind(':comment', $comment);
        
        return $this->db->execute();
    }
    
    // Update product rating
    public function updateProductRating($productId) {
        $this->db->query("UPDATE products p 
                         SET rating = (SELECT AVG(rating) FROM reviews WHERE product_id = :product_id),
                         review_count = (SELECT COUNT(*) FROM reviews WHERE product_id = :product_id)
                         WHERE p.id = :product_id");
        $this->db->bind(':product_id', $productId);
        return $this->db->execute();
    }
    
    // Check if user can review
    public function canReview($userId, $productId, $orderId) {
        $this->db->query("SELECT COUNT(*) as count FROM order_items oi 
                         JOIN orders o ON oi.order_id = o.id 
                         WHERE o.customer_id = :user_id 
                         AND oi.product_id = :product_id 
                         AND o.id = :order_id 
                         AND o.status = 'completed'");
        
        $this->db->bind(':user_id', $userId);
        $this->db->bind(':product_id', $productId);
        $this->db->bind(':order_id', $orderId);
        
        $result = $this->db->single();
        return $result['count'] > 0;
    }
    
    // Check if user has already reviewed
    public function hasReviewed($userId, $productId, $orderId) {
        $this->db->query("SELECT COUNT(*) as count FROM reviews 
                         WHERE customer_id = :user_id 
                         AND product_id = :product_id 
                         AND order_id = :order_id");
        
        $this->db->bind(':user_id', $userId);
        $this->db->bind(':product_id', $productId);
        $this->db->bind(':order_id', $orderId);
        
        $result = $this->db->single();
        return $result['count'] > 0;
    }
    
    // Get total product count
    public function getTotalCount() {
        $this->db->query("SELECT COUNT(*) as count FROM products WHERE status = 'active'");
        $result = $this->db->single();
        return $result['count'] ?? 0;
    }
    
    // Get wishlist items
    public function getWishlistItems($userId) {
        $this->db->query("SELECT p.*, u.full_name as tailor_name 
                         FROM wishlist w 
                         JOIN products p ON w.product_id = p.id 
                         JOIN users u ON p.tailor_id = u.id 
                         WHERE w.user_id = :user_id 
                         ORDER BY w.created_at DESC");
        $this->db->bind(':user_id', $userId);
        return $this->db->resultSet();
    }
    
    // Add to wishlist
    public function addToWishlist($userId, $productId) {
        $this->db->query("INSERT INTO wishlist (user_id, product_id) VALUES (:user_id, :product_id)");
        $this->db->bind(':user_id', $userId);
        $this->db->bind(':product_id', $productId);
        return $this->db->execute();
    }
    
    // Remove from wishlist
    public function removeFromWishlist($userId, $productId) {
        $this->db->query("DELETE FROM wishlist WHERE user_id = :user_id AND product_id = :product_id");
        $this->db->bind(':user_id', $userId);
        $this->db->bind(':product_id', $productId);
        return $this->db->execute();
    }
    
    // Check if product is in wishlist
    public function isInWishlist($userId, $productId) {
        $this->db->query("SELECT COUNT(*) as count FROM wishlist WHERE user_id = :user_id AND product_id = :product_id");
        $this->db->bind(':user_id', $userId);
        $this->db->bind(':product_id', $productId);
        $result = $this->db->single();
        return $result['count'] > 0;
    }
    
    // Check product ownership
    public function isOwner($productId, $tailorId) {
        $this->db->query("SELECT COUNT(*) as count FROM products WHERE id = :id AND tailor_id = :tailor_id");
        $this->db->bind(':id', $productId);
        $this->db->bind(':tailor_id', $tailorId);
        $result = $this->db->single();
        return $result['count'] > 0;
    }
}
?>