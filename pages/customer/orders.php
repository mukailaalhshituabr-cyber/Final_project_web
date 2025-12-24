<?php
require_once dirname(__DIR__, 2) . '/config.php';
require_once ROOT_PATH . '/includes/classes/Database.php';
require_once ROOT_PATH . '/includes/classes/Order.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'customer') {
    header('Location: ../auth/login.php');
    exit();
}

$db = Database::getInstance();
$orderObj = new Order();
$userId = $_SESSION['user_id'];

// Get filter parameters
$status = $_GET['status'] ?? '';
$search = $_GET['search'] ?? '';
$page = max(1, intval($_GET['page'] ?? 1));
$perPage = 10;

// Get orders with filters
$filters = [];
if ($status) $filters['status'] = $status;
if ($search) $filters['search'] = $search;
$filters['customer_id'] = $userId;

$orders = $orderObj->getCustomerOrders($userId, $status);

// Calculate pagination
$totalOrders = count($orders);
$totalPages = ceil($totalOrders / $perPage);
$offset = ($page - 1) * $perPage;
$paginatedOrders = array_slice($orders, $offset, $perPage);
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
        .order-product-img {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 8px;
        }
        .badge-status {
            padding: 0.5rem 1rem;
            font-size: 0.875rem;
            font-weight: 600;
        }
        .filter-card {
            background: white;
            border-radius: 10px;
            padding: 1.5rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            margin-bottom: 1.5rem;
        }
        .empty-state {
            text-align: center;
            padding: 3rem 1rem;
        }
        .empty-state-icon {
            font-size: 3rem;
            color: #dee2e6;
            margin-bottom: 1rem;
        }
        .pagination .page-link {
            border-color: #e9ecef;
            color: #667eea;
        }
        .pagination .page-item.active .page-link {
            background: #667eea;
            border-color: #667eea;
        }
    </style>
</head>
<body>
    <?php include '../../includes/components/navbar.php'; ?>
    
    <div class="container orders-container py-5">
        <div class="row">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h1 class="h3 fw-bold">My Orders</h1>
                    <a href="<?php echo SITE_URL; ?>/pages/products/" class="btn btn-primary">
                        <i class="bi bi-bag-plus me-2"></i> Continue Shopping
                    </a>
                </div>

                <!-- Filters -->
                <div class="filter-card">
                    <form method="GET" action="" class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">Order Status</label>
                            <select class="form-select" name="status">
                                <option value="">All Orders</option>
                                <option value="pending" <?php echo $status == 'pending' ? 'selected' : ''; ?>>Pending</option>
                                <option value="confirmed" <?php echo $status == 'confirmed' ? 'selected' : ''; ?>>Confirmed</option>
                                <option value="processing" <?php echo $status == 'processing' ? 'selected' : ''; ?>>Processing</option>
                                <option value="shipped" <?php echo $status == 'shipped' ? 'selected' : ''; ?>>Shipped</option>
                                <option value="delivered" <?php echo $status == 'delivered' ? 'selected' : ''; ?>>Delivered</option>
                                <option value="cancelled" <?php echo $status == 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                            </select>
                        </div>
                        <div class="col-md-5">
                            <label class="form-label">Search Orders</label>
                            <input type="text" class="form-control" name="search" 
                                   placeholder="Search by order number, tailor name..." 
                                   value="<?php echo htmlspecialchars($search); ?>">
                        </div>
                        <div class="col-md-3 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="bi bi-filter me-2"></i> Filter
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Orders List -->
                <?php if (!empty($paginatedOrders)): ?>
                    <?php foreach ($paginatedOrders as $order): 
                        $orderItems = $orderObj->getOrderItems($order['id']);
                        $firstItem = !empty($orderItems) ? $orderItems[0] : null;
                    ?>
                    <div class="order-card">
                        <div class="order-header">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="fw-bold mb-1">Order #<?php echo $order['order_number']; ?></h6>
                                    <small class="text-muted">
                                        Placed on <?php echo date('F d, Y', strtotime($order['created_at'])); ?>
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
                            <!-- Order Items -->
                            <?php foreach ($orderItems as $item): 
                                $images = json_decode($item['images'] ?? '[]', true);
                                $productImage = !empty($images) && is_array($images) ? $images[0] : ASSETS_URL . 'images/products/default.jpg';
                            ?>
                            <div class="d-flex align-items-center mb-3 pb-3 border-bottom">
                                <img src="<?php echo htmlspecialchars($productImage); ?>" 
                                     class="order-product-img me-3"
                                     alt="Product"
                                     onerror="this.src='<?php echo ASSETS_URL; ?>images/products/default.jpg'">
                                <div class="flex-grow-1">
                                    <h6 class="fw-bold mb-1"><?php echo htmlspecialchars($item['title']); ?></h6>
                                    <?php if ($item['sku']): ?>
                                    <small class="text-muted">SKU: <?php echo $item['sku']; ?></small>
                                    <?php endif; ?>
                                </div>
                                <div class="text-end">
                                    <div class="fw-bold"><?php echo format_price($item['unit_price']); ?></div>
                                    <small class="text-muted">Qty: <?php echo $item['quantity']; ?></small>
                                </div>
                            </div>
                            <?php endforeach; ?>
                            
                            <!-- Order Actions -->
                            <div class="d-flex justify-content-between align-items-center mt-3">
                                <div>
                                    <small class="text-muted">
                                        Tailor: <strong><?php echo htmlspecialchars($order['tailor_name']); ?></strong>
                                    </small>
                                </div>
                                <div class="d-flex gap-2">
                                    <a href="order-details.php?id=<?php echo $order['id']; ?>" 
                                       class="btn btn-outline-primary btn-sm">
                                        <i class="bi bi-eye me-1"></i> View Details
                                    </a>
                                    <?php if ($order['status'] == 'pending' || $order['status'] == 'confirmed'): ?>
                                    <a href="cancel-order.php?id=<?php echo $order['id']; ?>" 
                                       class="btn btn-outline-danger btn-sm"
                                       onclick="return confirm('Are you sure you want to cancel this order?')">
                                        <i class="bi bi-x-circle me-1"></i> Cancel
                                    </a>
                                    <?php endif; ?>
                                    <?php if ($order['status'] == 'delivered' && $order['payment_status'] == 'paid'): ?>
                                    <a href="../products/review.php?order_id=<?php echo $order['id']; ?>" 
                                       class="btn btn-outline-success btn-sm">
                                        <i class="bi bi-star me-1"></i> Write Review
                                    </a>
                                    <?php endif; ?>
                                </div>
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
                    <div class="empty-state">
                        <div class="empty-state-icon">
                            <i class="bi bi-bag"></i>
                        </div>
                        <h4 class="mb-3">No orders found</h4>
                        <p class="text-muted mb-4">
                            <?php echo $status || $search ? 'Try adjusting your filters' : 'Start shopping to see your orders here'; ?>
                        </p>
                        <a href="<?php echo SITE_URL; ?>/pages/products/" class="btn btn-primary">
                            <i class="bi bi-bag-plus me-2"></i> Browse Products
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <?php include '../../includes/components/footer.php'; ?>

    <script>
        // Order status update
        document.querySelectorAll('.btn-update-status').forEach(button => {
            button.addEventListener('click', function(e) {
                e.preventDefault();
                const orderId = this.dataset.orderId;
                const newStatus = prompt('Enter new status (pending/confirmed/processing/shipped/delivered/cancelled):');
                
                if (newStatus) {
                    fetch('update-order-status.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({
                            order_id: orderId,
                            status: newStatus
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
            });
        });
        
        // Print order
        function printOrder(orderId) {
            window.open('print-order.php?id=' + orderId, '_blank');
        }
    </script>
</body>
</html>