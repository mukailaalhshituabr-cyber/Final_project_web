<?php
class Product {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    // Get total products count
    public function getTotalProductsCount() {
        $this->db->query("SELECT COUNT(*) as count FROM products WHERE status = 'active'");
        $result = $this->db->single();
        return $result['count'] ?? 0;
    }

    // Get products by tailor
    public function getProductsByTailor($tailorId, $limit = null, $status = 'active') {
        $sql = "
            SELECT p.*, u.full_name as tailor_name, u.profile_pic as tailor_avatar
            FROM products p 
            JOIN users u ON p.tailor_id = u.id 
            WHERE p.tailor_id = :tailor_id
        ";
        
        $params = [':tailor_id' => $tailorId];
        
        if ($status) {
            $sql .= " AND p.status = :status";
            $params[':status'] = $status;
        }
        
        $sql .= " ORDER BY p.created_at DESC";
        
        if ($limit) {
            $sql .= " LIMIT :limit";
            $params[':limit'] = $limit;
        }
        
        $this->db->query($sql);
        
        foreach ($params as $key => $value) {
            if ($key === ':limit') {
                $this->db->bind($key, $value, PDO::PARAM_INT);
            } else {
                $this->db->bind($key, $value);
            }
        }
        
        return $this->db->resultSet();
    }

    // Get featured products
    public function getFeaturedProducts($limit = 8) {
        $this->db->query("
            SELECT p.*, u.full_name as tailor_name, u.profile_pic as tailor_avatar
            FROM products p 
            JOIN users u ON p.tailor_id = u.id 
            WHERE p.featured = 1 
            AND p.status = 'active'
            ORDER BY p.created_at DESC 
            LIMIT :limit
        ");
        $this->db->bind(':limit', $limit, PDO::PARAM_INT);
        return $this->db->resultSet();
    }

    // Get products by category
    public function getProductsByCategory($category, $limit = 12) {
        $this->db->query("
            SELECT p.*, u.full_name as tailor_name 
            FROM products p 
            JOIN users u ON p.tailor_id = u.id 
            WHERE (p.category = :category OR p.subcategory = :category)
            AND p.status = 'active'
            ORDER BY p.created_at DESC 
            LIMIT :limit
        ");
        $this->db->bind(':category', $category);
        $this->db->bind(':limit', $limit, PDO::PARAM_INT);
        return $this->db->resultSet();
    }

    // Get product by ID
    public function getProductById($id) {
        $this->db->query("
            SELECT p.*, u.full_name as tailor_name, u.profile_pic as tailor_avatar,
                   u.bio as tailor_bio, u.experience as tailor_experience,
                   u.specialization as tailor_specialization, u.address as tailor_address
            FROM products p 
            JOIN users u ON p.tailor_id = u.id 
            WHERE p.id = :id
        ");
        $this->db->bind(':id', $id);
        return $this->db->single();
    }

    // Search products
    public function searchProducts($query, $limit = 20) {
        $this->db->query("
            SELECT p.*, u.full_name as tailor_name 
            FROM products p 
            JOIN users u ON p.tailor_id = u.id 
            WHERE (p.title LIKE :query OR p.description LIKE :query OR p.tags LIKE :query)
            AND p.status = 'active'
            ORDER BY p.created_at DESC 
            LIMIT :limit
        ");
        $this->db->bind(':query', "%$query%");
        $this->db->bind(':limit', $limit, PDO::PARAM_INT);
        return $this->db->resultSet();
    }

    // Create product
    public function createProduct($data) {
        try {
            $this->db->beginTransaction();

            // Generate slug from title
            $slug = $this->generateSlug($data['title']);

            // Prepare product data
            $productData = [
                'tailor_id' => $data['tailor_id'],
                'title' => $data['title'],
                'slug' => $slug,
                'description' => $data['description'] ?? '',
                'price' => $data['price'],
                'compare_price' => $data['compare_price'] ?? null,
                'cost_price' => $data['cost_price'] ?? null,
                'sku' => $data['sku'] ?? '',
                'category' => $data['category'] ?? '',
                'subcategory' => $data['subcategory'] ?? '',
                'material' => $data['material'] ?? '',
                'size' => $data['size'] ?? null,
                'color' => $data['color'] ?? '',
                'brand' => $data['brand'] ?? '',
                'tags' => isset($data['tags']) ? json_encode($data['tags']) : '[]',
                'images' => isset($data['images']) ? json_encode($data['images']) : '[]',
                'specifications' => isset($data['specifications']) ? json_encode($data['specifications']) : '{}',
                'is_customizable' => $data['is_customizable'] ?? 0,
                'customization_options' => isset($data['customization_options']) ? json_encode($data['customization_options']) : '{}',
                'stock_quantity' => $data['stock_quantity'] ?? 1,
                'low_stock_threshold' => $data['low_stock_threshold'] ?? 5,
                'weight' => $data['weight'] ?? null,
                'dimensions' => $data['dimensions'] ?? '',
                'status' => $data['status'] ?? 'draft',
                'featured' => $data['featured'] ?? 0
            ];

            if ($this->db->insert('products', $productData)) {
                $productId = $this->db->lastInsertId();
                
                // Add product tags if provided
                if (!empty($data['tag_ids'])) {
                    foreach ($data['tag_ids'] as $tagId) {
                        $this->db->insert('product_tags', [
                            'product_id' => $productId,
                            'tag_id' => $tagId
                        ]);
                    }
                }
                
                $this->db->commit();
                return ['success' => true, 'product_id' => $productId];
            }

            throw new Exception("Failed to create product");

        } catch (Exception $e) {
            $this->db->rollBack();
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    // Update product
    public function updateProduct($productId, $data) {
        $allowedFields = [
            'title', 'description', 'price', 'compare_price', 'cost_price', 'sku',
            'category', 'subcategory', 'material', 'size', 'color', 'brand', 'tags',
            'images', 'specifications', 'is_customizable', 'customization_options',
            'stock_quantity', 'low_stock_threshold', 'weight', 'dimensions',
            'status', 'featured'
        ];

        $updateData = [];
        foreach ($allowedFields as $field) {
            if (isset($data[$field])) {
                // Handle JSON fields
                if (in_array($field, ['tags', 'images', 'specifications', 'customization_options'])) {
                    $updateData[$field] = json_encode($data[$field]);
                } else {
                    $updateData[$field] = $data[$field];
                }
            }
        }

        if (empty($updateData)) {
            return ['success' => false, 'error' => 'No fields to update'];
        }

        // If title is updated, regenerate slug
        if (isset($updateData['title'])) {
            $updateData['slug'] = $this->generateSlug($updateData['title']);
        }

        $success = $this->db->update('products', $updateData, ['id' => $productId]);
        return ['success' => $success];
    }

    // Delete product
    public function deleteProduct($productId) {
        try {
            $this->db->beginTransaction();
            
            // Delete product tags
            $this->db->delete('product_tags', ['product_id' => $productId]);
            
            // Delete product
            $success = $this->db->delete('products', ['id' => $productId]);
            
            if ($success) {
                $this->db->commit();
                return ['success' => true];
            }
            
            throw new Exception("Failed to delete product");
            
        } catch (Exception $e) {
            $this->db->rollBack();
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    // Generate slug
    private function generateSlug($title) {
        $slug = strtolower(trim($title));
        $slug = preg_replace('/[^a-z0-9-]/', '-', $slug);
        $slug = preg_replace('/-+/', '-', $slug);
        $slug = trim($slug, '-');
        
        // Check if slug exists
        $counter = 1;
        $originalSlug = $slug;
        
        while ($this->slugExists($slug)) {
            $slug = $originalSlug . '-' . $counter;
            $counter++;
        }
        
        return $slug;
    }

    // Check if slug exists
    private function slugExists($slug) {
        $this->db->query("SELECT id FROM products WHERE slug = :slug LIMIT 1");
        $this->db->bind(':slug', $slug);
        $this->db->execute();
        return $this->db->rowCount() > 0;
    }

    // Get low stock products
    public function getLowStockProducts($tailorId = null, $threshold = 5) {
        $sql = "
            SELECT p.*, u.full_name as tailor_name 
            FROM products p 
            JOIN users u ON p.tailor_id = u.id 
            WHERE p.stock_quantity <= :threshold
            AND p.status = 'active'
        ";
        
        $params = [':threshold' => $threshold];
        
        if ($tailorId) {
            $sql .= " AND p.tailor_id = :tailor_id";
            $params[':tailor_id'] = $tailorId;
        }
        
        $sql .= " ORDER BY p.stock_quantity ASC LIMIT 10";
        
        $this->db->query($sql);
        foreach ($params as $key => $value) {
            $this->db->bind($key, $value);
        }
        
        return $this->db->resultSet();
    }

    // Get products with filters
    public function getProductsWithFilters($filters = [], $page = 1, $perPage = 12) {
        $offset = ($page - 1) * $perPage;
        
        $sql = "
            SELECT p.*, u.full_name as tailor_name, u.profile_pic as tailor_avatar
            FROM products p 
            JOIN users u ON p.tailor_id = u.id 
            WHERE p.status = 'active'
        ";
        
        $params = [];
        
        // Apply filters
        if (!empty($filters['category'])) {
            $sql .= " AND (p.category = :category OR p.subcategory = :category)";
            $params[':category'] = $filters['category'];
        }
        
        if (!empty($filters['tailor_id'])) {
            $sql .= " AND p.tailor_id = :tailor_id";
            $params[':tailor_id'] = $filters['tailor_id'];
        }
        
        if (!empty($filters['min_price'])) {
            $sql .= " AND p.price >= :min_price";
            $params[':min_price'] = $filters['min_price'];
        }
        
        if (!empty($filters['max_price'])) {
            $sql .= " AND p.price <= :max_price";
            $params[':max_price'] = $filters['max_price'];
        }
        
        if (!empty($filters['search'])) {
            $sql .= " AND (p.title LIKE :search OR p.description LIKE :search OR p.tags LIKE :search)";
            $params[':search'] = "%{$filters['search']}%";
        }
        
        if (!empty($filters['featured'])) {
            $sql .= " AND p.featured = 1";
        }
        
        if (!empty($filters['is_customizable'])) {
            $sql .= " AND p.is_customizable = 1";
        }
        
        // Order by
        $orderBy = 'p.created_at DESC';
        if (!empty($filters['sort'])) {
            switch ($filters['sort']) {
                case 'price_low':
                    $orderBy = 'p.price ASC';
                    break;
                case 'price_high':
                    $orderBy = 'p.price DESC';
                    break;
                case 'rating':
                    $orderBy = 'p.rating DESC';
                    break;
                case 'popular':
                    $orderBy = 'p.view_count DESC';
                    break;
            }
        }
        
        $sql .= " ORDER BY $orderBy LIMIT :limit OFFSET :offset";
        
        $this->db->query($sql);
        
        foreach ($params as $key => $value) {
            $this->db->bind($key, $value);
        }
        
        $this->db->bind(':limit', $perPage, PDO::PARAM_INT);
        $this->db->bind(':offset', $offset, PDO::PARAM_INT);
        
        return $this->db->resultSet();
    }

    // Increment view count
    public function incrementViewCount($productId) {
        $this->db->query("UPDATE products SET view_count = view_count + 1 WHERE id = :product_id");
        $this->db->bind(':product_id', $productId);
        return $this->db->execute();
    }

    // Update product rating
    public function updateProductRating($productId) {
        $this->db->query("
            UPDATE products p
            SET p.rating = (
                SELECT AVG(rating) 
                FROM reviews 
                WHERE product_id = :product_id 
                AND status = 'approved'
            ),
            p.review_count = (
                SELECT COUNT(*) 
                FROM reviews 
                WHERE product_id = :product_id 
                AND status = 'approved'
            )
            WHERE p.id = :product_id
        ");
        $this->db->bind(':product_id', $productId);
        return $this->db->execute();
    }

    // Get related products
    public function getRelatedProducts($productId, $limit = 4) {
        // First get the product to find its category
        $product = $this->getProductById($productId);
        
        if (!$product) {
            return [];
        }
        
        $this->db->query("
            SELECT p.*, u.full_name as tailor_name 
            FROM products p 
            JOIN users u ON p.tailor_id = u.id 
            WHERE p.id != :product_id 
            AND (p.category = :category OR p.tailor_id = :tailor_id)
            AND p.status = 'active'
            ORDER BY RAND()
            LIMIT :limit
        ");
        
        $this->db->bind(':product_id', $productId);
        $this->db->bind(':category', $product['category']);
        $this->db->bind(':tailor_id', $product['tailor_id']);
        $this->db->bind(':limit', $limit, PDO::PARAM_INT);
        
        return $this->db->resultSet();
    }

    // Get product statistics for tailor
    public function getProductStatistics($tailorId) {
        $stats = [];
        
        // Total products
        $this->db->query("SELECT COUNT(*) as total_products FROM products WHERE tailor_id = :tailor_id");
        $this->db->bind(':tailor_id', $tailorId);
        $result = $this->db->single();
        $stats['total_products'] = $result['total_products'] ?? 0;
        
        // Active products
        $this->db->query("SELECT COUNT(*) as active_products FROM products WHERE tailor_id = :tailor_id AND status = 'active'");
        $this->db->bind(':tailor_id', $tailorId);
        $result = $this->db->single();
        $stats['active_products'] = $result['active_products'] ?? 0;
        
        // Draft products
        $this->db->query("SELECT COUNT(*) as draft_products FROM products WHERE tailor_id = :tailor_id AND status = 'draft'");
        $this->db->bind(':tailor_id', $tailorId);
        $result = $this->db->single();
        $stats['draft_products'] = $result['draft_products'] ?? 0;
        
        // Out of stock products
        $this->db->query("SELECT COUNT(*) as out_of_stock FROM products WHERE tailor_id = :tailor_id AND status = 'out_of_stock'");
        $this->db->bind(':tailor_id', $tailorId);
        $result = $this->db->single();
        $stats['out_of_stock'] = $result['out_of_stock'] ?? 0;
        
        // Low stock products
        $this->db->query("SELECT COUNT(*) as low_stock FROM products WHERE tailor_id = :tailor_id AND stock_quantity <= low_stock_threshold AND status = 'active'");
        $this->db->bind(':tailor_id', $tailorId);
        $result = $this->db->single();
        $stats['low_stock'] = $result['low_stock'] ?? 0;
        
        // Featured products
        $this->db->query("SELECT COUNT(*) as featured_products FROM products WHERE tailor_id = :tailor_id AND featured = 1 AND status = 'active'");
        $this->db->bind(':tailor_id', $tailorId);
        $result = $this->db->single();
        $stats['featured_products'] = $result['featured_products'] ?? 0;
        
        // Customizable products
        $this->db->query("SELECT COUNT(*) as customizable_products FROM products WHERE tailor_id = :tailor_id AND is_customizable = 1 AND status = 'active'");
        $this->db->bind(':tailor_id', $tailorId);
        $result = $this->db->single();
        $stats['customizable_products'] = $result['customizable_products'] ?? 0;
        
        return $stats;
    }
}
?>