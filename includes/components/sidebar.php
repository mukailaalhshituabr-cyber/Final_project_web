<?php
// Ensure user is authenticated
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