<?php
// ============================================
// SHOPPING CART PAGE
// ============================================

require_once '../../config.php';

$page_title = 'Shopping Cart';

if (!is_logged_in()) {
    redirect('?page=login', 'Please login to view your cart', 'warning');
}

try {
    // Get cart items
    $cart_items = Database::fetchAll("
        SELECT c.*, p.title, p.price, p.images, p.stock_quantity, 
               u.username as tailor_name, p.is_customizable
        FROM cart c
        LEFT JOIN products p ON c.product_id = p.id
        LEFT JOIN users u ON p.tailor_id = u.id
        WHERE c.user_id = ?
        ORDER BY c.created_at DESC
    ", [$_SESSION['user_id']]);
    
    $cart_count = count($cart_items);
    
    // Calculate totals
    $subtotal = 0;
    foreach ($cart_items as $item) {
        $subtotal += $item['price'] * $item['quantity'];
    }
    
    $shipping = $subtotal >= 50 ? 0 : 5.99;
    $tax = $subtotal * 0.10; // 10% tax
    $total = $subtotal + $shipping + $tax;
    
} catch (Exception $e) {
    $cart_items = [];
    $cart_count = 0;
    $subtotal = $shipping = $tax = $total = 0;
    $error = "Error loading cart: " . $e->getMessage();
}
?>

<div class="cart-page">
    <div class="container">
        <!-- Page Header -->
        <div class="page-header mb-5">
            <h1 class="mb-3">Shopping Cart</h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="?page=home">Home</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Cart</li>
                </ol>
            </nav>
        </div>
        
        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <div class="row">
            <!-- Cart Items -->
            <div class="col-lg-8 mb-4">
                <?php if ($cart_count > 0): ?>
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">Cart Items (<?php echo $cart_count; ?>)</h5>
                            <button id="clearCart" class="btn btn-sm btn-outline-danger">
                                <i class="fas fa-trash me-1"></i> Clear Cart
                            </button>
                        </div>
                        <div class="card-body">
                            <div class="cart-items">
                                <?php foreach ($cart_items as $item): ?>
                                <div class="cart-item mb-4 pb-4 border-bottom" data-id="<?php echo $item['id']; ?>">
                                    <div class="row align-items-center">
                                        <div class="col-md-2">
                                            <div class="cart-item-image placeholder-image bg-light d-flex align-items-center justify-content-center" style="height: 100px;">
                                                <i class="fas fa-tshirt fa-3x text-muted"></i>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <h6 class="cart-item-title"><?php echo htmlspecialchars($item['title']); ?></h6>
                                            <p class="cart-item-tailor text-muted small mb-1">
                                                <i class="fas fa-user-tie me-1"></i> <?php echo htmlspecialchars($item['tailor_name']); ?>
                                            </p>
                                            <?php if ($item['customization']): ?>
                                                <p class="cart-item-customization small text-info">
                                                    <i class="fas fa-cut me-1"></i> Custom: <?php echo htmlspecialchars($item['customization']); ?>
                                                </p>
                                            <?php endif; ?>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="d-flex align-items-center justify-content-between">
                                                <div class="quantity-control">
                                                    <button class="btn btn-sm btn-outline-secondary quantity-minus" data-item-id="<?php echo $item['id']; ?>">-</button>
                                                    <input type="number" class="form-control form-control-sm text-center quantity-input" 
                                                           value="<?php echo $item['quantity']; ?>" min="1" max="<?php echo $item['stock_quantity']; ?>" 
                                                           style="width: 60px;" data-item-id="<?php echo $item['id']; ?>">
                                                    <button class="btn btn-sm btn-outline-secondary quantity-plus" data-item-id="<?php echo $item['id']; ?>">+</button>
                                                </div>
                                                <div class="text-end">
                                                    <div class="cart-item-price h6 mb-1">
                                                        $<?php echo number_format($item['price'] * $item['quantity'], 2); ?>
                                                    </div>
                                                    <button class="btn btn-sm btn-outline-danger remove-item" data-item-id="<?php echo $item['id']; ?>">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="card text-center py-5">
                        <i class="fas fa-shopping-cart fa-4x text-muted mb-4"></i>
                        <h3>Your cart is empty</h3>
                        <p class="text-muted mb-4">Looks like you haven't added any items to your cart yet.</p>
                        <a href="?page=products" class="btn btn-primary btn-lg">
                            <i class="fas fa-shopping-bag me-2"></i> Start Shopping
                        </a>
                    </div>
                <?php endif; ?>
                
                <!-- Continue Shopping -->
                <div class="mt-4">
                    <a href="?page=products" class="btn btn-outline-primary">
                        <i class="fas fa-arrow-left me-2"></i> Continue Shopping
                    </a>
                </div>
            </div>
            
            <!-- Order Summary -->
            <div class="col-lg-4">
                <?php if ($cart_count > 0): ?>
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Order Summary</h5>
                    </div>
                    <div class="card-body">
                        <div class="order-summary">
                            <div class="d-flex justify-content-between mb-2">
                                <span>Subtotal</span>
                                <span>$<?php echo number_format($subtotal, 2); ?></span>
                            </div>
                            <div class="d-flex justify-content-between mb-2">
                                <span>Shipping</span>
                                <span>$<?php echo number_format($shipping, 2); ?></span>
                            </div>
                            <div class="d-flex justify-content-between mb-2">
                                <span>Tax (10%)</span>
                                <span>$<?php echo number_format($tax, 2); ?></span>
                            </div>
                            <hr>
                            <div class="d-flex justify-content-between mb-4">
                                <strong>Total</strong>
                                <strong class="h5">$<?php echo number_format($total, 2); ?></strong>
                            </div>
                            
                            <!-- Coupon Code -->
                            <div class="mb-4">
                                <label for="couponCode" class="form-label">Coupon Code</label>
                                <div class="input-group">
                                    <input type="text" class="form-control" id="couponCode" placeholder="Enter code">
                                    <button class="btn btn-outline-secondary" type="button" id="applyCoupon">Apply</button>
                                </div>
                                <div id="couponApplied" class="mt-2 d-none">
                                    <div class="alert alert-success py-2 d-flex justify-content-between align-items-center">
                                        <span>Coupon applied: <strong class="coupon-code"></strong></span>
                                        <button class="btn btn-sm btn-outline-danger" id="removeCoupon">Remove</button>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Checkout Button -->
                            <a href="?page=checkout" class="btn btn-primary btn-lg w-100">
                                <i class="fas fa-lock me-2"></i> Proceed to Checkout
                            </a>
                            
                            <!-- Payment Methods -->
                            <div class="mt-3 text-center">
                                <small class="text-muted">We accept:</small>
                                <div class="mt-2">
                                    <i class="fab fa-cc-visa fa-2x text-primary mx-1"></i>
                                    <i class="fab fa-cc-mastercard fa-2x text-danger mx-1"></i>
                                    <i class="fab fa-cc-paypal fa-2x text-info mx-1"></i>
                                    <i class="fab fa-cc-stripe fa-2x text-success mx-1"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- Security Info -->
                <div class="card mt-4">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <i class="fas fa-shield-alt fa-2x text-success me-3"></i>
                            <div>
                                <h6 class="mb-1">Secure Shopping</h6>
                                <p class="small text-muted mb-0">Your information is protected with 256-bit SSL encryption</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // Update quantity
    $('.quantity-minus, .quantity-plus').on('click', function() {
        const itemId = $(this).data('item-id');
        const input = $(`.quantity-input[data-item-id="${itemId}"]`);
        let quantity = parseInt(input.val());
        
        if ($(this).hasClass('quantity-minus')) {
            quantity = Math.max(1, quantity - 1);
        } else {
            quantity += 1;
        }
        
        input.val(quantity);
        updateCartItem(itemId, quantity);
    });
    
    $('.quantity-input').on('change', function() {
        const itemId = $(this).data('item-id');
        const quantity = parseInt($(this).val()) || 1;
        updateCartItem(itemId, quantity);
    });
    
    // Remove item
    $('.remove-item').on('click', function() {
        const itemId = $(this).data('item-id');
        removeCartItem(itemId);
    });
    
    // Clear cart
    $('#clearCart').on('click', function() {
        if (confirm('Are you sure you want to clear your entire cart?')) {
            clearCart();
        }
    });
    
    // Apply coupon
    $('#applyCoupon').on('click', function() {
        const couponCode = $('#couponCode').val().trim();
        if (!couponCode) {
            showNotification('Please enter a coupon code', 'warning');
            return;
        }
        
        applyCoupon(couponCode);
    });
    
    // Remove coupon
    $('#removeCoupon').on('click', function() {
        removeCoupon();
    });
    
    function updateCartItem(itemId, quantity) {
        $.ajax({
            url: 'api/cart.php?action=update',
            method: 'POST',
            data: { item_id: itemId, quantity: quantity },
            success: function(response) {
                if (response.success) {
                    updateCartDisplay(response);
                }
            }
        });
    }
    
    function removeCartItem(itemId) {
        if (confirm('Remove this item from cart?')) {
            $.ajax({
                url: 'api/cart.php?action=remove',
                method: 'POST',
                data: { item_id: itemId },
                success: function(response) {
                    if (response.success) {
                        $(`.cart-item[data-id="${itemId}"]`).fadeOut(300, function() {
                            $(this).remove();
                            updateCartDisplay(response);
                            checkEmptyCart();
                        });
                    }
                }
            });
        }
    }
    
    function clearCart() {
        $.ajax({
            url: 'api/cart.php?action=clear',
            method: 'POST',
            success: function(response) {
                if (response.success) {
                    $('.cart-item').fadeOut(300, function() {
                        $(this).remove();
                        updateCartDisplay(response);
                        checkEmptyCart();
                    });
                }
            }
        });
    }
    
    function updateCartDisplay(response) {
        if (response.totals) {
            // Update totals in the UI
            $('.cart-item-price').each(function() {
                // You would update individual item prices here
            });
        }
        
        // Update cart count in navbar
        updateCartCount(response.cart_count);
    }
    
    function checkEmptyCart() {
        if ($('.cart-item').length === 0) {
            $('.cart-items').html(`
                <div class="text-center py-5">
                    <i class="fas fa-shopping-cart fa-4x text-muted mb-4"></i>
                    <h3>Your cart is empty</h3>
                    <p class="text-muted mb-4">Looks like you haven't added any items to your cart yet.</p>
                    <a href="?page=products" class="btn btn-primary btn-lg">
                        <i class="fas fa-shopping-bag me-2"></i> Start Shopping
                    </a>
                </div>
            `);
            $('.order-summary').html(`
                <div class="text-center py-4">
                    <p class="text-muted">Add items to your cart to see order summary</p>
                </div>
            `);
        }
    }
    
    function applyCoupon(couponCode) {
        $.ajax({
            url: 'api/cart.php?action=apply_coupon',
            method: 'POST',
            data: { coupon_code: couponCode },
            success: function(response) {
                if (response.success) {
                    $('#couponApplied').removeClass('d-none').find('.coupon-code').text(couponCode);
                    $('#applyCoupon').hide();
                    $('#removeCoupon').show();
                    showNotification('Coupon applied successfully!', 'success');
                } else {
                    showNotification(response.message || 'Invalid coupon code', 'danger');
                }
            }
        });
    }
    
    function removeCoupon() {
        $.ajax({
            url: 'api/cart.php?action=remove_coupon',
            method: 'POST',
            success: function(response) {
                if (response.success) {
                    $('#couponApplied').addClass('d-none');
                    $('#couponCode').val('');
                    $('#applyCoupon').show();
                    $('#removeCoupon').hide();
                    showNotification('Coupon removed', 'info');
                }
            }
        });
    }
});
</script>



<?php
/*require_once '../../config.php';
require_once '../../includes/classes/Cart.php';
require_once '../../includes/classes/Product.php';

// Check if user is logged in (cart can be used without login using session)
$cart = new Cart();
$product = new Product();

// Get cart items
$cartItems = isset($_SESSION['cart']) ? $_SESSION['cart'] : [];
$subtotal = 0;
$totalItems = 0;

// Process cart actions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['update_cart'])) {
        foreach ($_POST['quantity'] as $productId => $quantity) {
            if ($quantity <= 0) {
                unset($cartItems[$productId]);
            } else {
                $cartItems[$productId]['quantity'] = $quantity;
            }
        }
        $_SESSION['cart'] = $cartItems;
    } elseif (isset($_POST['remove_item'])) {
        $productId = $_POST['product_id'];
        unset($cartItems[$productId]);
        $_SESSION['cart'] = $cartItems;
    } elseif (isset($_POST['clear_cart'])) {
        $_SESSION['cart'] = [];
        $cartItems = [];
    }
}

// Calculate totals
foreach ($cartItems as $item) {
    $subtotal += $item['price'] * $item['quantity'];
    $totalItems += $item['quantity'];
}

$shipping = $subtotal > 100 ? 0 : 9.99; // Free shipping over $100
$tax = $subtotal * 0.08; // 8% tax
$total = $subtotal + $shipping + $tax;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your Shopping Cart - <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            --secondary-gradient: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            --success-gradient: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);
            --glass-bg: rgba(255, 255, 255, 0.95);
            --glass-border: rgba(255, 255, 255, 0.2);
        }
        
        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
            color: #333;
        }
        
        .header {
            background: var(--primary-gradient);
            padding: 2rem 0;
            margin-bottom: 3rem;
            border-radius: 0 0 30px 30px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }
        
        .cart-container {
            background: var(--glass-bg);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.1);
            overflow: hidden;
            margin-bottom: 3rem;
        }
        
        .cart-item {
            padding: 1.5rem;
            border-bottom: 1px solid #eee;
            transition: all 0.3s ease;
        }
        
        .cart-item:hover {
            background: rgba(102, 126, 234, 0.05);
        }
        
        .product-image {
            width: 120px;
            height: 120px;
            object-fit: cover;
            border-radius: 12px;
            border: 3px solid white;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .quantity-control {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .quantity-btn {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            border: 2px solid #667eea;
            background: white;
            color: #667eea;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .quantity-btn:hover {
            background: #667eea;
            color: white;
            transform: scale(1.1);
        }
        
        .quantity-input {
            width: 60px;
            text-align: center;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            padding: 8px;
            font-weight: 600;
        }
        
        .summary-card {
            background: white;
            border-radius: 20px;
            padding: 2rem;
            box-shadow: 0 10px 30px rgba(0,0,0,0.08);
            position: sticky;
            top: 2rem;
        }
        
        .summary-item {
            display: flex;
            justify-content: space-between;
            padding: 1rem 0;
            border-bottom: 1px dashed #e5e7eb;
        }
        
        .total-item {
            font-size: 1.2rem;
            font-weight: 700;
            color: #667eea;
        }
        
        .btn-primary-glow {
            background: var(--primary-gradient);
            border: none;
            color: white;
            padding: 1rem 2rem;
            border-radius: 12px;
            font-weight: 600;
            font-size: 1.1rem;
            width: 100%;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        
        .btn-primary-glow:hover {
            transform: translateY(-3px);
            box-shadow: 0 15px 30px rgba(102, 126, 234, 0.3);
        }
        
        .btn-secondary-glow {
            background: white;
            border: 2px solid #667eea;
            color: #667eea;
            padding: 1rem 2rem;
            border-radius: 12px;
            font-weight: 600;
            font-size: 1.1rem;
            width: 100%;
            transition: all 0.3s ease;
        }
        
        .btn-secondary-glow:hover {
            background: #667eea;
            color: white;
            transform: translateY(-3px);
        }
        
        .empty-cart {
            text-align: center;
            padding: 4rem 2rem;
        }
        
        .empty-cart-icon {
            font-size: 6rem;
            background: var(--primary-gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 1.5rem;
        }
        
        .promo-badge {
            background: var(--success-gradient);
            color: white;
            padding: 8px 20px;
            border-radius: 20px;
            font-weight: 600;
            display: inline-block;
            margin-bottom: 1rem;
        }
        
        .suggested-products {
            background: white;
            border-radius: 20px;
            padding: 2rem;
            margin-top: 3rem;
            box-shadow: 0 10px 30px rgba(0,0,0,0.08);
        }
        
        .suggested-card {
            background: #f8f9fa;
            border-radius: 15px;
            padding: 1rem;
            transition: all 0.3s ease;
        }
        
        .suggested-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
        }
        
        .shipping-progress {
            height: 10px;
            background: #e5e7eb;
            border-radius: 5px;
            overflow: hidden;
            margin: 1rem 0;
        }
        
        .shipping-progress-bar {
            height: 100%;
            background: var(--success-gradient);
            border-radius: 5px;
        }
        
        .continue-shopping {
            background: white;
            color: #667eea;
            border: 2px solid #667eea;
            padding: 12px 30px;
            border-radius: 50px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 10px;
        }
        
        .continue-shopping:hover {
            background: #667eea;
            color: white;
            transform: translateX(-5px);
        }
        
        .remove-btn {
            color: #ef4444;
            background: transparent;
            border: none;
            padding: 8px;
            border-radius: 8px;
            transition: all 0.3s ease;
        }
        
        .remove-btn:hover {
            background: #fee2e2;
            transform: rotate(90deg);
        }
    </style>
</head>
<body>
    <!-- Header -->
    <?php include '../../includes/components/navbar.php'; ?>
    
    <div class="header">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h1 class="display-5 fw-bold text-white mb-3">Your Shopping Cart</h1>
                    <p class="text-light lead">Review your items and proceed to checkout</p>
                </div>
                <div class="col-md-4 text-end">
                    <div class="bg-white rounded-pill px-4 py-2 d-inline-flex align-items-center">
                        <i class="bi bi-cart3 text-primary fs-4 me-3"></i>
                        <div>
                            <div class="fw-bold"><?php echo $totalItems; ?> items</div>
                            <small class="text-muted">In your cart</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="container">
        <div class="row">
            <!-- Cart Items -->
            <div class="col-lg-8">
                <?php if (!empty($cartItems)): ?>
                    <form method="POST" action="">
                        <div class="cart-container">
                            <!-- Cart Header -->
                            <div class="row align-items-center p-3 bg-light">
                                <div class="col-md-6">
                                    <h4 class="mb-0 fw-bold">Product</h4>
                                </div>
                                <div class="col-md-2 text-center">
                                    <h4 class="mb-0 fw-bold">Price</h4>
                                </div>
                                <div class="col-md-2 text-center">
                                    <h4 class="mb-0 fw-bold">Quantity</h4>
                                </div>
                                <div class="col-md-2 text-center">
                                    <h4 class="mb-0 fw-bold">Total</h4>
                                </div>
                            </div>
                            
                            <!-- Cart Items -->
                            <?php foreach ($cartItems as $productId => $item): ?>
                                <div class="cart-item">
                                    <div class="row align-items-center">
                                        <div class="col-md-6">
                                            <div class="d-flex align-items-center gap-3">
                                                <img src="<?php echo SITE_URL; ?>/assets/images/products/<?php echo $item['image']; ?>" 
                                                     class="product-image" 
                                                     alt="<?php echo htmlspecialchars($item['title']); ?>">
                                                <div>
                                                    <h5 class="fw-bold mb-1"><?php echo htmlspecialchars($item['title']); ?></h5>
                                                    <p class="text-muted mb-1"><?php echo htmlspecialchars($item['tailor']); ?></p>
                                                    <div class="d-flex gap-2">
                                                        <?php if ($item['size']): ?>
                                                            <span class="badge bg-light text-dark">Size: <?php echo $item['size']; ?></span>
                                                        <?php endif; ?>
                                                        <?php if ($item['color']): ?>
                                                            <span class="badge bg-light text-dark">Color: <?php echo $item['color']; ?></span>
                                                        <?php endif; ?>
                                                    </div>
                                                    <button type="submit" name="remove_item" class="remove-btn mt-2">
                                                        <i class="bi bi-trash"></i> Remove
                                                    </button>
                                                    <input type="hidden" name="product_id" value="<?php echo $productId; ?>">
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-2 text-center">
                                            <h5 class="fw-bold">$<?php echo number_format($item['price'], 2); ?></h5>
                                        </div>
                                        <div class="col-md-2">
                                            <div class="quantity-control justify-content-center">
                                                <button type="button" class="quantity-btn decrease" data-id="<?php echo $productId; ?>">-</button>
                                                <input type="number" 
                                                       name="quantity[<?php echo $productId; ?>]" 
                                                       value="<?php echo $item['quantity']; ?>" 
                                                       min="1" 
                                                       class="quantity-input"
                                                       readonly>
                                                <button type="button" class="quantity-btn increase" data-id="<?php echo $productId; ?>">+</button>
                                            </div>
                                        </div>
                                        <div class="col-md-2 text-center">
                                            <h5 class="fw-bold text-primary">$<?php echo number_format($item['price'] * $item['quantity'], 2); ?></h5>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                            
                            <!-- Cart Actions -->
                            <div class="p-3 bg-light">
                                <div class="row">
                                    <div class="col-md-6">
                                        <a href="<?php echo SITE_URL; ?>/pages/products/" class="continue-shopping">
                                            <i class="bi bi-arrow-left"></i> Continue Shopping
                                        </a>
                                    </div>
                                    <div class="col-md-6 text-end">
                                        <button type="submit" name="update_cart" class="btn btn-secondary-glow me-2">
                                            <i class="bi bi-arrow-clockwise"></i> Update Cart
                                        </button>
                                        <button type="submit" name="clear_cart" class="btn btn-outline-danger">
                                            <i class="bi bi-trash"></i> Clear Cart
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>
                    
                    <!-- Promo Code -->
                    <div class="cart-container mt-4">
                        <div class="p-4">
                            <div class="row align-items-center">
                                <div class="col-md-8">
                                    <h5 class="fw-bold mb-2">Have a promo code?</h5>
                                    <p class="text-muted mb-0">Enter your code to get special discounts</p>
                                </div>
                                <div class="col-md-4">
                                    <div class="input-group">
                                        <input type="text" class="form-control" placeholder="Enter code">
                                        <button class="btn btn-primary-glow" type="button">Apply</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Shipping Progress -->
                    <div class="cart-container mt-4">
                        <div class="p-4">
                            <?php if ($subtotal < 100): ?>
                                <div class="promo-badge">
                                    <i class="bi bi-truck"></i> Free shipping on orders over $100
                                </div>
                                <div class="mt-3">
                                    <div class="d-flex justify-content-between mb-2">
                                        <span>Add $<?php echo number_format(100 - $subtotal, 2); ?> more for free shipping!</span>
                                        <span><?php echo number_format(($subtotal/100)*100, 0); ?>%</span>
                                    </div>
                                    <div class="shipping-progress">
                                        <div class="shipping-progress-bar" style="width: <?php echo min(($subtotal/100)*100, 100); ?>%"></div>
                                    </div>
                                </div>
                            <?php else: ?>
                                <div class="alert alert-success">
                                    <i class="bi bi-check-circle"></i> Congratulations! You've earned free shipping!
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                <?php else: ?>
                    <!-- Empty Cart -->
                    <div class="cart-container">
                        <div class="empty-cart">
                            <i class="bi bi-cart-x empty-cart-icon"></i>
                            <h2 class="fw-bold mb-3">Your cart is empty</h2>
                            <p class="text-muted mb-4">Looks like you haven't added any items to your cart yet</p>
                            <a href="<?php echo SITE_URL; ?>/pages/products/" class="btn btn-primary-glow">
                                <i class="bi bi-bag me-2"></i> Start Shopping
                            </a>
                        </div>
                    </div>
                <?php endif; ?>
                
                <!-- Suggested Products -->
                <?php if (!empty($cartItems)): ?>
                    <div class="suggested-products">
                        <h3 class="fw-bold mb-4">Frequently bought together</h3>
                        <div class="row">
                            <?php for ($i = 1; $i <= 4; $i++): ?>
                                <div class="col-md-3 mb-3">
                                    <div class="suggested-card">
                                        <img src="<?php echo SITE_URL; ?>/assets/images/products/suggested<?php echo $i; ?>.jpg" 
                                             class="img-fluid rounded mb-3" 
                                             alt="Suggested Product">
                                        <h6 class="fw-bold mb-1">Matching Accessory Set</h6>
                                        <p class="text-muted small mb-2">Perfect with your order</p>
                                        <div class="d-flex justify-content-between align-items-center">
                                            <span class="fw-bold text-primary">$29.99</span>
                                            <button class="btn btn-sm btn-outline-primary">
                                                <i class="bi bi-plus"></i> Add
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            <?php endfor; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- Order Summary -->
            <?php if (!empty($cartItems)): ?>
                <div class="col-lg-4">
                    <div class="summary-card">
                        <h3 class="fw-bold mb-4">Order Summary</h3>
                        
                        <div class="summary-item">
                            <span>Subtotal (<?php echo $totalItems; ?> items)</span>
                            <span class="fw-bold">$<?php echo number_format($subtotal, 2); ?></span>
                        </div>
                        
                        <div class="summary-item">
                            <span>Shipping</span>
                            <span class="<?php echo $shipping == 0 ? 'text-success' : ''; ?>">
                                <?php if ($shipping == 0): ?>
                                    <i class="bi bi-check-circle"></i> FREE
                                <?php else: ?>
                                    $<?php echo number_format($shipping, 2); ?>
                                <?php endif; ?>
                            </span>
                        </div>
                        
                        <div class="summary-item">
                            <span>Estimated Tax</span>
                            <span>$<?php echo number_format($tax, 2); ?></span>
                        </div>
                        
                        <div class="summary-item total-item mt-3">
                            <span>Total</span>
                            <span>$<?php echo number_format($total, 2); ?></span>
                        </div>
                        
                        <div class="mt-4">
                            <a href="checkout.php" class="btn btn-primary-glow mb-3">
                                <i class="bi bi-lock"></i> Proceed to Checkout
                            </a>
                            
                            <button type="button" class="btn btn-secondary-glow">
                                <i class="bi bi-credit-card"></i> Checkout with PayPal
                            </button>
                        </div>
                        
                        <div class="mt-4">
                            <div class="alert alert-light border">
                                <div class="d-flex align-items-center">
                                    <i class="bi bi-shield-check text-success fs-4 me-3"></i>
                                    <div>
                                        <small class="fw-bold">Secure Checkout</small>
                                        <p class="mb-0 small">Your payment is secure and encrypted</p>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="alert alert-light border">
                                <div class="d-flex align-items-center">
                                    <i class="bi bi-arrow-repeat text-primary fs-4 me-3"></i>
                                    <div>
                                        <small class="fw-bold">Easy Returns</small>
                                        <p class="mb-0 small">30-day return policy for all items</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Payment Methods -->
                    <div class="summary-card mt-4">
                        <h6 class="fw-bold mb-3">We Accept</h6>
                        <div class="d-flex gap-3">
                            <img src="https://cdn-icons-png.flaticon.com/512/349/349221.png" width="50" alt="Visa">
                            <img src="https://cdn-icons-png.flaticon.com/512/349/349228.png" width="50" alt="Mastercard">
                            <img src="https://cdn-icons-png.flaticon.com/512/349/349230.png" width="50" alt="Amex">
                            <img src="https://cdn-icons-png.flaticon.com/512/217/217425.png" width="50" alt="PayPal">
                            <img src="https://cdn-icons-png.flaticon.com/512/217/217426.png" width="50" alt="Apple Pay">
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Footer -->
    <?php include '../../includes/components/footer.php'; ?>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        $(document).ready(function() {
            // Quantity controls
            $('.quantity-btn').click(function() {
                var input = $(this).siblings('.quantity-input');
                var currentVal = parseInt(input.val());
                
                if ($(this).hasClass('increase')) {
                    input.val(currentVal + 1);
                } else if ($(this).hasClass('decrease')) {
                    if (currentVal > 1) {
                        input.val(currentVal - 1);
                    }
                }
                
                // Update cart via AJAX
                updateCart($(this).data('id'), input.val());
            });
            
            // Remove item with animation
            $('.remove-btn').click(function(e) {
                e.preventDefault();
                var item = $(this).closest('.cart-item');
                item.addClass('animate__animated animate__fadeOutLeft');
                setTimeout(function() {
                    item.remove();
                    location.reload(); // Reload to update totals
                }, 300);
            });
            
            function updateCart(productId, quantity) {
                $.ajax({
                    url: '../../api/cart.php',
                    method: 'POST',
                    data: {
                        action: 'update',
                        product_id: productId,
                        quantity: quantity
                    },
                    success: function(response) {
                        // Update totals on page
                        location.reload();
                    }
                });
            }
            
            // Animate cart items on load
            $('.cart-item').each(function(index) {
                $(this).css('animation-delay', (index * 0.1) + 's');
                $(this).addClass('animate__animated animate__fadeIn');
            });
        });
    </script>
</body>
</html>*/