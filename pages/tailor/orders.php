
<?php
require_once '../../config.php';
require_once '../../includes/classes/Database.php';
require_once '../../includes/classes/User.php';
require_once '../../includes/classes/Order.php';

session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'tailor') {
    header('Location: ../auth/login.php');
    exit();
}

$tailorId = $_SESSION['user_id'];
$order = new Order();

// Handle status filter
$status = $_GET['status'] ?? 'all';
$search = $_GET['search'] ?? '';

// Get orders
if ($search) {
    $orders = $order->searchOrders($tailorId, $search, $status !== 'all' ? $status : null);
} elseif ($status !== 'all') {
    $orders = $order->getOrdersByTailor($tailorId, $status);
} else {
    $orders = $order->getOrdersByTailor($tailorId);
}

// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $orderId = $_POST['order_id'];
    $newStatus = $_POST['status'];
    $notes = $_POST['notes'] ?? '';
    
    if ($order->updateOrderStatus($orderId, $newStatus, $notes)) {
        $success = "Order status updated successfully!";
    } else {
        $error = "Failed to update order status.";
    }
}

// Get counts for status tabs
$allCount = $order->getTotalOrdersByTailor($tailorId);
$pendingCount = $order->getPendingOrdersCount($tailorId);
$completedCount = $order->getCompletedOrdersCount($tailorId);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Orders - Tailor Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">
    <style>
        body {
            background-color: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .dashboard-container {
            padding: 20px 0;
        }
        
        .card {
            border: none;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        
        .card-header {
            background-color: white;
            border-bottom: 1px solid #eaeaea;
            padding: 1rem 1.5rem;
            border-radius: 10px 10px 0 0;
        }
        
        .nav-tabs .nav-link {
            border: none;
            color: #6c757d;
            padding: 0.75rem 1.5rem;
            position: relative;
        }
        
        .nav-tabs .nav-link.active {
            color: #0d6efd;
            background: none;
            font-weight: 600;
        }
        
        .nav-tabs .nav-link.active::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            height: 3px;
            background-color: #0d6efd;
            border-radius: 3px 3px 0 0;
        }
        
        .badge-pill {
            padding: 0.25rem 0.75rem;
            border-radius: 50rem;
            font-size: 0.75rem;
            font-weight: 600;
        }
        
        .table {
            margin-bottom: 0;
        }
        
        .table th {
            border-top: none;
            font-weight: 600;
            color: #495057;
            background-color: #f8f9fa;
        }
        
        .status-badge {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }
        
        .status-pending {
            background-color: #fff3cd;
            color: #856404;
        }
        
        .status-processing {
            background-color: #cce5ff;
            color: #004085;
        }
        
        .status-shipped {
            background-color: #d1ecf1;
            color: #0c5460;
        }
        
        .status-delivered {
            background-color: #d4edda;
            color: #155724;
        }
        
        .status-cancelled {
            background-color: #f8d7da;
            color: #721c24;
        }
        
        .btn-action {
            padding: 4px 12px;
            font-size: 0.875rem;
        }
        
        .search-box {
            max-width: 300px;
        }
        
        .order-details-modal .modal-body {
            max-height: 500px;
            overflow-y: auto;
        }
        
        .timeline {
            position: relative;
            padding-left: 30px;
        }
        
        .timeline::before {
            content: '';
            position: absolute;
            left: 11px;
            top: 0;
            bottom: 0;
            width: 2px;
            background-color: #e9ecef;
        }
        
        .timeline-item {
            position: relative;
            margin-bottom: 20px;
        }
        
        .timeline-item::before {
            content: '';
            position: absolute;
            left: -24px;
            top: 5px;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background-color: #0d6efd;
            border: 2px solid white;
        }
        
        .timeline-date {
            font-size: 0.8rem;
            color: #6c757d;
        }
    </style>
</head>
<body>
    <?php include '../../includes/components/navbar.php'; ?>
    
    <div class="container-fluid dashboard-container">
        <div class="row">
            <div class="col-12">
                <!-- Header -->
                <div class="card">
                    <div class="card-header">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h4 class="mb-0 fw-bold">Orders Management</h4>
                                <p class="text-muted mb-0">Manage and track customer orders</p>
                            </div>
                            <div class="d-flex gap-2">
                                <!-- Search -->
                                <form method="GET" class="search-box">
                                    <div class="input-group">
                                        <input type="text" class="form-control" name="search" 
                                               placeholder="Search orders..." value="<?php echo htmlspecialchars($search); ?>">
                                        <button class="btn btn-outline-secondary" type="submit">
                                            <i class="bi bi-search"></i>
                                        </button>
                                        <?php if ($search): ?>
                                            <a href="orders.php" class="btn btn-outline-danger">
                                                <i class="bi bi-x"></i>
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Status Tabs -->
                    <div class="card-body">
                        <ul class="nav nav-tabs mb-4">
                            <li class="nav-item">
                                <a class="nav-link <?php echo $status === 'all' ? 'active' : ''; ?>" 
                                   href="orders.php?status=all">
                                    All Orders
                                    <span class="badge-pill bg-secondary ms-1"><?php echo $allCount; ?></span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link <?php echo $status === 'pending' ? 'active' : ''; ?>" 
                                   href="orders.php?status=pending">
                                    Pending
                                    <span class="badge-pill bg-warning ms-1"><?php echo $pendingCount; ?></span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link <?php echo $status === 'processing' ? 'active' : ''; ?>" 
                                   href="orders.php?status=processing">
                                    Processing
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link <?php echo $status === 'shipped' ? 'active' : ''; ?>" 
                                   href="orders.php?status=shipped">
                                    Shipped
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link <?php echo $status === 'delivered' ? 'active' : ''; ?>" 
                                   href="orders.php?status=delivered">
                                    Delivered
                                    <span class="badge-pill bg-success ms-1"><?php echo $completedCount; ?></span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link <?php echo $status === 'cancelled' ? 'active' : ''; ?>" 
                                   href="orders.php?status=cancelled">
                                    Cancelled
                                </a>
                            </li>
                        </ul>
                        
                        <!-- Alerts -->
                        <?php if (isset($success)): ?>
                            <div class="alert alert-success alert-dismissible fade show" role="alert">
                                <?php echo $success; ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (isset($error)): ?>
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <?php echo $error; ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>
                        
                        <!-- Orders Table -->
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Order ID</th>
                                        <th>Customer</th>
                                        <th>Date</th>
                                        <th>Amount</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (!empty($orders)): ?>
                                        <?php foreach ($orders as $orderItem): ?>
                                            <tr>
                                                <td>
                                                    <strong>#<?php echo htmlspecialchars($orderItem['order_number']); ?></strong>
                                                </td>
                                                <td>
                                                    <div><?php echo htmlspecialchars($orderItem['customer_name']); ?></div>
                                                    <small class="text-muted"><?php echo htmlspecialchars($orderItem['customer_email']); ?></small>
                                                </td>
                                                <td><?php echo date('M d, Y', strtotime($orderItem['created_at'])); ?></td>
                                                <td><strong>$<?php echo number_format($orderItem['total_amount'], 2); ?></strong></td>
                                                <td>
                                                    <span class="status-badge status-<?php echo $orderItem['status']; ?>">
                                                        <?php echo ucfirst($orderItem['status']); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <div class="btn-group">
                                                        <button class="btn btn-sm btn-outline-primary" 
                                                                data-bs-toggle="modal" 
                                                                data-bs-target="#orderModal<?php echo $orderItem['id']; ?>">
                                                            <i class="bi bi-eye"></i> View
                                                        </button>
                                                        <button class="btn btn-sm btn-outline-secondary" 
                                                                data-bs-toggle="modal" 
                                                                data-bs-target="#statusModal<?php echo $orderItem['id']; ?>">
                                                            <i class="bi bi-pencil"></i> Update
                                                        </button>
                                                    </div>
                                                </td>
                                            </tr>
                                            
                                            <!-- Order Details Modal -->
                                            <div class="modal fade order-details-modal" id="orderModal<?php echo $orderItem['id']; ?>" tabindex="-1">
                                                <div class="modal-dialog modal-lg">
                                                    <div class="modal-content">
                                                        <div class="modal-header">
                                                            <h5 class="modal-title">Order #<?php echo htmlspecialchars($orderItem['order_number']); ?></h5>
                                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                        </div>
                                                        <div class="modal-body">
                                                            <!-- Order Details -->
                                                            <div class="row mb-4">
                                                                <div class="col-md-6">
                                                                    <h6>Customer Information</h6>
                                                                    <p class="mb-1">
                                                                        <strong>Name:</strong> <?php echo htmlspecialchars($orderItem['customer_name']); ?>
                                                                    </p>
                                                                    <p class="mb-1">
                                                                        <strong>Email:</strong> <?php echo htmlspecialchars($orderItem['customer_email']); ?>
                                                                    </p>
                                                                    <p class="mb-0">
                                                                        <strong>Phone:</strong> <?php echo htmlspecialchars($orderItem['customer_phone'] ?? 'N/A'); ?>
                                                                    </p>
                                                                </div>
                                                                <div class="col-md-6">
                                                                    <h6>Order Information</h6>
                                                                    <p class="mb-1">
                                                                        <strong>Date:</strong> <?php echo date('F j, Y, g:i a', strtotime($orderItem['created_at'])); ?>
                                                                    </p>
                                                                    <p class="mb-1">
                                                                        <strong>Status:</strong> 
                                                                        <span class="status-badge status-<?php echo $orderItem['status']; ?>">
                                                                            <?php echo ucfirst($orderItem['status']); ?>
                                                                        </span>
                                                                    </p>
                                                                    <p class="mb-0">
                                                                        <strong>Total Amount:</strong> 
                                                                        <span class="fw-bold">$<?php echo number_format($orderItem['total_amount'], 2); ?></span>
                                                                    </p>
                                                                </div>
                                                            </div>
                                                            
                                                            <!-- Order Items -->
                                                            <h6>Order Items</h6>
                                                            <div class="table-responsive">
                                                                <table class="table table-sm">
                                                                    <thead>
                                                                        <tr>
                                                                            <th>Product</th>
                                                                            <th>Quantity</th>
                                                                            <th>Price</th>
                                                                            <th>Total</th>
                                                                        </tr>
                                                                    </thead>
                                                                    <tbody>
                                                                        <?php 
                                                                        $orderDetails = $order->getOrderById($orderItem['id'], $tailorId);
                                                                        $items = $orderDetails['items'] ?? [];
                                                                        ?>
                                                                        <?php if (!empty($items)): ?>
                                                                            <?php foreach ($items as $item): ?>
                                                                                <tr>
                                                                                    <td>
                                                                                        <div><?php echo htmlspecialchars($item['title']); ?></div>
                                                                                        <?php if (!empty($item['size'])): ?>
                                                                                            <small class="text-muted">Size: <?php echo $item['size']; ?></small>
                                                                                        <?php endif; ?>
                                                                                        <?php if (!empty($item['color'])): ?>
                                                                                            <small class="text-muted">Color: <?php echo $item['color']; ?></small>
                                                                                        <?php endif; ?>
                                                                                    </td>
                                                                                    <td><?php echo $item['quantity']; ?></td>
                                                                                    <td>$<?php echo number_format($item['price'], 2); ?></td>
                                                                                    <td>$<?php echo number_format($item['price'] * $item['quantity'], 2); ?></td>
                                                                                </tr>
                                                                            <?php endforeach; ?>
                                                                        <?php else: ?>
                                                                            <tr>
                                                                                <td colspan="4" class="text-center">No items found</td>
                                                                            </tr>
                                                                        <?php endif; ?>
                                                                    </tbody>
                                                                </table>
                                                            </div>
                                                            
                                                            <!-- Status Timeline -->
                                                            <?php if (!empty($orderDetails['status_history'])): ?>
                                                                <h6 class="mt-4">Status Timeline</h6>
                                                                <div class="timeline">
                                                                    <?php foreach ($orderDetails['status_history'] as $history): ?>
                                                                        <div class="timeline-item">
                                                                            <div class="d-flex justify-content-between">
                                                                                <div>
                                                                                    <strong class="text-capitalize"><?php echo $history['status']; ?></strong>
                                                                                    <?php if (!empty($history['notes'])): ?>
                                                                                        <p class="mb-0 text-muted"><?php echo htmlspecialchars($history['notes']); ?></p>
                                                                                    <?php endif; ?>
                                                                                </div>
                                                                                <div class="timeline-date">
                                                                                    <?php echo date('M d, Y g:i a', strtotime($history['created_at'])); ?>
                                                                                </div>
                                                                            </div>
                                                                        </div>
                                                                    <?php endforeach; ?>
                                                                </div>
                                                            <?php endif; ?>
                                                        </div>
                                                        <div class="modal-footer">
                                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                                            <button type="button" class="btn btn-primary" 
                                                                    data-bs-toggle="modal" 
                                                                    data-bs-target="#statusModal<?php echo $orderItem['id']; ?>"
                                                                    data-bs-dismiss="modal">
                                                                Update Status
                                                            </button>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            
                                            <!-- Status Update Modal -->
                                            <div class="modal fade" id="statusModal<?php echo $orderItem['id']; ?>" tabindex="-1">
                                                <div class="modal-dialog">
                                                    <div class="modal-content">
                                                        <form method="POST">
                                                            <div class="modal-header">
                                                                <h5 class="modal-title">Update Order Status</h5>
                                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                            </div>
                                                            <div class="modal-body">
                                                                <input type="hidden" name="order_id" value="<?php echo $orderItem['id']; ?>">
                                                                
                                                                <div class="mb-3">
                                                                    <label class="form-label">Current Status</label>
                                                                    <div class="form-control">
                                                                        <span class="status-badge status-<?php echo $orderItem['status']; ?>">
                                                                            <?php echo ucfirst($orderItem['status']); ?>
                                                                        </span>
                                                                    </div>
                                                                </div>
                                                                
                                                                <div class="mb-3">
                                                                    <label class="form-label">New Status</label>
                                                                    <select class="form-select" name="status" required>
                                                                        <option value="pending" <?php echo $orderItem['status'] === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                                                        <option value="processing" <?php echo $orderItem['status'] === 'processing' ? 'selected' : ''; ?>>Processing</option>
                                                                        <option value="shipped" <?php echo $orderItem['status'] === 'shipped' ? 'selected' : ''; ?>>Shipped</option>
                                                                        <option value="delivered" <?php echo $orderItem['status'] === 'delivered' ? 'selected' : ''; ?>>Delivered</option>
                                                                        <option value="cancelled" <?php echo $orderItem['status'] === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                                                                    </select>
                                                                </div>
                                                                
                                                                <div class="mb-3">
                                                                    <label class="form-label">Notes (Optional)</label>
                                                                    <textarea class="form-control" name="notes" rows="3" 
                                                                              placeholder="Add any notes about this status update..."></textarea>
                                                                </div>
                                                            </div>
                                                            <div class="modal-footer">
                                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                                <button type="submit" name="update_status" class="btn btn-primary">Update Status</button>
                                                            </div>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="6" class="text-center py-4">
                                                <div class="text-muted">
                                                    <i class="bi bi-bag display-6"></i>
                                                    <h5 class="mt-3">No orders found</h5>
                                                    <p>When you receive orders, they'll appear here.</p>
                                                    <?php if ($status !== 'all'): ?>
                                                        <a href="orders.php?status=all" class="btn btn-primary">View All Orders</a>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Auto-focus search input
        document.addEventListener('DOMContentLoaded', function() {
            const searchInput = document.querySelector('input[name="search"]');
            if (searchInput) {
                searchInput.focus();
            }
            
            // Refresh page when modal closes to show updated status
            const statusModals = document.querySelectorAll('.modal');
            statusModals.forEach(modal => {
                modal.addEventListener('hidden.bs.modal', function() {
                    if (window.location.search.includes('update_status')) {
                        window.location.reload();
                    }
                });
            });
        });
    </script>
</body>
</html>



