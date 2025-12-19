<?php
require_once '../../config.php';
require_once '../../includes/classes/Database.php';
require_once '../../includes/classes/Product.php';

session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'customer') {
    header('Location: ../auth/login.php');
    exit();
}

$userId = $_SESSION['user_id'];
$product = new Product();

// Get wishlist items
$db = Database::getInstance();
$db->query("SELECT p.* FROM products p 
           JOIN wishlist w ON p.id = w.product_id 
           WHERE w.user_id = :user_id AND p.status = 'active'");
$db->bind(':user_id', $userId);
$wishlistItems = $db->resultSet();

$db = Database::getInstance();
$db->query("SELECT p.*, u.full_name as tailor_name 
           FROM products p 
           JOIN wishlist w ON p.id = w.product_id 
           JOIN users u ON p.tailor_id = u.id
           WHERE w.user_id = :user_id AND p.status = 'active'");
$db->bind(':user_id', $userId);
$wishlistItems = $db->resultSet();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Wishlist - Clothing Marketplace</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">
    <style>
        .product-card {
            border: 1px solid #dee2e6;
            border-radius: 10px;
            overflow: hidden;
            transition: all 0.3s;
            height: 100%;
        }
        
        .product-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
        }
        
        .product-image {
            height: 200px;
            object-fit: cover;
            width: 100%;
        }
        
        .remove-btn {
            position: absolute;
            top: 10px;
            right: 10px;
            background: white;
            border: none;
            border-radius: 50%;
            width: 36px;
            height: 36px;
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0.8;
            transition: all 0.3s;
        }
        
        .remove-btn:hover {
            opacity: 1;
            background: #dc3545;
            color: white;
        }
    </style>
</head>
<body>
    <?php include '../../includes/components/navbar.php'; ?>
    
    <div class="container mt-4">
        <div class="row">
            <div class="col-12">
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                        <li class="breadcrumb-item active">My Wishlist</li>
                    </ol>
                </nav>
                
                <div class="card">
                    <div class="card-header">
                        <h4 class="mb-0"><i class="bi bi-heart me-2"></i>My Wishlist</h4>
                    </div>
                    
                    <div class="card-body">
                        <?php if (!empty($wishlistItems)): ?>
                            <div class="row row-cols-1 row-cols-md-3 row-cols-lg-4 g-4">
                                <?php foreach ($wishlistItems as $item): 
                                    $images = json_decode($item['images'] ?? '[]', true);
                                    $firstImage = !empty($images) ? $images[0] : 'default.jpg';
                                ?>
                                    <div class="col">
                                        <div class="product-card position-relative">
                                            <button class="remove-btn" 
                                                    onclick="removeFromWishlist(<?php echo $item['id']; ?>)"
                                                    title="Remove from wishlist">
                                                <i class="bi bi-x-lg"></i>
                                            </button>
                                            
                                            <img src="../../assets/images/products/<?php echo $firstImage; ?>" 
                                                 class="product-image" 
                                                 alt="<?php echo htmlspecialchars($item['title']); ?>">
                                            
                                            <div class="p-3">
                                                <h6 class="fw-bold mb-2"><?php echo htmlspecialchars($item['title']); ?></h6>
                                                <div class="d-flex justify-content-between align-items-center mb-2">
                                                    <span class="fw-bold text-primary">
                                                        $<?php echo number_format($item['price'], 2); ?>
                                                    </span>
                                                    <div class="rating small">
                                                        <i class="bi bi-star-fill text-warning"></i>
                                                        <span><?php echo number_format($item['rating'], 1); ?></span>
                                                    </div>
                                                </div>
                                                
                                                <div class="d-grid gap-2">
                                                    <a href="../products/product.php?id=<?php echo $item['id']; ?>" 
                                                       class="btn btn-outline-primary btn-sm">
                                                        <i class="bi bi-eye me-1"></i> View Details
                                                    </a>
                                                    <button class="btn btn-primary btn-sm" 
                                                            onclick="addToCart(<?php echo $item['id']; ?>)">
                                                        <i class="bi bi-cart-plus me-1"></i> Add to Cart
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="text-center py-5">
                                <i class="bi bi-heart display-6 text-muted mb-3"></i>
                                <h5>Your wishlist is empty</h5>
                                <p class="text-muted">Save items you love for later.</p>
                                <a href="../products/index.php" class="btn btn-primary">
                                    <i class="bi bi-bag me-2"></i> Start Shopping
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function removeFromWishlist(productId) {
            if (confirm('Remove this item from wishlist?')) {
                fetch('../../api/wishlist.php?action=remove&product_id=' + productId)
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            location.reload();
                        } else {
                            alert('Failed to remove item.');
                        }
                    });
            }
        }
        
        function addToCart(productId) {
            fetch('../../api/cart.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({
                    action: 'add',
                    product_id: productId,
                    quantity: 1
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Added to cart successfully!');
                } else {
                    alert('Failed to add to cart.');
                }
            });
        }
    </script>
</body>
</html>