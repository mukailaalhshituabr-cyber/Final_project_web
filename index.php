<?php
require_once 'config.php'; 
require_once 'includes/classes/Database.php';
require_once 'includes/classes/Product.php';
require_once 'includes/classes/User.php';

$db = Database::getInstance();
$productObj = new Product();

// Get featured products
$featuredProducts = $productObj->getFeaturedProducts(8);

// Get categories
$categories = $db->getCategories();

// Current category from URL
$currentCategory = $_GET['category'] ?? '';
$search = $_GET['search'] ?? '';

// Get category name
$categoryName = 'Custom Clothing Marketplace';
if ($currentCategory && !empty($categories)) {
    foreach ($categories as $cat) {
        if ($cat['slug'] == $currentCategory) {
            $categoryName = $cat['name'];
            break;
        }
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo SITE_NAME; ?> - Global Clothing Marketplace</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: #667eea;
            --secondary-color: #764ba2;
        }

        body {
            font-family: 'Poppins', sans-serif;
        }

        .hero-section {
            background: linear-gradient(rgba(255,255,255,0.95), rgba(255,255,255,0.95));
            padding: 100px 0;
            min-height: 80vh;
            display: flex;
            align-items: center;
        }

        .product-card {
            transition: all 0.3s ease;
            border-radius: 15px;
            overflow: hidden;
            border: none;
            height: 100%;
        }

        .product-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
        }

        .category-card {
            text-decoration: none;
            color: inherit;
            display: block;
            height: 100%;
        }

        .category-card .card {
            transition: all 0.3s ease;
            border: none;
            height: 100%;
        }

        .category-card:hover .card {
            transform: translateY(-10px);
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }

        .featured-badge {
            position: absolute;
            top: 15px;
            right: 15px;
            background: var(--primary-color);
            color: white;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 0.875rem;
            font-weight: 600;
            z-index: 10;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            border: none;
            transition: all 0.3s ease;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(102, 126, 234, 0.3);
        }

        .btn-outline-primary {
            border-color: var(--primary-color);
            color: var(--primary-color);
            transition: all 0.3s ease;
        }

        .btn-outline-primary:hover {
            background: var(--primary-color);
            color: white;
            transform: translateY(-2px);
        }

        .stat-box h3 {
            font-size: 3.5rem;
            font-weight: 700;
            color: var(--primary-color);
            line-height: 1;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-20px); }
        }

        .animate-float {
            animation: float 6s ease-in-out infinite;
        }

        .step-card {
            text-align: center;
            padding: 2rem 1rem;
            height: 100%;
        }

        .step-icon {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1rem;
            color: white;
            font-size: 1.5rem;
        }

        .testimonial-card {
            background: white;
            border: 1px solid #e9ecef;
            border-radius: 15px;
            padding: 1.5rem;
            transition: all 0.3s ease;
            height: 100%;
        }

        .testimonial-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }

        .cta-section {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            padding: 80px 0;
        }

        @media (max-width: 768px) {
            .hero-section {
                padding: 60px 0;
                min-height: auto;
            }

            .stat-box h3 {
                font-size: 2.5rem;
            }

            .display-4 {
                font-size: 2.5rem;
            }

            .display-5 {
                font-size: 2rem;
            }
        }
    </style>
</head>
<body>
    <?php include 'includes/components/navbar.php'; ?>

    <!-- Hero Section -->
    <section class="hero-section">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-6">
                    <h1 class="display-4 fw-bold mb-3"><?php echo htmlspecialchars($categoryName); ?></h1>
                    <p class="lead mb-4">Find custom-made traditional and modern outfits from independent tailors across the globe. Connect directly with artisans for personalized designs.</p>
                    <div class="d-flex flex-wrap gap-3">
                        <a href="<?php echo SITE_URL; ?>/pages/products/" class="btn btn-primary btn-lg px-4">
                            <i class="bi bi-bag me-2"></i> Shop Now
                        </a>
                        <a href="<?php echo SITE_URL; ?>/pages/auth/register.php?type=tailor" class="btn btn-outline-primary btn-lg px-4">
                            <i class="bi bi-person-plus me-2"></i> Become a Tailor
                        </a>
                    </div>
                </div>

                <div class="col-lg-6 text-center">
                    <img src="<?php echo ASSETS_URL; ?>images/banner/image1.webp" 
                        alt="Fashion Models" 
                        class="img-fluid rounded-3 shadow-lg animate-float"
                        style="max-width: 90%; border-radius: 20px;"
                        onerror="this.src='<?php echo ASSETS_URL; ?>images/banner/default.jpg'">
                </div>
            </div>
        </div>
    </section>

    <!-- Stats Section -->
    <section class="stats-section py-5">
        <div class="container">
            <div class="row text-center">
                <div class="col-md-3 col-6 mb-4">
                    <div class="stat-box">
                        <h3 class="display-4 fw-bold text-primary">500+</h3>
                        <p class="text-muted">Talented Tailors</p>
                    </div>
                </div>
                <div class="col-md-3 col-6 mb-4">
                    <div class="stat-box">
                        <h3 class="display-4 fw-bold text-primary">10K+</h3>
                        <p class="text-muted">Unique Designs</p>
                    </div>
                </div>
                <div class="col-md-3 col-6 mb-4">
                    <div class="stat-box">
                        <h3 class="display-4 fw-bold text-primary">50K+</h3>
                        <p class="text-muted">Happy Customers</p>
                    </div>
                </div>
                <div class="col-md-3 col-6 mb-4">
                    <div class="stat-box">
                        <h3 class="display-4 fw-bold text-primary">100+</h3>
                        <p class="text-muted">Countries</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Featured Categories -->
    <section class="categories-section py-5 bg-light">
        <div class="container">
            <div class="text-center mb-5">
                <h2 class="display-5 fw-bold mb-3">Browse by Category</h2>
                <p class="lead text-muted">Discover unique clothing from different cultures and styles</p>
            </div>

            <div class="row g-4">
                <?php if (!empty($categories)): ?>
                    <?php foreach ($categories as $category): ?>
                    <div class="col-md-3 col-sm-6">
                        <a href="<?php echo SITE_URL; ?>/pages/products/?category=<?php echo urlencode($category['slug']); ?>" 
                           class="category-card">
                            <div class="card border-0 shadow-sm h-100">
                                <?php if (!empty($category['image'])): ?>
                                <img src="<?php echo ASSETS_URL . 'images/categories/' . htmlspecialchars($category['image']); ?>"
                                     class="card-img-top" 
                                     alt="<?php echo htmlspecialchars($category['name']); ?>"
                                     style="height: 250px; object-fit: cover;"
                                     onerror="this.src='<?php echo ASSETS_URL; ?>images/categories/default.jpg'">
                                <?php else: ?>
                                <div class="card-img-top bg-secondary d-flex align-items-center justify-content-center" 
                                     style="height: 250px;">
                                    <i class="bi bi-grid-3x3-gap-fill text-white" style="font-size: 3rem;"></i>
                                </div>
                                <?php endif; ?>
                                <div class="card-body text-center">
                                    <h5 class="fw-bold mb-2"><?php echo htmlspecialchars($category['name']); ?></h5>
                                    <?php if (!empty($category['description'])): ?>
                                    <p class="text-muted mb-0"><?php echo htmlspecialchars($category['description']); ?></p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </a>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <!-- Fallback categories -->
                    <?php 
                    $fallbackCategories = [
                        ['slug' => 'traditional-wear', 'name' => 'Traditional Wear', 'image' => 'assets/images/products/image1.jpeg'],
                        ['slug' => 'modern-fashion', 'name' => 'Modern Fashion', 'image' => 'assets/images/products/image12.jpg'],
                        ['slug' => 'formal', 'name' => 'Formal Wear', 'image' => 'assets/images/products/image2.webp'],
                        ['slug' => 'custom', 'name' => 'Custom Designs', 'image' => 'assets/images/products/image8.jpeg']
                    ];
                    ?>
                    <?php foreach ($fallbackCategories as $cat): ?>
                    <div class="col-md-3 col-sm-6">
                        <a href="<?php echo SITE_URL; ?>/pages/products/?category=<?php echo urlencode($cat['slug']); ?>" class="category-card">
                            <div class="card border-0 shadow-sm h-100">
                                <div class="card-img-top bg-secondary d-flex align-items-center justify-content-center" 
                                     style="height: 250px;">
                                    <i class="bi bi-grid-3x3-gap-fill text-white" style="font-size: 3rem;"></i>
                                </div>
                                <div class="card-body text-center">
                                    <h5 class="fw-bold mb-2"><?php echo htmlspecialchars($cat['name']); ?></h5>
                                    <p class="text-muted mb-0">Explore our collection</p>
                                </div>
                            </div>
                        </a>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- Featured Products -->
    <section class="products-section py-5">
        <div class="container">
            <div class="d-flex justify-content-between align-items-center mb-5">
                <div>
                    <h2 class="display-5 fw-bold mb-2">Featured Products</h2>
                    <p class="lead text-muted mb-0">Handpicked designs from our top tailors</p>
                </div>
                <a href="<?php echo SITE_URL; ?>/pages/products/" class="btn btn-outline-primary">
                    View All <i class="bi bi-arrow-right ms-2"></i>
                </a>
            </div>

            <div class="row">
                <?php if (!empty($featuredProducts)): ?>
                    <?php foreach ($featuredProducts as $product): 
                        // Decode images JSON
                        $images = json_decode($product['images'] ?? '[]', true);
                        $firstImage = !empty($images) && is_array($images) ? $images[0] : ASSETS_URL . 'images/products/default.jpg';
                    ?>
                    <div class="col-lg-3 col-md-4 col-sm-6 mb-4">
                        <div class="card product-card border-0 shadow-sm h-100">
                            <div class="position-relative">
                                <div class="featured-badge">Featured</div>
                                <img src="<?php echo htmlspecialchars($firstImage); ?>" 
                                    class="card-img-top" 
                                    alt="<?php echo htmlspecialchars($product['title']); ?>"
                                    style="height: 220px; object-fit: cover; width: 100%;"
                                    onerror="this.src='<?php echo ASSETS_URL; ?>images/products/default.jpg'">
                            </div>
                            <div class="card-body p-4">
                                <h5 class="fw-bold mb-2"><?php echo htmlspecialchars($product['title']); ?></h5>
                                <p class="text-muted small mb-3">By <?php echo htmlspecialchars($product['tailor_name'] ?? 'Professional Tailor'); ?></p>
                                <div class="d-flex justify-content-between align-items-center mt-auto">
                                    <span class="h5 text-primary fw-bold mb-0">
                                        <?php echo format_price($product['price']); ?>
                                    </span>
                                    <div class="text-warning">
                                        <i class="bi bi-star-fill"></i>
                                        <span class="ms-1 text-dark small"><?php echo number_format($product['rating'] ?? 0, 1); ?></span>
                                    </div>
                                </div>
                            </div>
                            <div class="card-footer bg-white border-0 p-4 pt-0">
                                <a href="<?php echo SITE_URL; ?>/pages/products/view.php?id=<?php echo $product['id']; ?>" 
                                   class="btn btn-primary w-100 py-2">
                                    <i class="bi bi-eye me-2"></i> View Details
                                </a>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <!-- Fallback products -->
                    <div class="col-lg-3 col-md-4 col-sm-6 mb-4">
                        <div class="card product-card border-0 shadow-sm h-100">
                            <div class="position-relative">
                                <div class="featured-badge">Popular</div>
                                <img src="<?php echo ASSETS_URL; ?>images/products/default.jpg" 
                                    class="card-img-top" 
                                    alt="Traditional Nigerian Agbada"
                                    style="height: 220px; object-fit: cover; width: 100%;">
                            </div>
                            <div class="card-body p-4">
                                <h5 class="fw-bold mb-2">Traditional Nigerian Agbada</h5>
                                <p class="text-muted small mb-3">Hand-embroidered with golden thread</p>
                                <div class="d-flex justify-content-between align-items-center mt-auto">
                                    <span class="h5 text-primary fw-bold mb-0"><?php echo format_price(50000); ?></span>
                                    <div class="text-warning">
                                        <i class="bi bi-star-fill"></i>
                                        <span class="ms-1 text-dark small">4.8</span>
                                    </div>
                                </div>
                            </div>
                            <div class="card-footer bg-white border-0 p-4 pt-0">
                                <a href="<?php echo SITE_URL; ?>/pages/products/view.php?id=1" class="btn btn-primary w-100 py-2">
                                    <i class="bi bi-eye me-2"></i> View Details
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- How It Works -->
    <section class="how-it-works py-5 bg-light">
        <div class="container">
            <div class="text-center mb-5">
                <h2 class="display-5 fw-bold mb-3">How It Works</h2>
                <p class="lead text-muted">Get your perfect outfit in 4 simple steps</p>
            </div>

            <div class="row g-4">
                <div class="col-md-3 col-sm-6">
                    <div class="step-card">
                        <div class="step-icon mb-3">
                            <i class="bi bi-search"></i>
                        </div>
                        <h4 class="fw-bold mb-3">Browse & Select</h4>
                        <p class="text-muted">Explore unique designs from talented tailors worldwide</p>
                    </div>
                </div>
                <div class="col-md-3 col-sm-6">
                    <div class="step-card">
                        <div class="step-icon mb-3">
                            <i class="bi bi-chat-dots"></i>
                        </div>
                        <h4 class="fw-bold mb-3">Customize</h4>
                        <p class="text-muted">Chat directly with the tailor for customizations</p>
                    </div>
                </div>
                <div class="col-md-3 col-sm-6">
                    <div class="step-card">
                        <div class="step-icon mb-3">
                            <i class="bi bi-credit-card"></i>
                        </div>
                        <h4 class="fw-bold mb-3">Secure Payment</h4>
                        <p class="text-muted">Pay securely with multiple payment options</p>
                    </div>
                </div>
                <div class="col-md-3 col-sm-6">
                    <div class="step-card">
                        <div class="step-icon mb-3">
                            <i class="bi bi-truck"></i>
                        </div>
                        <h4 class="fw-bold mb-3">Delivery</h4>
                        <p class="text-muted">Receive your unique outfit with worldwide shipping</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="cta-section py-5 text-white text-center">
        <div class="container">
            <h2 class="display-5 fw-bold mb-4">Ready to Start Your Fashion Journey?</h2>
            <p class="lead mb-5">Join thousands of customers and tailors in our global community</p>
            <div class="d-flex flex-wrap gap-3 justify-content-center">
                <a href="<?php echo SITE_URL; ?>/pages/auth/register.php?type=customer" class="btn btn-light btn-lg px-4">
                    <i class="bi bi-bag me-2"></i> Shop as Customer
                </a>
                <a href="<?php echo SITE_URL; ?>/pages/auth/register.php?type=tailor" class="btn btn-outline-light btn-lg px-4">
                    <i class="bi bi-scissors me-2"></i> Become a Tailor
                </a>
            </div>
        </div>
    </section>

    <?php include 'includes/components/footer.php'; ?>

    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        $(document).ready(function() {
            // Product card hover effect
            $('.product-card').hover(
                function() {
                    $(this).css('transform', 'translateY(-10px)');
                },
                function() {
                    $(this).css('transform', 'translateY(0)');
                }
            );

            // Category card hover effect
            $('.category-card').hover(
                function() {
                    $(this).find('.card').css('transform', 'translateY(-10px)');
                },
                function() {
                    $(this).find('.card').css('transform', 'translateY(0)');
                }
            );

            // Initialize tooltips
            $('[title]').tooltip();
            
            // Smooth scroll for anchor links
            $('a[href^="#"]').on('click', function(event) {
                if (this.hash !== "") {
                    event.preventDefault();
                    const hash = this.hash;
                    $('html, body').animate({
                        scrollTop: $(hash).offset().top - 70
                    }, 800);
                }
            });
        });
    </script>
</body>
</html>