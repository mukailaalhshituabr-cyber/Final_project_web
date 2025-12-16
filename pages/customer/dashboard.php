<?php
require_once '../../config.php';
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