<?php
class Order {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    // Get all orders for a tailor
    public function getOrdersByTailor($tailorId, $status = null, $limit = null) {
        $sql = "SELECT o.*, u.full_name as customer_name, u.email as customer_email 
                FROM orders o 
                LEFT JOIN users u ON o.customer_id = u.id 
                WHERE o.tailor_id = :tailor_id";
        
        if ($status) {
            $sql .= " AND o.status = :status";
        }
        
        $sql .= " ORDER BY o.created_at DESC";
        
        if ($limit) {
            $sql .= " LIMIT :limit";
        }
        
        $this->db->query($sql);
        $this->db->bind(':tailor_id', $tailorId);
        
        if ($status) {
            $this->db->bind(':status', $status);
        }
        
        if ($limit) {
            $this->db->bind(':limit', (int)$limit);
        }
        
        return $this->db->resultSet();
    }
    
    // Get single order with details
    public function getOrderById($orderId, $tailorId = null) {
        $sql = "SELECT o.*, u.full_name as customer_name, u.email as customer_email, 
                       u.phone as customer_phone, u.address as customer_address
                FROM orders o 
                LEFT JOIN users u ON o.customer_id = u.id 
                WHERE o.id = :order_id";
        
        if ($tailorId) {
            $sql .= " AND o.tailor_id = :tailor_id";
        }
        
        $this->db->query($sql);
        $this->db->bind(':order_id', $orderId);
        
        if ($tailorId) {
            $this->db->bind(':tailor_id', $tailorId);
        }
        
        $order = $this->db->single();
        
        if ($order) {
            // Get order items
            $this->db->query("SELECT oi.*, p.title, p.images 
                             FROM order_items oi 
                             LEFT JOIN products p ON oi.product_id = p.id 
                             WHERE oi.order_id = :order_id");
            $this->db->bind(':order_id', $orderId);
            $order['items'] = $this->db->resultSet();
            
            // Get order status history
            $this->db->query("SELECT * FROM order_status_history 
                             WHERE order_id = :order_id 
                             ORDER BY created_at DESC");
            $this->db->bind(':order_id', $orderId);
            $order['status_history'] = $this->db->resultSet();
        }
        
        return $order;
    }
    
    // Update order status
    public function updateOrderStatus($orderId, $status, $notes = null) {
        try {
            $this->db->beginTransaction();
            
            // Update order status
            $this->db->query("UPDATE orders SET status = :status, updated_at = NOW() WHERE id = :id");
            $this->db->bind(':status', $status);
            $this->db->bind(':id', $orderId);
            $this->db->execute();
            
            // Add to status history
            $this->db->query("INSERT INTO order_status_history (order_id, status, notes, created_at) 
                             VALUES (:order_id, :status, :notes, NOW())");
            $this->db->bind(':order_id', $orderId);
            $this->db->bind(':status', $status);
            $this->db->bind(':notes', $notes);
            $this->db->execute();
            
            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            error_log("Order status update error: " . $e->getMessage());
            return false;
        }
    }
    
    // Get total orders count
    public function getTotalOrdersByTailor($tailorId) {
        $this->db->query("SELECT COUNT(*) as total FROM orders WHERE tailor_id = :tailor_id");
        $this->db->bind(':tailor_id', $tailorId);
        $result = $this->db->single();
        return $result['total'] ?? 0;
    }
    
    // Get pending orders count
    public function getPendingOrdersCount($tailorId) {
        $this->db->query("SELECT COUNT(*) as count FROM orders WHERE tailor_id = :tailor_id AND status = 'pending'");
        $this->db->bind(':tailor_id', $tailorId);
        $result = $this->db->single();
        return $result['count'] ?? 0;
    }
    
    // Get completed orders count
    public function getCompletedOrdersCount($tailorId) {
        $this->db->query("SELECT COUNT(*) as count FROM orders WHERE tailor_id = :tailor_id AND status = 'completed'");
        $this->db->bind(':tailor_id', $tailorId);
        $result = $this->db->single();
        return $result['count'] ?? 0;
    }
    
    // Get total revenue
    public function getTotalRevenueByTailor($tailorId) {
        $this->db->query("SELECT SUM(total_amount) as revenue FROM orders WHERE tailor_id = :tailor_id AND status = 'completed'");
        $this->db->bind(':tailor_id', $tailorId);
        $result = $this->db->single();
        return $result['revenue'] ?? 0;
    }
    
    // Get recent orders
    public function getRecentOrdersByTailor($tailorId, $limit = 5) {
        $this->db->query("SELECT o.*, u.full_name as customer_name 
                         FROM orders o 
                         LEFT JOIN users u ON o.customer_id = u.id 
                         WHERE o.tailor_id = :tailor_id 
                         ORDER BY o.created_at DESC 
                         LIMIT :limit");
        $this->db->bind(':tailor_id', $tailorId);
        $this->db->bind(':limit', (int)$limit);
        return $this->db->resultSet();
    }
    
    // Get orders statistics by month
    public function getOrdersStatistics($tailorId, $months = 6) {
        $this->db->query("
            SELECT 
                DATE_FORMAT(created_at, '%Y-%m') as month,
                COUNT(*) as order_count,
                SUM(total_amount) as revenue,
                AVG(total_amount) as average_order_value
            FROM orders 
            WHERE tailor_id = :tailor_id 
            AND created_at >= DATE_SUB(NOW(), INTERVAL :months MONTH)
            GROUP BY DATE_FORMAT(created_at, '%Y-%m')
            ORDER BY month DESC
        ");
        $this->db->bind(':tailor_id', $tailorId);
        $this->db->bind(':months', (int)$months);
        return $this->db->resultSet();
    }
    
    // Search orders
    public function searchOrders($tailorId, $searchTerm, $status = null) {
        $sql = "SELECT o.*, u.full_name as customer_name 
                FROM orders o 
                LEFT JOIN users u ON o.customer_id = u.id 
                WHERE o.tailor_id = :tailor_id 
                AND (o.order_number LIKE :search 
                     OR u.full_name LIKE :search 
                     OR u.email LIKE :search)";
        
        if ($status) {
            $sql .= " AND o.status = :status";
        }
        
        $sql .= " ORDER BY o.created_at DESC";
        
        $this->db->query($sql);
        $this->db->bind(':tailor_id', $tailorId);
        $this->db->bind(':search', '%' . $searchTerm . '%');
        
        if ($status) {
            $this->db->bind(':status', $status);
        }
        
        return $this->db->resultSet();
    }
    
    // Get orders by date range
    public function getOrdersByDateRange($tailorId, $startDate, $endDate) {
        $this->db->query("SELECT o.*, u.full_name as customer_name 
                         FROM orders o 
                         LEFT JOIN users u ON o.customer_id = u.id 
                         WHERE o.tailor_id = :tailor_id 
                         AND DATE(o.created_at) BETWEEN :start_date AND :end_date 
                         ORDER BY o.created_at DESC");
        $this->db->bind(':tailor_id', $tailorId);
        $this->db->bind(':start_date', $startDate);
        $this->db->bind(':end_date', $endDate);
        return $this->db->resultSet();
    }

    // Get recent orders for a customer
    public function getRecentOrders($userId, $limit = 5) {
        $this->db->query("SELECT * FROM orders 
                        WHERE customer_id = :user_id 
                        ORDER BY created_at DESC 
                        LIMIT :limit");
        
        $this->db->bind(':user_id', $userId);
        $this->db->bind(':limit', (int)$limit);
        
        return $this->db->resultSet();
    }
}
?>



<?php
/*class Order {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    // Get all orders for a tailor
    public function getOrdersByTailor($tailorId, $status = null, $limit = null) {
        $sql = "SELECT o.*, u.full_name as customer_name, u.email as customer_email 
                FROM orders o 
                LEFT JOIN users u ON o.customer_id = u.id 
                WHERE o.tailor_id = :tailor_id";
        
        if ($status) {
            $sql .= " AND o.status = :status";
        }
        
        $sql .= " ORDER BY o.created_at DESC";
        
        if ($limit) {
            $sql .= " LIMIT :limit";
        }
        
        $this->db->query($sql);
        $this->db->bind(':tailor_id', $tailorId);
        
        if ($status) {
            $this->db->bind(':status', $status);
        }
        
        if ($limit) {
            $this->db->bind(':limit', $limit);
        }
        
        return $this->db->resultSet();
    }
    
    // Get single order with details
    public function getOrderById($orderId, $tailorId = null) {
        $sql = "SELECT o.*, u.full_name as customer_name, u.email as customer_email, 
                       u.phone as customer_phone, u.address as customer_address
                FROM orders o 
                LEFT JOIN users u ON o.customer_id = u.id 
                WHERE o.id = :order_id";
        
        if ($tailorId) {
            $sql .= " AND o.tailor_id = :tailor_id";
        }
        
        $this->db->query($sql);
        $this->db->bind(':order_id', $orderId);
        
        if ($tailorId) {
            $this->db->bind(':tailor_id', $tailorId);
        }
        
        $order = $this->db->single();
        
        if ($order) {
            // Get order items
            $this->db->query("SELECT oi.*, p.title, p.images 
                             FROM order_items oi 
                             LEFT JOIN products p ON oi.product_id = p.id 
                             WHERE oi.order_id = :order_id");
            $this->db->bind(':order_id', $orderId);
            $order['items'] = $this->db->resultSet();
            
            // Get order status history
            $this->db->query("SELECT * FROM order_status_history 
                             WHERE order_id = :order_id 
                             ORDER BY created_at DESC");
            $this->db->bind(':order_id', $orderId);
            $order['status_history'] = $this->db->resultSet();
        }
        
        return $order;
    }
    
    // Update order status
    public function updateOrderStatus($orderId, $status, $notes = null) {
        try {
            // Begin transaction
            $this->db->beginTransaction();
            
            // Update order status
            $this->db->query("UPDATE orders SET status = :status, updated_at = NOW() WHERE id = :id");
            $this->db->bind(':status', $status);
            $this->db->bind(':id', $orderId);
            $this->db->execute();
            
            // Add to status history
            $this->db->query("INSERT INTO order_status_history (order_id, status, notes, created_at) 
                             VALUES (:order_id, :status, :notes, NOW())");
            $this->db->bind(':order_id', $orderId);
            $this->db->bind(':status', $status);
            $this->db->bind(':notes', $notes);
            $this->db->execute();
            
            // Commit transaction
            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            return false;
        }
    }
    
    // Get total orders count
    public function getTotalOrdersByTailor($tailorId) {
        $this->db->query("SELECT COUNT(*) as total FROM orders WHERE tailor_id = :tailor_id");
        $this->db->bind(':tailor_id', $tailorId);
        $result = $this->db->single();
        return $result['total'] ?? 0;
    }
    
    // Get pending orders count
    public function getPendingOrdersCount($tailorId) {
        $this->db->query("SELECT COUNT(*) as count FROM orders WHERE tailor_id = :tailor_id AND status = 'pending'");
        $this->db->bind(':tailor_id', $tailorId);
        $result = $this->db->single();
        return $result['count'] ?? 0;
    }
    
    // Get completed orders count
    public function getCompletedOrdersCount($tailorId) {
        $this->db->query("SELECT COUNT(*) as count FROM orders WHERE tailor_id = :tailor_id AND status = 'completed'");
        $this->db->bind(':tailor_id', $tailorId);
        $result = $this->db->single();
        return $result['count'] ?? 0;
    }
    
    // Get total revenue
    public function getTotalRevenueByTailor($tailorId) {
        $this->db->query("SELECT SUM(total_amount) as revenue FROM orders WHERE tailor_id = :tailor_id AND status = 'completed'");
        $this->db->bind(':tailor_id', $tailorId);
        $result = $this->db->single();
        return $result['revenue'] ?? 0;
    }
    
    // Get recent orders
    public function getRecentOrdersByTailor($tailorId, $limit = 5) {
        $this->db->query("SELECT o.*, u.full_name as customer_name 
                         FROM orders o 
                         LEFT JOIN users u ON o.customer_id = u.id 
                         WHERE o.tailor_id = :tailor_id 
                         ORDER BY o.created_at DESC 
                         LIMIT :limit");
        $this->db->bind(':tailor_id', $tailorId);
        $this->db->bind(':limit', $limit);
        return $this->db->resultSet();
    }
    
    // Get orders statistics by month
    public function getOrdersStatistics($tailorId, $months = 6) {
        $this->db->query("
            SELECT 
                DATE_FORMAT(created_at, '%Y-%m') as month,
                COUNT(*) as order_count,
                SUM(total_amount) as revenue,
                AVG(total_amount) as average_order_value
            FROM orders 
            WHERE tailor_id = :tailor_id 
            AND created_at >= DATE_SUB(NOW(), INTERVAL :months MONTH)
            GROUP BY DATE_FORMAT(created_at, '%Y-%m')
            ORDER BY month DESC
        ");
        $this->db->bind(':tailor_id', $tailorId);
        $this->db->bind(':months', $months);
        return $this->db->resultSet();
    }
    
    // Search orders
    public function searchOrders($tailorId, $searchTerm, $status = null) {
        $sql = "SELECT o.*, u.full_name as customer_name 
                FROM orders o 
                LEFT JOIN users u ON o.customer_id = u.id 
                WHERE o.tailor_id = :tailor_id 
                AND (o.order_number LIKE :search 
                     OR u.full_name LIKE :search 
                     OR u.email LIKE :search)";
        
        if ($status) {
            $sql .= " AND o.status = :status";
        }
        
        $sql .= " ORDER BY o.created_at DESC";
        
        $this->db->query($sql);
        $this->db->bind(':tailor_id', $tailorId);
        $this->db->bind(':search', '%' . $searchTerm . '%');
        
        if ($status) {
            $this->db->bind(':status', $status);
        }
        
        return $this->db->resultSet();
    }
    
    // Get orders by date range
    public function getOrdersByDateRange($tailorId, $startDate, $endDate) {
        $this->db->query("SELECT o.*, u.full_name as customer_name 
                         FROM orders o 
                         LEFT JOIN users u ON o.customer_id = u.id 
                         WHERE o.tailor_id = :tailor_id 
                         AND DATE(o.created_at) BETWEEN :start_date AND :end_date 
                         ORDER BY o.created_at DESC");
        $this->db->bind(':tailor_id', $tailorId);
        $this->db->bind(':start_date', $startDate);
        $this->db->bind(':end_date', $endDate);
        return $this->db->resultSet();
    }

    public function getRecentOrders($userId, $limit = 5) {
        // UPDATED: Changed user_id to customer_id to match your new table
        $this->db->query("SELECT * FROM orders 
                        WHERE customer_id = :user_id 
                        ORDER BY created_at DESC 
                        LIMIT :limit");
        
        $this->db->bind(':user_id', $userId);
        $this->db->bind(':limit', $limit);
        
        return $this->db->resultSet();
    }
}
?>
*/