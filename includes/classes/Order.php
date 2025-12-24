<?php
class Order {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    // Get total orders count
    public function getTotalOrdersCount() {
        $this->db->query("SELECT COUNT(*) as count FROM orders");
        $result = $this->db->single();
        return $result['count'] ?? 0;
    }

    // Get orders by tailor
    public function getTotalOrdersByTailor($tailorId) {
        $this->db->query("SELECT COUNT(*) as count FROM orders WHERE tailor_id = :tailor_id");
        $this->db->bind(':tailor_id', $tailorId);
        $result = $this->db->single();
        return $result['count'] ?? 0;
    }

    // Get recent orders
    public function getRecentOrders($limit = 5) {
        $this->db->query("
            SELECT o.*, u.full_name as customer_name 
            FROM orders o 
            JOIN users u ON o.customer_id = u.id 
            ORDER BY o.created_at DESC 
            LIMIT :limit
        ");
        $this->db->bind(':limit', $limit, PDO::PARAM_INT);
        return $this->db->resultSet();
    }

    // Get recent orders by tailor
    public function getRecentOrdersByTailor($tailorId, $limit = 5) {
        $this->db->query("
            SELECT o.*, u.full_name as customer_name 
            FROM orders o 
            JOIN users u ON o.customer_id = u.id 
            WHERE o.tailor_id = :tailor_id
            ORDER BY o.created_at DESC 
            LIMIT :limit
        ");
        $this->db->bind(':tailor_id', $tailorId);
        $this->db->bind(':limit', $limit, PDO::PARAM_INT);
        return $this->db->resultSet();
    }

    // Get order by ID
    public function getOrderById($orderId) {
        $this->db->query("
            SELECT o.*, 
                   c.full_name as customer_name, c.email as customer_email, c.phone as customer_phone,
                   t.full_name as tailor_name, t.email as tailor_email, t.phone as tailor_phone
            FROM orders o
            LEFT JOIN users c ON o.customer_id = c.id
            LEFT JOIN users t ON o.tailor_id = t.id
            WHERE o.id = :order_id
        ");
        $this->db->bind(':order_id', $orderId);
        return $this->db->single();
    }

    // Get order items
    public function getOrderItems($orderId) {
        $this->db->query("
            SELECT oi.*, p.title, p.images, p.sku
            FROM order_items oi
            JOIN products p ON oi.product_id = p.id
            WHERE oi.order_id = :order_id
        ");
        $this->db->bind(':order_id', $orderId);
        return $this->db->resultSet();
    }

    // Create order
    public function createOrder($data) {
        try {
            $this->db->beginTransaction();

            // Generate unique order number
            $orderNumber = 'ORD' . date('YmdHis') . str_pad(rand(0, 999), 3, '0', STR_PAD_LEFT);

            // Insert order
            $orderData = [
                'order_number' => $orderNumber,
                'customer_id' => $data['customer_id'],
                'tailor_id' => $data['tailor_id'],
                'total_amount' => $data['total_amount'],
                'subtotal' => $data['subtotal'],
                'tax_amount' => $data['tax_amount'] ?? 0,
                'shipping_amount' => $data['shipping_amount'] ?? 0,
                'discount_amount' => $data['discount_amount'] ?? 0,
                'coupon_code' => $data['coupon_code'] ?? null,
                'status' => $data['status'] ?? 'pending',
                'payment_status' => $data['payment_status'] ?? 'pending',
                'payment_method' => $data['payment_method'] ?? null,
                'shipping_method' => $data['shipping_method'] ?? null,
                'shipping_address' => $data['shipping_address'] ?? '',
                'billing_address' => $data['billing_address'] ?? '',
                'customer_notes' => $data['customer_notes'] ?? '',
                'estimated_delivery' => $data['estimated_delivery'] ?? null
            ];

            if ($this->db->insert('orders', $orderData)) {
                $orderId = $this->db->lastInsertId();

                // Insert order items
                if (!empty($data['items'])) {
                    foreach ($data['items'] as $item) {
                        $itemData = [
                            'order_id' => $orderId,
                            'product_id' => $item['product_id'],
                            'quantity' => $item['quantity'],
                            'unit_price' => $item['unit_price'],
                            'total_price' => $item['total_price'],
                            'customization_details' => $item['customization_details'] ?? '',
                            'measurements' => isset($item['measurements']) ? json_encode($item['measurements']) : null
                        ];
                        $this->db->insert('order_items', $itemData);
                    }
                }

                $this->db->commit();
                return ['success' => true, 'order_id' => $orderId, 'order_number' => $orderNumber];
            }

            throw new Exception("Failed to create order");

        } catch (Exception $e) {
            $this->db->rollBack();
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    // Update order status
    public function updateOrderStatus($orderId, $status, $notes = '') {
        $allowedStatuses = ['pending', 'confirmed', 'processing', 'ready', 'shipped', 'delivered', 'cancelled', 'refunded'];
        
        if (!in_array($status, $allowedStatuses)) {
            return ['success' => false, 'error' => 'Invalid status'];
        }

        $updateData = ['status' => $status];
        
        if ($status == 'delivered') {
            $updateData['delivered_at'] = date('Y-m-d H:i:s');
        } elseif ($status == 'cancelled') {
            $updateData['cancelled_at'] = date('Y-m-d H:i:s');
        }
        
        if ($notes) {
            $updateData['tailor_notes'] = $notes;
        }

        $success = $this->db->update('orders', $updateData, ['id' => $orderId]);
        
        if ($success) {
            // Update order items status
            $itemStatus = 'pending';
            if (in_array($status, ['processing', 'confirmed'])) {
                $itemStatus = 'in_production';
            } elseif ($status == 'delivered') {
                $itemStatus = 'completed';
            } elseif ($status == 'cancelled') {
                $itemStatus = 'cancelled';
            }
            
            $this->db->update('order_items', ['status' => $itemStatus], ['order_id' => $orderId]);
        }

        return ['success' => $success];
    }

    // Update payment status
    public function updatePaymentStatus($orderId, $status, $paymentId = null, $paymentMethod = null) {
        $allowedStatuses = ['pending', 'paid', 'failed', 'refunded'];
        
        if (!in_array($status, $allowedStatuses)) {
            return ['success' => false, 'error' => 'Invalid payment status'];
        }

        $updateData = ['payment_status' => $status];
        
        if ($paymentId) {
            $updateData['payment_id'] = $paymentId;
        }
        
        if ($paymentMethod) {
            $updateData['payment_method'] = $paymentMethod;
        }

        $success = $this->db->update('orders', $updateData, ['id' => $orderId]);
        return ['success' => $success];
    }

    // Get customer orders
    public function getCustomerOrders($customerId, $status = null) {
        $sql = "
            SELECT o.*, u.full_name as tailor_name, u.profile_pic as tailor_avatar
            FROM orders o
            JOIN users u ON o.tailor_id = u.id
            WHERE o.customer_id = :customer_id
        ";
        
        $params = [':customer_id' => $customerId];
        
        if ($status) {
            $sql .= " AND o.status = :status";
            $params[':status'] = $status;
        }
        
        $sql .= " ORDER BY o.created_at DESC";
        
        $this->db->query($sql);
        foreach ($params as $key => $value) {
            $this->db->bind($key, $value);
        }
        
        return $this->db->resultSet();
    }

    // Get tailor orders
    public function getTailorOrders($tailorId, $status = null) {
        $sql = "
            SELECT o.*, u.full_name as customer_name
            FROM orders o
            JOIN users u ON o.customer_id = u.id
            WHERE o.tailor_id = :tailor_id
        ";
        
        $params = [':tailor_id' => $tailorId];
        
        if ($status) {
            $sql .= " AND o.status = :status";
            $params[':status'] = $status;
        }
        
        $sql .= " ORDER BY o.created_at DESC";
        
        $this->db->query($sql);
        foreach ($params as $key => $value) {
            $this->db->bind($key, $value);
        }
        
        return $this->db->resultSet();
    }

    // Get orders with filters (admin)
    public function getOrdersWithFilters($filters = [], $page = 1, $perPage = 20) {
        $offset = ($page - 1) * $perPage;
        
        $sql = "
            SELECT o.*, 
                   c.full_name as customer_name, c.email as customer_email,
                   t.full_name as tailor_name, t.email as tailor_email
            FROM orders o
            LEFT JOIN users c ON o.customer_id = c.id
            LEFT JOIN users t ON o.tailor_id = t.id
            WHERE 1=1
        ";
        
        $params = [];
        
        // Apply filters
        if (!empty($filters['status'])) {
            $sql .= " AND o.status = :status";
            $params[':status'] = $filters['status'];
        }
        
        if (!empty($filters['payment_status'])) {
            $sql .= " AND o.payment_status = :payment_status";
            $params[':payment_status'] = $filters['payment_status'];
        }
        
        if (!empty($filters['date_from'])) {
            $sql .= " AND DATE(o.created_at) >= :date_from";
            $params[':date_from'] = $filters['date_from'];
        }
        
        if (!empty($filters['date_to'])) {
            $sql .= " AND DATE(o.created_at) <= :date_to";
            $params[':date_to'] = $filters['date_to'];
        }
        
        if (!empty($filters['search'])) {
            $sql .= " AND (o.order_number LIKE :search OR c.full_name LIKE :search OR t.full_name LIKE :search)";
            $params[':search'] = "%{$filters['search']}%";
        }
        
        $sql .= " ORDER BY o.created_at DESC LIMIT :limit OFFSET :offset";
        
        $this->db->query($sql);
        
        foreach ($params as $key => $value) {
            $this->db->bind($key, $value);
        }
        
        $this->db->bind(':limit', $perPage, PDO::PARAM_INT);
        $this->db->bind(':offset', $offset, PDO::PARAM_INT);
        
        return $this->db->resultSet();
    }

    // Get order statistics
    public function getOrderStatistics($tailorId = null) {
        $stats = [];
        
        // Total orders
        $sql = "SELECT COUNT(*) as total_orders FROM orders";
        if ($tailorId) {
            $sql .= " WHERE tailor_id = :tailor_id";
        }
        $this->db->query($sql);
        if ($tailorId) {
            $this->db->bind(':tailor_id', $tailorId);
        }
        $result = $this->db->single();
        $stats['total_orders'] = $result['total_orders'] ?? 0;
        
        // Total revenue
        $sql = "SELECT SUM(total_amount) as total_revenue FROM orders WHERE payment_status = 'paid'";
        if ($tailorId) {
            $sql .= " AND tailor_id = :tailor_id";
        }
        $this->db->query($sql);
        if ($tailorId) {
            $this->db->bind(':tailor_id', $tailorId);
        }
        $result = $this->db->single();
        $stats['total_revenue'] = $result['total_revenue'] ?? 0;
        
        // Orders by status
        $sql = "SELECT status, COUNT(*) as count FROM orders";
        if ($tailorId) {
            $sql .= " WHERE tailor_id = :tailor_id";
        }
        $sql .= " GROUP BY status";
        $this->db->query($sql);
        if ($tailorId) {
            $this->db->bind(':tailor_id', $tailorId);
        }
        $statusResults = $this->db->resultSet();
        
        $stats['orders_by_status'] = [];
        foreach ($statusResults as $row) {
            $stats['orders_by_status'][$row['status']] = $row['count'];
        }
        
        // Today's orders
        $sql = "SELECT COUNT(*) as today_orders FROM orders WHERE DATE(created_at) = CURDATE()";
        if ($tailorId) {
            $sql .= " AND tailor_id = :tailor_id";
        }
        $this->db->query($sql);
        if ($tailorId) {
            $this->db->bind(':tailor_id', $tailorId);
        }
        $result = $this->db->single();
        $stats['today_orders'] = $result['today_orders'] ?? 0;
        
        return $stats;
    }
}
?>