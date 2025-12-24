<?php
require_once dirname(__DIR__, 2) . '/config.php';
require_once ROOT_PATH . '/includes/classes/Database.php';
require_once ROOT_PATH . '/includes/classes/User.php';
require_once ROOT_PATH . '/includes/classes/Order.php';
require_once ROOT_PATH . '/includes/classes/Product.php';

if (session_status() === PHP_SESSION_NONE) session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'tailor') {
    header('Location: ../auth/login.php');
    exit();
}

$db = Database::getInstance();
$userObj = new User();
$orderObj = new Order();
$productObj = new Product();

$tailorId = $_SESSION['user_id'];
$userData = $userObj->getUserById($tailorId);

// Get stats
$totalOrders = $orderObj->getTotalOrdersByTailor($tailorId);
$recentOrders = $orderObj->getRecentOrdersByTailor($tailorId, 5);
$recentProducts = $productObj->getProductsByTailor($tailorId, 10);
$productsLowStock = $productObj->getLowStockProducts($tailorId);

// Get revenue
$db->query("SELECT SUM(total_amount) as revenue FROM orders WHERE tailor_id = :tailor_id AND payment_status = 'paid'");
$db->bind(':tailor_id', $tailorId);
$revenueResult = $db->single();
$revenue = $revenueResult['revenue'] ?? 0;

// Get today's orders
$db->query("SELECT COUNT(*) as count FROM orders WHERE tailor_id = :tailor_id AND DATE(created_at) = CURDATE()");
$db->bind(':tailor_id', $tailorId);
$todayResult = $db->single();
$todayOrders = $todayResult['count'] ?? 0;

// Get pending orders
$db->query("SELECT COUNT(*) as count FROM orders WHERE tailor_id = :tailor_id AND status = 'pending'");
$db->bind(':tailor_id', $tailorId);
$pendingResult = $db->single();
$pendingOrders = $pendingResult['count'] ?? 0;

// Get total products
$db->query("SELECT COUNT(*) as count FROM products WHERE tailor_id = :tailor_id AND status = 'active'");
$db->bind(':tailor_id', $tailorId);
$productsResult = $db->single();
$totalProducts = $productsResult['count'] ?? 0;

// Get unread messages
$db->query("SELECT COUNT(*) as count FROM messages WHERE receiver_id = :receiver_id AND is_read = 0");
$db->bind(':receiver_id', $tailorId);
$messagesResult = $db->single();
$unreadMessages = $messagesResult['count'] ?? 0;

// Get recent messages
$db->query("
    SELECT m.*, u.full_name as sender_name, u.profile_pic 
    FROM messages m 
    JOIN users u ON m.sender_id = u.id 
    WHERE m.receiver_id = :receiver_id 
    ORDER BY m.created_at DESC 
    LIMIT 5
");
$db->bind(':receiver_id', $tailorId);
$recentMessages = $db->resultSet();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tailor Dashboard - <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
        body {
            background-color: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .dashboard-wrapper {
            display: flex;
            min-height: 100vh;
        }
        
        /* Sidebar */
        .sidebar {
            width: 250px;
            background: linear-gradient(180deg, #2c3e50 0%, #34495e 100%);
            color: white;
            position: fixed;
            height: 100vh;
            overflow-y: auto;
            z-index: 1000;
        }
        
        .sidebar-brand {
            padding: 1.5rem;
            text-align: center;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        
        .profile-section {
            padding: 1.5rem;
            text-align: center;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        
        .profile-img {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            border: 3px solid rgba(255,255,255,0.3);
            object-fit: cover;
            margin-bottom: 1rem;
        }
        
        .tailor-badge {
            background: rgba(255,255,255,0.2);
            padding: 3px 10px;
            border-radius: 15px;
            font-size: 0.8rem;
            display: inline-block;
            margin-top: 5px;
        }
        
        .sidebar-nav {
            padding: 1rem 0;
        }
        
        .nav-item {
            margin-bottom: 0.25rem;
        }
        
        .nav-link {
            color: rgba(255,255,255,0.8);
            padding: 0.75rem 1.5rem;
            display: flex;
            align-items: center;
            transition: all 0.3s;
            border-left: 4px solid transparent;
        }
        
        .nav-link:hover, .nav-link.active {
            color: white;
            background: rgba(255,255,255,0.1);
            border-left-color: #667eea;
            text-decoration: none;
        }
        
        .nav-link i {
            width: 20px;
            margin-right: 10px;
            font-size: 1.1rem;
        }
        
        .nav-badge {
            margin-left: auto;
            font-size: 0.75rem;
        }
        
        /* Main Content */
        .main-content {
            flex: 1;
            margin-left: 250px;
            padding: 20px;
        }
        
        .header {
            background: white;
            padding: 1.5rem;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 1.5rem;
        }
        
        .welcome-text h1 {
            font-size: 1.8rem;
            margin-bottom: 0.5rem;
            color: #2c3e50;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .stat-card {
            background: white;
            border-radius: 10px;
            padding: 1.5rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            transition: transform 0.3s;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
        }
        
        .stat-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }
        
        .stat-icon {
            width: 50px;
            height: 50px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
        }
        
        .stat-icon.revenue { background: rgba(16, 185, 129, 0.1); color: #10b981; }
        .stat-icon.orders { background: rgba(245, 158, 11, 0.1); color: #f59e0b; }
        .stat-icon.products { background: rgba(59, 130, 246, 0.1); color: #3b82f6; }
        .stat-icon.pending { background: rgba(239, 68, 68, 0.1); color: #ef4444; }
        .stat-icon.messages { background: rgba(139, 92, 246, 0.1); color: #8b5cf6; }
        .stat-icon.today { background: rgba(14, 165, 233, 0.1); color: #0ea5e9; }
        
        .stat-value {
            font-size: 1.8rem;
            font-weight: 700;
            color: #2c3e50;
            line-height: 1;
        }
        
        .stat-label {
            color: #6c757d;
            font-size: 0.9rem;
            margin-top: 0.5rem;
        }
        
        .section-card {
            background: white;
            border-radius: 10px;
            padding: 1.5rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 1.5rem;
        }
        
        .section-title {
            font-size: 1.2rem;
            font-weight: 600;
            margin-bottom: 1.5rem;
            color: #2c3e50;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .order-row {
            display: grid;
            grid-template-columns: 2fr 1fr 1fr auto;
            padding: 1rem 0;
            border-bottom: 1px solid #eee;
            align-items: center;
        }
        
        .order-row:last-child {
            border-bottom: none;
        }
        
        .status-badge {
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }
        
        .status-pending { background: #fef3c7; color: #92400e; }
        .status-processing { background: #dbeafe; color: #1e40af; }
        .status-completed { background: #d1fae5; color: #065f46; }
        .status-cancelled { background: #fee2e2; color: #991b1b; }
        
        .message-item {
            display: flex;
            padding: 1rem 0;
            border-bottom: 1px solid #eee;
            align-items: center;
        }
        
        .message-item:last-child {
            border-bottom: none;
        }
        
        .message-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
            margin-right: 1rem;
        }
        
        .product-card-sm {
            border: 1px solid #dee2e6;
            border-radius: 8px;
            overflow: hidden;
            transition: all 0.3s;
        }
        
        .product-card-sm:hover {
            border-color: #667eea;
        }
        
        .product-img-sm {
            height: 100px;
            object-fit: cover;
            width: 100%;
        }
        
        .stock-warning {
            color: #dc3545;
            font-size: 0.8rem;
        }
        
        @media (max-width: 992px) {
            .sidebar {
                width: 70px;
                overflow: hidden;
            }
            
            .sidebar .nav-text,
            .sidebar-brand span,
            .profile-section h5 {
                display: none;
            }
            
            .main-content {
                margin-left: 70px;
            }
            
            .profile-img {
                width: 40px;
                height: 40px;
            }
        }
        
        @media (max-width: 768px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .order-row {
                grid-template-columns: 1fr;
                gap: 0.5rem;
                text-align: center;
            }
            
            .sidebar {
                display: none;
            }
            
            .main-content {
                margin-left: 0;
            }
        }
    </style>
</head>
<body>
    <div class="dashboard-wrapper">
        <!-- Sidebar -->
        <div class="sidebar">
            <div class="sidebar-brand">
                <h4 class="mb-0"><i class="bi bi-scissors"></i> <span>Tailor Hub</span></h4>
            </div>
            
            <div class="profile-section">
                <img src="../../assets/images/avatars/<?php echo !empty($userData['profile_pic']) ? htmlspecialchars($userData['profile_pic']) : 'default.jpg'; ?>" 
                     class="profile-img" 
                     alt="<?php echo htmlspecialchars($userData['full_name']); ?>"
                     onerror="this.src='../../assets/images/avatars/default.jpg'">
                <h5><?php echo htmlspecialchars(explode(' ', $userData['full_name'])[0]); ?></h5>
                <span class="tailor-badge">Tailor</span>
            </div>
            
            <div class="sidebar-nav">
                <ul class="nav flex-column">
                    <li class="nav-item">
                        <a class="nav-link active" href="dashboard.php">
                            <i class="bi bi-speedometer2"></i>
                            <span class="nav-text">Dashboard</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="products.php">
                            <i class="bi bi-box"></i>
                            <span class="nav-text">Products</span>
                            <?php if ($totalProducts > 0): ?>
                            <span class="nav-badge badge bg-primary"><?php echo $totalProducts; ?></span>
                            <?php endif; ?>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="orders.php">
                            <i class="bi bi-bag-check"></i>
                            <span class="nav-text">Orders</span>
                            <?php if ($pendingOrders > 0): ?>
                            <span class="nav-badge badge bg-warning"><?php echo $pendingOrders; ?></span>
                            <?php endif; ?>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="messages.php">
                            <i class="bi bi-chat-dots"></i>
                            <span class="nav-text">Messages</span>
                            <?php if ($unreadMessages > 0): ?>
                            <span class="nav-badge badge bg-danger"><?php echo $unreadMessages; ?></span>
                            <?php endif; ?>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="profile.php">
                            <i class="bi bi-person"></i>
                            <span class="nav-text">Profile</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="settings.php">
                            <i class="bi bi-gear"></i>
                            <span class="nav-text">Settings</span>
                        </a>
                    </li>
                    <li class="nav-item mt-4">
                        <a class="nav-link text-danger" href="../../pages/auth/logout.php">
                            <i class="bi bi-box-arrow-right"></i>
                            <span class="nav-text">Logout</span>
                        </a>
                    </li>
                </ul>
            </div>
        </div>
        
        <!-- Main Content -->
        <div class="main-content">
            <!-- Header -->
            <div class="header">
                <div class="d-flex justify-content-between align-items-center">
                    <div class="welcome-text">
                        <h1>Welcome back, <?php echo htmlspecialchars(explode(' ', $userData['full_name'])[0]); ?>! 👋</h1>
                        <p class="text-muted mb-0">Here's what's happening with your tailoring business today.</p>
                    </div>
                    <div class="quick-actions">
                        <a href="products.php?action=add" class="btn btn-primary me-2">
                            <i class="bi bi-plus-circle me-1"></i> Add Product
                        </a>
                        <a href="orders.php" class="btn btn-outline-primary">
                            <i class="bi bi-eye me-1"></i> View Orders
                        </a>
                    </div>
                </div>
            </div>
            
            <!-- Stats Cards -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-header">
                        <div>
                            <div class="stat-value">$<?php echo number_format($revenue, 2); ?></div>
                            <div class="stat-label">Total Revenue</div>
                        </div>
                        <div class="stat-icon revenue">
                            <i class="bi bi-currency-dollar"></i>
                        </div>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-header">
                        <div>
                            <div class="stat-value"><?php echo $totalOrders; ?></div>
                            <div class="stat-label">Total Orders</div>
                        </div>
                        <div class="stat-icon orders">
                            <i class="bi bi-bag-check"></i>
                        </div>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-header">
                        <div>
                            <div class="stat-value"><?php echo $todayOrders; ?></div>
                            <div class="stat-label">Today's Orders</div>
                        </div>
                        <div class="stat-icon today">
                            <i class="bi bi-calendar-day"></i>
                        </div>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-header">
                        <div>
                            <div class="stat-value"><?php echo $totalProducts; ?></div>
                            <div class="stat-label">Products</div>
                        </div>
                        <div class="stat-icon products">
                            <i class="bi bi-box"></i>
                        </div>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-header">
                        <div>
                            <div class="stat-value"><?php echo $pendingOrders; ?></div>
                            <div class="stat-label">Pending Orders</div>
                        </div>
                        <div class="stat-icon pending">
                            <i class="bi bi-clock"></i>
                        </div>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-header">
                        <div>
                            <div class="stat-value"><?php echo $unreadMessages; ?></div>
                            <div class="stat-label">Unread Messages</div>
                        </div>
                        <div class="stat-icon messages">
                            <i class="bi bi-chat-left-text"></i>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Recent Orders -->
            <div class="section-card">
                <div class="section-title">
                    <span>Recent Orders</span>
                    <a href="orders.php" class="btn btn-sm btn-outline-primary">View All</a>
                </div>
                
                <?php if (!empty($recentOrders)): ?>
                    <div class="table-responsive">
                        <div class="order-row" style="font-weight: 600; background: #f8f9fa; border-radius: 5px;">
                            <div>Customer / Order #</div>
                            <div>Amount</div>
                            <div>Status</div>
                            <div>Action</div>
                        </div>
                        <?php foreach ($recentOrders as $orderItem): ?>
                        <div class="order-row">
                            <div>
                                <div class="fw-bold"><?php echo htmlspecialchars($orderItem['customer_name'] ?? 'Customer'); ?></div>
                                <small class="text-muted">#<?php echo $orderItem['order_number'] ?? $orderItem['id']; ?></small>
                            </div>
                            <div class="fw-bold">$<?php echo number_format($orderItem['total_amount'] ?? 0, 2); ?></div>
                            <div>
                                <?php 
                                $status = $orderItem['status'] ?? 'pending';
                                $statusClass = 'status-' . str_replace(' ', '_', $status);
                                ?>
                                <span class="status-badge <?php echo $statusClass; ?>">
                                    <?php echo ucfirst($status); ?>
                                </span>
                            </div>
                            <div>
                                <a href="order-details.php?id=<?php echo $orderItem['id']; ?>" class="btn btn-sm btn-outline-primary">
                                    View
                                </a>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="text-center py-4">
                        <i class="bi bi-bag display-4 text-muted mb-3"></i>
                        <h5>No orders yet</h5>
                        <p class="text-muted">When you receive orders, they'll appear here.</p>
                        <a href="../../pages/products/index.php" class="btn btn-primary">
                            Promote Your Products
                        </a>
                    </div>
                <?php endif; ?>
            </div>
            
            <div class="row">
                <!-- Recent Messages -->
                <div class="col-md-6">
                    <div class="section-card">
                        <div class="section-title">
                            <span>Recent Messages</span>
                            <a href="messages.php" class="btn btn-sm btn-outline-primary">View All</a>
                        </div>
                        
                        <?php if (!empty($recentMessages)): ?>
                            <?php foreach ($recentMessages as $message): ?>
                            <a href="messages.php?user_id=<?php echo $message['sender_id']; ?>" class="message-item text-decoration-none text-dark">
                                <img src="../../assets/images/avatars/<?php echo !empty($message['profile_pic']) ? htmlspecialchars($message['profile_pic']) : 'default.jpg'; ?>" 
                                     class="message-avatar"
                                     alt="<?php echo htmlspecialchars($message['sender_name']); ?>"
                                     onerror="this.src='../../assets/images/avatars/default.jpg'">
                                <div class="flex-grow-1">
                                    <div class="fw-bold"><?php echo htmlspecialchars($message['sender_name']); ?></div>
                                    <div class="text-truncate" style="max-width: 200px;">
                                        <?php echo htmlspecialchars($message['message']); ?>
                                    </div>
                                </div>
                                <div class="text-muted small text-nowrap">
                                    <?php echo date('h:i A', strtotime($message['created_at'])); ?>
                                </div>
                            </a>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="text-center py-4">
                                <i class="bi bi-chat display-4 text-muted mb-3"></i>
                                <h5>No messages</h5>
                                <p class="text-muted">Customer messages will appear here.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Low Stock Products -->
                <div class="col-md-6">
                    <div class="section-card">
                        <div class="section-title">
                            <span>Low Stock Products</span>
                            <a href="products.php" class="btn btn-sm btn-outline-primary">Manage</a>
                        </div>
                        
                        <?php if (!empty($productsLowStock)): ?>
                            <div class="row g-3">
                                <?php foreach ($productsLowStock as $productItem): ?>
                                <div class="col-6">
                                    <a href="products.php?action=edit&id=<?php echo $productItem['id']; ?>" class="text-decoration-none text-dark">
                                        <div class="product-card-sm">
                                            <?php 
                                            $images = json_decode($productItem['images'] ?? '[]', true);
                                            $firstImage = !empty($images) && is_array($images) ? $images[0] : 'default.jpg';
                                            ?>
                                            <img src="../../assets/images/products/<?php echo htmlspecialchars($firstImage); ?>" 
                                                 class="product-img-sm"
                                                 alt="<?php echo htmlspecialchars($productItem['title']); ?>"
                                                 onerror="this.src='../../assets/images/products/default.jpg'">
                                            <div class="p-2">
                                                <div class="small fw-bold text-truncate"><?php echo htmlspecialchars($productItem['title']); ?></div>
                                                <div class="stock-warning">
                                                    <i class="bi bi-exclamation-triangle"></i>
                                                    Stock: <?php echo $productItem['stock']; ?>
                                                </div>
                                            </div>
                                        </div>
                                    </a>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="text-center py-4">
                                <i class="bi bi-check-circle display-4 text-success mb-3"></i>
                                <h5>All products in stock</h5>
                                <p class="text-muted">Great job managing your inventory!</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Simple dashboard interactions
        document.addEventListener('DOMContentLoaded', function() {
            // Update status on click
            document.querySelectorAll('.status-badge').forEach(badge => {
                badge.addEventListener('click', function(e) {
                    e.preventDefault();
                    const orderId = this.closest('.order-row').querySelector('.text-muted').textContent.replace('#', '');
                    console.log('Update status for order:', orderId);
                    // In real app, this would open a modal
                });
            });
            
            // Auto-refresh every 60 seconds
            setInterval(() => {
                console.log('Refreshing dashboard data...');
                // In real app, fetch updated stats via AJAX
            }, 60000);
            
            // Mobile sidebar toggle
            const sidebarToggle = document.createElement('button');
            sidebarToggle.className = 'btn btn-primary d-lg-none position-fixed';
            sidebarToggle.style.bottom = '20px';
            sidebarToggle.style.right = '20px';
            sidebarToggle.style.zIndex = '1000';
            sidebarToggle.innerHTML = '<i class="bi bi-list"></i>';
            document.body.appendChild(sidebarToggle);
            
            sidebarToggle.addEventListener('click', function() {
                document.querySelector('.sidebar').classList.toggle('d-none');
            });
        });
    </script>
</body>
</html>