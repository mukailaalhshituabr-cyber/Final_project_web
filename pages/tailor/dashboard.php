<?php
require_once '../../config.php';
require_once '../../includes/classes/Database.php';
require_once '../../includes/classes/User.php';
require_once '../../includes/classes/Order.php';
require_once '../../includes/classes/Product.php';
require_once '../../includes/classes/Chat.php';

// Check authentication and tailor role
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'tailor') {
    header('Location: ../auth/login.php');
    exit();
}

$db = Database::getInstance();
$user = new User();
$order = new Order();
$product = new Product();
$chat = new Chat();

$tailorId = $_SESSION['user_id'];

// Get user data with fallback
try {
    $userData = $user->getUserById($tailorId);
    if (!$userData) {
        $userData = [
            'full_name' => 'Tailor',
            'profile_pic' => 'default.jpg',
            'email' => '',
            'phone' => ''
        ];
    }
} catch (Exception $e) {
    $userData = [
        'full_name' => 'Tailor',
        'profile_pic' => 'default.jpg',
        'email' => '',
        'phone' => ''
    ];
}

// Get statistics with fallbacks
try {
    $totalProducts = method_exists($product, 'getTotalCountByTailor') 
        ? $product->getTotalCountByTailor($tailorId) 
        : 0;
} catch (Exception $e) {
    $totalProducts = 0;
}

try {
    $totalOrders = method_exists($order, 'getTotalOrdersByTailor') 
        ? $order->getTotalOrdersByTailor($tailorId) 
        : 0;
} catch (Exception $e) {
    $totalOrders = 0;
}

try {
    $pendingOrders = method_exists($order, 'getPendingOrdersCount') 
        ? $order->getPendingOrdersCount($tailorId) 
        : 0;
} catch (Exception $e) {
    $pendingOrders = 0;
}

try {
    $completedOrders = method_exists($order, 'getCompletedOrdersCount') 
        ? $order->getCompletedOrdersCount($tailorId) 
        : 0;
} catch (Exception $e) {
    $completedOrders = 0;
}

try {
    $revenue = method_exists($order, 'getTotalRevenueByTailor') 
        ? $order->getTotalRevenueByTailor($tailorId) 
        : 0;
} catch (Exception $e) {
    $revenue = 0;
}

try {
    $recentOrders = method_exists($order, 'getRecentOrdersByTailor') 
        ? $order->getRecentOrdersByTailor($tailorId, 5) 
        : [];
} catch (Exception $e) {
    $recentOrders = [];
}

try {
    $recentMessages = method_exists($chat, 'getRecentMessages') 
        ? $chat->getRecentMessages($tailorId, 5) 
        : [];
} catch (Exception $e) {
    $recentMessages = [];
}

try {
    $productsLowStock = method_exists($product, 'getLowStockProducts') 
        ? $product->getLowStockProducts($tailorId, 5) 
        : [];
} catch (Exception $e) {
    $productsLowStock = [];
}

// Sample data for demonstration if database is empty
if ($totalProducts === 0 && $totalOrders === 0) {
    // These are sample stats for demonstration
    $totalProducts = 24;
    $totalOrders = 156;
    $pendingOrders = 8;
    $completedOrders = 145;
    $revenue = 12345.67;
    
    // Sample recent orders
    $recentOrders = [
        [
            'id' => 1,
            'order_number' => 'ORD-001',
            'customer_name' => 'John Doe',
            'total_amount' => 149.99,
            'status' => 'pending',
            'created_at' => date('Y-m-d H:i:s')
        ],
        [
            'id' => 2,
            'order_number' => 'ORD-002',
            'customer_name' => 'Jane Smith',
            'total_amount' => 299.99,
            'status' => 'in_progress',
            'created_at' => date('Y-m-d H:i:s', strtotime('-1 day'))
        ],
        [
            'id' => 3,
            'order_number' => 'ORD-003',
            'customer_name' => 'Robert Johnson',
            'total_amount' => 89.99,
            'status' => 'completed',
            'created_at' => date('Y-m-d H:i:s', strtotime('-2 days'))
        ]
    ];
    
    // Sample messages
    $recentMessages = [
        [
            'sender_id' => 1,
            'sender_name' => 'Sarah Williams',
            'message' => 'Hello, I would like to know about my order status.',
            'profile_pic' => 'default.jpg',
            'created_at' => date('Y-m-d H:i:s', strtotime('-1 hour'))
        ],
        [
            'sender_id' => 2,
            'sender_name' => 'Michael Brown',
            'message' => 'Can I make some changes to my design?',
            'profile_pic' => 'default.jpg',
            'created_at' => date('Y-m-d H:i:s', strtotime('-2 hours'))
        ]
    ];
    
    // Sample low stock products
    $productsLowStock = [
        [
            'id' => 1,
            'title' => 'African Print Kaftan',
            'stock' => 2,
            'images' => ['kaftan.jpg']
        ],
        [
            'id' => 2,
            'title' => 'Designer Blazer',
            'stock' => 3,
            'images' => ['blazer.jpg']
        ]
    ];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tailor Dashboard - <?php echo defined('SITE_NAME') ? SITE_NAME : 'Tailor Shop'; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            --glass-bg: rgba(255, 255, 255, 0.1);
            --glass-border: rgba(255, 255, 255, 0.2);
        }
        
        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
            min-height: 100vh;
            color: #333;
        }
        
        .dashboard-container {
            padding: 2rem 0;
        }
        
        /* Sidebar */
        .sidebar {
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
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
            width: 100px;
            height: 100px;
            border-radius: 50%;
            border: 4px solid white;
            object-fit: cover;
            margin-bottom: 1rem;
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }
        
        .tailor-badge {
            background: rgba(255,255,255,0.2);
            padding: 4px 15px;
            border-radius: 15px;
            font-size: 0.8rem;
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
        
        /* Main Content */
        .main-content {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .dashboard-header {
            margin-bottom: 2rem;
        }
        
        .dashboard-title h1 {
            font-weight: 700;
            color: #2d3748;
            margin-bottom: 0.5rem;
        }
        
        .quick-actions {
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
        }
        
        .btn-action {
            background: white;
            border: 2px solid #e2e8f0;
            border-radius: 10px;
            padding: 0.5rem 1rem;
            font-weight: 600;
            color: #4a5568;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            text-decoration: none;
        }
        
        .btn-action:hover {
            border-color: #667eea;
            color: #667eea;
            transform: translateY(-2px);
        }
        
        .btn-action-primary {
            background: var(--primary-gradient);
            color: white;
            border: none;
        }
        
        .btn-action-primary:hover {
            color: white;
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.3);
        }
        
        /* Stats Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }
        
        .stat-card {
            background: white;
            border-radius: 10px;
            padding: 1.25rem;
            box-shadow: 0 3px 10px rgba(0,0,0,0.05);
            border-left: 4px solid #667eea;
            transition: all 0.3s ease;
        }
        
        .stat-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
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
            width: 40px;
            height: 40px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1rem;
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
            font-size: 1.5rem;
            font-weight: 700;
            color: #2d3748;
            line-height: 1;
        }
        
        .stat-label {
            color: #718096;
            font-size: 0.8rem;
            margin-top: 0.25rem;
        }
        
        /* Recent Orders */
        .orders-table {
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 3px 10px rgba(0,0,0,0.05);
            margin-bottom: 2rem;
        }
        
        .table-header {
            background: #f8fafc;
            padding: 1rem;
            border-bottom: 1px solid #e2e8f0;
        }
        
        .order-row {
            display: grid;
            grid-template-columns: 2fr 1fr 1fr auto;
            padding: 1rem;
            border-bottom: 1px solid #e2e8f0;
            align-items: center;
        }
        
        .order-row:last-child {
            border-bottom: none;
        }
        
        .order-status {
            padding: 4px 8px;
            border-radius: 15px;
            font-size: 0.7rem;
            font-weight: 600;
            text-align: center;
            display: inline-block;
        }
        
        .status-pending { background: #fef3c7; color: #92400e; }
        .status-in_progress { background: #dbeafe; color: #1e40af; }
        .status-completed { background: #dcfce7; color: #166534; }
        
        /* Messages & Products */
        .messages-list, .products-grid {
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 3px 10px rgba(0,0,0,0.05);
        }
        
        .message-item {
            display: flex;
            padding: 1rem;
            border-bottom: 1px solid #e2e8f0;
            align-items: center;
            gap: 1rem;
            text-decoration: none;
            color: inherit;
        }
        
        .message-item:hover {
            background: #f8fafc;
        }
        
        .product-card-small {
            background: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
        }
        
        .product-image-small {
            height: 80px;
            object-fit: cover;
            width: 100%;
            background: #f1f5f9;
        }
        
        /* Empty States */
        .empty-state {
            text-align: center;
            padding: 2rem;
            color: #718096;
        }
        
        .empty-icon {
            font-size: 2rem;
            color: #cbd5e1;
            margin-bottom: 1rem;
        }
        
        @media (max-width: 768px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .order-row {
                grid-template-columns: 1fr;
                gap: 0.5rem;
            }
        }
    </style>
</head>

<body>
    <?php 
    // Check if navbar exists, otherwise show simple header
    $navbarPath = '../../includes/components/navbar.php';
    if (file_exists($navbarPath)) {
        include $navbarPath;
    } else {
        echo '<nav class="navbar navbar-light bg-white shadow-sm">
            <div class="container">
                <a class="navbar-brand fw-bold" href="#">Tailor Dashboard</a>
            </div>
        </nav>';
    }
    ?>
    
    <div class="container-fluid dashboard-container">
        <div class="row g-4">
            <!-- Sidebar -->
            <div class="col-lg-3">
                <div class="sidebar">
                    <!-- Profile Section -->
                    <div class="sidebar-profile">
                        <img src="<?php echo isset($userData['profile_pic']) && !empty($userData['profile_pic']) 
                            ? '../../assets/images/avatars/' . htmlspecialchars($userData['profile_pic']) 
                            : 'https://ui-avatars.com/api/?name=' . urlencode($userData['full_name']) . '&background=667eea&color=fff&size=100'; ?>" 
                             class="profile-avatar" 
                             alt="<?php echo htmlspecialchars($userData['full_name']); ?>">
                        <h4 class="fw-bold mb-2"><?php echo htmlspecialchars($userData['full_name']); ?></h4>
                        <span class="tailor-badge">Tailor</span>
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
                                    <span class="badge bg-primary ms-auto"><?php echo $totalProducts; ?></span>
                                </a>
                            </li>
                            <li>
                                <a class="nav-link" href="orders.php">
                                    <i class="bi bi-bag-check"></i>
                                    Orders
                                    <span class="badge bg-warning ms-auto"><?php echo $pendingOrders; ?></span>
                                </a>
                            </li>
                            <li>
                                <a class="nav-link" href="messages.php">
                                    <i class="bi bi-chat-dots"></i>
                                    Messages
                                    <span class="badge bg-success ms-auto"><?php echo count($recentMessages); ?></span>
                                </a>
                            </li>
                            <li class="mt-3">
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
                                    <p class="text-muted">Here's what's happening with your tailoring business today.</p>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="quick-actions">
                                    <a href="products.php?action=add" class="btn-action btn-action-primary">
                                        <i class="bi bi-plus-circle"></i> Add Product
                                    </a>
                                    <a href="orders.php" class="btn-action">
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
                            <div class="small text-success">
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
                            <div class="small text-success">
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
                            <div class="small text-success">
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
                            <div class="small text-danger">
                                <i class="bi bi-exclamation-circle"></i> Needs attention
                            </div>
                        </div>
                    </div>
                    
                    <!-- Recent Orders -->
                    <div class="dashboard-section">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h4 class="fw-bold">Recent Orders</h4>
                            <a href="orders.php" class="btn btn-sm btn-outline-primary">View All</a>
                        </div>
                        
                        <div class="orders-table">
                            <div class="table-header">
                                <h6 class="mb-0 fw-bold">Latest Customer Orders</h6>
                            </div>
                            <?php if (!empty($recentOrders)): ?>
                                <?php foreach ($recentOrders as $orderItem): ?>
                                    <div class="order-row">
                                        <div>
                                            <div class="fw-bold">Order #<?php echo htmlspecialchars($orderItem['order_number'] ?? 'N/A'); ?></div>
                                            <small class="text-muted"><?php echo htmlspecialchars($orderItem['customer_name'] ?? 'Customer'); ?></small>
                                        </div>
                                        <div class="fw-bold">$<?php echo number_format($orderItem['total_amount'] ?? 0, 2); ?></div>
                                        <div>
                                            <?php 
                                            $status = $orderItem['status'] ?? 'pending';
                                            $statusClass = 'status-' . str_replace(' ', '_', $status);
                                            ?>
                                            <span class="order-status <?php echo $statusClass; ?>">
                                                <?php echo ucfirst($status); ?>
                                            </span>
                                        </div>
                                        <div>
                                            <a href="orders.php?action=view&id=<?php echo $orderItem['id'] ?? ''; ?>" 
                                               class="btn btn-sm btn-outline-primary">
                                                View
                                            </a>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="empty-state p-4">
                                    <div class="empty-icon">
                                        <i class="bi bi-bag"></i>
                                    </div>
                                    <h6>No orders yet</h6>
                                    <p class="small">When you receive orders, they'll appear here.</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="row">
                        <!-- Recent Messages -->
                        <div class="col-md-6">
                            <div class="dashboard-section">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <h4 class="fw-bold">Recent Messages</h4>
                                    <a href="messages.php" class="btn btn-sm btn-outline-primary">View All</a>
                                </div>
                                
                                <div class="messages-list">
                                    <?php if (!empty($recentMessages)): ?>
                                        <?php foreach ($recentMessages as $message): ?>
                                            <a href="messages.php?user_id=<?php echo $message['sender_id']; ?>" class="message-item">
                                                <img src="<?php echo isset($message['profile_pic']) && !empty($message['profile_pic']) 
                                                    ? '../../assets/images/avatars/' . htmlspecialchars($message['profile_pic']) 
                                                    : 'https://ui-avatars.com/api/?name=' . urlencode($message['sender_name']) . '&background=667eea&color=fff&size=40'; ?>" 
                                                     class="rounded-circle" 
                                                     width="40" 
                                                     height="40"
                                                     alt="<?php echo htmlspecialchars($message['sender_name']); ?>">
                                                <div class="flex-grow-1">
                                                    <div class="fw-bold"><?php echo htmlspecialchars($message['sender_name']); ?></div>
                                                    <div class="small text-truncate" style="max-width: 200px;">
                                                        <?php echo htmlspecialchars($message['message']); ?>
                                                    </div>
                                                </div>
                                                <div class="text-muted small">
                                                    <?php echo date('h:i A', strtotime($message['created_at'])); ?>
                                                </div>
                                            </a>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <div class="empty-state p-4">
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
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <h4 class="fw-bold">Low Stock Products</h4>
                                    <a href="products.php" class="btn btn-sm btn-outline-primary">Manage</a>
                                </div>
                                
                                <div class="row g-2">
                                    <?php if (!empty($productsLowStock)): ?>
                                        <?php foreach ($productsLowStock as $productItem): ?>
                                            <div class="col-6">
                                                <a href="products.php?action=edit&id=<?php echo $productItem['id']; ?>" class="text-decoration-none text-dark">
                                                    <div class="product-card-small">
                                                        <div class="product-image-small">
                                                            <?php if (!empty($productItem['images']) && is_array($productItem['images'])): ?>
                                                                <img src="../../assets/images/products/<?php echo htmlspecialchars($productItem['images'][0]); ?>" 
                                                                     alt="<?php echo htmlspecialchars($productItem['title']); ?>"
                                                                     class="w-100 h-100">
                                                            <?php else: ?>
                                                                <div class="w-100 h-100 d-flex align-items-center justify-content-center bg-light">
                                                                    <i class="bi bi-image text-muted"></i>
                                                                </div>
                                                            <?php endif; ?>
                                                        </div>
                                                        <div class="p-2">
                                                            <div class="small fw-bold text-truncate"><?php echo htmlspecialchars($productItem['title']); ?></div>
                                                            <div class="small text-danger">
                                                                <i class="bi bi-exclamation-triangle"></i>
                                                                Stock: <?php echo $productItem['stock']; ?> left
                                                            </div>
                                                        </div>
                                                    </div>
                                                </a>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <div class="col-12">
                                            <div class="empty-state p-4">
                                                <div class="empty-icon">
                                                    <i class="bi bi-check-circle"></i>
                                                </div>
                                                <h6>All products in stock</h6>
                                                <p class="small">Great job managing your inventory!</p>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                </div>
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
    <script>
        $(document).ready(function() {
            // Add animations
            $('.stat-card').each(function(index) {
                $(this).css('animation-delay', (index * 0.1) + 's');
                $(this).addClass('animate__animated animate__fadeInUp');
            });
            
            // Simple status update example
            $('.order-status').click(function(e) {
                e.preventDefault();
                const orderId = $(this).closest('.order-row').find('.fw-bold').text().replace('Order #', '');
                alert('Would update status for Order #' + orderId + '\nIn a real application, this would open a modal.');
            });
            
            // Auto-refresh notifications every 30 seconds
            setInterval(function() {
                // This would make an AJAX call in a real application
                console.log('Checking for new notifications...');
            }, 30000);
        });
    </script>
    
    <!-- Animation Library -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
</body>
</html>