<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
$isLoggedIn = isset($_SESSION['user_id']);
$userType = $_SESSION['user_type'] ?? '';
$userName = $_SESSION['full_name'] ?? '';

// Get cart count from session or database
$cartCount = 0;
if ($isLoggedIn && isset($_SESSION['user_id'])) {
    require_once ROOT_PATH . '/includes/classes/Database.php';
    $db = Database::getInstance();
    $db->query("SELECT COUNT(*) as count FROM cart WHERE user_id = :user_id");
    $db->bind(':user_id', $_SESSION['user_id']);
    $result = $db->single();
    $cartCount = $result['count'] ?? 0;
}

// Get current page for active highlighting
$currentPage = basename($_SERVER['PHP_SELF']);
$currentPath = $_SERVER['REQUEST_URI'];
?>

<nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm sticky-top">
    <div class="container">
        <!-- Logo -->
        <a class="navbar-brand fw-bold fs-3 text-primary" href="<?php echo SITE_URL; ?>">
            <i class="bi bi-shop me-2"></i><?php echo SITE_NAME; ?>
        </a>

        <!-- Mobile Toggle -->
        <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>

        <!-- Navigation Links -->
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav mx-auto">
                <li class="nav-item">
                    <a class="nav-link <?php echo ($currentPage == 'index.php' || $currentPath == '/') ? 'active' : ''; ?>" 
                       href="<?php echo SITE_URL; ?>">
                        <i class="bi bi-house me-1"></i> Home
                    </a>
                </li>

                <li class="nav-item">
                    <a class="nav-link <?php echo strpos($currentPath, '/pages/products') !== false ? 'active' : ''; ?>" 
                       href="<?php echo SITE_URL; ?>/pages/products/index.php">
                        <i class="bi bi-bag me-1"></i> Shop
                    </a>
                </li>

                <li class="nav-item">
                    <a class="nav-link <?php echo strpos($currentPath, '/pages/tailor') !== false ? 'active' : ''; ?>" 
                       href="<?php echo SITE_URL; ?>/pages/tailor/dashboard.php">
                        <i class="bi bi-scissors me-1"></i> Find Tailors
                    </a>
                </li>

                <li class="nav-item">
                    <a class="nav-link <?php echo strpos($currentPath, '/pages/how-it-works.php') !== false ? 'active' : ''; ?>" 
                       href="<?php echo SITE_URL; ?>/pages/how-it-works.php">
                        <i class="bi bi-question-circle me-1"></i> How It Works
                    </a>
                </li>

                <li class="nav-item">
                    <a class="nav-link <?php echo strpos($currentPath, '/pages/contact.php') !== false ? 'active' : ''; ?>" 
                       href="<?php echo SITE_URL; ?>/pages/contact.php">
                        <i class="bi bi-envelope me-1"></i> Contact
                    </a>
                </li>
            </ul>

            <!-- Right Side -->
            <div class="d-flex align-items-center">
                <!-- Search Form -->
                <form action="<?php echo SITE_URL; ?>/pages/products/index.php" method="GET" class="me-3 d-none d-md-block" style="min-width: 250px;">
                    <div class="input-group">
                        <input type="text" class="form-control" name="search" placeholder="Search products..." 
                               value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
                        <button class="btn btn-outline-primary" type="submit">
                            <i class="bi bi-search"></i>
                        </button>
                    </div>
                </form>

                <!-- Cart -->
                <a href="<?php echo SITE_URL; ?>/pages/cart/index.php" class="btn btn-outline-primary me-3 position-relative">
                    <i class="bi bi-cart"></i>
                    <?php if ($cartCount > 0): ?>
                    <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                        <?php echo $cartCount > 99 ? '99+' : $cartCount; ?>
                    </span>
                    <?php endif; ?>
                </a>

                <!-- User Menu -->
                <?php if ($isLoggedIn): ?>
                    <div class="dropdown">
                        <button class="btn btn-outline-primary dropdown-toggle d-flex align-items-center" type="button" data-bs-toggle="dropdown">
                            <i class="bi bi-person-circle me-2"></i>
                            <span class="d-none d-md-inline"><?php echo htmlspecialchars(explode(' ', $userName)[0]); ?></span>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <?php if ($userType == 'customer'): ?>
                                <li><h6 class="dropdown-header"><i class="bi bi-person me-2"></i>Customer</h6></li>
                                <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>/pages/customer/dashboard.php"><i class="bi bi-speedometer2 me-2"></i> Dashboard</a></li>
                                <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>/pages/customer/profile.php"><i class="bi bi-person me-2"></i> My Profile</a></li>
                                <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>/pages/customer/orders.php"><i class="bi bi-bag me-2"></i> My Orders</a></li>

                            <?php elseif ($userType == 'tailor'): ?>
                                <li><h6 class="dropdown-header"><i class="bi bi-scissors me-2"></i>Tailor Panel</h6></li>
                                <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>/pages/tailor/dashboard.php"><i class="bi bi-speedometer2 me-2"></i> Dashboard</a></li>
                                <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>/pages/tailor/products.php"><i class="bi bi-grid me-2"></i> My Products</a></li>
                                <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>/pages/tailor/orders.php"><i class="bi bi-bag-check me-2"></i> Orders</a></li>

                            <?php elseif ($userType == 'admin'): ?>
                                <li><h6 class="dropdown-header"><i class="bi bi-shield-check me-2"></i>Admin</h6></li>
                                <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>/pages/admin/dashboard.php"><i class="bi bi-speedometer2 me-2"></i> Dashboard</a></li>
                                <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>/pages/admin/users.php"><i class="bi bi-people me-2"></i> Users</a></li>
                            <?php endif; ?>

                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>/pages/messages.php"><i class="bi bi-chat-dots me-2"></i> Messages</a></li>
                            <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>/pages/settings.php"><i class="bi bi-gear me-2"></i> Settings</a></li>
                            <li><hr class="dropdown-divider"></li>

                            <li>
                                <a class="dropdown-item text-danger" href="<?php echo SITE_URL; ?>/pages/auth/logout.php">
                                    <i class="bi bi-box-arrow-right me-2"></i> Logout
                                </a>
                            </li>
                        </ul>
                    </div>
                <?php else: ?>
                    <div class="d-flex gap-2">
                        <a href="<?php echo SITE_URL; ?>/pages/auth/login.php" class="btn btn-outline-primary">
                            <i class="bi bi-box-arrow-in-right me-2"></i> <span class="d-none d-md-inline">Login</span>
                        </a>
                        <a href="<?php echo SITE_URL; ?>/pages/auth/register.php" class="btn btn-primary">
                            <i class="bi bi-person-plus me-2"></i> <span class="d-none d-md-inline">Register</span>
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</nav>

<style>
.navbar {
    backdrop-filter: blur(10px);
    background: rgba(255, 255, 255, 0.95);
}

.nav-link {
    font-weight: 500;
    color: #4a5568;
    padding: 0.5rem 1rem;
    border-radius: 8px;
    transition: all 0.3s ease;
    position: relative;
}

.nav-link:hover {
    color: #667eea;
    background: rgba(102, 126, 234, 0.1);
}

.nav-link.active {
    color: #667eea !important;
    font-weight: 600;
    background: rgba(102, 126, 234, 0.1);
}

.nav-link.active::after {
    content: '';
    position: absolute;
    bottom: 0;
    left: 50%;
    transform: translateX(-50%);
    width: 20px;
    height: 3px;
    background: #667eea;
    border-radius: 3px;
}

.dropdown-menu {
    border: none;
    box-shadow: 0 10px 30px rgba(0,0,0,0.1);
    border-radius: 15px;
    padding: 0.5rem;
}

.dropdown-item {
    border-radius: 8px;
    padding: 0.5rem 1rem;
    margin: 0.25rem 0;
    transition: all 0.3s ease;
}

.dropdown-item:hover {
    background: rgba(102, 126, 234, 0.1);
    color: #667eea;
}

.dropdown-header {
    color: #667eea;
    font-weight: 600;
    padding: 0.5rem 1.5rem;
    font-size: 0.85rem;
}

.btn-outline-primary {
    border-color: #667eea;
    color: #667eea;
    transition: all 0.3s ease;
}

.btn-outline-primary:hover {
    background: #667eea;
    color: white;
    border-color: #667eea;
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(102, 126, 234, 0.3);
}

.btn-primary {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border: none;
    transition: all 0.3s ease;
}

.btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 10px 20px rgba(102, 126, 234, 0.3);
}

.input-group {
    border-radius: 8px;
    overflow: hidden;
    border: 1px solid #e2e8f0;
}

.input-group input {
    border: none;
    padding: 0.75rem 1rem;
}

.input-group input:focus {
    box-shadow: none;
    border: none;
}

.input-group .btn {
    border: none;
    background: #f7fafc;
    padding: 0.75rem 1rem;
}

.input-group .btn:hover {
    background: #edf2f7;
}

/* Mobile Responsive */
@media (max-width: 992px) {
    .navbar-nav {
        padding: 1rem 0;
        text-align: center;
    }

    .nav-link {
        margin: 0.25rem 0;
        display: inline-block;
        width: auto;
    }

    .nav-link.active::after {
        display: none;
    }

    .d-flex {
        justify-content: center;
        margin-top: 1rem;
        width: 100%;
    }

    .input-group {
        margin: 1rem auto;
        max-width: 100%;
        order: 3;
        width: 100%;
    }

    .dropdown-menu {
        text-align: center;
        margin: 0.5rem auto;
        width: 90%;
    }
}

@media (max-width: 576px) {
    .navbar-brand {
        font-size: 1.2rem;
    }

    .btn {
        padding: 0.4rem 0.8rem;
        font-size: 0.9rem;
    }

    .nav-link {
        padding: 0.5rem !important;
        font-size: 0.9rem;
    }
}

/* Animation for cart badge */
@keyframes bounce {
    0%, 100% { transform: scale(1); }
    50% { transform: scale(1.2); }
}

.badge.bg-danger {
    animation: bounce 0.3s ease;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Highlight active link based on current URL
    const currentPath = window.location.pathname;
    const navLinks = document.querySelectorAll('.nav-link');
    
    navLinks.forEach(link => {
        const href = link.getAttribute('href');
        if (href && currentPath.includes(href.replace(SITE_URL, ''))) {
            link.classList.add('active');
        } else {
            link.classList.remove('active');
        }
    });
    
    // Cart badge animation
    const cartBadge = document.querySelector('.badge.bg-danger');
    if (cartBadge) {
        cartBadge.style.animation = 'bounce 0.3s ease';
    }
    
    // Toggle search on mobile
    const searchForm = document.querySelector('form');
    if (window.innerWidth < 992 && searchForm) {
        searchForm.classList.remove('d-none');
        searchForm.classList.add('w-100', 'mt-3');
    }
});
</script>