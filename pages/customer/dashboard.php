<?php
require_once dirname(__DIR__, 2) . '/config.php';
require_once ROOT_PATH . '/includes/classes/Database.php';
require_once ROOT_PATH . '/includes/classes/User.php';

if (session_status() === PHP_SESSION_NONE) session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'customer') {
    header('Location: ../auth/login.php');
    exit();
}

$db = Database::getInstance();
$user = new User();
$userId = $_SESSION['user_id'];

$userData = $user->getUserById($userId);
if (!$userData) {
    header('Location: ../auth/login.php');
    exit();
}

// Database stats
$db->query("SELECT COUNT(*) as count FROM orders WHERE customer_id = :user_id");
$db->bind(':user_id', $userId);
$totalOrders = $db->single()['count'] ?? 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Dashboard - <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #667eea;
            --primary-dark: #5a67d8;
            --secondary: #764ba2;
            --success: #10b981;
            --warning: #f59e0b;
            --danger: #ef4444;
            --light: #f8fafc;
            --dark: #1e293b;
        }
 edit       
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f5f7fa;
            color: #333;
        }
        
        .dashboard-container {
            min-height: 100vh;
        }
        
        /* Sidebar */
        .sidebar {
            background: white;
            border-right: 1px solid #e2e8f0;
            height: 100vh;
            position: sticky;
            top: 0;
            padding: 20px 0;
        }
        
        .user-profile {
            text-align: center;
            padding: 20px;
            border-bottom: 1px solid #e2e8f0;
            margin-bottom: 20px;
        }
        
        .user-avatar {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            object-fit: cover;
            border: 4px solid var(--primary);
            margin-bottom: 15px;
        }
        
        .user-name {
            font-weight: 600;
            color: var(--dark);
            margin-bottom: 5px;
        }
        
        .user-role {
            color: var(--primary);
            font-size: 0.9rem;
            margin-bottom: 15px;
        }
        
        .sidebar-nav .nav-link {
            color: #64748b;
            padding: 12px 20px;
            margin: 5px 0;
            border-radius: 8px;
            display: flex;
            align-items: center;
            transition: all 0.3s ease;
        }
        
        .sidebar-nav .nav-link:hover {
            background-color: #f1f5f9;
            color: var(--primary);
            transform: translateX(5px);
        }
        
        .sidebar-nav .nav-link.active {
            background-color: rgba(102, 126, 234, 0.1);
            color: var(--primary);
            font-weight: 500;
        }
        
        .sidebar-nav .nav-link i {
            width: 24px;
            font-size: 1.1rem;
        }
        
        /* Main Content */
        .main-content {
            padding: 30px;
        }
        
        .welcome-section {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
            border-radius: 15px;
            padding: 30px;
            margin-bottom: 30px;
        }
        
        .welcome-title {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 10px;
        }
        
        /* Stats Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
            border-left: 4px solid var(--primary);
            transition: transform 0.3s ease;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
        }
        
        .stat-card.orders { border-left-color: var(--primary); }
        .stat-card.pending { border-left-color: var(--warning); }
        .stat-card.wishlist { border-left-color: var(--danger); }
        .stat-card.spent { border-left-color: var(--success); }
        
        .stat-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
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
        
        .stat-card.orders .stat-icon { background: rgba(102, 126, 234, 0.1); color: var(--primary); }
        .stat-card.pending .stat-icon { background: rgba(245, 158, 11, 0.1); color: var(--warning); }
        .stat-card.wishlist .stat-icon { background: rgba(239, 68, 68, 0.1); color: var(--danger); }
        .stat-card.spent .stat-icon { background: rgba(16, 185, 129, 0.1); color: var(--success); }
        
        .stat-value {
            font-size: 2rem;
            font-weight: 700;
            color: var(--dark);
            line-height: 1;
        }
        
        .stat-label {
            color: #64748b;
            font-size: 0.9rem;
            margin-top: 5px;
        }
        
        /* Recent Orders & Wishlist */
        .section-card {
            background: white;
            border-radius: 12px;
            padding: 25px;
            margin-bottom: 30px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
        }
        
        .section-title {
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--dark);
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .order-item {
            display: flex;
            align-items: center;
            padding: 15px;
            border-bottom: 1px solid #e2e8f0;
            transition: background-color 0.3s ease;
        }
        
        .order-item:hover {
            background-color: #f8fafc;
        }
        
        .order-item:last-child {
            border-bottom: none;
        }
        
        .order-image {
            width: 70px;
            height: 70px;
            border-radius: 8px;
            object-fit: cover;
            margin-right: 15px;
        }
        
        .order-details {
            flex: 1;
        }
        
        .order-title {
            font-weight: 600;
            margin-bottom: 5px;
        }
        
        .order-meta {
            color: #64748b;
            font-size: 0.9rem;
            margin-bottom: 5px;
        }
        
        .order-status {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        
        .status-pending { background: #fef3c7; color: #92400e; }
        .status-processing { background: #dbeafe; color: #1e40af; }
        .status-completed { background: #dcfce7; color: #166534; }
        .status-cancelled { background: #fee2e2; color: #991b1b; }
        
        /* Wishlist Grid */
        .wishlist-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 20px;
        }
        
        .wishlist-item {
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
            transition: transform 0.3s ease;
        }
        
        .wishlist-item:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
        }
        
        .wishlist-image {
            width: 100%;
            height: 150px;
            object-fit: cover;
        }
        
        .wishlist-details {
            padding: 15px;
        }
        
        .wishlist-title {
            font-weight: 600;
            margin-bottom: 5px;
            font-size: 0.95rem;
        }
        
        .wishlist-price {
            color: var(--primary);
            font-weight: 600;
            font-size: 1.1rem;
        }
        
        /* Empty States */
        .empty-state {
            text-align: center;
            padding: 40px 20px;
            color: #64748b;
        }
        
        .empty-icon {
            font-size: 3rem;
            color: #cbd5e1;
            margin-bottom: 15px;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .sidebar {
                height: auto;
                position: static;
            }
            
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .wishlist-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }
        
        @media (max-width: 576px) {
            .wishlist-grid {
                grid-template-columns: 1fr;
            }
            
            .main-content {
                padding: 15px;
            }
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <div class="row g-0">
            <!-- Sidebar -->
            <div class="col-lg-3 col-xl-2">
                <div class="sidebar">
                    <!-- User Profile -->
                    <div class="user-profile">
                        <img src="<?php echo $profilePic; ?>" 
                             class="user-avatar" 
                             alt="<?php echo htmlspecialchars($userData['full_name']); ?>"
                             onerror="this.src='<?php echo SITE_URL; ?>/assets/images/avatars/default.jpg'">
                        <h5 class="user-name"><?php echo htmlspecialchars($userData['full_name']); ?></h5>
                        <div class="user-role">Customer</div>
                        <a href="profile.php" class="btn btn-sm btn-outline-primary">Edit Profile</a>
                        <a href="<?php echo SITE_URL; ?>/pages/auth/logout.php">
                            <i class="bi bi-box-arrow-right me-2"></i> Logout
                        </a>
                    </div>
                    
                    <!-- Navigation -->
                    <div class="sidebar-nav">
                        <a href="dashboard.php" class="nav-link active">
                            <i class="bi bi-speedometer2"></i>
                            <span>Dashboard</span>
                        </a>
                        <a href="orders.php" class="nav-link">
                            <i class="bi bi-bag"></i>
                            <span>My Orders</span>
                            <?php if ($totalOrders > 0): ?>
                            <span class="badge bg-primary ms-auto"><?php echo $totalOrders; ?></span>
                            <?php endif; ?>
                        </a>
                        <a href="wishlist.php" class="nav-link">
                            <i class="bi bi-heart"></i>
                            <span>Wishlist</span>
                            <?php if ($wishlistCount > 0): ?>
                            <span class="badge bg-danger ms-auto"><?php echo $wishlistCount; ?></span>
                            <?php endif; ?>
                        </a>
                        <a href="messages.php" class="nav-link">
                            <i class="bi bi-chat-dots"></i>
                            <span>Messages</span>
                            <?php if ($unreadMessages > 0): ?>
                            <span class="badge bg-success ms-auto"><?php echo $unreadMessages; ?></span>
                            <?php endif; ?>
                        </a>
                        <a href="address.php" class="nav-link">
                            <i class="bi bi-geo-alt"></i>
                            <span>Addresses</span>
                        </a>
                        <a href="reviews.php" class="nav-link">
                            <i class="bi bi-star"></i>
                            <span>My Reviews</span>
                            <?php if ($reviewsGiven > 0): ?>
                            <span class="badge bg-warning ms-auto"><?php echo $reviewsGiven; ?></span>
                            <?php endif; ?>
                        </a>
                        <a href="settings.php" class="nav-link">
                            <i class="bi bi-gear"></i>
                            <span>Settings</span>
                        </a>
                        <a href="<?php echo SITE_URL; ?>/pages/auth/logout.php" class="nav-link text-danger">
                            <i class="bi bi-box-arrow-right"></i>
                            <span>Logout</span>
                        </a>
                    </div>
                </div>
            </div>
            
            <!-- Main Content -->
            <div class="col-lg-9 col-xl-10">
                <div class="main-content">
                    <!-- Welcome Section -->
                    <div class="welcome-section">
                        <div class="row align-items-center">
                            <div class="col-md-8">
                                <h1 class="welcome-title">Welcome back, <?php echo htmlspecialchars(explode(' ', $userData['full_name'])[0]); ?>! 👋</h1>
                                <p>Here's what's happening with your account today.</p>
                            </div>
                            <div class="col-md-4 text-end">
                                <a href="<?php echo SITE_URL; ?>/pages/products/" class="btn btn-light btn-lg">
                                    <i class="bi bi-search me-2"></i> Shop Now
                                </a>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Statistics -->
                    <div class="stats-grid">
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
                        </div>
                        
                        <div class="stat-card wishlist">
                            <div class="stat-header">
                                <div>
                                    <div class="stat-value"><?php echo $wishlistCount; ?></div>
                                    <div class="stat-label">Wishlist Items</div>
                                </div>
                                <div class="stat-icon">
                                    <i class="bi bi-heart"></i>
                                </div>
                            </div>
                        </div>
                        
                        <div class="stat-card spent">
                            <div class="stat-header">
                                <div>
                                    <div class="stat-value">$<?php echo number_format($totalSpent, 2); ?></div>
                                    <div class="stat-label">Total Spent</div>
                                </div>
                                <div class="stat-icon">
                                    <i class="bi bi-currency-dollar"></i>
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
                            <?php foreach ($recentOrders as $order): 
                                // Get first product image
                                $images = json_decode($order['images'] ?? '[]', true);
                                $productImage = !empty($images) ? $images[0] : SITE_URL . '/assets/images/products/default.jpg';
                                $productImage = strpos($productImage, 'http') === 0 ? $productImage : SITE_URL . '/assets/images/products/' . $productImage;
                            ?>
                            <div class="order-item">
                                <img src="<?php echo htmlspecialchars($productImage); ?>" 
                                     class="order-image" 
                                     alt="<?php echo htmlspecialchars($order['product_title'] ?? 'Product'); ?>"
                                     onerror="this.src='<?php echo SITE_URL; ?>/assets/images/products/default.jpg'">
                                <div class="order-details">
                                    <div class="order-title">
                                        <?php echo htmlspecialchars($order['product_title'] ?? 'Order #' . $order['order_number']); ?>
                                    </div>
                                    <div class="order-meta">
                                        Order #<?php echo htmlspecialchars($order['order_number']); ?> • 
                                        $<?php echo number_format($order['total_amount'] ?? 0, 2); ?> • 
                                        <?php echo date('M d, Y', strtotime($order['created_at'] ?? '')); ?>
                                    </div>
                                    <span class="order-status status-<?php echo str_replace(' ', '-', strtolower($order['status'] ?? 'pending')); ?>">
                                        <?php echo ucfirst($order['status'] ?? 'Pending'); ?>
                                    </span>
                                </div>
                                <a href="orders.php?action=view&id=<?php echo $order['id']; ?>" 
                                   class="btn btn-sm btn-outline-primary">
                                    View Details
                                </a>
                            </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="empty-state">
                                <div class="empty-icon">
                                    <i class="bi bi-bag"></i>
                                </div>
                                <h5>No orders yet</h5>
                                <p>Start shopping to see your orders here</p>
                                <a href="<?php echo SITE_URL; ?>/pages/products/" class="btn btn-primary">
                                    <i class="bi bi-cart me-2"></i> Start Shopping
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Wishlist -->
                    <div class="section-card">
                        <div class="section-title">
                            <span>Wishlist Items</span>
                            <a href="wishlist.php" class="btn btn-sm btn-outline-primary">View All</a>
                        </div>
                        
                        <?php if (!empty($wishlistItems)): ?>
                            <div class="wishlist-grid">
                                <?php foreach ($wishlistItems as $item): 
                                    // Get first product image
                                    $images = json_decode($item['images'] ?? '[]', true);
                                    $productImage = !empty($images) ? $images[0] : SITE_URL . '/assets/images/products/default.jpg';
                                    $productImage = strpos($productImage, 'http') === 0 ? $productImage : SITE_URL . '/assets/images/products/' . $productImage;
                                ?>
                                <div class="wishlist-item">
                                    <img src="<?php echo htmlspecialchars($productImage); ?>" 
                                         class="wishlist-image" 
                                         alt="<?php echo htmlspecialchars($item['title']); ?>"
                                         onerror="this.src='<?php echo SITE_URL; ?>/assets/images/products/default.jpg'">
                                    <div class="wishlist-details">
                                        <div class="wishlist-title"><?php echo htmlspecialchars($item['title']); ?></div>
                                        <div class="wishlist-price">$<?php echo number_format($item['price'] ?? 0, 2); ?></div>
                                        <div class="mt-3">
                                            <a href="<?php echo SITE_URL; ?>/pages/products/view.php?id=<?php echo $item['id']; ?>" 
                                               class="btn btn-sm btn-primary w-100">
                                                <i class="bi bi-cart-plus me-1"></i> Add to Cart
                                            </a>
                                        </div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="empty-state">
                                <div class="empty-icon">
                                    <i class="bi bi-heart"></i>
                                </div>
                                <h5>Your wishlist is empty</h5>
                                <p>Save items you love for later</p>
                                <a href="<?php echo SITE_URL; ?>/pages/products/" class="btn btn-primary">
                                    <i class="bi bi-search me-2"></i> Browse Products
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Simple animations
        document.addEventListener('DOMContentLoaded', function() {
            // Add fade-in animation to cards
            const cards = document.querySelectorAll('.stat-card, .order-item, .wishlist-item');
            cards.forEach((card, index) => {
                card.style.opacity = '0';
                card.style.transform = 'translateY(20px)';
                card.style.transition = 'all 0.5s ease';
                
                setTimeout(() => {
                    card.style.opacity = '1';
                    card.style.transform = 'translateY(0)';
                }, index * 100);
            });
            
            // Update last active time
            function updateLastActive() {
                // This would send an AJAX request in a real application
                console.log('Updating last active time...');
            }
            
            // Update every 5 minutes
            setInterval(updateLastActive, 5 * 60 * 1000);
        });
    </script>
</body>
</html>





<?php
/*require_once '../../config.php';
require_once '../../includes/classes/User.php';
require_once '../../includes/classes/Order.php';
require_once '../../includes/classes/Product.php';

// Check authentication
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'customer') {
    header('Location: ../auth/login.php');
    exit();
}

$user = new User();
$order = new Order();
$product = new Product();

$userData = $user->getUserById($_SESSION['user_id']);
$recentOrders = $order->getRecentOrders($_SESSION['user_id']);
$wishlistItems = $product->getWishlistItems($_SESSION['user_id']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Playfair+Display:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/dashboard.css">
    <style>
        :root {
            --primary-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            --secondary-gradient: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            --success-gradient: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);
            --warning-gradient: linear-gradient(135deg, #fa709a 0%, #fee140 100%);
            --glass-bg: rgba(255, 255, 255, 0.1);
            --glass-border: rgba(255, 255, 255, 0.2);
        }
        
        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(rgba(0, 0, 0, 0.7), rgba(0, 0, 0, 0.7)), 
                        url('<?php echo SITE_URL; ?>/assets/images/banners/dashboard-bg.jpg');
            background-size: cover;
            background-attachment: fixed;
            color: #fff;
            min-height: 100vh;
        }
        
        .glass-card {
            background: var(--glass-bg);
            backdrop-filter: blur(10px);
            border: 1px solid var(--glass-border);
            border-radius: 20px;
            padding: 2rem;
            transition: transform 0.3s ease;
        }
        
        .glass-card:hover {
            transform: translateY(-5px);
        }
        
        .dashboard-header {
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.9), rgba(118, 75, 162, 0.9));
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 2rem;
            margin-bottom: 2rem;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        .stat-card {
            background: var(--glass-bg);
            border-radius: 15px;
            padding: 1.5rem;
            text-align: center;
            margin-bottom: 1.5rem;
            border: 1px solid var(--glass-border);
            transition: all 0.3s ease;
        }
        
        .stat-card:hover {
            background: rgba(255, 255, 255, 0.15);
            transform: scale(1.05);
        }
        
        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1rem;
            font-size: 1.5rem;
        }
        
        .product-card {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 15px;
            overflow: hidden;
            transition: all 0.3s ease;
            color: #333;
        }
        
        .product-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 20px 40px rgba(0,0,0,0.3);
        }
        
        .product-img {
            height: 180px;
            object-fit: cover;
            width: 100%;
        }
        
        .nav-tabs .nav-link {
            color: rgba(255, 255, 255, 0.7);
            border: none;
            border-bottom: 3px solid transparent;
            padding: 0.75rem 1.5rem;
            font-weight: 500;
        }
        
        .nav-tabs .nav-link.active {
            color: #fff;
            background: transparent;
            border-bottom: 3px solid #667eea;
        }
        
        .btn-glow {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            color: white;
            padding: 12px 30px;
            border-radius: 50px;
            font-weight: 600;
            position: relative;
            overflow: hidden;
            z-index: 1;
        }
        
        .btn-glow:hover {
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(102, 126, 234, 0.3);
        }
        
        .welcome-text {
            font-family: 'Playfair Display', serif;
            font-size: 2.5rem;
            font-weight: 600;
            background: linear-gradient(135deg, #fff, #a8edea);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
    </style>
</head>
<body>
    <?php include '../../includes/components/navbar.php'; ?>
    
    <div class="container-fluid py-4">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-lg-3 mb-4">
                <div class="glass-card">
                    <!-- User Profile -->
                    <div class="text-center mb-4">
                        <div class="position-relative d-inline-block">
                            <img src="<?php echo SITE_URL; ?>/assets/images/avatars/<?php echo $userData['profile_pic'] ?: 'default.jpg'; ?>" 
                                 class="rounded-circle" 
                                 width="120" 
                                 height="120"
                                 style="object-fit: cover; border: 3px solid #667eea;">
                            <span class="position-absolute bottom-0 end-0 p-1 bg-success border border-3 border-white rounded-circle" style="width: 20px; height: 20px;"></span>
                        </div>
                        <h4 class="mt-3 mb-1"><?php echo htmlspecialchars($userData['full_name']); ?></h4>
                        <p class="text-light mb-3">Customer</p>
                        <div class="d-grid">
                            <a href="profile.php" class="btn btn-outline-light btn-sm">Edit Profile</a>
                        </div>
                    </div>
                    
                    <!-- Navigation -->
                    <ul class="nav flex-column">
                        <li class="nav-item mb-2">
                            <a class="nav-link active d-flex align-items-center" href="dashboard.php">
                                <i class="bi bi-speedometer2 me-3"></i> Dashboard
                            </a>
                        </li>
                        <li class="nav-item mb-2">
                            <a class="nav-link d-flex align-items-center" href="orders.php">
                                <i class="bi bi-bag me-3"></i> My Orders
                                <span class="badge bg-primary ms-auto"><?php echo count($recentOrders); ?></span>
                            </a>
                        </li>
                        <li class="nav-item mb-2">
                            <a class="nav-link d-flex align-items-center" href="wishlist.php">
                                <i class="bi bi-heart me-3"></i> Wishlist
                                <span class="badge bg-danger ms-auto"><?php echo count($wishlistItems); ?></span>
                            </a>
                        </li>
                        <li class="nav-item mb-2">
                            <a class="nav-link d-flex align-items-center" href="messages.php">
                                <i class="bi bi-chat-dots me-3"></i> Messages
                                <span class="badge bg-success ms-auto">3</span>
                            </a>
                        </li>
                        <li class="nav-item mb-2">
                            <a class="nav-link d-flex align-items-center" href="address.php">
                                <i class="bi bi-geo-alt me-3"></i> Addresses
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link d-flex align-items-center text-danger" href="../../includes/functions/auth_functions.php?action=logout">
                                <i class="bi bi-box-arrow-right me-3"></i> Logout
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
            
            <!-- Main Content -->
            <div class="col-lg-9">
                <!-- Welcome Header -->
                <div class="dashboard-header">
                    <div class="row align-items-center">
                        <div class="col-md-8">
                            <h1 class="welcome-text mb-2">Welcome back, <?php echo explode(' ', $userData['full_name'])[0]; ?>! 👋</h1>
                            <p class="mb-0">Track your orders, manage wishlist, and discover new styles</p>
                        </div>
                        <div class="col-md-4 text-end">
                            <a href="<?php echo SITE_URL; ?>/pages/products/" class="btn btn-glow">
                                <i class="bi bi-search me-2"></i> Browse Collection
                            </a>
                        </div>
                    </div>
                </div>
                
                <!-- Stats Cards -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="stat-card">
                            <div class="stat-icon" style="background: linear-gradient(135deg, #667eea, #764ba2);">
                                <i class="bi bi-bag-check text-white"></i>
                            </div>
                            <h3 class="mb-1">12</h3>
                            <p class="mb-0 text-light">Total Orders</p>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card">
                            <div class="stat-icon" style="background: linear-gradient(135deg, #f093fb, #f5576c);">
                                <i class="bi bi-heart text-white"></i>
                            </div>
                            <h3 class="mb-1"><?php echo count($wishlistItems); ?></h3>
                            <p class="mb-0 text-light">Wishlist Items</p>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card">
                            <div class="stat-icon" style="background: linear-gradient(135deg, #43e97b, #38f9d7);">
                                <i class="bi bi-chat-dots text-white"></i>
                            </div>
                            <h3 class="mb-1">3</h3>
                            <p class="mb-0 text-light">Unread Messages</p>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card">
                            <div class="stat-icon" style="background: linear-gradient(135deg, #fa709a, #fee140);">
                                <i class="bi bi-star text-white"></i>
                            </div>
                            <h3 class="mb-1">8</h3>
                            <p class="mb-0 text-light">Reviews Given</p>
                        </div>
                    </div>
                </div>
                
                <!-- Recent Orders -->
                <div class="glass-card mb-4">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h3 class="mb-0">Recent Orders</h3>
                        <a href="orders.php" class="btn btn-outline-light">View All</a>
                    </div>
                    
                    <?php if (!empty($recentOrders)): ?>
                        <div class="row">
                            <?php foreach ($recentOrders as $order): ?>
                                <div class="col-md-6 mb-3">
                                    <div class="product-card p-3">
                                        <div class="row">
                                            <div class="col-4">
                                                <img src="<?php echo SITE_URL; ?>/assets/images/products/<?php echo $order['product_image']; ?>" 
                                                     class="img-fluid rounded" alt="Product">
                                            </div>
                                            <div class="col-8">
                                                <h6 class="mb-1"><?php echo htmlspecialchars($order['product_title']); ?></h6>
                                                <p class="text-muted small mb-2">Order #<?php echo $order['order_number']; ?></p>
                                                <div class="d-flex justify-content-between align-items-center">
                                                    <span class="fw-bold">$<?php echo number_format($order['total_amount'], 2); ?></span>
                                                    <span class="badge bg-<?php 
                                                        switch($order['status']) {
                                                            case 'completed': echo 'success'; break;
                                                            case 'in_progress': echo 'warning'; break;
                                                            case 'pending': echo 'secondary'; break;
                                                            default: echo 'light';
                                                        }
                                                    ?>"><?php echo ucfirst($order['status']); ?></span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-5">
                            <i class="bi bi-bag-x display-1 text-light mb-3"></i>
                            <h4 class="text-light">No orders yet</h4>
                            <p class="text-light mb-4">Start shopping to see your orders here</p>
                            <a href="<?php echo SITE_URL; ?>/pages/products/" class="btn btn-glow">Start Shopping</a>
                        </div>
                    <?php endif; ?>
                </div>
                
                <!-- Wishlist Preview -->
                <div class="glass-card">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h3 class="mb-0">Your Wishlist</h3>
                        <a href="wishlist.php" class="btn btn-outline-light">View All</a>
                    </div>
                    
                    <?php if (!empty($wishlistItems)): ?>
                        <div class="row">
                            <?php foreach (array_slice($wishlistItems, 0, 4) as $item): ?>
                                <div class="col-md-3 mb-3">
                                    <div class="product-card">
                                        <img src="<?php echo SITE_URL; ?>/assets/images/products/<?php echo $item['images'][0]; ?>" 
                                             class="product-img" alt="<?php echo htmlspecialchars($item['title']); ?>">
                                        <div class="p-3">
                                            <h6 class="mb-1"><?php echo htmlspecialchars($item['title']); ?></h6>
                                            <p class="text-muted small mb-2"><?php echo htmlspecialchars($item['tailor_name']); ?></p>
                                            <div class="d-flex justify-content-between align-items-center">
                                                <span class="fw-bold text-primary">$<?php echo number_format($item['price'], 2); ?></span>
                                                <a href="<?php echo SITE_URL; ?>/pages/products/view.php?id=<?php echo $item['id']; ?>" 
                                                   class="btn btn-sm btn-primary">
                                                    <i class="bi bi-cart-plus"></i>
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-4">
                            <i class="bi bi-heart display-1 text-light mb-3"></i>
                            <h4 class="text-light">Your wishlist is empty</h4>
                            <p class="text-light mb-4">Save items you love for later</p>
                            <a href="<?php echo SITE_URL; ?>/pages/products/" class="btn btn-glow">Browse Products</a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="<?php echo SITE_URL; ?>/assets/js/dashboard.js"></script>
</body>
</html>
*/