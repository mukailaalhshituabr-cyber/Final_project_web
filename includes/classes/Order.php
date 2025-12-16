// Get completed orders count
public function getCompletedOrdersCount($tailorId) {
    $this->db->query("SELECT COUNT(*) as count FROM orders WHERE tailor_id = :tailor_id AND status = 'completed'");
    $this->db->bind(':tailor_id', $tailorId);
    $result = $this->db->single();
    return $result['count'] ?? 0;
}

// Get total revenue by tailor
public function getTotalRevenueByTailor($tailorId) {
    $this->db->query("SELECT SUM(total_amount) as revenue FROM orders WHERE tailor_id = :tailor_id AND payment_status = 'paid'");
    $this->db->bind(':tailor_id', $tailorId);
    $result = $this->db->single();
    return $result['revenue'] ?? 0;
}

// Get recent orders by tailor
public function getRecentOrdersByTailor($tailorId, $limit = 5) {
    $this->db->query("SELECT o.*, u.full_name as customer_name, u.profile_pic as customer_pic 
                     FROM orders o 
                     JOIN users u ON o.customer_id = u.id 
                     WHERE o.tailor_id = :tailor_id 
                     ORDER BY o.created_at DESC 
                     LIMIT :limit");
    $this->db->bind(':tailor_id', $tailorId);
    $this->db->bind(':limit', $limit);
    return $this->db->resultSet();
}

// Get total count by tailor
public function getTotalCountByTailor($tailorId) {
    $this->db->query("SELECT COUNT(*) as count FROM products WHERE tailor_id = :tailor_id AND status = 'active'");
    $this->db->bind(':tailor_id', $tailorId);
    $result = $this->db->single();
    return $result['count'] ?? 0;
}

// Get low stock products
public function getLowStockProducts($tailorId, $limit = 5) {
    $this->db->query("SELECT * FROM products WHERE tailor_id = :tailor_id AND stock <= 5 AND status = 'active' ORDER BY stock ASC LIMIT :limit");
    $this->db->bind(':tailor_id', $tailorId);
    $this->db->bind(':limit', $limit);
    return $this->db->resultSet();
}
// Get recent messages for tailor
public function getRecentMessages($tailorId, $limit = 5) {
    $this->db->query("SELECT m.*, u.full_name as sender_name, u.profile_pic 
                     FROM messages m 
                     JOIN users u ON m.sender_id = u.id 
                     WHERE m.receiver_id = :tailor_id 
                     ORDER BY m.created_at DESC 
                     LIMIT :limit");
    $this->db->bind(':tailor_id', $tailorId);
    $this->db->bind(':limit', $limit);
    return $this->db->resultSet();
}


// Get tailor orders with filters
public function getTailorOrders($tailorId, $status = '', $search = '', $page = 1, $perPage = 10) {
    $where = "WHERE o.tailor_id = :tailor_id";
    $params = [':tailor_id' => $tailorId];
    
    if ($status) {
        $where .= " AND o.status = :status";
        $params[':status'] = $status;
    }
    
    if ($search) {
        $where .= " AND (o.order_number LIKE :search OR u.full_name LIKE :search OR u.email LIKE :search)";
        $params[':search'] = "%$search%";
    }
    
    $offset = ($page - 1) * $perPage;
    
    $query = "SELECT o.*, u.full_name as customer_name, u.email as customer_email 
             FROM orders o 
             JOIN users u ON o.customer_id = u.id 
             $where 
             ORDER BY o.created_at DESC 
             LIMIT :limit OFFSET :offset";
    
    $this->db->query($query);
    
    foreach ($params as $key => $value) {
        $this->db->bind($key, $value);
    }
    
    $this->db->bind(':limit', $perPage);
    $this->db->bind(':offset', $offset);
    
    return $this->db->resultSet();
}

// Get tailor orders count
public function getTailorOrdersCount($tailorId, $status = '', $search = '') {
    $where = "WHERE o.tailor_id = :tailor_id";
    $params = [':tailor_id' => $tailorId];
    
    if ($status) {
        $where .= " AND o.status = :status";
        $params[':status'] = $status;
    }
    
    if ($search) {
        $where .= " AND (o.order_number LIKE :search OR u.full_name LIKE :search OR u.email LIKE :search)";
        $params[':search'] = "%$search%";
    }
    
    $query = "SELECT COUNT(*) as count 
             FROM orders o 
             JOIN users u ON o.customer_id = u.id 
             $where";
    
    $this->db->query($query);
    
    foreach ($params as $key => $value) {
        $this->db->bind($key, $value);
    }
    
    $result = $this->db->single();
    return $result['count'] ?? 0;
}

// Update order status
public function updateStatus($orderId, $status, $notes = '') {
    $this->db->query("UPDATE orders SET status = :status, notes = CONCAT(notes, '\n', :notes) WHERE id = :id");
    $this->db->bind(':status', $status);
    $this->db->bind(':notes', date('Y-m-d H:i:s') . ": Status changed to $status. " . $notes);
    $this->db->bind(':id', $orderId);
    return $this->db->execute();
}

// Get order items
public function getOrderItems($orderId) {
    $this->db->query("SELECT oi.*, p.title, p.images, p.tailor_id 
                     FROM order_items oi 
                     JOIN products p ON oi.product_id = p.id 
                     WHERE oi.order_id = :order_id");
    $this->db->bind(':order_id', $orderId);
    return $this->db->resultSet();
}

