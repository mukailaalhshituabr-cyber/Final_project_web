<?php
require_once dirname(__DIR__, 2) . '/config.php';
require_once ROOT_PATH . '/includes/classes/Database.php';
require_once ROOT_PATH . '/includes/classes/Product.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'customer') {
    header('Location: ../auth/login.php');
    exit();
}

$db = Database::getInstance();
$productObj = new Product();
$userId = $_SESSION['user_id'];

// Handle actions
if (isset($_GET['action'])) {
    $productId = intval($_GET['id'] ?? 0);
    
    if ($_GET['action'] == 'remove' && $productId) {
        $db->query("DELETE FROM wishlist WHERE user_id = :user_id AND product_id = :product_id");
        $db->bind(':user_id', $userId);
        $db->bind(':product_id', $productId);
        $db->execute();
        
        header('Location: wishlist.php?removed=1');
        exit();
    } elseif ($_GET['action'] == 'add_to_cart' && $productId) {
        // Check if product already in cart
        $db->query("SELECT id FROM cart WHERE user_id = :user_id AND product_id = :product_id");
        $db->bind(':user_id', $userId);
        $db->bind(':product_id', $productId);
        $exists = $db->single();
        
        if (!$exists) {
            $db->query("INSERT INTO cart (user_id, product_id, quantity) VALUES (:user_id, :product_id, 1)");
            $db->bind(':user_id', $userId);
            $db->bind(':product_id', $productId);
            $db->execute();
        }
        
        header('Location: wishlist.php?added_to_cart=1');
        exit();
    } elseif ($_GET['action'] == 'clear_all') {
        $db->query("DELETE FROM wishlist WHERE user_id = :user_id");
        $db->bind(':user_id', $userId);
        $db->execute();
        
        header('Location: wishlist.php?cleared=1');
        exit();
    }
}

// Get wishlist items
$db->query("
    SELECT p.*, w.created_at as added_date
    FROM wishlist w
    JOIN products p ON w.product_id = p.id
    WHERE w.user_id = :user_id
    ORDER BY w.created_at DESC
");
$db->bind(':user_id', $userId);
$wishlistItems = $db->resultSet();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Wishlist - <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">
    <style>
        .wishlist-container {
            min-height: calc(100vh - 200px);
        }
        .wishlist-card {
            border-radius: 10px;
            overflow: hidden;
            transition: all 0.3s ease;
            height: 100%;
        }
        .wishlist-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
        }
        .product-image {
            height: 200px;
            object-fit: cover;
            width: 100%;
        }
        .product-price {
            color: #667eea;
            font-weight: 700;
            font-size: 1.25rem;
        }
        .stock-badge {
            position: absolute;
            top: 10px;
            left: 10px;
            z-index: 1;
        }
        .wishlist-actions {
            position: absolute;
            top: 10px;
            right: 10px;
            z-index: 1;
        }
        .empty-wishlist {
            text-align: center;
            padding: 4rem 1rem;
        }
        .empty-wishlist-icon {
            font-size: 4rem;
            color: #dee2e6;
            margin-bottom: 1rem;
        }
        .comparison-table {
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        .comparison-row {
            border-bottom: 1px solid #e9ecef;
            transition: background-color 0.2s;
        }
        .comparison-row:hover {
            background-color: #f8f9fa;
        }
    </style>
</head>
<body>
    <?php include '../../includes/components/navbar.php'; ?>
    
    <div class="container wishlist-container py-5">
        <div class="row mb-4">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center">
                    <h1 class="h3 fw-bold">My Wishlist</h1>
                    <div class="d-flex gap-2">
                        <a href="<?php echo SITE_URL; ?>/pages/products/" class="btn btn-outline-primary">
                            <i class="bi bi-bag-plus me-2"></i> Continue Shopping
                        </a>
                        <?php if (!empty($wishlistItems)): ?>
                        <a href="wishlist.php?action=clear_all" 
                           class="btn btn-outline-danger"
                           onclick="return confirm('Are you sure you want to clear your entire wishlist?')">
                            <i class="bi bi-trash me-2"></i> Clear All
                        </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <?php if (isset($_GET['removed'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle me-2"></i>Item removed from wishlist
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>
        
        <?php if (isset($_GET['added_to_cart'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle me-2"></i>Item added to cart
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>
        
        <?php if (isset($_GET['cleared'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle me-2"></i>Wishlist cleared
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <?php if (!empty($wishlistItems)): ?>
            <!-- Grid View -->
            <div class="row mb-5">
                <div class="col-12 mb-4">
                    <h5 class="fw-bold mb-0"><?php echo count($wishlistItems); ?> items in wishlist</h5>
                </div>
                
                <?php foreach ($wishlistItems as $item): 
                    $images = json_decode($item['images'] ?? '[]', true);
                    $firstImage = !empty($images) && is_array($images) ? $images[0] : ASSETS_URL . 'images/products/default.jpg';
                    $productUrl = SITE_URL . '/pages/products/view.php?id=' . $item['id'];
                ?>
                <div class="col-lg-3 col-md-4 col-sm-6 mb-4">
                    <div class="card wishlist-card border-0 shadow-sm">
                        <div class="position-relative">
                            <img src="<?php echo htmlspecialchars($firstImage); ?>" 
                                 class="product-image"
                                 alt="<?php echo htmlspecialchars($item['title']); ?>"
                                 onerror="this.src='<?php echo ASSETS_URL; ?>images/products/default.jpg'">
                            
                            <!-- Stock Status -->
                            <?php if ($item['stock_quantity'] <= 0): ?>
                            <span class="stock-badge badge bg-danger">Out of Stock</span>
                            <?php elseif ($item['stock_quantity'] <= $item['low_stock_threshold']): ?>
                            <span class="stock-badge badge bg-warning">Low Stock</span>
                            <?php endif; ?>
                            
                            <!-- Actions -->
                            <div class="wishlist-actions">
                                <a href="wishlist.php?action=remove&id=<?php echo $item['id']; ?>" 
                                   class="btn btn-danger btn-sm rounded-circle"
                                   onclick="return confirm('Remove from wishlist?')">
                                    <i class="bi bi-heart-fill"></i>
                                </a>
                            </div>
                        </div>
                        
                        <div class="card-body">
                            <h6 class="fw-bold mb-2">
                                <a href="<?php echo $productUrl; ?>" class="text-decoration-none text-dark">
                                    <?php echo htmlspecialchars($item['title']); ?>
                                </a>
                            </h6>
                            
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span class="product-price"><?php echo format_price($item['price']); ?></span>
                                <?php if ($item['compare_price'] && $item['compare_price'] > $item['price']): ?>
                                <small class="text-muted text-decoration-line-through">
                                    <?php echo format_price($item['compare_price']); ?>
                                </small>
                                <?php endif; ?>
                            </div>
                            
                            <div class="text-muted small mb-3">
                                <i class="bi bi-star-fill text-warning me-1"></i>
                                <?php echo number_format($item['rating'] ?? 0, 1); ?> 
                                (<?php echo $item['review_count'] ?? 0; ?> reviews)
                            </div>
                            
                            <div class="d-grid gap-2">
                                <?php if ($item['stock_quantity'] > 0): ?>
                                <a href="wishlist.php?action=add_to_cart&id=<?php echo $item['id']; ?>" 
                                   class="btn btn-primary btn-sm">
                                    <i class="bi bi-cart-plus me-2"></i> Add to Cart
                                </a>
                                <?php else: ?>
                                <button class="btn btn-secondary btn-sm" disabled>
                                    <i class="bi bi-cart-x me-2"></i> Out of Stock
                                </button>
                                <?php endif; ?>
                                <a href="<?php echo $productUrl; ?>" class="btn btn-outline-primary btn-sm">
                                    <i class="bi bi-eye me-2"></i> View Details
                                </a>
                            </div>
                        </div>
                        
                        <div class="card-footer bg-white border-0 pt-0">
                            <small class="text-muted">
                                Added <?php echo time_elapsed_string($item['added_date']); ?>
                            </small>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- Comparison Table View -->
            <div class="row">
                <div class="col-12">
                    <div class="card comparison-table">
                        <div class="card-header bg-light">
                            <h5 class="fw-bold mb-0">Compare Products</h5>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Product</th>
                                        <th>Price</th>
                                        <th>Rating</th>
                                        <th>Stock</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($wishlistItems as $item): 
                                        $images = json_decode($item['images'] ?? '[]', true);
                                        $firstImage = !empty($images) && is_array($images) ? $images[0] : ASSETS_URL . 'images/products/default.jpg';
                                    ?>
                                    <tr class="comparison-row">
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <img src="<?php echo htmlspecialchars($firstImage); ?>" 
                                                     class="rounded me-3" 
                                                     width="60" 
                                                     height="60"
                                                     alt="<?php echo htmlspecialchars($item['title']); ?>"
                                                     onerror="this.src='<?php echo ASSETS_URL; ?>images/products/default.jpg'">
                                                <div>
                                                    <h6 class="fw-bold mb-1"><?php echo htmlspecialchars($item['title']); ?></h6>
                                                    <small class="text-muted">By <?php echo htmlspecialchars($item['tailor_name'] ?? 'Tailor'); ?></small>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="product-price"><?php echo format_price($item['price']); ?></div>
                                            <?php if ($item['compare_price'] && $item['compare_price'] > $item['price']): ?>
                                            <small class="text-muted text-decoration-line-through d-block">
                                                <?php echo format_price($item['compare_price']); ?>
                                            </small>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="text-warning">
                                                <i class="bi bi-star-fill"></i>
                                                <span class="text-dark ms-1"><?php echo number_format($item['rating'] ?? 0, 1); ?></span>
                                            </div>
                                            <small class="text-muted"><?php echo $item['review_count'] ?? 0; ?> reviews</small>
                                        </td>
                                        <td>
                                            <?php if ($item['stock_quantity'] > 0): ?>
                                            <span class="badge bg-success">In Stock</span>
                                            <small class="d-block text-muted"><?php echo $item['stock_quantity']; ?> available</small>
                                            <?php else: ?>
                                            <span class="badge bg-danger">Out of Stock</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="d-flex gap-2">
                                                <?php if ($item['stock_quantity'] > 0): ?>
                                                <a href="wishlist.php?action=add_to_cart&id=<?php echo $item['id']; ?>" 
                                                   class="btn btn-primary btn-sm">
                                                    <i class="bi bi-cart-plus"></i>
                                                </a>
                                                <?php else: ?>
                                                <button class="btn btn-secondary btn-sm" disabled>
                                                    <i class="bi bi-cart-x"></i>
                                                </button>
                                                <?php endif; ?>
                                                <a href="<?php echo SITE_URL; ?>/pages/products/view.php?id=<?php echo $item['id']; ?>" 
                                                   class="btn btn-outline-primary btn-sm">
                                                    <i class="bi bi-eye"></i>
                                                </a>
                                                <a href="wishlist.php?action=remove&id=<?php echo $item['id']; ?>" 
                                                   class="btn btn-outline-danger btn-sm"
                                                   onclick="return confirm('Remove from wishlist?')">
                                                    <i class="bi bi-trash"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

        <?php else: ?>
            <!-- Empty Wishlist -->
            <div class="empty-wishlist">
                <div class="empty-wishlist-icon">
                    <i class="bi bi-heart"></i>
                </div>
                <h3 class="mb-3">Your wishlist is empty</h3>
                <p class="text-muted mb-4">Save items you love for later. They'll show up here.</p>
                <div class="d-flex flex-wrap gap-3 justify-content-center">
                    <a href="<?php echo SITE_URL; ?>/pages/products/" class="btn btn-primary btn-lg">
                        <i class="bi bi-bag-plus me-2"></i> Start Shopping
                    </a>
                    <a href="<?php echo SITE_URL; ?>/pages/tailor/dashboard.php" class="btn btn-outline-primary btn-lg">
                        <i class="bi bi-scissors me-2"></i> Browse Tailors
                    </a>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <?php include '../../includes/components/footer.php'; ?>

    <script>
        // Toggle between grid and list view
        function toggleView(viewType) {
            const gridView = document.getElementById('gridView');
            const listView = document.getElementById('listView');
            
            if (viewType === 'grid') {
                gridView.classList.remove('d-none');
                listView.classList.add('d-none');
                document.querySelector('.btn-grid-view').classList.add('active');
                document.querySelector('.btn-list-view').classList.remove('active');
            } else {
                gridView.classList.add('d-none');
                listView.classList.remove('d-none');
                document.querySelector('.btn-grid-view').classList.remove('active');
                document.querySelector('.btn-list-view').classList.add('active');
            }
        }
        
        // Add to cart with animation
        function addToCart(productId, button) {
            const originalHtml = button.innerHTML;
            button.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Adding...';
            button.disabled = true;
            
            fetch('wishlist.php?action=add_to_cart&id=' + productId)
                .then(response => {
                    if (response.ok) {
                        // Show success animation
                        button.innerHTML = '<i class="bi bi-check-circle me-2"></i>Added!';
                        button.classList.remove('btn-primary');
                        button.classList.add('btn-success');
                        
                        // Update cart count in navbar
                        updateCartCount();
                        
                        // Reset button after 2 seconds
                        setTimeout(() => {
                            button.innerHTML = originalHtml;
                            button.classList.remove('btn-success');
                            button.classList.add('btn-primary');
                            button.disabled = false;
                        }, 2000);
                    }
                })
                .catch(error => {
                    button.innerHTML = originalHtml;
                    button.disabled = false;
                    alert('Error adding to cart');
                });
        }
        
        // Update cart count in navbar
        function updateCartCount() {
            fetch('get-cart-count.php')
                .then(response => response.json())
                .then(data => {
                    const cartBadge = document.querySelector('.cart-badge');
                    if (cartBadge) {
                        if (data.count > 0) {
                            cartBadge.textContent = data.count > 99 ? '99+' : data.count;
                            cartBadge.classList.remove('d-none');
                        } else {
                            cartBadge.classList.add('d-none');
                        }
                    }
                });
        }
        
        // Initialize tooltips
        document.addEventListener('DOMContentLoaded', function() {
            const tooltips = document.querySelectorAll('[data-bs-toggle="tooltip"]');
            tooltips.forEach(tooltip => {
                new bootstrap.Tooltip(tooltip);
            });
        });
    </script>
    
    <?php
    function time_elapsed_string($datetime, $full = false) {
        $now = new DateTime;
        $ago = new DateTime($datetime);
        $diff = $now->diff($ago);

        $diff->w = floor($diff->d / 7);
        $diff->d -= $diff->w * 7;

        $string = array(
            'y' => 'year',
            'm' => 'month',
            'w' => 'week',
            'd' => 'day',
            'h' => 'hour',
            'i' => 'minute',
            's' => 'second',
        );
        foreach ($string as $k => &$v) {
            if ($diff->$k) {
                $v = $diff->$k . ' ' . $v . ($diff->$k > 1 ? 's' : '');
            } else {
                unset($string[$k]);
            }
        }

        if (!$full) $string = array_slice($string, 0, 1);
        return $string ? implode(', ', $string) . ' ago' : 'just now';
    }
    ?>
</body>
</html>