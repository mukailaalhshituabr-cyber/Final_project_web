<?php
require_once '../../config.php';
require_once '../../includes/classes/Database.php';
require_once '../../includes/classes/Order.php';

session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'customer') {
    header('Location: ../auth/login.php');
    exit();
}


$userId = $_SESSION['user_id'];
$order = new Order();

// Get orders
$status = $_GET['status'] ?? 'all';
if ($status !== 'all') {
    $orders = $order->getOrdersByCustomer($userId, $status);
} else {
    $orders = $order->getOrdersByCustomer($userId);
}


// In pages/customer/orders.php - REPLACE the Get orders section:

// Get orders - Use existing methods
$status = $_GET['status'] ?? 'all';
$allOrders = $orderObj->getRecentOrders($userId, 100); // Get many orders

// Filter by status if needed
$orders = [];
if ($status === 'all') {
    $orders = $allOrders;
} else {
    foreach ($allOrders as $order) {
        if ($order['status'] === $status) {
            $orders[] = $order;
        }
    }
}

// Get tailor names for each order
foreach ($orders as &$orderItem) {
    $orderDetails = $orderObj->getOrderById($orderItem['id']);
    $orderItem['tailor_name'] = $orderDetails['customer_name'] ?? 'Unknown Tailor';
    $orderItem['item_count'] = count($orderDetails['items'] ?? []);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Orders - Clothing Marketplace</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">
    <style>
        .order-card {
            border: 1px solid #dee2e6;
            border-radius: 10px;
            padding: 1.5rem;
            margin-bottom: 1rem;
            transition: all 0.3s;
        }
        
        .order-card:hover {
            border-color: #667eea;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .status-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }
        
        .status-pending { background: #fef3c7; color: #92400e; }
        .status-processing { background: #dbeafe; color: #1e40af; }
        .status-shipped { background: #d1fae5; color: #065f46; }
        .status-delivered { background: #dcfce7; color: #166534; }
        .status-cancelled { background: #fee2e2; color: #991b1b; }
    </style>
</head>
<body>
    <?php include '../../includes/components/navbar.php'; ?>
    
    <div class="container mt-4">
        <div class="row">
            <div class="col-12">
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                        <li class="breadcrumb-item active">My Orders</li>
                    </ol>
                </nav>
                
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h4 class="mb-0">My Orders</h4>
                        <div class="btn-group">
                            <a href="orders.php?status=all" 
                               class="btn btn-sm <?php echo $status === 'all' ? 'btn-primary' : 'btn-outline-primary'; ?>">
                                All
                            </a>
                            <a href="orders.php?status=pending" 
                               class="btn btn-sm <?php echo $status === 'pending' ? 'btn-primary' : 'btn-outline-primary'; ?>">
                                Pending
                            </a>
                            <a href="orders.php?status=processing" 
                               class="btn btn-sm <?php echo $status === 'processing' ? 'btn-primary' : 'btn-outline-primary'; ?>">
                                Processing
                            </a>
                            <a href="orders.php?status=delivered" 
                               class="btn btn-sm <?php echo $status === 'delivered' ? 'btn-primary' : 'btn-outline-primary'; ?>">
                                Delivered
                            </a>
                        </div>
                    </div>
                    
                    <div class="card-body">
                        <?php if (!empty($orders)): ?>
                            <?php foreach ($orders as $orderItem): ?>
                                <div class="order-card">
                                    <div class="row align-items-center">
                                        <div class="col-md-3">
                                            <div class="fw-bold">Order #<?php echo $orderItem['order_number']; ?></div>
                                            <div class="text-muted small">
                                                <?php echo date('M d, Y', strtotime($orderItem['created_at'])); ?>
                                            </div>
                                        </div>
                                        
                                        <div class="col-md-3">
                                            <div>Tailor: <?php echo htmlspecialchars($orderItem['tailor_name'] ?? 'N/A'); ?></div>
                                            <div class="small text-muted">
                                                Items: <?php echo $orderItem['item_count'] ?? 1; ?>
                                            </div>
                                        </div>
                                        
                                        <div class="col-md-2">
                                            <span class="status-badge status-<?php echo $orderItem['status']; ?>">
                                                <?php echo ucfirst($orderItem['status']); ?>
                                            </span>
                                        </div>
                                        
                                        <div class="col-md-2 text-end">
                                            <div class="fw-bold">$<?php echo number_format($orderItem['total_amount'], 2); ?></div>
                                        </div>
                                        
                                        <div class="col-md-2 text-end">
                                            <a href="order-details.php?id=<?php echo $orderItem['id']; ?>" 
                                               class="btn btn-outline-primary btn-sm">
                                                <i class="bi bi-eye"></i> View Details
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="text-center py-5">
                                <i class="bi bi-bag display-6 text-muted mb-3"></i>
                                <h5>No orders found</h5>
                                <p class="text-muted">You haven't placed any orders yet.</p>
                                <a href="../products/index.php" class="btn btn-primary">
                                    <i class="bi bi-bag me-2"></i> Start Shopping
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

