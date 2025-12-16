<?php
require_once '../../config.php';
require_once '../../includes/classes/User.php';
require_once '../../includes/classes/Order.php';
require_once '../../includes/classes/Product.php';
require_once '../../includes/classes/Chat.php';

// Check authentication and tailor role
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'tailor') {
    header('Location: ../auth/login.php');
    exit();
}

$user = new User();
$order = new Order();
$product = new Product();
$chat = new Chat();

$tailorId = $_SESSION['user_id'];
$userData = $user->getUserById($tailorId);

// Get statistics for the dashboard
$totalProducts = $product->getTotalCountByTailor($tailorId);
$totalOrders = $order->getTotalOrdersByTailor($tailorId);
$pendingOrders = $order->getPendingOrdersCount($tailorId);
$completedOrders = $order->getCompletedOrdersCount($tailorId);
$revenue = $order->getTotalRevenueByTailor($tailorId);
$recentOrders = $order->getRecentOrdersByTailor($tailorId, 5);
$recentMessages = $chat->getRecentMessages($tailorId, 5);
$productsLowStock = $product->getLowStockProducts($tailorId, 5);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tailor Dashboard - <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            --secondary-gradient: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            --success-gradient: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);
            --warning-gradient: linear-gradient(135deg, #fa709a 0%, #fee140 100%);
            --info-gradient: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
            --glass-bg: rgba(255, 255, 255, 0.1);
            --glass-border: rgba(255, 255, 255, 0.2);
        }
        
        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            color: #333;
        }
        
        .dashboard-container {
            padding: 2rem 0;
        }
        
        /* Sidebar */
        .sidebar {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            height: 100%;
            overflow: hidden;
        }
        
        .sidebar-profile {
            background: var(--primary-gradient);
            padding: 2rem;
            text-align: center;
            color: white;
        }
        
        .profile-avatar {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            border: 4px solid white;
            object-fit: cover;
            margin-bottom: 1rem;
            box-shadow: 0 10px 20px rgba(0,0,0,0.2);
        }
        
        .tailor-badge {
            background: rgba(255,255,255,0.2);
            padding: 6px 20px;
            border-radius: 20px;
            font-size: 0.875rem;
            font-weight: 600;
            display: inline-block;
            margin-top: 0.5rem;
        }
        
        .sidebar-nav {
            padding: 1.5rem;
        }
        
        .nav-link {
            color: #4a5568;
            padding: 0.75rem 1rem;
            border-radius: 10px;
            margin-bottom: 0.5rem;
            font-weight: 500;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        
        .nav-link:hover, .nav-link.active {
            color: #667eea;
            background: rgba(102, 126, 234, 0.1);
            transform: translateX(5px);
        }
        
        .nav-link.active {
            font-weight: 600;
        }
        
        .nav-badge {
            margin-left: auto;
            font-size: 0.75rem;
        }
        
        /* Main Content */
        .main-content {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 20px;
            padding: 2rem;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            min-height: calc(100vh - 4rem);
        }
        
        .dashboard-header {
            margin-bottom: 2rem;
        }
        
        .dashboard-title h1 {
            font-weight: 700;
            color: #2d3748;
            margin-bottom: 0.5rem;
        }
        
        .dashboard-title p {
            color: #718096;
            margin-bottom: 0;
        }
        
        .quick-actions {
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
        }
        
        .btn-action {
            background: white;
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            padding: 0.75rem 1.5rem;
            font-weight: 600;
            color: #4a5568;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .btn-action:hover {
            border-color: #667eea;
            color: #667eea;
            transform: translateY(-3px);
            box-shadow: 0 10px 20px rgba(102, 126, 234, 0.1);
        }
        
        .btn-action-primary {
            background: var(--primary-gradient);
            color: white;
            border: none;
        }
        
        .btn-action-primary:hover {
            color: white;
            box-shadow: 0 10px 20px rgba(102, 126, 234, 0.3);
        }
        
        /* Stats Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .stat-card {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
            border-left: 4px solid #667eea;
            transition: all 0.3s ease;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 30px rgba(0,0,0,0.1);
        }
        
        .stat-card.revenue { border-left-color: #10b981; }
        .stat-card.orders { border-left-color: #f59e0b; }
        .stat-card.products { border-left-color: #3b82f6; }
        .stat-card.pending { border-left-color: #ef4444; }
        
        .stat-header {
            display: flex;
            justify-content: space-between;
            align-items: start;
            margin-bottom: 1rem;
        }
        
        .stat-icon {
            width: 50px;
            height: 50px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.25rem;
        }
        
        .stat-card .stat-icon {
            background: rgba(102, 126, 234, 0.1);
            color: #667eea;
        }
        
        .stat-card.revenue .stat-icon { background: rgba(16, 185, 129, 0.1); color: #10b981; }
        .stat-card.orders .stat-icon { background: rgba(245, 158, 11, 0.1); color: #f59e0b; }
        .stat-card.products .stat-icon { background: rgba(59, 130, 246, 0.1); color: #3b82f6; }
        .stat-card.pending .stat-icon { background: rgba(239, 68, 68, 0.1); color: #ef4444; }
        
        .stat-value {
            font-size: 2rem;
            font-weight: 700;
            color: #2d3748;
            line-height: 1;
        }
        
        .stat-label {
            color: #718096;
            font-size: 0.875rem;
            margin-top: 0.5rem;
        }
        
        .stat-change {
            font-size: 0.875rem;
            font-weight: 600;
            margin-top: 0.5rem;
        }
        
        .stat-change.positive { color: #10b981; }
        .stat-change.negative { color: #ef4444; }
        
        /* Dashboard Sections */
        .dashboard-section {
            margin-bottom: 2rem;
        }
        
        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }
        
        .section-title {
            font-weight: 600;
            color: #2d3748;
            margin: 0;
        }
        
        /* Recent Orders Table */
        .orders-table {
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
        }
        
        .table-header {
            background: #f8fafc;
            padding: 1rem 1.5rem;
            border-bottom: 1px solid #e2e8f0;
        }
        
        .table-body {
            padding: 0;
        }
        
        .order-row {
            display: grid;
            grid-template-columns: 2fr 1fr 1fr 1fr;
            padding: 1rem 1.5rem;
            border-bottom: 1px solid #e2e8f0;
            align-items: center;
            transition: all 0.3s ease;
        }
        
        .order-row:last-child {
            border-bottom: none;
        }
        
        .order-row:hover {
            background: #f8fafc;
        }
        
        .order-id {
            font-weight: 600;
            color: #4a5568;
        }
        
        .order-customer {
            color: #718096;
            font-size: 0.875rem;
        }
        
        .order-amount {
            font-weight: 600;
            color: #2d3748;
        }
        
        .order-status {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
            text-align: center;
            display: inline-block;
        }
        
        .status-pending { background: #fef3c7; color: #92400e; }
        .status-confirmed { background: #d1fae5; color: #065f46; }
        .status-in-progress { background: #dbeafe; color: #1e40af; }
        .status-completed { background: #dcfce7; color: #166534; }
        .status-cancelled { background: #fee2e2; color: #991b1b; }
        
        /* Recent Messages */
        .messages-list {
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
        }
        
        .message-item {
            display: flex;
            padding: 1rem 1.5rem;
            border-bottom: 1px solid #e2e8f0;
            align-items: center;
            gap: 1rem;
            transition: all 0.3s ease;
        }
        
        .message-item:hover {
            background: #f8fafc;
        }
        
        .message-item:last-child {
            border-bottom: none;
        }
        
        .message-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
        }
        
        .message-content {
            flex: 1;
        }
        
        .message-sender {
            font-weight: 600;
            color: #2d3748;
            margin-bottom: 0.25rem;
        }
        
        .message-text {
            color: #718096;
            font-size: 0.875rem;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            max-width: 200px;
        }
        
        .message-time {
            color: #a0aec0;
            font-size: 0.75rem;
        }
        
        /* Low Stock Products */
        .products-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 1rem;
        }
        
        .product-card-small {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 3px 10px rgba(0,0,0,0.05);
            transition: all 0.3s ease;
        }
        
        .product-card-small:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
        }
        
        .product-image-small {
            height: 120px;
            object-fit: cover;
            width: 100%;
        }
        
        .product-info-small {
            padding: 1rem;
        }
        
        .product-title-small {
            font-weight: 600;
            font-size: 0.875rem;
            color: #2d3748;
            margin-bottom: 0.25rem;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        
        .product-stock {
            font-size: 0.75rem;
            color: #718096;
        }
        
        .stock-warning {
            color: #ef4444;
            font-weight: 600;
        }
        
        /* Chart Container */
        .chart-container {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
            margin-top: 2rem;
        }
        
        /* Empty States */
        .empty-state {
            text-align: center;
            padding: 3rem;
            color: #718096;
        }
        
        .empty-icon {
            font-size: 3rem;
            color: #cbd5e1;
            margin-bottom: 1rem;
        }
        
        /* Responsive */
        @media (max-width: 992px) {
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .order-row {
                grid-template-columns: 1fr;
                gap: 0.5rem;
            }
        }
        
        @media (max-width: 768px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .products-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }
    </style>
</head>
<body>
    <?php include '../../includes/components/navbar.php'; ?>
    
    <div class="container-fluid dashboard-container">
        <div class="row g-4">
            <!-- Sidebar -->
            <div class="col-lg-3">
                <div class="sidebar">
                    <!-- Profile Section -->
                    <div class="sidebar-profile">
                        <img src="<?php echo SITE_URL; ?>/assets/images/avatars/<?php echo $userData['profile_pic'] ?: 'default.jpg'; ?>" 
                             class="profile-avatar" 
                             alt="<?php echo htmlspecialchars($userData['full_name']); ?>">
                        <h4 class="fw-bold mb-2"><?php echo htmlspecialchars($userData['full_name']); ?></h4>
                        <span class="tailor-badge">Verified Tailor</span>
                        <div class="rating mt-3">
                            <div class="text-warning">
                                <i class="bi bi-star-fill"></i>
                                <i class="bi bi-star-fill"></i>
                                <i class="bi bi-star-fill"></i>
                                <i class="bi bi-star-fill"></i>
                                <i class="bi bi-star-half"></i>
                                <span class="text-white ms-2">4.5</span>
                            </div>
                            <small class="text-light">150 Reviews</small>
                        </div>
                    </div>
                    
                    <!-- Navigation -->
                    <div class="sidebar-nav">
                        <ul class="nav flex-column">
                            <li>
                                <a class="nav-link active" href="dashboard.php">
                                    <i class="bi bi-speedometer2"></i>
                                    Dashboard
                                </a>
                            </li>
                            <li>
                                <a class="nav-link" href="products.php">
                                    <i class="bi bi-grid"></i>
                                    Products
                                    <span class="nav-badge badge bg-primary"><?php echo $totalProducts; ?></span>
                                </a>
                            </li>
                            <li>
                                <a class="nav-link" href="orders.php">
                                    <i class="bi bi-bag-check"></i>
                                    Orders
                                    <span class="nav-badge badge bg-warning"><?php echo $pendingOrders; ?></span>
                                </a>
                            </li>
                            <li>
                                <a class="nav-link" href="analytics.php">
                                    <i class="bi bi-graph-up"></i>
                                    Analytics
                                </a>
                            </li>
                            <li>
                                <a class="nav-link" href="messages.php">
                                    <i class="bi bi-chat-dots"></i>
                                    Messages
                                    <span class="nav-badge badge bg-success">5</span>
                                </a>
                            </li>
                            <li>
                                <a class="nav-link" href="earnings.php">
                                    <i class="bi bi-wallet2"></i>
                                    Earnings
                                </a>
                            </li>
                            <li>
                                <a class="nav-link" href="reviews.php">
                                    <i class="bi bi-star"></i>
                                    Reviews
                                </a>
                            </li>
                            <li class="mt-4">
                                <a class="nav-link text-danger" href="../../pages/auth/logout.php">
                                    <i class="bi bi-box-arrow-right"></i>
                                    Logout
                                </a>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
            
            <!-- Main Content -->
            <div class="col-lg-9">
                <div class="main-content">
                    <!-- Header -->
                    <div class="dashboard-header">
                        <div class="row align-items-center">
                            <div class="col-md-8">
                                <div class="dashboard-title">
                                    <h1>Welcome back, <?php echo explode(' ', $userData['full_name'])[0]; ?>! 👋</h1>
                                    <p>Here's what's happening with your tailoring business today.</p>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="quick-actions">
                                    <a href="products.php?action=add" class="btn btn-action btn-action-primary">
                                        <i class="bi bi-plus-circle"></i> Add Product
                                    </a>
                                    <a href="orders.php" class="btn btn-action">
                                        <i class="bi bi-eye"></i> View Orders
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Stats Cards -->
                    <div class="stats-grid">
                        <div class="stat-card revenue">
                            <div class="stat-header">
                                <div>
                                    <div class="stat-value">$<?php echo number_format($revenue, 2); ?></div>
                                    <div class="stat-label">Total Revenue</div>
                                </div>
                                <div class="stat-icon">
                                    <i class="bi bi-currency-dollar"></i>
                                </div>
                            </div>
                            <div class="stat-change positive">
                                <i class="bi bi-arrow-up"></i> 12.5% from last month
                            </div>
                        </div>
                        
                        <div class="stat-card orders">
                            <div class="stat-header">
                                <div>
                                    <div class="stat-value"><?php echo $totalOrders; ?></div>
                                    <div class="stat-label">Total Orders</div>
                                </div>
                                <div class="stat-icon">
                                    <i class="bi bi-bag-check"></i>
                                </div>
                            </div>
                            <div class="stat-change positive">
                                <i class="bi bi-arrow-up"></i> 8.7% from last month
                            </div>
                        </div>
                        
                        <div class="stat-card products">
                            <div class="stat-header">
                                <div>
                                    <div class="stat-value"><?php echo $totalProducts; ?></div>
                                    <div class="stat-label">Active Products</div>
                                </div>
                                <div class="stat-icon">
                                    <i class="bi bi-grid"></i>
                                </div>
                            </div>
                            <div class="stat-change positive">
                                <i class="bi bi-arrow-up"></i> 3.2% from last month
                            </div>
                        </div>
                        
                        <div class="stat-card pending">
                            <div class="stat-header">
                                <div>
                                    <div class="stat-value"><?php echo $pendingOrders; ?></div>
                                    <div class="stat-label">Pending Orders</div>
                                </div>
                                <div class="stat-icon">
                                    <i class="bi bi-clock"></i>
                                </div>
                            </div>
                            <div class="stat-change negative">
                                <i class="bi bi-arrow-up"></i> 2 orders need attention
                            </div>
                        </div>
                    </div>
                    
                    <!-- Recent Orders -->
                    <div class="dashboard-section">
                        <div class="section-header">
                            <h3 class="section-title">Recent Orders</h3>
                            <a href="orders.php" class="btn btn-sm btn-outline-primary">View All</a>
                        </div>
                        
                        <div class="orders-table">
                            <div class="table-header">
                                <h6 class="mb-0 fw-bold">Latest Customer Orders</h6>
                            </div>
                            <div class="table-body">
                                <?php if (!empty($recentOrders)): ?>
                                    <?php foreach ($recentOrders as $order): ?>
                                        <div class="order-row">
                                            <div>
                                                <div class="order-id">Order #<?php echo $order['order_number']; ?></div>
                                                <div class="order-customer"><?php echo htmlspecialchars($order['customer_name']); ?></div>
                                            </div>
                                            <div class="order-amount">$<?php echo number_format($order['total_amount'], 2); ?></div>
                                            <div>
                                                <span class="order-status status-<?php echo $order['status']; ?>">
                                                    <?php echo ucfirst($order['status']); ?>
                                                </span>
                                            </div>
                                            <div class="text-end">
                                                <a href="orders.php?action=view&id=<?php echo $order['id']; ?>" class="btn btn-sm btn-outline-primary">
                                                    View
                                                </a>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <div class="empty-state">
                                        <div class="empty-icon">
                                            <i class="bi bi-bag"></i>
                                        </div>
                                        <h5>No orders yet</h5>
                                        <p>When you receive orders, they'll appear here.</p>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <!-- Recent Messages -->
                        <div class="col-md-6">
                            <div class="dashboard-section">
                                <div class="section-header">
                                    <h3 class="section-title">Recent Messages</h3>
                                    <a href="messages.php" class="btn btn-sm btn-outline-primary">View All</a>
                                </div>
                                
                                <div class="messages-list">
                                    <?php if (!empty($recentMessages)): ?>
                                        <?php foreach ($recentMessages as $message): ?>
                                            <a href="messages.php?user_id=<?php echo $message['sender_id']; ?>" class="text-decoration-none">
                                                <div class="message-item">
                                                    <img src="<?php echo SITE_URL; ?>/assets/images/avatars/<?php echo $message['profile_pic'] ?: 'default.jpg'; ?>" 
                                                         class="message-avatar" 
                                                         alt="<?php echo htmlspecialchars($message['sender_name']); ?>">
                                                    <div class="message-content">
                                                        <div class="message-sender"><?php echo htmlspecialchars($message['sender_name']); ?></div>
                                                        <div class="message-text"><?php echo htmlspecialchars($message['message']); ?></div>
                                                    </div>
                                                    <div class="message-time">
                                                        <?php echo date('h:i A', strtotime($message['created_at'])); ?>
                                                    </div>
                                                </div>
                                            </a>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <div class="empty-state p-3">
                                            <div class="empty-icon">
                                                <i class="bi bi-chat"></i>
                                            </div>
                                            <h6>No messages</h6>
                                            <p class="small">When customers message you, they'll appear here.</p>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Low Stock Products -->
                        <div class="col-md-6">
                            <div class="dashboard-section">
                                <div class="section-header">
                                    <h3 class="section-title">Low Stock Products</h3>
                                    <a href="products.php" class="btn btn-sm btn-outline-primary">Manage</a>
                                </div>
                                
                                <div class="products-grid">
                                    <?php if (!empty($productsLowStock)): ?>
                                        <?php foreach ($productsLowStock as $product): ?>
                                            <a href="products.php?action=edit&id=<?php echo $product['id']; ?>" class="text-decoration-none">
                                                <div class="product-card-small">
                                                    <img src="<?php echo SITE_URL; ?>/assets/images/products/<?php echo $product['images'][0]; ?>" 
                                                         class="product-image-small" 
                                                         alt="<?php echo htmlspecialchars($product['title']); ?>">
                                                    <div class="product-info-small">
                                                        <div class="product-title-small"><?php echo htmlspecialchars($product['title']); ?></div>
                                                        <div class="product-stock <?php echo $product['stock'] <= 3 ? 'stock-warning' : ''; ?>">
                                                            Stock: <?php echo $product['stock']; ?> left
                                                        </div>
                                                    </div>
                                                </div>
                                            </a>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <div class="empty-state p-3 w-100">
                                            <div class="empty-icon">
                                                <i class="bi bi-check-circle"></i>
                                            </div>
                                            <h6>All products in stock</h6>
                                            <p class="small">Great job managing your inventory!</p>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Sales Chart -->
                    <div class="chart-container">
                        <div class="section-header mb-3">
                            <h3 class="section-title">Sales Overview</h3>
                            <div class="btn-group">
                                <button class="btn btn-sm btn-outline-primary active">This Month</button>
                                <button class="btn btn-sm btn-outline-primary">Last Month</button>
                                <button class="btn btn-sm btn-outline-primary">This Year</button>
                            </div>
                        </div>
                        <div id="salesChart" style="height: 300px;">
                            <!-- Chart will be loaded here -->
                            <div class="text-center py-5">
                                <i class="bi bi-bar-chart display-4 text-muted mb-3"></i>
                                <p class="text-muted">Sales data will appear here</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        $(document).ready(function() {
            // Initialize sales chart
            const salesCtx = document.createElement('canvas');
            $('#salesChart').html(salesCtx);
            
            const salesChart = new Chart(salesCtx, {
                type: 'line',
                data: {
                    labels: ['Week 1', 'Week 2', 'Week 3', 'Week 4'],
                    datasets: [{
                        label: 'Revenue ($)',
                        data: [1200, 1900, 1500, 2500],
                        borderColor: '#667eea',
                        backgroundColor: 'rgba(102, 126, 234, 0.1)',
                        borderWidth: 3,
                        fill: true,
                        tension: 0.4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: {
                                color: 'rgba(0,0,0,0.05)'
                            }
                        },
                        x: {
                            grid: {
                                display: false
                            }
                        }
                    }
                }
            });
            
            // Add animations
            $('.stat-card').each(function(index) {
                $(this).css('animation-delay', (index * 0.1) + 's');
                $(this).addClass('animate__animated animate__fadeInUp');
            });
            
            // Update order statuses
            $('.order-status').click(function(e) {
                e.preventDefault();
                const orderId = $(this).closest('.order-row').find('.order-id').text().replace('Order #', '');
                const currentStatus = $(this).text().toLowerCase();
                
                // Show status update modal
                $('#statusUpdateModal').modal('show');
                $('#updateOrderId').val(orderId);
                $('#updateOrderStatus').val(currentStatus);
            });
            
            // Update order status via AJAX
            $('#updateStatusForm').submit(function(e) {
                e.preventDefault();
                const orderId = $('#updateOrderId').val();
                const newStatus = $('#updateOrderStatus').val();
                
                $.ajax({
                    url: '../../api/orders.php',
                    method: 'POST',
                    data: {
                        action: 'update_status',
                        order_id: orderId,
                        status: newStatus
                    },
                    success: function(response) {
                        if (response.success) {
                            location.reload();
                        }
                    }
                });
            });
            
            // Load real-time notifications
            function loadNotifications() {
                $.ajax({
                    url: '../../api/notifications.php',
                    method: 'GET',
                    data: { action: 'get_tailor_notifications' },
                    success: function(response) {
                        if (response.notifications.length > 0) {
                            // Update notification badges
                            // You can implement notification display here
                        }
                    }
                });
            }
            
            // Load notifications every 30 seconds
            setInterval(loadNotifications, 30000);
            
            // Mark messages as read
            $('.message-item').click(function() {
                const messageId = $(this).data('message-id');
                if (messageId) {
                    $.ajax({
                        url: '../../api/chat.php',
                        method: 'POST',
                        data: {
                            action: 'mark_read',
                            message_id: messageId
                        }
                    });
                }
            });
        });
    </script>
    
    <!-- Status Update Modal -->
    <div class="modal fade" id="statusUpdateModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Update Order Status</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="updateStatusForm">
                    <div class="modal-body">
                        <input type="hidden" id="updateOrderId">
                        <div class="mb-3">
                            <label class="form-label">Order Status</label>
                            <select class="form-select" id="updateOrderStatus">
                                <option value="pending">Pending</option>
                                <option value="confirmed">Confirmed</option>
                                <option value="in_progress">In Progress</option>
                                <option value="completed">Completed</option>
                                <option value="cancelled">Cancelled</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Update Notes (Optional)</label>
                            <textarea class="form-control" id="updateNotes" rows="3" placeholder="Add any notes about this status update..."></textarea>
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
    
    <!-- Animation Library -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
</body>
</html>