<?php
// Start session if not already started
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
                                <a class="dropdown-item" href="<?php echo SITE_URL; ?>/pages/auth/logout.php">
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