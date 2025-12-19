<?php
// Start session if not already started

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// FIX: Define a stable SITE_URL that doesn't change based on the current folder
if (!defined('SITE_URL')) {
    // This creates: http://169.239.251.102:341/~mukaila.shittu/Final_project_web
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    define('SITE_URL', $protocol . '://' . $_SERVER['HTTP_HOST'] . '/~mukaila.shittu/Final_project_web');
}

// Check if user is logged in
$isLoggedIn = isset($_SESSION['user_id']);
$userType = $_SESSION['user_type'] ?? '';
$userName = $_SESSION['full_name'] ?? '';

// Get cart count
$cartCount = 0;
if ($isLoggedIn && isset($_SESSION['user_id'])) {
    // Try to get cart from session first
    $cartCount = isset($_SESSION['cart']) && is_array($_SESSION['cart']) ? count($_SESSION['cart']) : 0;
}

// Get current page for active highlighting
$currentPage = basename($_SERVER['PHP_SELF']);
$currentDir = dirname($_SERVER['PHP_SELF']);
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
                    <a class="nav-link <?php echo ($currentPage == 'index.php' || $currentPage == '') ? 'active' : ''; ?>" 
                    href="<?php echo SITE_URL; ?>/index.php">
                        <i class="bi bi-house me-1"></i> Home
                    </a>
                </li>

                <li class="nav-item">
                    <a class="nav-link <?php echo (strpos($currentDir, 'products') !== false) ? 'active' : ''; ?>" 
                    href="<?php echo SITE_URL; ?>/pages/products/index.php">
                        <i class="bi bi-bag me-1"></i> Shop
                    </a>
                </li>

                <li class="nav-item">
                    <a class="nav-link <?php echo (strpos($currentDir, 'tailor') !== false && $currentPage != 'dashboard.php') ? 'active' : ''; ?>" 
                    href="<?php echo SITE_URL; ?>/pages/tailor/dashboard.php">
                        <i class="bi bi-person-badge me-1"></i> Find Tailors
                    </a>
                </li>

                <li class="nav-item">
                    <a class="nav-link <?php echo ($currentPage == 'how-it-works.php') ? 'active' : ''; ?>" 
                    href="<?php echo SITE_URL; ?>/pages/how-it-works.php">
                        <i class="bi bi-question-circle me-1"></i> How It Works
                    </a>
                </li>

                <li class="nav-item">
                    <a class="nav-link <?php echo ($currentPage == 'contact.php') ? 'active' : ''; ?>" 
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
                        <span class="d-none d-md-inline"><?php echo htmlspecialchars($userName); ?></span>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <!-- Customer Menu -->
                        <?php if ($userType == 'customer'): ?>
                            <li><h6 class="dropdown-header"><i class="bi bi-person me-2"></i>Customer</h6></li>
                            <li><a class="dropdown-item <?php echo ($currentPage == 'dashboard.php') ? 'active' : ''; ?>" 
                                href="<?php echo SITE_URL; ?>/pages/customer/dashboard.php">
                                <i class="bi bi-speedometer2 me-2"></i> Dashboard
                            </a></li>
                            <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>/pages/customer/profile.php">
                                <i class="bi bi-person me-2"></i> My Profile
                            </a></li>
                            <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>/pages/customer/orders.php">
                                <i class="bi bi-bag me-2"></i> My Orders
                            </a></li>
                            <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>/pages/customer/wishlist.php">
                                <i class="bi bi-heart me-2"></i> Wishlist
                            </a></li>
                            
                        <!-- Tailor Menu -->
                        <?php elseif ($userType == 'tailor'): ?>
                            <li><h6 class="dropdown-header"><i class="bi bi-scissors me-2"></i>Tailor</h6></li>
                            <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>/pages/tailor/dashboard.php">
                                <i class="bi bi-speedometer2 me-2"></i> Dashboard
                            </a></li>
                            <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>/pages/tailor/products.php">
                                <i class="bi bi-grid me-2"></i> My Products
                            </a></li>
                            <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>/pages/tailor/orders.php">
                                <i class="bi bi-bag-check me-2"></i> Orders
                            </a></li>
                            <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>/pages/tailor/profile.php">
                                <i class="bi bi-person me-2"></i> Profile
                            </a></li>
                            
                        <!-- Admin Menu -->
                        <?php elseif ($userType == 'admin'): ?>
                            <li><h6 class="dropdown-header"><i class="bi bi-shield-check me-2"></i>Admin</h6></li>
                            <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>/pages/admin/dashboard.php">
                                <i class="bi bi-speedometer2 me-2"></i> Dashboard
                            </a></li>
                            <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>/pages/admin/users.php">
                                <i class="bi bi-people me-2"></i> Users
                            </a></li>
                            <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>/pages/admin/products.php">
                                <i class="bi bi-box-seam me-2"></i> Products
                            </a></li>
                            <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>/pages/admin/orders.php">
                                <i class="bi bi-bag-check me-2"></i> Orders
                            </a></li>
                        <?php endif; ?>
                        
                        
                        <!-- Common Menu Items -->
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>/pages/customer/messages.php">
                            <i class="bi bi-chat-dots me-2"></i> Messages
                        </a></li>
                        <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>/pages/settings.php">
                            <i class="bi bi-gear me-2"></i> Settings
                        </a></li>
                        <li><hr class="dropdown-divider"></li>
                        
                        <a class="nav-link text-danger" href="<?php echo SITE_URL; ?>/pages/auth/logout.php">
                            <i class="bi bi-box-arrow-right"></i> Logout
                        </a>
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
        .navbar { backdrop-filter: blur(10px); background: rgba(255, 255, 255, 0.95); }
        .nav-link { font-weight: 500; color: #4a5568; padding: 0.5rem 1rem; border-radius: 8px; transition: all 0.3s ease; }
        .nav-link:hover { color: #667eea; background: rgba(102, 126, 234, 0.1); }
        .dropdown-menu { border: none; box-shadow: 0 10px 30px rgba(0,0,0,0.1); border-radius: 15px; padding: 0.5rem; }
        .dropdown-item { border-radius: 8px; padding: 0.5rem 1rem; margin: 0.25rem 0; }
        .dropdown-item:hover { background: rgba(102, 126, 234, 0.1); color: #667eea; }
        .fixed-bottom { backdrop-filter: blur(10px); background: rgba(255, 255, 255, 0.95); border-top: 1px solid rgba(0,0,0,0.1); }


    .navbar-brand {
        font-size: 1.5rem;
        font-weight: 700;
        color: #667eea !important;
    }



    .nav-link.active {
        color: #667eea !important;
        font-weight: 600;
        background: rgba(102, 126, 234, 0.1);
    }

    .nav-link.active::after {
        content: '';
        position: absolute;
        bottom: 5px;
        left: 50%;
        transform: translateX(-50%);
        width: 20px;
        height: 3px;
        background: #667eea;
        border-radius: 3px;
    }



    .dropdown-header {
        color: #667eea;
        font-weight: 600;
        padding: 0.5rem 1.5rem;
        font-size: 0.85rem;
    }



    .dropdown-item i {
        width: 20px;
        text-align: center;
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

    .btn-outline-primary {
        border-color: #667eea;
        color: #667eea;
        padding: 0.5rem 1rem;
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
        padding: 0.5rem 1.5rem;
        transition: all 0.3s ease;
    }

    .btn-primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 10px 20px rgba(102, 126, 234, 0.3);
    }

    .badge {
        font-size: 0.7rem;
        padding: 0.25rem 0.5rem;
        min-width: 20px;
        min-height: 20px;
        display: flex;
        align-items: center;
        justify-content: center;
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
        
        .search-form {
            order: 3;
            width: 100%;
            margin: 1rem 0;
        }
    }

    @media (max-width: 576px) {
        .navbar-brand {
            font-size: 1.5rem;
        }
        
        .btn {
            padding: 0.4rem 0.8rem;
            font-size: 0.9rem;
        }
        
        .nav-link {
            padding: 0.5rem 1rem !important;
            font-size: 0.9rem;
        }
        
        .dropdown-menu {
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
// Add active class based on current page
document.addEventListener('DOMContentLoaded', function() {
    // Highlight active link
    const currentPath = window.location.pathname;
    
    // Toggle search on mobile
    const searchForm = document.querySelector('form');
    if (window.innerWidth < 992) {
        if (searchForm) {
            searchForm.classList.remove('d-none');
            searchForm.classList.add('w-100', 'mt-3');
        }
    }
    
    // Cart badge animation
    const cartBadge = document.querySelector('.badge.bg-danger');
    if (cartBadge) {
        cartBadge.style.animation = 'bounce 0.3s ease';
    }
});
</script>
























<?php
/*// Check if user is logged in
$isLoggedIn = isset($_SESSION['user_id']);
$userType = $_SESSION['user_type'] ?? '';
$userName = $_SESSION['full_name'] ?? '';
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
                    <a class="nav-link active" href="<?php echo SITE_URL; ?>">
                        <i class="bi bi-house me-1"></i> Home
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="<?php echo SITE_URL; ?>/pages/products/">
                        <i class="bi bi-bag me-1"></i> Shop
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="<?php echo SITE_URL; ?>/pages/tailor/browse.php">
                        <i class="bi bi-person-badge me-1"></i> Find Tailors
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="<?php echo SITE_URL; ?>/pages/how-it-works.php">
                        <i class="bi bi-question-circle me-1"></i> How It Works
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="<?php echo SITE_URL; ?>/pages/contact.php">
                        <i class="bi bi-envelope me-1"></i> Contact
                    </a>
                </li>
            </ul>
            
            <!-- Right Side -->
            <div class="d-flex align-items-center">
                <!-- Search -->
                <div class="input-group me-3" style="max-width: 300px;">
                    <input type="text" class="form-control" placeholder="Search products...">
                    <button class="btn btn-outline-primary" type="button">
                        <i class="bi bi-search"></i>
                    </button>
                </div>
                
                <!-- Cart -->
                <a href="<?php echo SITE_URL; ?>/pages/cart.php" class="btn btn-outline-primary me-3 position-relative">
                    <i class="bi bi-cart"></i>
                    <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                        0
                    </span>
                </a>
                
                <!-- User Menu -->
                <?php if ($isLoggedIn): ?>
                <div class="dropdown">
                    <button class="btn btn-outline-primary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                        <i class="bi bi-person-circle me-2"></i>
                        <?php echo htmlspecialchars($userName); ?>
                    </button>
                    <ul class="dropdown-menu">
                        <?php if ($userType == 'customer'): ?>
                            <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>/pages/customer/dashboard.php">
                                <i class="bi bi-speedometer2 me-2"></i> Dashboard
                            </a></li>
                            <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>/pages/customer/profile.php">
                                <i class="bi bi-person me-2"></i> My Profile
                            </a></li>
                            <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>/pages/customer/orders.php">
                                <i class="bi bi-bag me-2"></i> My Orders
                            </a></li>
                        <?php elseif ($userType == 'tailor'): ?>
                            <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>/pages/tailor/dashboard.php">
                                <i class="bi bi-speedometer2 me-2"></i> Tailor Dashboard
                            </a></li>
                            <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>/pages/tailor/portfolio.php">
                                <i class="bi bi-images me-2"></i> My Portfolio
                            </a></li>
                        <?php endif; ?>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item text-danger" href="<?php echo SITE_URL; ?>/pages/auth/login.php">
                            <i class="bi bi-box-arrow-right me-2"></i> Logout
                        </a></li>
                    </ul>
                </div>
                <?php else: ?>
                <div class="d-flex gap-2">
                    <a href="<?php echo SITE_URL; ?>/pages/auth/login.php" class="btn btn-outline-primary">
                        <i class="bi bi-box-arrow-in-right me-2"></i> Login
                    </a>
                    <a href="<?php echo SITE_URL; ?>/pages/auth/register.php" class="btn btn-primary">
                        <i class="bi bi-person-plus me-2"></i> Register
                    </a>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</nav>

<style>
.navbar {
    padding: 0.75rem 0;
    transition: all 0.3s ease;
}

.navbar-brand {
    font-size: 1.75rem;
    font-weight: 700;
}

.nav-link {
    font-weight: 500;
    padding: 0.5rem 1rem !important;
    border-radius: 8px;
    transition: all 0.3s ease;
}

.nav-link:hover, .nav-link.active {
    background: rgba(102, 126, 234, 0.1);
    color: #667eea !important;
}

.dropdown-menu {
    border: none;
    box-shadow: 0 10px 30px rgba(0,0,0,0.1);
    border-radius: 10px;
    padding: 0.5rem 0;
}

.dropdown-item {
    padding: 0.75rem 1.5rem;
    transition: all 0.3s ease;
}

.dropdown-item:hover {
    background: rgba(102, 126, 234, 0.1);
    color: #667eea;
}

.input-group {
    border-radius: 8px;
    overflow: hidden;
}

.input-group input {
    border-right: none;
}

.input-group .btn {
    border-left: none;
}

@media (max-width: 992px) {
    .navbar-nav {
        padding: 1rem 0;
        text-align: center;
    }
    
    .d-flex {
        justify-content: center;
        margin-top: 1rem;
    }
    
    .input-group {
        margin: 1rem auto;
    }
}
</style>
*/




//1111111111111111111111111111111111111111111111
/*// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Get cart count - FIXED with safety checks
$cartCount = 0;
if (isset($_SESSION['user_id'])) {
    // Check if file exists before requiring to prevent fatal error
    $cartClassPath = __DIR__ . '/../classes/Cart.php';
    if (file_exists($cartClassPath)) {
        require_once $cartClassPath;
        $cart = new Cart();
        // Use a safe count method that doesn't trigger Stripe
        $cartCount = $cart->getCartCount(); 
    }
} elseif (isset($_SESSION['cart'])) {
    // Logic for guests (not logged in)
    $cartCount = array_sum(array_column($_SESSION['cart'], 'quantity'));
}

// Get user info if logged in
$userName = '';
$userType = '';
$profilePic = 'default.jpg';
if (isset($_SESSION['user_id'])) {
    $userClassPath = __DIR__ . '/../classes/User.php';
    if (file_exists($userClassPath)) {
        require_once $userClassPath;
        $user = new User();
        $userData = $user->getUserById($_SESSION['user_id']);
        if ($userData) {
            $userName = $userData['full_name'];
            $userType = $userData['user_type'];
            $profilePic = $userData['profile_pic'] ?: 'default.jpg';
        }
    }
}
?>
<nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm fixed-top" style="z-index: 1030;">
    <div class="container">
        <a class="navbar-brand" href="<?php echo SITE_URL; ?>/index.php">
            <div class="d-flex align-items-center">
                <div class="logo-icon" style="
                    width: 40px;
                    height: 40px;
                    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                    border-radius: 10px;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    margin-right: 10px;
                ">
                    <i class="bi bi-scissors text-white"></i>
                </div>
                <span class="fw-bold fs-4" style="
                    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                    -webkit-background-clip: text;
                    -webkit-text-fill-color: transparent;
                "><?php echo SITE_NAME; ?></span>
            </div>
        </a>

        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarMain">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navbarMain">
            <ul class="navbar-nav mx-auto">
                <li class="nav-item">
                    <a class="nav-link" href="<?php echo SITE_URL; ?>/index.php">
                        <i class="bi bi-house me-1"></i> Home
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="<?php echo SITE_URL; ?>/pages/products/">
                        <i class="bi bi-grid me-1"></i> Shop
                    </a>
                </li>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="categoriesDropdown" role="button" data-bs-toggle="dropdown">
                        <i class="bi bi-tags me-1"></i> Categories
                    </a>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>/pages/products/?category=traditional">Traditional</a></li>
                        <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>/pages/products/?category=modern">Modern</a></li>
                        <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>/pages/products/?category=formal">Formal</a></li>
                        <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>/pages/products/?category=casual">Casual</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>/pages/products/?category=custom">Custom Designs</a></li>
                    </ul>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="<?php echo SITE_URL; ?>/pages/tailor/dashboard.php">
                        <i class="bi bi-person-badge me-1"></i> For Tailors
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="<?php echo SITE_URL; ?>/pages/about.php">
                        <i class="bi bi-info-circle me-1"></i> About
                    </a>
                </li>
            </ul>

            <div class="d-flex align-items-center">
                <div class="nav-item dropdown me-3">
                    <a class="nav-link" href="#" id="searchDropdown" role="button" data-bs-toggle="dropdown">
                        <i class="bi bi-search"></i>
                    </a>
                    <div class="dropdown-menu dropdown-menu-end p-2" style="min-width: 300px;">
                        <form action="<?php echo SITE_URL; ?>/pages/products/search.php" method="GET">
                            <div class="input-group">
                                <input type="text" class="form-control" name="q" placeholder="Search products..." required>
                                <button class="btn btn-primary" type="submit">
                                    <i class="bi bi-search"></i>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="nav-item me-3 position-relative">
                    <a class="nav-link" href="<?php echo SITE_URL; ?>/pages/cart/">
                        <i class="bi bi-cart3"></i>
                        <?php if ($cartCount > 0): ?>
                            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                                <?php echo $cartCount; ?>
                            </span>
                        <?php endif; ?>
                    </a>
                </div>

                <?php if (isset($_SESSION['user_id'])): ?>
                    <div class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown">
                            <img src="<?php echo SITE_URL; ?>/assets/images/avatars/<?php echo $profilePic; ?>" 
                                 class="rounded-circle" 
                                 width="32" 
                                 height="32"
                                 style="object-fit: cover;">
                            <span class="ms-2 d-none d-md-inline"><?php echo explode(' ', $userName)[0]; ?></span>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li>
                                <a class="dropdown-item" href="<?php 
                                    if ($userType == 'admin') echo SITE_URL . '/pages/admin/dashboard.php';
                                    elseif ($userType == 'tailor') echo SITE_URL . '/pages/tailor/dashboard.php';
                                    else echo SITE_URL . '/pages/customer/dashboard.php';
                                ?>">
                                    <i class="bi bi-speedometer2 me-2"></i> Dashboard
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item" href="<?php 
                                    if ($userType == 'customer') echo SITE_URL . '/pages/customer/profile.php';
                                    elseif ($userType == 'tailor') echo SITE_URL . '/pages/tailor/profile.php';
                                    else echo SITE_URL . '/pages/tailors/profile.php';
                                ?>">
                                    <i class="bi bi-person me-2"></i> My Profile
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item" href="<?php echo SITE_URL; ?>/pages/messages.php">
                                    <i class="bi bi-chat-dots me-2"></i> Messages
                                    <?php if (isset($unreadCount) && $unreadCount > 0): ?>
                                        <span class="badge bg-danger float-end"><?php echo $unreadCount; ?></span>
                                    <?php endif; ?>
                                </a>
                            </li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <a class="dropdown-item" href="<?php echo SITE_URL; ?>/pages/auth/login.php">
                                    <i class="bi bi-box-arrow-right me-2 text-danger"></i> Logout
                                </a>
                            </li>
                        </ul>
                    </div>
                <?php else: ?>
                    <div class="d-flex gap-2">
                        <a href="<?php echo SITE_URL; ?>/pages/auth/login.php" class="btn btn-outline-primary">Login</a>
                        <a href="<?php echo SITE_URL; ?>/pages/auth/register.php" class="btn btn-primary">Sign Up</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</nav>

<div style="height: 76px;"></div>

<div class="d-block d-lg-none fixed-bottom bg-white shadow-lg" style="z-index: 1020;">
    <div class="container">
        <div class="row text-center py-2">
            <div class="col-3">
                <a href="<?php echo SITE_URL; ?>/index.php" class="text-decoration-none text-dark">
                    <i class="bi bi-house fs-5"></i>
                    <div class="small">Home</div>
                </a>
            </div>
            <div class="col-3">
                <a href="<?php echo SITE_URL; ?>/pages/products/" class="text-decoration-none text-dark">
                    <i class="bi bi-grid fs-5"></i>
                    <div class="small">Shop</div>
                </a>
            </div>
            <div class="col-3">
                <a href="<?php echo SITE_URL; ?>/pages/cart/" class="text-decoration-none text-dark position-relative">
                    <i class="bi bi-cart3 fs-5"></i>
                    <?php if ($cartCount > 0): ?>
                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" style="font-size: 0.6rem;">
                            <?php echo $cartCount; ?>
                        </span>
                    <?php endif; ?>
                    <div class="small">Cart</div>
                </a>
            </div>
            <div class="col-3">
                <?php if (isset($_SESSION['user_id'])): ?>
                    <a href="<?php 
                        if ($userType == 'admin') echo SITE_URL . '/pages/admin/dashboard.php';
                        elseif ($userType == 'tailor') echo SITE_URL . '/pages/tailor/dashboard.php';
                        else echo SITE_URL . '/pages/customer/dashboard.php';
                    ?>" class="text-decoration-none text-dark">
                        <i class="bi bi-person fs-5"></i>
                        <div class="small">Profile</div>
                    </a>
                <?php else: ?>
                    <a href="<?php echo SITE_URL; ?>/pages/auth/login.php" class="text-decoration-none text-dark">
                        <i class="bi bi-box-arrow-in-right fs-5"></i>
                        <div class="small">Login</div>
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<style>
    .navbar { backdrop-filter: blur(10px); background: rgba(255, 255, 255, 0.95); }
    .nav-link { font-weight: 500; color: #4a5568; padding: 0.5rem 1rem; border-radius: 8px; transition: all 0.3s ease; }
    .nav-link:hover { color: #667eea; background: rgba(102, 126, 234, 0.1); }
    .dropdown-menu { border: none; box-shadow: 0 10px 30px rgba(0,0,0,0.1); border-radius: 15px; padding: 0.5rem; }
    .dropdown-item { border-radius: 8px; padding: 0.5rem 1rem; margin: 0.25rem 0; }
    .dropdown-item:hover { background: rgba(102, 126, 234, 0.1); color: #667eea; }
    .fixed-bottom { backdrop-filter: blur(10px); background: rgba(255, 255, 255, 0.95); border-top: 1px solid rgba(0,0,0,0.1); }
</style>
*/