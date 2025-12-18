<?php
// ============================================
// SINGLE PRODUCT VIEW PAGE
// ============================================

require_once '../../config.php';
require_once '../../includes/config/database.php';

$product_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (!$product_id) {
    redirect('?page=products', 'Product not found', 'danger');
}

try {
    $product = Database::fetchAll("
        SELECT p.*, u.username as tailor_username, u.full_name as tailor_name, 
               u.bio as tailor_bio, u.experience as tailor_experience
        FROM products p 
        LEFT JOIN users u ON p.tailor_id = u.id 
        WHERE p.id = ? AND p.status = 'active'
    ", [$product_id]);
    
    if (!$product) {
        redirect('?page=products', 'Product not found', 'danger');
    }
    
    $page_title = $product['title'];
    
} catch (Exception $e) {
    redirect('?page=products', 'Error loading product', 'danger');
}
?>

<div class="product-page">
    <div class="container">
        <!-- Breadcrumb -->
        <nav aria-label="breadcrumb" class="mb-4">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="?page=home">Home</a></li>
                <li class="breadcrumb-item"><a href="?page=products">Products</a></li>
                <?php if ($product['category']): ?>
                    <li class="breadcrumb-item"><a href="?page=products&category=<?php echo $product['category']; ?>"><?php echo ucfirst($product['category']); ?></a></li>
                <?php endif; ?>
                <li class="breadcrumb-item active" aria-current="page"><?php echo $product['title']; ?></li>
            </ol>
        </nav>
        
        <div class="row">
            <!-- Product Images -->
            <div class="col-lg-6 mb-4">
                <div class="product-images">
                    <div class="main-image mb-3">
                        <div class="placeholder-image bg-light d-flex align-items-center justify-content-center" style="height: 400px;">
                            <i class="fas fa-tshirt fa-7x text-muted"></i>
                        </div>
                    </div>
                    <div class="thumbnail-images d-flex gap-2">
                        <div class="thumbnail active">
                            <div class="placeholder-thumbnail bg-light d-flex align-items-center justify-content-center" style="width: 80px; height: 80px;">
                                <i class="fas fa-tshirt"></i>
                            </div>
                        </div>
                        <div class="thumbnail">
                            <div class="placeholder-thumbnail bg-light d-flex align-items-center justify-content-center" style="width: 80px; height: 80px;">
                                <i class="fas fa-tshirt"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Product Info -->
            <div class="col-lg-6 mb-4">
                <div class="product-info">
                    <h1 class="product-title mb-3"><?php echo htmlspecialchars($product['title']); ?></h1>
                    
                    <div class="product-meta mb-4">
                        <div class="rating mb-2">
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                <i class="fas fa-star <?php echo $i <= $product['rating'] ? 'text-warning' : 'text-muted'; ?>"></i>
                            <?php endfor; ?>
                            <span class="ms-2"><?php echo number_format($product['rating'], 1); ?> (<?php echo $product['review_count']; ?> reviews)</span>
                        </div>
                        
                        <div class="tailor-info mb-3">
                            <p class="mb-1">
                                <strong>Tailor:</strong> 
                                <a href="?page=tailor&id=<?php echo $product['tailor_id']; ?>" class="text-decoration-none">
                                    <?php echo htmlspecialchars($product['tailor_name']); ?>
                                </a>
                            </p>
                            <?php if ($product['tailor_experience']): ?>
                                <p class="mb-1"><strong>Experience:</strong> <?php echo $product['tailor_experience']; ?> years</p>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="product-price mb-4">
                        <h2 class="text-primary">$<?php echo number_format($product['price'], 2); ?></h2>
                        <?php if ($product['compare_price']): ?>
                            <p class="text-muted"><s>Was: $<?php echo number_format($product['compare_price'], 2); ?></s></p>
                        <?php endif; ?>
                    </div>
                    
                    <div class="product-description mb-4">
                        <h5>Description</h5>
                        <p><?php echo nl2br(htmlspecialchars($product['description'])); ?></p>
                    </div>
                    
                    <?php if ($product['specifications']): ?>
                        <div class="product-specs mb-4">
                            <h5>Specifications</h5>
                            <?php
                            $specs = json_decode($product['specifications'], true);
                            if ($specs):
                            ?>
                                <ul class="list-unstyled">
                                    <?php foreach ($specs as $key => $value): ?>
                                        <li><strong><?php echo htmlspecialchars($key); ?>:</strong> <?php echo htmlspecialchars($value); ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Add to Cart -->
                    <div class="add-to-cart-section">
                        <form id="addToCartForm" class="row g-3">
                            <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                            
                            <?php if ($product['is_customizable']): ?>
                                <div class="col-12">
                                    <label class="form-label">Customization Options</label>
                                    <textarea class="form-control" name="customization" rows="3" placeholder="Describe your customization needs..."></textarea>
                                </div>
                            <?php endif; ?>
                            
                            <div class="col-md-4">
                                <label class="form-label">Quantity</label>
                                <div class="input-group">
                                    <button type="button" class="btn btn-outline-secondary quantity-minus">-</button>
                                    <input type="number" name="quantity" class="form-control text-center" value="1" min="1" max="<?php echo $product['stock_quantity']; ?>">
                                    <button type="button" class="btn btn-outline-secondary quantity-plus">+</button>
                                </div>
                                <div class="form-text">
                                    <?php if ($product['stock_quantity'] <= $product['low_stock_threshold']): ?>
                                        <span class="text-warning">Only <?php echo $product['stock_quantity']; ?> left in stock!</span>
                                    <?php else: ?>
                                        <span class="text-success">In Stock</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <div class="col-md-8">
                                <div class="d-grid gap-2">
                                    <button type="submit" class="btn btn-primary btn-lg">
                                        <i class="fas fa-cart-plus me-2"></i> Add to Cart
                                    </button>
                                    <button type="button" class="btn btn-outline-danger btn-lg toggle-wishlist" data-product-id="<?php echo $product['id']; ?>">
                                        <i class="far fa-heart me-2"></i> Add to Wishlist
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Tailor Info -->
        <div class="row mt-5">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">About the Tailor</h5>
                    </div>
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-md-2 text-center">
                                <div class="tailor-avatar">
                                    <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center mx-auto" style="width: 80px; height: 80px;">
                                        <i class="fas fa-user-tie fa-2x"></i>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-10">
                                <h4><?php echo htmlspecialchars($product['tailor_name']); ?></h4>
                                <?php if ($product['tailor_bio']): ?>
                                    <p><?php echo nl2br(htmlspecialchars($product['tailor_bio'])); ?></p>
                                <?php endif; ?>
                                <div class="tailor-stats">
                                    <span class="badge bg-info me-2"><?php echo $product['tailor_experience']; ?> years experience</span>
                                    <a href="?page=tailor&id=<?php echo $product['tailor_id']; ?>" class="btn btn-sm btn-outline-primary">
                                        View All Products
                                    </a>
                                    <button class="btn btn-sm btn-outline-success" data-bs-toggle="modal" data-bs-target="#messageModal">
                                        <i class="fas fa-envelope me-1"></i> Message Tailor
                                    </button>
                                </div>
                            </div>
                        </div>
                                            </div>
                </div>
            </div>
        </div>
        
        <!-- Reviews Section -->
        <div class="row mt-5">
            <div class="col-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Customer Reviews</h5>
                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#reviewModal">
                            <i class="fas fa-pen me-1"></i> Write a Review
                        </button>
                    </div>
                    <div class="card-body">
                        <!-- Review Summary -->
                        <div class="row mb-4">
                            <div class="col-md-3 text-center">
                                <div class="rating-summary">
                                    <h1 class="display-4 text-primary"><?php echo number_format($product['rating'], 1); ?></h1>
                                    <div class="stars mb-2">
                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                            <i class="fas fa-star <?php echo $i <= floor($product['rating']) ? 'text-warning' : 'text-muted'; ?>"></i>
                                        <?php endfor; ?>
                                    </div>
                                    <p class="text-muted"><?php echo $product['review_count']; ?> reviews</p>
                                </div>
                            </div>
                            <div class="col-md-9">
                                <!-- Rating breakdown would go here -->
                                <p class="text-muted">No reviews yet. Be the first to review this product!</p>
                            </div>
                        </div>
                        
                        <!-- Reviews List -->
                        <div id="reviewsContainer">
                            <!-- Reviews will be loaded here -->
                            <div class="text-center py-4">
                                <i class="fas fa-comments fa-3x text-muted mb-3"></i>
                                <h5>No reviews yet</h5>
                                <p class="text-muted">Be the first to share your experience!</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Related Products -->
        <?php
        try {
            $related_products = Database::fetchAll("
                SELECT p.*, u.username as tailor_name
                FROM products p 
                LEFT JOIN users u ON p.tailor_id = u.id 
                WHERE p.category = ? 
                AND p.id != ? 
                AND p.status = 'active'
                ORDER BY RAND()
                LIMIT 4
            ", [$product['category'], $product_id]);
            
            if (!empty($related_products)):
        ?>
        <div class="row mt-5">
            <div class="col-12">
                <h3 class="mb-4">Related Products</h3>
                <div class="row row-cols-1 row-cols-md-2 row-cols-lg-4 g-4">
                    <?php foreach ($related_products as $related): ?>
                    <div class="col">
                        <div class="card product-card h-100">
                            <div class="product-image">
                                <div class="placeholder-image bg-light d-flex align-items-center justify-content-center" style="height: 200px;">
                                    <i class="fas fa-tshirt fa-4x text-muted"></i>
                                </div>
                            </div>
                            <div class="card-body">
                                <h6 class="card-title"><?php echo htmlspecialchars($related['title']); ?></h6>
                                <p class="card-text text-muted small mb-2">by <?php echo htmlspecialchars($related['tailor_name']); ?></p>
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="h5 text-primary">$<?php echo number_format($related['price'], 2); ?></span>
                                    <a href="?page=product&id=<?php echo $related['id']; ?>" class="btn btn-sm btn-outline-primary">View</a>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <?php
            endif;
        } catch (Exception $e) {
            // Silently ignore related products error
        }
        ?>
    </div>
</div>

<!-- Review Modal -->
<div class="modal fade" id="reviewModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Write a Review</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="reviewForm" data-product-id="<?php echo $product_id; ?>">
                    <div class="mb-4">
                        <label class="form-label">Rating</label>
                        <div class="star-rating">
                            <input type="hidden" name="rating" value="0">
                            <div class="stars">
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                    <i class="fas fa-star star" data-rating="<?php echo $i; ?>"></i>
                                <?php endfor; ?>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="reviewTitle" class="form-label">Review Title</label>
                        <input type="text" class="form-control" id="reviewTitle" name="title" placeholder="Summarize your experience">
                    </div>
                    
                    <div class="mb-3">
                        <label for="reviewComment" class="form-label">Your Review</label>
                        <textarea class="form-control" id="reviewComment" name="comment" rows="5" placeholder="Share details about your experience with this product..."></textarea>
                    </div>
                    
                    <div class="text-end">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Submit Review</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Message Tailor Modal -->
<div class="modal fade" id="messageModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Message <?php echo htmlspecialchars($product['tailor_name']); ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="messageForm">
                    <input type="hidden" name="receiver_id" value="<?php echo $product['tailor_id']; ?>">
                    <input type="hidden" name="product_id" value="<?php echo $product_id; ?>">
                    
                    <div class="mb-3">
                        <label for="messageSubject" class="form-label">Subject</label>
                        <input type="text" class="form-control" id="messageSubject" name="subject" value="Regarding: <?php echo htmlspecialchars($product['title']); ?>" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="messageContent" class="form-label">Message</label>
                        <textarea class="form-control" id="messageContent" name="message" rows="5" placeholder="Type your message here..." required></textarea>
                    </div>
                    
                    <div class="text-end">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Send Message</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<style>
.star-rating .star {
    font-size: 2rem;
    color: #ddd;
    cursor: pointer;
    transition: color 0.3s;
}

.star-rating .star.active,
.star-rating .star:hover {
    color: #ffc107;
}

.star-rating .star:hover ~ .star {
    color: #ddd;
}

.quantity-minus, .quantity-plus {
    width: 40px;
}

.color-option {
    width: 30px;
    height: 30px;
    border-radius: 50%;
    border: 2px solid #dee2e6;
    margin-right: 5px;
    margin-bottom: 5px;
    cursor: pointer;
}

.color-option[data-color="red"] { background-color: #dc3545; }
.color-option[data-color="blue"] { background-color: #0d6efd; }
.color-option[data-color="green"] { background-color: #198754; }
.color-option[data-color="black"] { background-color: #000; }
.color-option[data-color="white"] { background-color: #fff; border: 2px solid #000; }
.color-option[data-color="purple"] { background-color: #6f42c1; }
.color-option[data-color="yellow"] { background-color: #ffc107; }
.color-option[data-color="pink"] { background-color: #d63384; }

.color-option.active {
    border-color: #0d6efd;
    transform: scale(1.1);
}
</style>

<script>
$(document).ready(function() {
    // Star rating
    $('.star-rating .star').on('click', function() {
        const rating = $(this).data('rating');
        $(this).siblings('.star').removeClass('active');
        $(this).prevAll('.star').addBack().addClass('active');
        $(this).closest('.star-rating').find('input[name="rating"]').val(rating);
    });
    
    // Quantity controls
    $('.quantity-minus').on('click', function() {
        const input = $(this).siblings('input[name="quantity"]');
        let value = parseInt(input.val()) || 1;
        if (value > 1) {
            input.val(value - 1);
        }
    });
    
    $('.quantity-plus').on('click', function() {
        const input = $(this).siblings('input[name="quantity"]');
        let value = parseInt(input.val()) || 1;
        const max = parseInt(input.attr('max')) || 999;
        if (value < max) {
            input.val(value + 1);
        }
    });
    
    // Add to cart
    $('#addToCartForm').on('submit', function(e) {
        e.preventDefault();
        
        const formData = $(this).serialize();
        
        $.ajax({
            url: 'api/cart.php?action=add',
            method: 'POST',
            data: formData,
            success: function(response) {
                if (response.success) {
                    showNotification('Added to cart successfully!', 'success');
                    updateCartCount(response.cart_count);
                }
            }
        });
    });
    
    // Submit review
    $('#reviewForm').on('submit', function(e) {
        e.preventDefault();
        
        const formData = $(this).serialize();
        const productId = $(this).data('product-id');
        
        $.ajax({
            url: 'api/reviews.php?action=submit',
            method: 'POST',
            data: formData + '&product_id=' + productId,
            success: function(response) {
                if (response.success) {
                    $('#reviewModal').modal('hide');
                    showNotification('Review submitted successfully!', 'success');
                    setTimeout(() => location.reload(), 1500);
                }
            }
        });
    });
    
    // Send message
    $('#messageForm').on('submit', function(e) {
        e.preventDefault();
        
        const formData = $(this).serialize();
        
        $.ajax({
            url: 'api/chat.php?action=send',
            method: 'POST',
            data: formData,
            success: function(response) {
                if (response.success) {
                    $('#messageModal').modal('hide');
                    showNotification('Message sent successfully!', 'success');
                    $(this).trigger('reset');
                }
            }
        });
    });
});
</script>