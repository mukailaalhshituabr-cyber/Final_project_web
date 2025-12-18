!-- Admin Sidebar Component -->
<style>
    .admin-sidebar {
        background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%);
        color: white;
        min-height: 100vh;
        padding: 0;
    }
    
    .sidebar-header {
        padding: 1.5rem;
        border-bottom: 1px solid rgba(255,255,255,0.1);
    }
    
    .sidebar-nav {
        padding: 1rem 0;
    }
    
    .nav-link {
        color: rgba(255,255,255,0.8);
        padding: 0.75rem 1.5rem;
        border-left: 4px solid transparent;
        transition: all 0.3s;
        text-decoration: none;
        display: block;
    }
    
    .nav-link:hover, .nav-link.active {
        color: white;
        background: rgba(255,255,255,0.1);
        border-left-color: #667eea;
    }
    
    .nav-link i {
        width: 20px;
        margin-right: 10px;
    }
</style>

<div class="admin-sidebar">
    <div class="sidebar-header text-center">
        <h4 class="fw-bold mb-0">Admin Panel</h4>
        <small class="text-muted">Clothing Marketplace</small>
    </div>
    
    <div class="sidebar-nav">
        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>" 
                   href="dashboard.php">
                    <i class="bi bi-speedometer2"></i> Dashboard
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'users.php' ? 'active' : ''; ?>" 
                   href="users.php">
                    <i class="bi bi-people"></i> Users
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'products.php' ? 'active' : ''; ?>" 
                   href="products.php">
                    <i class="bi bi-box"></i> Products
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'orders.php' ? 'active' : ''; ?>" 
                   href="orders.php">
                    <i class="bi bi-bag"></i> Orders
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'categories.php' ? 'active' : ''; ?>" 
                   href="categories.php">
                    <i class="bi bi-grid"></i> Categories
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'reviews.php' ? 'active' : ''; ?>" 
                   href="reviews.php">
                    <i class="bi bi-star"></i> Reviews
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'messages.php' ? 'active' : ''; ?>" 
                   href="messages.php">
                    <i class="bi bi-chat"></i> Messages
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'settings.php' ? 'active' : ''; ?>" 
                   href="settings.php">
                    <i class="bi bi-gear"></i> Settings
                </a>
            </li>
            <li class="nav-item mt-4">
                <a class="nav-link text-danger" href="../../pages/auth/logout.php">
                    <i class="bi bi-box-arrow-right"></i> Logout
                </a>
            </li>
        </ul>
    </div>
</div>
