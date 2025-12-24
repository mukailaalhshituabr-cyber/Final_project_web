<?php
// Ensure user is authenticated
if (!isset($_SESSION['user_id'])) {
    header('Location: ' . SITE_URL . '/pages/auth/login.php');
    exit();
}

// Get user data from database
require_once __DIR__ . '/../classes/Database.php';
$db = Database::getInstance();
$userData = $db->getUserById($_SESSION['user_id']);

if (!$userData) {
    session_destroy();
    header('Location: ' . SITE_URL . '/pages/auth/login.php');
    exit();
}

// Get counts for badges
$orderCount = $wishlistCount = $messageCount = 0;

if ($userData['user_type'] == 'customer') {
    // Get order count
    $db->query("SELECT COUNT(*) as count FROM orders WHERE customer_id = :user_id AND status != 'cancelled'");
    $db->bind(':user_id', $_SESSION['user_id']);
    $result = $db->single();
    $orderCount = $result['count'] ?? 0;
    
    // Get wishlist count
    $db->query("SELECT COUNT(*) as count FROM wishlist WHERE user_id = :user_id");
    $db->bind(':user_id', $_SESSION['user_id']);
    $result = $db->single();
    $wishlistCount = $result['count'] ?? 0;
    
    // Get unread messages count
    $db->query("SELECT COUNT(*) as count FROM messages WHERE receiver_id = :user_id AND is_read = 0");
    $db->bind(':user_id', $_SESSION['user_id']);
    $result = $db->single();
    $messageCount = $result['count'] ?? 0;
    
} elseif ($userData['user_type'] == 'tailor') {
    // Get product count
    $db->query("SELECT COUNT(*) as count FROM products WHERE tailor_id = :user_id AND status = 'active'");
    $db->bind(':user_id', $_SESSION['user_id']);
    $result = $db->single();
    $productCount = $result['count'] ?? 0;
    
    // Get order count
    $db->query("SELECT COUNT(*) as count FROM orders WHERE tailor_id = :user_id AND status IN ('pending', 'confirmed', 'processing')");
    $db->bind(':user_id', $_SESSION['user_id']);
    $result = $db->single();
    $orderCount = $result['count'] ?? 0;
    
    // Get average rating
    $db->query("SELECT AVG(rating) as avg_rating FROM reviews r JOIN products p ON r.product_id = p.id WHERE p.tailor_id = :user_id");
    $db->bind(':user_id', $_SESSION['user_id']);
    $result = $db->single();
    $avgRating = $result['avg_rating'] ?? 0;
}

// Get current page
$currentPage = basename($_SERVER['PHP_SELF']);
?>

<!-- Dashboard Sidebar -->
<div class="dashboard-sidebar">
    <!-- User Profile -->
    <div class="sidebar-profile text-center p-4">
        <div class="position-relative d-inline-block mb-3">
            <img src="<?php echo IMAGES_URL . 'avatars/' . ($userData['profile_pic'] ?: 'default.jpg'); ?>" 
                 class="rounded-circle border border-4 border-white shadow"
                 width="100"
                 height="100"
                 style="object-fit: cover;"
                 onerror="this.src='<?php echo IMAGES_URL; ?>avatars/default.jpg'">
            <?php if ($userData['user_type'] == 'tailor'): ?>
                <span class="position-absolute bottom-0 end-0 p-1 bg-success border border-3 border-white rounded-circle" 
                      style="width: 20px; height: 20px;"
                      title="Verified Tailor">
                </span>
            <?php endif; ?>
        </div>
        <h5 class="fw-bold mb-1 text-white"><?php echo htmlspecialchars($userData['full_name'] ?: $userData['username']); ?></h5>
        <span class="badge bg-<?php 
            switch($userData['user_type']) {
                case 'admin': echo 'danger'; break;
                case 'tailor': echo 'warning'; break;
                default: echo 'primary';
            }
        ?>"><?php echo ucfirst($userData['user_type']); ?></span>
        
        <?php if ($userData['user_type'] == 'tailor' && isset($avgRating) && $avgRating > 0): ?>
            <div class="mt-3">
                <div class="small text-light">Rating</div>
                <div class="text-warning">
                    <?php 
                    $fullStars = floor($avgRating);
                    $hasHalfStar = $avgRating - $fullStars >= 0.5;
                    
                    for ($i = 1; $i <= 5; $i++): 
                        if ($i <= $fullStars): ?>
                            <i class="bi bi-star-fill"></i>
                        <?php elseif ($i == $fullStars + 1 && $hasHalfStar): ?>
                            <i class="bi bi-star-half"></i>
                        <?php else: ?>
                            <i class="bi bi-star"></i>
                        <?php endif;
                    endfor; ?>
                    <span class="text-light ms-1"><?php echo number_format($avgRating, 1); ?></span>
                </div>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- Navigation -->
    <div class="sidebar-navigation">
        <?php if ($userData['user_type'] == 'admin'): ?>
            <!-- Admin Navigation -->
            <ul class="nav flex-column">
                <li class="nav-item">
                    <a class="nav-link <?php echo $currentPage == 'dashboard.php' ? 'active' : ''; ?>" 
                       href="<?php echo SITE_URL; ?>/pages/admin/dashboard.php">
                        <i class="bi bi-speedometer2 me-2"></i> Dashboard
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $currentPage == 'users.php' ? 'active' : ''; ?>" 
                       href="<?php echo SITE_URL; ?>/pages/admin/users.php">
                        <i class="bi bi-people me-2"></i> Users
                        <span class="badge bg-danger float-end">
                            <?php 
                            $db->query("SELECT COUNT(*) as count FROM users WHERE status = 'active'");
                            $result = $db->single();
                            echo $result['count'] ?? 0;
                            ?>
                        </span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $currentPage == 'products.php' ? 'active' : ''; ?>" 
                       href="<?php echo SITE_URL; ?>/pages/admin/products.php">
                        <i class="bi bi-box-seam me-2"></i> Products
                        <span class="badge bg-warning float-end">
                            <?php 
                            $db->query("SELECT COUNT(*) as count FROM products WHERE status = 'active'");
                            $result = $db->single();
                            echo $result['count'] ?? 0;
                            ?>
                        </span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $currentPage == 'orders.php' ? 'active' : ''; ?>" 
                       href="<?php echo SITE_URL; ?>/pages/admin/orders.php">
                        <i class="bi bi-bag-check me-2"></i> Orders
                        <span class="badge bg-success float-end">
                            <?php 
                            $db->query("SELECT COUNT(*) as count FROM orders WHERE status = 'pending'");
                            $result = $db->single();
                            echo $result['count'] ?? 0;
                            ?>
                        </span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $currentPage == 'categories.php' ? 'active' : ''; ?>" 
                       href="<?php echo SITE_URL; ?>/pages/admin/categories.php">
                        <i class="bi bi-tags me-2"></i> Categories
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $currentPage == 'reports.php' ? 'active' : ''; ?>" 
                       href="<?php echo SITE_URL; ?>/pages/admin/reports.php">
                        <i class="bi bi-graph-up me-2"></i> Analytics
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $currentPage == 'settings.php' ? 'active' : ''; ?>" 
                       href="<?php echo SITE_URL; ?>/pages/admin/settings.php">
                        <i class="bi bi-gear me-2"></i> Settings
                    </a>
                </li>
                <li class="nav-item mt-4">
                    <a class="nav-link text-danger" href="<?php echo SITE_URL; ?>/pages/auth/logout.php">
                        <i class="bi bi-box-arrow-right me-2"></i> Logout
                    </a>
                </li>
            </ul>
            
        <?php elseif ($userData['user_type'] == 'tailor'): ?>
            <!-- Tailor Navigation -->
            <ul class="nav flex-column">
                <li class="nav-item">
                    <a class="nav-link <?php echo $currentPage == 'dashboard.php' ? 'active' : ''; ?>" 
                       href="<?php echo SITE_URL; ?>/pages/tailor/dashboard.php">
                        <i class="bi bi-speedometer2 me-2"></i> Dashboard
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $currentPage == 'products.php' ? 'active' : ''; ?>" 
                       href="<?php echo SITE_URL; ?>/pages/tailor/products.php">
                        <i class="bi bi-grid me-2"></i> Products
                        <?php if (isset($productCount) && $productCount > 0): ?>
                        <span class="badge bg-primary float-end"><?php echo $productCount; ?></span>
                        <?php endif; ?>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $currentPage == 'orders.php' ? 'active' : ''; ?>" 
                       href="<?php echo SITE_URL; ?>/pages/tailor/orders.php">
                        <i class="bi bi-bag-check me-2"></i> Orders
                        <?php if ($orderCount > 0): ?>
                        <span class="badge bg-warning float-end"><?php echo $orderCount; ?></span>
                        <?php endif; ?>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $currentPage == 'analytics.php' ? 'active' : ''; ?>" 
                       href="<?php echo SITE_URL; ?>/pages/tailor/analytics.php">
                        <i class="bi bi-graph-up me-2"></i> Analytics
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $currentPage == 'messages.php' ? 'active' : ''; ?>" 
                       href="<?php echo SITE_URL; ?>/pages/messages.php">
                        <i class="bi bi-chat-dots me-2"></i> Messages
                        <?php if ($messageCount > 0): ?>
                        <span class="badge bg-success float-end"><?php echo $messageCount; ?></span>
                        <?php endif; ?>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $currentPage == 'profile.php' ? 'active' : ''; ?>" 
                       href="<?php echo SITE_URL; ?>/pages/tailor/profile.php">
                        <i class="bi bi-person me-2"></i> Profile
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $currentPage == 'earnings.php' ? 'active' : ''; ?>" 
                       href="<?php echo SITE_URL; ?>/pages/tailor/earnings.php">
                        <i class="bi bi-wallet2 me-2"></i> Earnings
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $currentPage == 'reviews.php' ? 'active' : ''; ?>" 
                       href="<?php echo SITE_URL; ?>/pages/tailor/reviews.php">
                        <i class="bi bi-star me-2"></i> Reviews
                    </a>
                </li>
                <li class="nav-item mt-4">
                    <a class="nav-link text-danger" href="<?php echo SITE_URL; ?>/pages/auth/logout.php">
                        <i class="bi bi-box-arrow-right me-2"></i> Logout
                    </a>
                </li>
            </ul>
            
        <?php else: ?>
            <!-- Customer Navigation -->
            <ul class="nav flex-column">
                <li class="nav-item">
                    <a class="nav-link <?php echo $currentPage == 'dashboard.php' ? 'active' : ''; ?>" 
                       href="<?php echo SITE_URL; ?>/pages/customer/dashboard.php">
                        <i class="bi bi-speedometer2 me-2"></i> Dashboard
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $currentPage == 'orders.php' ? 'active' : ''; ?>" 
                       href="<?php echo SITE_URL; ?>/pages/customer/orders.php">
                        <i class="bi bi-bag-check me-2"></i> My Orders
                        <?php if ($orderCount > 0): ?>
                        <span class="badge bg-primary float-end"><?php echo $orderCount; ?></span>
                        <?php endif; ?>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $currentPage == 'wishlist.php' ? 'active' : ''; ?>" 
                       href="<?php echo SITE_URL; ?>/pages/customer/wishlist.php">
                        <i class="bi bi-heart me-2"></i> Wishlist
                        <?php if ($wishlistCount > 0): ?>
                        <span class="badge bg-danger float-end"><?php echo $wishlistCount; ?></span>
                        <?php endif; ?>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $currentPage == 'messages.php' ? 'active' : ''; ?>" 
                       href="<?php echo SITE_URL; ?>/pages/messages.php">
                        <i class="bi bi-chat-dots me-2"></i> Messages
                        <?php if ($messageCount > 0): ?>
                        <span class="badge bg-success float-end"><?php echo $messageCount; ?></span>
                        <?php endif; ?>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $currentPage == 'address.php' ? 'active' : ''; ?>" 
                       href="<?php echo SITE_URL; ?>/pages/customer/address.php">
                        <i class="bi bi-geo-alt me-2"></i> Addresses
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $currentPage == 'reviews.php' ? 'active' : ''; ?>" 
                       href="<?php echo SITE_URL; ?>/pages/customer/reviews.php">
                        <i class="bi bi-star me-2"></i> My Reviews
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $currentPage == 'settings.php' ? 'active' : ''; ?>" 
                       href="<?php echo SITE_URL; ?>/pages/customer/settings.php">
                        <i class="bi bi-gear me-2"></i> Settings
                    </a>
                </li>
                <li class="nav-item mt-4">
                    <a class="nav-link text-danger" href="<?php echo SITE_URL; ?>/pages/auth/logout.php">
                        <i class="bi bi-box-arrow-right me-2"></i> Logout
                    </a>
                </li>
            </ul>
        <?php endif; ?>
    </div>
</div>

<style>
.dashboard-sidebar {
    background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
    color: white;
    min-height: calc(100vh - 70px);
    border-radius: 0 20px 20px 0;
    box-shadow: 5px 0 15px rgba(0,0,0,0.1);
    position: sticky;
    top: 70px;
}

.sidebar-profile {
    background: rgba(255, 255, 255, 0.05);
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
    padding: 2rem 1rem;
}

.sidebar-navigation {
    padding: 1.5rem 1rem;
    height: calc(100% - 200px);
    overflow-y: auto;
}

.sidebar-navigation::-webkit-scrollbar {
    width: 5px;
}

.sidebar-navigation::-webkit-scrollbar-track {
    background: rgba(255,255,255,0.05);
    border-radius: 10px;
}

.sidebar-navigation::-webkit-scrollbar-thumb {
    background: rgba(102, 126, 234, 0.5);
    border-radius: 10px;
}

.nav-link {
    color: #cbd5e1;
    padding: 0.75rem 1rem;
    margin: 0.25rem 0;
    border-radius: 10px;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    text-decoration: none;
    font-size: 0.95rem;
}

.nav-link:hover {
    color: white;
    background: rgba(255, 255, 255, 0.1);
    transform: translateX(5px);
}

.nav-link.active {
    color: white;
    background: rgba(79, 70, 229, 0.2);
    border-left: 4px solid #4f46e5;
    font-weight: 600;
}

.nav-link i {
    width: 20px;
    text-align: center;
    font-size: 1.1rem;
}

.nav-link .badge {
    font-size: 0.65rem;
    padding: 0.25rem 0.5rem;
    min-width: 25px;
    text-align: center;
}

.text-danger {
    color: #f87171 !important;
}

.text-danger:hover {
    color: #ef4444 !important;
}

@media (max-width: 768px) {
    .dashboard-sidebar {
        min-height: auto;
        border-radius: 0;
        position: relative;
        top: 0;
    }
    
    .sidebar-navigation {
        height: auto;
        max-height: 300px;
    }
}
</style>


<?php
/*// Ensure user is authenticated
if (!isset($_SESSION['user_id'])) {
    header('Location: ' . SITE_URL . '/pages/auth/login.php');
    exit();
}

require_once __DIR__ . '/../classes/User.php';
$user = new User();
$userData = $user->getUserById($_SESSION['user_id']);
?>
<!-- Dashboard Sidebar -->
<div class="dashboard-sidebar">
    <!-- User Profile -->
    <div class="sidebar-profile text-center p-4">
        <div class="position-relative d-inline-block mb-3">
            <img src="<?php echo SITE_URL; ?>/assets/images/avatars/<?php echo $userData['profile_pic'] ?: 'default.jpg'; ?>" 
                 class="rounded-circle border border-4 border-white shadow"
                 width="100"
                 height="100"
                 style="object-fit: cover;">
            <?php if ($userData['user_type'] == 'tailor'): ?>
                <span class="position-absolute bottom-0 end-0 p-1 bg-success border border-3 border-white rounded-circle" 
                      style="width: 20px; height: 20px;"
                      title="Verified Tailor">
                </span>
            <?php endif; ?>
        </div>
        <h5 class="fw-bold mb-1"><?php echo htmlspecialchars($userData['full_name']); ?></h5>
        <span class="badge bg-<?php 
            switch($userData['user_type']) {
                case 'admin': echo 'danger'; break;
                case 'tailor': echo 'warning'; break;
                default: echo 'primary';
            }
        ?>"><?php echo ucfirst($userData['user_type']); ?></span>
        
        <?php if ($userData['user_type'] == 'tailor'): ?>
            <div class="mt-3">
                <div class="small text-muted">Rating</div>
                <div class="text-warning">
                    <?php for ($i = 1; $i <= 5; $i++): ?>
                        <i class="bi <?php echo $i <= 4 ? 'bi-star-fill' : 'bi-star-half'; ?>"></i>
                    <?php endfor; ?>
                    <span class="text-dark ms-1">4.5</span>
                </div>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- Navigation -->
    <div class="sidebar-navigation">
        <?php if ($userData['user_type'] == 'admin'): ?>
            <!-- Admin Navigation -->
            <ul class="nav flex-column">
                <li class="nav-item">
                    <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>" 
                       href="<?php echo SITE_URL; ?>/pages/admin/dashboard.php">
                        <i class="bi bi-speedometer2 me-2"></i> Dashboard
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'users.php' ? 'active' : ''; ?>" 
                       href="<?php echo SITE_URL; ?>/pages/admin/users.php">
                        <i class="bi bi-people me-2"></i> Users
                        <span class="badge bg-danger float-end">3</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'products.php' ? 'active' : ''; ?>" 
                       href="<?php echo SITE_URL; ?>/pages/admin/products.php">
                        <i class="bi bi-box-seam me-2"></i> Products
                        <span class="badge bg-warning float-end">5</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'orders.php' ? 'active' : ''; ?>" 
                       href="<?php echo SITE_URL; ?>/pages/admin/orders.php">
                        <i class="bi bi-bag-check me-2"></i> Orders
                        <span class="badge bg-success float-end">12</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="<?php echo SITE_URL; ?>/pages/admin/categories.php">
                        <i class="bi bi-tags me-2"></i> Categories
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="<?php echo SITE_URL; ?>/pages/admin/reports.php">
                        <i class="bi bi-graph-up me-2"></i> Analytics
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="<?php echo SITE_URL; ?>/pages/admin/settings.php">
                        <i class="bi bi-gear me-2"></i> Settings
                    </a>
                </li>
                <li class="nav-item mt-4">
                    <a class="nav-link text-danger" href="<?php echo SITE_URL; ?>/pages/auth/logout.php">
                        <i class="bi bi-box-arrow-right me-2"></i> Logout
                    </a>
                </li>
            </ul>
            
        <?php elseif ($userData['user_type'] == 'tailor'): ?>
            <!-- Tailor Navigation -->
            <ul class="nav flex-column">
                <li class="nav-item">
                    <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>" 
                       href="<?php echo SITE_URL; ?>/pages/tailor/dashboard.php">
                        <i class="bi bi-speedometer2 me-2"></i> Dashboard
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'products.php' ? 'active' : ''; ?>" 
                       href="<?php echo SITE_URL; ?>/pages/tailor/products.php">
                        <i class="bi bi-grid me-2"></i> Products
                        <span class="badge bg-primary float-end">15</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'orders.php' ? 'active' : ''; ?>" 
                       href="<?php echo SITE_URL; ?>/pages/tailor/orders.php">
                        <i class="bi bi-bag-check me-2"></i> Orders
                        <span class="badge bg-warning float-end">3</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="<?php echo SITE_URL; ?>/pages/tailor/analytics.php">
                        <i class="bi bi-graph-up me-2"></i> Analytics
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="<?php echo SITE_URL; ?>/pages/messages.php">
                        <i class="bi bi-chat-dots me-2"></i> Messages
                        <span class="badge bg-success float-end">5</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="<?php echo SITE_URL; ?>/pages/tailor/profile.php">
                        <i class="bi bi-person me-2"></i> Profile
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="<?php echo SITE_URL; ?>/pages/tailor/earnings.php">
                        <i class="bi bi-wallet2 me-2"></i> Earnings
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="<?php echo SITE_URL; ?>/pages/tailor/reviews.php">
                        <i class="bi bi-star me-2"></i> Reviews
                    </a>
                </li>
                <li class="nav-item mt-4">
                    <a class="nav-link text-danger" href="<?php echo SITE_URL; ?>/pages/auth/logout.php">
                        <i class="bi bi-box-arrow-right me-2"></i> Logout
                    </a>
                </li>
            </ul>
            
        <?php else: ?>
            <!-- Customer Navigation -->
            <ul class="nav flex-column">
                <li class="nav-item">
                    <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>" 
                       href="<?php echo SITE_URL; ?>/pages/customer/dashboard.php">
                        <i class="bi bi-speedometer2 me-2"></i> Dashboard
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'orders.php' ? 'active' : ''; ?>" 
                       href="<?php echo SITE_URL; ?>/pages/customer/orders.php">
                        <i class="bi bi-bag-check me-2"></i> My Orders
                        <span class="badge bg-primary float-end">5</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'wishlist.php' ? 'active' : ''; ?>" 
                       href="<?php echo SITE_URL; ?>/pages/customer/wishlist.php">
                        <i class="bi bi-heart me-2"></i> Wishlist
                        <span class="badge bg-danger float-end">8</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="<?php echo SITE_URL; ?>/pages/messages.php">
                        <i class="bi bi-chat-dots me-2"></i> Messages
                        <span class="badge bg-success float-end">3</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="<?php echo SITE_URL; ?>/pages/customer/address.php">
                        <i class="bi bi-geo-alt me-2"></i> Addresses
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="<?php echo SITE_URL; ?>/pages/customer/reviews.php">
                        <i class="bi bi-star me-2"></i> My Reviews
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="<?php echo SITE_URL; ?>/pages/customer/settings.php">
                        <i class="bi bi-gear me-2"></i> Settings
                    </a>
                </li>
                <li class="nav-item mt-4">
                    <a class="nav-link text-danger" href="<?php echo SITE_URL; ?>/pages/auth/logout.php">
                        <i class="bi bi-box-arrow-right me-2"></i> Logout
                    </a>
                </li>
            </ul>
        <?php endif; ?>
    </div>
</div>

<style>
    .dashboard-sidebar {
        background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
        color: white;
        min-height: 100vh;
        border-radius: 0 20px 20px 0;
        box-shadow: 5px 0 15px rgba(0,0,0,0.1);
    }
    
    .sidebar-profile {
        background: rgba(255, 255, 255, 0.05);
        border-bottom: 1px solid rgba(255, 255, 255, 0.1);
    }
    
    .sidebar-navigation {
        padding: 1.5rem 1rem;
    }
    
    .nav-link {
        color: #cbd5e1;
        padding: 0.75rem 1rem;
        margin: 0.25rem 0;
        border-radius: 10px;
        transition: all 0.3s ease;
        display: flex;
        align-items: center;
    }
    
    .nav-link:hover {
        color: white;
        background: rgba(255, 255, 255, 0.1);
        transform: translateX(5px);
    }
    
    .nav-link.active {
        color: white;
        background: rgba(79, 70, 229, 0.2);
        border-left: 4px solid #4f46e5;
        font-weight: 600;
    }
    
    .nav-link .badge {
        font-size: 0.65rem;
        padding: 0.25rem 0.5rem;
    }
</style>
*/