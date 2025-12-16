<?php
require_once '../../config.php';
require_once '../../includes/classes/User.php';
require_once '../../includes/classes/Order.php';

// Check authentication and tailor role
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'tailor') {
    header('Location: ../auth/login.php');
    exit();
}

$user = new User();
$order = new Order();

$tailorId = $_SESSION['user_id'];
$userData = $user->getUserById($tailorId);

// Get filter parameters
$status = $_GET['status'] ?? '';
$search = $_GET['search'] ?? '';
$page = $_GET['page'] ?? 1;
$perPage = 10;

// Get orders based on filters
$orders = $order->getTailorOrders($tailorId, $status, $search, $page, $perPage);
$totalOrders = $order->getTailorOrdersCount($tailorId, $status, $search);
$totalPages = ceil($totalOrders / $perPage);

// Handle order actions
$action = $_GET['action'] ?? '';
$orderId = $_GET['id'] ?? 0;
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_status'])) {
    $result = $order->updateStatus($orderId, $_POST['status'], $_POST['notes'] ?? '');
    if ($result) {
        $message = 'Order status updated successfully!';
    } else {
        $error = 'Failed to update order status';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Orders - <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        /* Same styles as products.php */
        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            color: #333;
        }
        
        .dashboard-container {
            padding: 2rem 0;
        }
        
        .main-content {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 20px;
            padding: 2rem;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            min-height: calc(100vh - 4rem);
        }
        
        .order-card {
            background: white;
            border-radius: 12px;
            border: 1px solid #e2e8f0;
            margin-bottom: 1rem;
            transition: all 0.3s ease;
        }
        
        .order-card:hover {
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
            transform: translateY(-2px);
        }
        
        .order-header {
            background: #f8fafc;
            padding: 1rem 1.5rem;
            border-bottom: 1px solid #e2e8f0;
            border-radius: 12px 12px 0 0;
        }
        
        .order-body {
            padding: 1.5rem;
        }
        
        .order-item {
            display: flex;
            align-items: center;
            padding: 0.75rem 0;
            border-bottom: 1px solid #f1f5f9;
        }
        
        .order-item:last-child {
            border-bottom: none;
        }
        
        .item-image {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 8px;
            margin-right: 1rem;
        }
        
        .item-details {
            flex: 1;
        }
        
        .item-price {
            font-weight: 600;
            color: #2d3748;
            white-space: nowrap;
        }
        
        .status-badge {
            padding: 6px 16px;
            border-radius: 20px;
            font-size: 0.875rem;
            font-weight: 600;
            display: inline-block;
        }
        
        .status-pending { background: #fef3c7; color: #92400e; }
        .status-confirmed { background: #d1fae5; color: #065f46; }
        .status-in-progress { background: #dbeafe; color: #1e40af; }
        .status-completed { background: #dcfce7; color: #166534; }
        .status-cancelled { background: #fee2e2; color: #991b1b; }
        
        .payment-badge {
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        
        .payment-pending { background: #fef3c7; color: #92400e; }
        .payment-paid { background: #d1fae5; color: #065f46; }
        .payment-failed { background: #fee2e2; color: #991b1b; }
        
        .pagination {
            justify-content: center;
            margin-top: 2rem;
        }
        
        .page-link {
            border: none;
            color: #667eea;
            padding: 0.5rem 1rem;
            border-radius: 8px;
            margin: 0 2px;
        }
        
        .page-link:hover {
            background: rgba(102, 126, 234, 0.1);
        }
        
        .page-item.active .page-link {
            background: #667eea;
            color: white;
        }
    </style>
</head>
<body>
    <?php include '../../includes/components/navbar.php'; ?>
    
    <div class="container-fluid dashboard-container">
        <div class="row g-4">
            <!-- Sidebar -->
            <div class="col-lg-3">
                <?php include '../../includes/components/sidebar.php'; ?>
            </div>
            
            <!-- Main Content -->
            <div class="col-lg-9">
                <div class="main-content">
                    <!-- Header -->
                    <div class="dashboard-header mb-4">
                        <div class="row align-items-center">
                            <div class="col-md-8">
                                <div class="dashboard-title">
                                    <h1>Customer Orders</h1>
                                    <p>Manage and track all your orders</p>
                                </div>
                            </div>
                            <div class="col-md-4 text-end">
                                <a href="?status=pending" class="btn btn-warning">
                                    <i class="bi bi-clock me-2"></i> Pending: <?php echo $order->getPendingOrdersCount($tailorId); ?>
                                </a>
                            </div>
                        </div>
                    </div>
                    
                    <?php if ($message): ?>
                        <div class="alert alert-success"><?php echo $message; ?></div>
                    <?php endif; ?>
                    
                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                    <?php endif; ?>
                    
                    <!-- Filters -->
                    <div class="card border-0 shadow-sm mb-4">
                        <div class="card-body">
                            <form method="GET" class="row g-3">
                                <div class="col-md-4">
                                    <input type="text" 
                                           class="form-control" 
                                           name="search" 
                                           placeholder="Search by order number or customer..."
                                           value="<?php echo htmlspecialchars($search); ?>">
                                </div>
                                <div class="col-md-3">
                                    <select class="form-select" name="status">
                                        <option value="">All Status</option>
                                        <option value="pending" <?php echo $status == 'pending' ? 'selected' : ''; ?>>Pending</option>
                                        <option value="confirmed" <?php echo $status == 'confirmed' ? 'selected' : ''; ?>>Confirmed</option>
                                        <option value="in_progress" <?php echo $status == 'in_progress' ? 'selected' : ''; ?>>In Progress</option>
                                        <option value="completed" <?php echo $status == 'completed' ? 'selected' : ''; ?>>Completed</option>
                                        <option value="cancelled" <?php echo $status == 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <select class="form-select" name="payment_status">
                                        <option value="">All Payments</option>
                                        <option value="pending">Payment Pending</option>
                                        <option value="paid">Payment Paid</option>
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <button type="submit" class="btn btn-primary w-100">
                                        <i class="bi bi-filter"></i> Filter
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                    
                    <!-- Orders List -->
                    <?php if (!empty($orders)): ?>
                        <?php foreach ($orders as $orderData): ?>
                            <div class="order-card">
                                <div class="order-header">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <h6 class="fw-bold mb-1">Order #<?php echo $orderData['order_number']; ?></h6>
                                            <small class="text-muted">
                                                Customer: <?php echo htmlspecialchars($orderData['customer_name']); ?> • 
                                                <?php echo date('M d, Y', strtotime($orderData['created_at'])); ?>
                                            </small>
                                        </div>
                                        <div class="d-flex align-items-center gap-3">
                                            <span class="status-badge status-<?php echo $orderData['status']; ?>">
                                                <?php echo ucfirst(str_replace('_', ' ', $orderData['status'])); ?>
                                            </span>
                                            <span class="payment-badge payment-<?php echo $orderData['payment_status']; ?>">
                                                <?php echo ucfirst($orderData['payment_status']); ?>
                                            </span>
                                            <span class="fw-bold text-primary">$<?php echo number_format($orderData['total_amount'], 2); ?></span>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="order-body">
                                    <!-- Order Items -->
                                    <?php 
                                    $orderItems = $order->getOrderItems($orderData['id']);
                                    if (!empty($orderItems)): 
                                    ?>
                                        <?php foreach ($orderItems as $item): ?>
                                            <div class="order-item">
                                                <img src="<?php echo SITE_URL; ?>/assets/images/products/<?php echo $item['image']; ?>" 
                                                     class="item-image" 
                                                     alt="<?php echo htmlspecialchars($item['title']); ?>">
                                                <div class="item-details">
                                                    <h6 class="fw-bold mb-1"><?php echo htmlspecialchars($item['title']); ?></h6>
                                                    <p class="text-muted small mb-1">Quantity: <?php echo $item['quantity']; ?></p>
                                                    <?php if ($item['customization_details']): ?>
                                                        <p class="text-primary small mb-0">
                                                            <i class="bi bi-gear"></i> Custom: <?php echo htmlspecialchars($item['customization_details']); ?>
                                                        </p>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="item-price">$<?php echo number_format($item['price'] * $item['quantity'], 2); ?></div>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                    
                                    <!-- Order Actions -->
                                    <div class="mt-3 pt-3 border-top">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div>
                                                <small class="text-muted">
                                                    Shipping Address: <?php echo nl2br(htmlspecialchars($orderData['shipping_address'])); ?>
                                                </small>
                                            </div>
                                            <div class="d-flex gap-2">
                                                <a href="messages.php?order_id=<?php echo $orderData['id']; ?>" 
                                                   class="btn btn-sm btn-outline-primary">
                                                    <i class="bi bi-chat-dots"></i> Message
                                                </a>
                                                <button type="button" 
                                                        class="btn btn-sm btn-outline-success" 
                                                        data-bs-toggle="modal" 
                                                        data-bs-target="#updateStatusModal"
                                                        data-order-id="<?php echo $orderData['id']; ?>"
                                                        data-current-status="<?php echo $orderData['status']; ?>">
                                                    <i class="bi bi-pencil"></i> Update Status
                                                </button>
                                                <a href="order-details.php?id=<?php echo $orderData['id']; ?>" 
                                                   class="btn btn-sm btn-primary">
                                                    <i class="bi bi-eye"></i> View Details
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                        
                        <!-- Pagination -->
                        <?php if ($totalPages > 1): ?>
                            <nav aria-label="Page navigation">
                                <ul class="pagination">
                                    <?php if ($page > 1): ?>
                                        <li class="page-item">
                                            <a class="page-link" href="?page=<?php echo $page - 1; ?>&status=<?php echo $status; ?>&search=<?php echo urlencode($search); ?>">
                                                <i class="bi bi-chevron-left"></i>
                                            </a>
                                        </li>
                                    <?php endif; ?>
                                    
                                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                        <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                            <a class="page-link" href="?page=<?php echo $i; ?>&status=<?php echo $status; ?>&search=<?php echo urlencode($search); ?>">
                                                <?php echo $i; ?>
                                            </a>
                                        </li>
                                    <?php endfor; ?>
                                    
                                    <?php if ($page < $totalPages): ?>
                                        <li class="page-item">
                                            <a class="page-link" href="?page=<?php echo $page + 1; ?>&status=<?php echo $status; ?>&search=<?php echo urlencode($search); ?>">
                                                <i class="bi bi-chevron-right"></i>
                                            </a>
                                        </li>
                                    <?php endif; ?>
                                </ul>
                            </nav>
                        <?php endif; ?>
                        
                    <?php else: ?>
                        <div class="text-center py-5">
                            <i class="bi bi-bag display-4 text-muted mb-3"></i>
                            <h4 class="text-muted mb-3">No orders found</h4>
                            <p class="text-muted mb-4">When customers place orders, they'll appear here.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Update Status Modal -->
    <div class="modal fade" id="updateStatusModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Update Order Status</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="">
                    <div class="modal-body">
                        <input type="hidden" id="modalOrderId" name="order_id">
                        <input type="hidden" name="update_status" value="1">
                        
                        <div class="mb-3">
                            <label class="form-label fw-bold">Order Status</label>
                            <select class="form-select" id="modalOrderStatus" name="status" required>
                                <option value="pending">Pending</option>
                                <option value="confirmed">Confirmed</option>
                                <option value="in_progress">In Progress</option>
                                <option value="completed">Completed</option>
                                <option value="cancelled">Cancelled</option>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Update Notes (Optional)</label>
                            <textarea class="form-control" name="notes" rows="3" placeholder="Add any notes about this status update..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Update Status</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        $(document).ready(function() {
            // Update status modal
            $('#updateStatusModal').on('show.bs.modal', function(event) {
                var button = $(event.relatedTarget);
                var orderId = button.data('order-id');
                var currentStatus = button.data('current-status');
                
                var modal = $(this);
                modal.find('#modalOrderId').val(orderId);
                modal.find('#modalOrderStatus').val(currentStatus);
            });
            
            // Filter by payment status
            $('select[name="payment_status"]').change(function() {
                $('form').submit();
            });
            
            // Auto-refresh pending orders count
            function updatePendingCount() {
                $.ajax({
                    url: '../../api/orders.php',
                    method: 'GET',
                    data: { action: 'pending_count', tailor_id: <?php echo $tailorId; ?> },
                    success: function(response) {
                        if (response.success) {
                            $('.btn-warning').html('<i class="bi bi-clock me-2"></i> Pending: ' + response.count);
                        }
                    }
                });
            }
            
            // Update every 30 seconds
            setInterval(updatePendingCount, 30000);
        });
    </script>
</body>
</html>




