<?php
require_once dirname(__DIR__, 2) . '/config.php';
require_once ROOT_PATH . '/includes/classes/Database.php';
require_once ROOT_PATH . '/includes/classes/Order.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'tailor') {
    header('Location: ../auth/login.php');
    exit();
}

$db = Database::getInstance();
$orderObj = new Order();
$tailorId = $_SESSION['user_id'];

// Handle order status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $orderId = intval($_POST['order_id']);
    $status = $_POST['status'];
    $notes = trim($_POST['notes'] ?? '');
    
    $result = $orderObj->updateOrderStatus($orderId, $status, $notes);
    
    if ($result['success']) {
        header('Location: orders.php?updated=1');
    } else {
        header('Location: orders.php?error=' . urlencode($result['error']));
    }
    exit();
}

// Get filter parameters
$status = $_GET['status'] ?? '';
$search = $_GET['search'] ?? '';
$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';
$page = max(1, intval($_GET['page'] ?? 1));
$perPage = 10;

// Get orders with filters
$filters = ['tailor_id' => $tailorId];
if ($status) $filters['status'] = $status;
if ($search) $filters['search'] = $search;
if ($date_from) $filters['date_from'] = $date_from;
if ($date_to) $filters['date_to'] = $date_to;

$allOrders = $orderObj->getTailorOrders($tailorId, $status ?: null);
$totalOrders = count($allOrders);
$totalPages = ceil($totalOrders / $perPage);
$offset = ($page - 1) * $perPage;

// Get paginated orders
$sql = "
    SELECT o.*, u.full_name as customer_name, u.email as customer_email, u.phone as customer_phone
    FROM orders o
    JOIN users u ON o.customer_id = u.id
    WHERE o.tailor_id = :tailor_id
";

$params = [':tailor_id' => $tailorId];

if ($status) {
    $sql .= " AND o.status = :status";
    $params[':status'] = $status;
}

if ($search) {
    $sql .= " AND (o.order_number LIKE :search OR u.full_name LIKE :search OR u.email LIKE :search)";
    $params[':search'] = "%$search%";
}

if ($date_from) {
    $sql .= " AND DATE(o.created_at) >= :date_from";
    $params[':date_from'] = $date_from;
}

if ($date_to) {
    $sql .= " AND DATE(o.created_at) <= :date_to";
    $params[':date_to'] = $date_to;
}

$sql .= " ORDER BY o.created_at DESC LIMIT :limit OFFSET :offset";

$db->query($sql);

foreach ($params as $key => $value) {
    $db->bind($key, $value);
}

$db->bind(':limit', $perPage, PDO::PARAM_INT);
$db->bind(':offset', $offset, PDO::PARAM_INT);

$orders = $db->resultSet();

// Get order statistics
$stats = $orderObj->getOrderStatistics($tailorId);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Orders - <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">
    <style>
        .orders-container {
            min-height: calc(100vh - 200px);
        }
        .stats-card {
            border-radius: 10px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            transition: transform 0.3s ease;
        }
        .stats-card:hover {
            transform: translateY(-5px);
        }
        .stats-icon {
            width: 50px;
            height: 50px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
        }
        .order-card {
            border-radius: 10px;
            border: 1px solid #e9ecef;
            transition: all 0.3s ease;
            margin-bottom: 1.5rem;
        }
        .order-card:hover {
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            transform: translateY(-2px);
        }
        .order-header {
            background: #f8f9fa;
            padding: 1rem 1.5rem;
            border-bottom: 1px solid #e9ecef;
            border-radius: 10px 10px 0 0;
        }
        .badge-status {
            padding: 0.5rem 1rem;
            font-size: 0.875rem;
            font-weight: 600;
        }
        .product-img-sm {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: 8px;
        }
        .empty-state {
            text-align: center;
            padding: 4rem 1rem;
        }
        .empty-state-icon {
            font-size: 4rem;
            color: #dee2e6;
            margin-bottom: 1rem;
        }
        .timeline {
            position: relative;
            padding-left: 30px;
        }
        .timeline::before {
            content: '';
            position: absolute;
            left: 15px;
            top: 0;
            bottom: 0;
            width: 2px;
            background: #e9ecef;
        }
        .timeline-item {
            position: relative;
            margin-bottom: 1rem;
        }
        .timeline-item::before {
            content: '';
            position: absolute;
            left: -23px;
            top: 5px;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background: #667eea;
            border: 2px solid white;
        }
    </style>
</head>
<body>
    <?php include '../../includes/components/navbar.php'; ?>
    
    <div class="container orders-container py-5">
        <!-- Header -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center">
                    <h1 class="h3 fw-bold">My Orders</h1>
                    <a href="orders-export.php" class="btn btn-outline-secondary">
                        <i class="bi bi-download me-2"></i> Export Orders
                    </a>
                </div>
            </div>
        </div>

        <!-- Messages -->
        <?php if (isset($_GET['updated'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle me-2"></i>Order status updated successfully
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>
        
        <?php if (isset($_GET['error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="bi bi-exclamation-triangle me-2"></i><?php echo htmlspecialchars($_GET['error']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <!-- Stats Cards -->
        <div class="row mb-4">
            <div class="col-xl-3 col-md-6 mb-3">
                <div class="stats-card bg-white shadow-sm border">
                    <div class="d-flex align-items-center">
                        <div class="stats-icon me-3 bg-primary bg-opacity-10 text-primary">
                            <i class="bi bi-bag"></i>
                        </div>
                        <div>
                            <h3 class="fw-bold mb-0"><?php echo $stats['total_orders']; ?></h3>
                            <p class="text-muted mb-0">Total Orders</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6 mb-3">
                <div class="stats-card bg-white shadow-sm border">
                    <div class="d-flex align-items-center">
                        <div class="stats-icon me-3 bg-warning bg-opacity-10 text-warning">
                            <i class="bi bi-clock"></i>
                        </div>
                        <div>
                            <h3 class="fw-bold mb-0"><?php echo $stats['orders_by_status']['pending'] ?? 0; ?></h3>
                            <p class="text-muted mb-0">Pending</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6 mb-3">
                <div class="stats-card bg-white shadow-sm border">
                    <div class="d-flex align-items-center">
                        <div class="stats-icon me-3 bg-success bg-opacity-10 text-success">
                            <i class="bi bi-currency-dollar"></i>
                        </div>
                        <div>
                            <h3 class="fw-bold mb-0"><?php echo format_price($stats['total_revenue']); ?></h3>
                            <p class="text-muted mb-0">Total Revenue</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6 mb-3">
                <div class="stats-card bg-white shadow-sm border">
                    <div class="d-flex align-items-center">
                        <div class="stats-icon me-3 bg-info bg-opacity-10 text-info">
                            <i class="bi bi-calendar-day"></i>
                        </div>
                        <div>
                            <h3 class="fw-bold mb-0"><?php echo $stats['today_orders']; ?></h3>
                            <p class="text-muted mb-0">Today's Orders</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <form method="GET" action="" class="row g-3">
                            <div class="col-md-2">
                                <label class="form-label">Status</label>
                                <select class="form-select" name="status">
                                    <option value="">All Status</option>
                                    <option value="pending" <?php echo $status == 'pending' ? 'selected' : ''; ?>>Pending</option>
                                    <option value="confirmed" <?php echo $status == 'confirmed' ? 'selected' : ''; ?>>Confirmed</option>
                                    <option value="processing" <?php echo $status == 'processing' ? 'selected' : ''; ?>>Processing</option>
                                    <option value="ready" <?php echo $status == 'ready' ? 'selected' : ''; ?>>Ready</option>
                                    <option value="shipped" <?php echo $status == 'shipped' ? 'selected' : ''; ?>>Shipped</option>
                                    <option value="delivered" <?php echo $status == 'delivered' ? 'selected' : ''; ?>>Delivered</option>
                                    <option value="cancelled" <?php echo $status == 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Date From</label>
                                <input type="date" class="form-control" name="date_from" value="<?php echo htmlspecialchars($date_from); ?>">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Date To</label>
                                <input type="date" class="form-control" name="date_to" value="<?php echo htmlspecialchars($date_to); ?>">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Search</label>
                                <input type="text" class="form-control" name="search" 
                                       placeholder="Search by order #, customer..." 
                                       value="<?php echo htmlspecialchars($search); ?>">
                            </div>
                            <div class="col-md-1 d-flex align-items-end">
                                <button type="submit" class="btn btn-primary w-100">
                                    <i class="bi bi-filter"></i>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Orders List -->
        <?php if (!empty($orders)): ?>
            <?php foreach ($orders as $order): 
                $orderItems = $orderObj->getOrderItems($order['id']);
            ?>
            <div class="order-card">
                <div class="order-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="fw-bold mb-1">
                                Order #<?php echo $order['order_number']; ?>
                                <?php if ($order['payment_status'] == 'paid'): ?>
                                <i class="bi bi-check-circle-fill text-success ms-2" title="Paid"></i>
                                <?php elseif ($order['payment_status'] == 'pending'): ?>
                                <i class="bi bi-clock text-warning ms-2" title="Pending Payment"></i>
                                <?php endif; ?>
                            </h6>
                            <small class="text-muted">
                                Placed on <?php echo date('F d, Y h:i A', strtotime($order['created_at'])); ?>
                            </small>
                        </div>
                        <div class="text-end">
                            <div class="fw-bold h5 mb-2"><?php echo format_price($order['total_amount']); ?></div>
                            <span class="badge-status badge bg-<?php echo get_order_status_badge($order['status']); ?>">
                                <?php echo ucfirst($order['status']); ?>
                            </span>
                        </div>
                    </div>
                </div>
                
                <div class="p-4">
                    <!-- Customer Info -->
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <h6 class="fw-bold mb-3">Customer Information</h6>
                            <div class="d-flex align-items-center mb-2">
                                <i class="bi bi-person me-3 text-primary"></i>
                                <div>
                                    <div class="fw-bold"><?php echo htmlspecialchars($order['customer_name']); ?></div>
                                    <small class="text-muted">Customer</small>
                                </div>
                            </div>
                            <div class="d-flex align-items-center mb-2">
                                <i class="bi bi-envelope me-3 text-primary"></i>
                                <div>
                                    <div><?php echo htmlspecialchars($order['customer_email']); ?></div>
                                    <small class="text-muted">Email</small>
                                </div>
                            </div>
                            <?php if ($order['customer_phone']): ?>
                            <div class="d-flex align-items-center">
                                <i class="bi bi-telephone me-3 text-primary"></i>
                                <div>
                                    <div><?php echo htmlspecialchars($order['customer_phone']); ?></div>
                                    <small class="text-muted">Phone</small>
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>
                        <div class="col-md-6">
                            <h6 class="fw-bold mb-3">Order Details</h6>
                            <div class="row g-2">
                                <div class="col-6">
                                    <small class="text-muted d-block">Payment Status</small>
                                    <span class="badge bg-<?php echo get_payment_status_badge($order['payment_status']); ?>">
                                        <?php echo ucfirst($order['payment_status']); ?>
                                    </span>
                                </div>
                                <div class="col-6">
                                    <small class="text-muted d-block">Payment Method</small>
                                    <span><?php echo $order['payment_method'] ?: 'N/A'; ?></span>
                                </div>
                                <?php if ($order['estimated_delivery']): ?>
                                <div class="col-12 mt-2">
                                    <small class="text-muted d-block">Estimated Delivery</small>
                                    <span><?php echo date('F d, Y', strtotime($order['estimated_delivery'])); ?></span>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Order Items -->
                    <h6 class="fw-bold mb-3">Order Items</h6>
                    <div class="table-responsive mb-4">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Product</th>
                                    <th>Quantity</th>
                                    <th>Unit Price</th>
                                    <th>Total</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($orderItems as $item): 
                                    $images = json_decode($item['images'] ?? '[]', true);
                                    $productImage = !empty($images) && is_array($images) ? $images[0] : ASSETS_URL . 'images/products/default.jpg';
                                ?>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <img src="<?php echo htmlspecialchars($productImage); ?>" 
                                                 class="product-img-sm me-3"
                                                 alt="Product"
                                                 onerror="this.src='<?php echo ASSETS_URL; ?>images/products/default.jpg'">
                                            <div>
                                                <div class="fw-bold"><?php echo htmlspecialchars($item['title']); ?></div>
                                                <?php if ($item['sku']): ?>
                                                <small class="text-muted">SKU: <?php echo $item['sku']; ?></small>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </td>
                                    <td><?php echo $item['quantity']; ?></td>
                                    <td><?php echo format_price($item['unit_price']); ?></td>
                                    <td><?php echo format_price($item['total_price']); ?></td>
                                    <td>
                                        <span class="badge bg-<?php echo get_order_status_badge($item['status']); ?>">
                                            <?php echo ucfirst($item['status']); ?>
                                        </span>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td colspan="3" class="text-end fw-bold">Subtotal:</td>
                                    <td colspan="2"><?php echo format_price($order['subtotal']); ?></td>
                                </tr>
                                <?php if ($order['shipping_amount'] > 0): ?>
                                <tr>
                                    <td colspan="3" class="text-end fw-bold">Shipping:</td>
                                    <td colspan="2"><?php echo format_price($order['shipping_amount']); ?></td>
                                </tr>
                                <?php endif; ?>
                                <?php if ($order['tax_amount'] > 0): ?>
                                <tr>
                                    <td colspan="3" class="text-end fw-bold">Tax:</td>
                                    <td colspan="2"><?php echo format_price($order['tax_amount']); ?></td>
                                </tr>
                                <?php endif; ?>
                                <?php if ($order['discount_amount'] > 0): ?>
                                <tr>
                                    <td colspan="3" class="text-end fw-bold">Discount:</td>
                                    <td colspan="2">-<?php echo format_price($order['discount_amount']); ?></td>
                                </tr>
                                <?php endif; ?>
                                <tr>
                                    <td colspan="3" class="text-end fw-bold">Total:</td>
                                    <td colspan="2" class="fw-bold text-primary"><?php echo format_price($order['total_amount']); ?></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                    
                    <!-- Customer Notes -->
                    <?php if ($order['customer_notes']): ?>
                    <div class="mb-4">
                        <h6 class="fw-bold mb-2">Customer Notes</h6>
                        <div class="alert alert-light border">
                            <?php echo nl2br(htmlspecialchars($order['customer_notes'])); ?>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Tailor Notes -->
                    <?php if ($order['tailor_notes']): ?>
                    <div class="mb-4">
                        <h6 class="fw-bold mb-2">Your Notes</h6>
                        <div class="alert alert-info border">
                            <?php echo nl2br(htmlspecialchars($order['tailor_notes'])); ?>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Shipping Address -->
                    <?php if ($order['shipping_address']): ?>
                    <div class="mb-4">
                        <h6 class="fw-bold mb-2">Shipping Address</h6>
                        <div class="alert alert-light border">
                            <?php echo nl2br(htmlspecialchars($order['shipping_address'])); ?>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Order Actions -->
                    <div class="d-flex justify-content-between align-items-center border-top pt-4">
                        <div>
                            <a href="messages.php?user_id=<?php echo $order['customer_id']; ?>" 
                               class="btn btn-outline-primary btn-sm me-2">
                                <i class="bi bi-chat-dots me-1"></i> Message Customer
                            </a>
                            <a href="print-order.php?id=<?php echo $order['id']; ?>" 
                               class="btn btn-outline-secondary btn-sm"
                               target="_blank">
                                <i class="bi bi-printer me-1"></i> Print Order
                            </a>
                        </div>
                        
                        <!-- Status Update Form -->
                        <?php if (!in_array($order['status'], ['delivered', 'cancelled', 'refunded'])): ?>
                        <form method="POST" action="" class="d-flex gap-2 align-items-center">
                            <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                            <div>
                                <select class="form-select form-select-sm" name="status" required>
                                    <option value="">Update Status</option>
                                    <option value="confirmed" <?php echo $order['status'] == 'confirmed' ? 'selected' : ''; ?>>Confirmed</option>
                                    <option value="processing" <?php echo $order['status'] == 'processing' ? 'selected' : ''; ?>>Processing</option>
                                    <option value="ready" <?php echo $order['status'] == 'ready' ? 'selected' : ''; ?>>Ready</option>
                                    <option value="shipped" <?php echo $order['status'] == 'shipped' ? 'selected' : ''; ?>>Shipped</option>
                                    <option value="delivered" <?php echo $order['status'] == 'delivered' ? 'selected' : ''; ?>>Delivered</option>
                                    <option value="cancelled" <?php echo $order['status'] == 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                                </select>
                            </div>
                            <div>
                                <input type="text" class="form-control form-control-sm" name="notes" 
                                       placeholder="Add notes (optional)">
                            </div>
                            <div>
                                <button type="submit" name="update_status" class="btn btn-primary btn-sm">
                                    <i class="bi bi-check-circle me-1"></i> Update
                                </button>
                            </div>
                        </form>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>

            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
            <nav aria-label="Order pagination" class="mt-4">
                <ul class="pagination justify-content-center">
                    <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                        <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page - 1])); ?>">
                            <i class="bi bi-chevron-left"></i>
                        </a>
                    </li>
                    
                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                    <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                        <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>">
                            <?php echo $i; ?>
                        </a>
                    </li>
                    <?php endfor; ?>
                    
                    <li class="page-item <?php echo $page >= $totalPages ? 'disabled' : ''; ?>">
                        <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page + 1])); ?>">
                            <i class="bi bi-chevron-right"></i>
                        </a>
                    </li>
                </ul>
            </nav>
            <?php endif; ?>
            
        <?php else: ?>
            <!-- Empty State -->
            <div class="empty-state">
                <div class="empty-state-icon">
                    <i class="bi bi-bag"></i>
                </div>
                <h3 class="mb-3">No orders found</h3>
                <p class="text-muted mb-4">
                    <?php echo $status || $search || $date_from || $date_to ? 'Try adjusting your filters' : 'You haven\'t received any orders yet'; ?>
                </p>
                <div class="d-flex flex-wrap gap-3 justify-content-center">
                    <a href="../products/" class="btn btn-primary btn-lg">
                        <i class="bi bi-bag-plus me-2"></i> Promote Your Products
                    </a>
                    <?php if ($status || $search || $date_from || $date_to): ?>
                    <a href="orders.php" class="btn btn-outline-primary btn-lg">
                        <i class="bi bi-x-circle me-2"></i> Clear Filters
                    </a>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <?php include '../../includes/components/footer.php'; ?>

    <script>
        // Order timeline
        function showOrderTimeline(orderId) {
            fetch('order-timeline.php?id=' + orderId)
                .then(response => response.text())
                .then(html => {
                    const modal = new bootstrap.Modal(document.getElementById('timelineModal'));
                    document.getElementById('timelineContent').innerHTML = html;
                    modal.show();
                });
        }
        
        // Update tracking number
        function updateTrackingNumber(orderId) {
            const trackingNumber = prompt('Enter tracking number:');
            if (trackingNumber) {
                fetch('update-tracking.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        order_id: orderId,
                        tracking_number: trackingNumber
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    } else {
                        alert('Error: ' + data.error);
                    }
                });
            }
        }
        
        // Mark as shipped
        function markAsShipped(orderId) {
            if (confirm('Mark this order as shipped?')) {
                fetch('mark-shipped.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        order_id: orderId
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    } else {
                        alert('Error: ' + data.error);
                    }
                });
            }
        }
        
        // Mark as delivered
        function markAsDelivered(orderId) {
            if (confirm('Mark this order as delivered?')) {
                fetch('mark-delivered.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        order_id: orderId
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    } else {
                        alert('Error: ' + data.error);
                    }
                });
            }
        }
        
        // Auto-refresh orders every 30 seconds
        setInterval(() => {
            if (!document.hidden) {
                fetch(window.location.href)
                    .then(response => response.text())
                    .then(html => {
                        // Update only if there are new orders
                        // This is a simple implementation - in production, you'd want to check for actual changes
                        console.log('Refreshing orders...');
                    });
            }
        }, 30000);
    </script>
    
    <!-- Timeline Modal -->
    <div class="modal fade" id="timelineModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Order Timeline</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="timelineContent">
                    <!-- Timeline content will be loaded here -->
                </div>
            </div>
        </div>
    </div>
</body>
</html>