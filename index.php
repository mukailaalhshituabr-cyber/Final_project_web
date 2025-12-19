<?php
require_once 'config.php'; 
require_once 'includes/classes/Database.php';

$db = Database::getInstance();

// Get featured products from your database
$featuredProducts = $db->getFeaturedProducts(4);

// Get categories from your database
$categories = $db->getCategories();

// Current category from URL
$currentCategory = $_GET['category'] ?? '';
$search = $_GET['search'] ?? '';

// Get category name
$categoryName = 'Custom Clothing Marketplace';
if ($currentCategory) {
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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: #667eea;
            --secondary-color: #764ba2;
        }
        
        body {
            margin: 10px;
            font-family: 'Poppins', sans-serif;
        }
        
        .hero-section {
            background: linear-gradient(rgba(255,255,255,0.95), rgba(255,255,255,0.95));
            padding: 90px 0;
        }
        
        .product-card {
            transition: all 0.3s ease;
            border-radius: 15px;
            overflow: hidden;
        }
        
        .product-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
        }
        
        .category-card {
            text-decoration: none;
            color: inherit;
        }
        
        .category-card .card {
            transition: all 0.3s ease;
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
        }
        
        .btn-outline-primary {
            border-color: var(--primary-color);
            color: var(--primary-color);
        }
        
        .btn-outline-primary:hover {
            background: var(--primary-color);
            color: white;
        }
        
        .stat-box h3 {
            font-size: 3.5rem;
            font-weight: 700;
            color: var(--primary-color);
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
        }
        
        .testimonial-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }
        
        .cta-section {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
        }
        
        @media (max-width: 768px) {
            .hero-section {
                padding: 60px 0;
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
    <?php 
    if (file_exists('includes/components/navbar.php')) {
        include 'includes/components/navbar.php';
    } else {
        echo '<nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm">
                <div class="container">
                    <a class="navbar-brand fw-bold fs-3 text-primary" href="' . SITE_URL . '">
                        <i class="bi bi-shop me-2"></i>' . SITE_NAME . '
                    </a>
                </div>
              </nav>';
    }
    ?>
    
    <!-- Hero Section -->
    <section class="hero-section">
        <div class="container">
            <div class="row align-items-center min-vh-80">
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
                    <img src="<?php echo SITE_URL; ?>/assets/images/banner/image1.webp" 
                        alt="Fashion Models" 
                        class="img-fluid rounded-3 shadow-lg animate-float"
                        style="max-width: 90%; border-radius: 20px;">
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
                    <div class="col-md-3">
                        <a href="<?php echo SITE_URL; ?>/pages/products/?category=<?php echo urlencode($category['slug']); ?>" 
                           class="category-card">
                            <div class="card border-0 shadow-sm h-100">
                                <?php if (!empty($category['image'])): ?>
                                <img src="<?php echo IMAGES_URL . 'categories/' . htmlspecialchars($category['image']); ?>"
                                     class="card-img-top" 
                                     alt="<?php echo htmlspecialchars($category['name']); ?>"
                                     style="height: 250px; object-fit: cover;">
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
                    <!-- Fallback categories if database is empty -->
                    <div class="col-md-3">
                        <a href="<?php echo SITE_URL; ?>/pages/products/?category=traditional-wear" class="category-card">
                            <div class="card border-0 shadow-sm h-100">
                                <img src="<?php echo SITE_URL; ?>/assets/images/products/images%20(2).jpeg"
                                     class="card-img-top" 
                                     alt="Traditional Wear"
                                     style="height: 250px; object-fit: cover;">
                                <div class="card-body text-center">
                                    <h5 class="fw-bold mb-2">Traditional Wear</h5>
                                    <p class="text-muted mb-0">Cultural outfits from around the world</p>
                                </div>
                            </div>
                        </a>
                    </div>
                    <div class="col-md-3">
                        <a href="<?php echo SITE_URL; ?>/pages/products/?category=modern-fashion" class="category-card">
                            <div class="card border-0 shadow-sm h-100">
                                <img src="<?php echo SITE_URL; ?>/assets/images/products/image5.jpeg"
                                     class="card-img-top" 
                                     alt="Modern Fashion"
                                     style="height: 250px; object-fit: cover;">
                                <div class="card-body text-center">
                                    <h5 class="fw-bold mb-2">Modern Fashion</h5>
                                    <p class="text-muted mb-0">Contemporary fashion trends</p>
                                </div>
                            </div>
                        </a>
                    </div>
                    <div class="col-md-3">
                        <a href="<?php echo SITE_URL; ?>/pages/products/?category=formal" class="category-card">
                            <div class="card border-0 shadow-sm h-100">
                                <img src="<?php echo SITE_URL; ?>/assets/images/products/image7.jpeg"
                                     class="card-img-top" 
                                     alt="Formal Wear"
                                     style="height: 250px; object-fit: cover;">
                                <div class="card-body text-center">
                                    <h5 class="fw-bold mb-2">Formal Wear</h5>
                                    <p class="text-muted mb-0">Elegant and sophisticated attire</p>
                                </div>
                            </div>
                        </a>
                    </div>
                    <div class="col-md-3">
                        <a href="<?php echo SITE_URL; ?>/pages/products/?category=custom" class="category-card">
                            <div class="card border-0 shadow-sm h-100">
                                <img src="<?php echo SITE_URL; ?>/assets/images/products/image3.jpeg"
                                     class="card-img-top" 
                                     alt="Custom Designs"
                                     style="height: 250px; object-fit: cover;">
                                <div class="card-body text-center">
                                    <h5 class="fw-bold mb-2">Custom Designs</h5>
                                    <p class="text-muted mb-0">Personalized designs just for you</p>
                                </div>
                            </div>
                        </a>
                    </div>
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
                        $firstImage = !empty($images) ? $images[0] : SITE_URL . '/assets/images/products/default.jpg';
                    ?>
                    <div class="col-lg-3 col-md-4 col-sm-6 mb-4">
                        <div class="card product-card border-0 shadow-sm h-100">
                            <div class="position-relative">
                                <div class="featured-badge">Featured</div>
                                <img src="<?php echo htmlspecialchars($firstImage); ?>" 
                                    class="card-img-top" 
                                    alt="<?php echo htmlspecialchars($product['title']); ?>"
                                    style="height: 220px; object-fit: cover; width: 100%;">
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
                                        <span class="ms-1 text-dark small"><?php echo number_format($product['rating'], 1); ?></span>
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
                    <!-- Fallback products if database is empty -->
                    <div class="col-lg-3 col-md-4 col-sm-6 mb-4">
                        <div class="card product-card border-0 shadow-sm h-100">
                            <div class="position-relative">
                                <div class="featured-badge">Popular</div>
                                <img src="<?php echo SITE_URL; ?>/assets/images/products/image11.jpeg" 
                                    class="card-img-top" 
                                    alt="Traditional Nigerian Agbada"
                                    style="height: 220px; object-fit: cover; width: 100%;">
                            </div>
                            <div class="card-body p-4">
                                <h5 class="fw-bold mb-2">Traditional Nigerian Agbada</h5>
                                <p class="text-muted small mb-3">Hand-embroidered with golden thread</p>
                                <div class="d-flex justify-content-between align-items-center mt-auto">
                                    <span class="h5 text-primary fw-bold mb-0">50,000 CFA</span>
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
                    
                    <div class="col-lg-3 col-md-4 col-sm-6 mb-4">
                        <div class="card product-card border-0 shadow-sm h-100">
                            <div class="position-relative">
                                <img src="<?php echo SITE_URL; ?>/assets/images/products/image12.jpg" 
                                    class="card-img-top" 
                                    alt="Traditional Nigerien Loincloths"
                                    style="height: 220px; object-fit: cover; width: 100%;">
                            </div>
                            <div class="card-body p-4">
                                <h5 class="fw-bold mb-2">Traditional Nigerien Loincloths</h5>
                                <p class="text-muted small mb-3">Traditional cotton fabric with patterns</p>
                                <div class="d-flex justify-content-between align-items-center mt-auto">
                                    <span class="h5 text-primary fw-bold mb-0">9,000 CFA</span>
                                    <div class="text-warning">
                                        <i class="bi bi-star-fill"></i>
                                        <span class="ms-1 text-dark small">4.8</span>
                                    </div>
                                </div>
                            </div>
                            <div class="card-footer bg-white border-0 p-4 pt-0">
                                <a href="<?php echo SITE_URL; ?>/pages/products/view.php?id=2" class="btn btn-primary w-100 py-2">
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
                <div class="col-md-3 col-6">
                    <div class="step-card">
                        <div class="step-icon mb-3">
                            <i class="bi bi-search"></i>
                        </div>
                        <h4 class="fw-bold mb-3">Browse & Select</h4>
                        <p class="text-muted">Explore unique designs from talented tailors worldwide</p>
                    </div>
                </div>
                <div class="col-md-3 col-6">
                    <div class="step-card">
                        <div class="step-icon mb-3">
                            <i class="bi bi-chat-dots"></i>
                        </div>
                        <h4 class="fw-bold mb-3">Customize</h4>
                        <p class="text-muted">Chat directly with the tailor for customizations</p>
                    </div>
                </div>
                <div class="col-md-3 col-6">
                    <div class="step-card">
                        <div class="step-icon mb-3">
                            <i class="bi bi-credit-card"></i>
                        </div>
                        <h4 class="fw-bold mb-3">Secure Payment</h4>
                        <p class="text-muted">Pay securely with multiple payment options</p>
                    </div>
                </div>
                <div class="col-md-3 col-6">
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
    <section class="cta-section py-5 text-white">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h2 class="display-5 fw-bold mb-3">Ready to Start Your Fashion Journey?</h2>
                    <p class="lead mb-4">Join thousands of customers and tailors in our global community</p>
                </div>
                <div class="col-md-4 text-end">
                    <a href="<?php echo SITE_URL; ?>/pages/auth/register.php" class="btn btn-light btn-lg px-4">
                        Get Started <i class="bi bi-arrow-right ms-2"></i>
                    </a>
                </div>
            </div>
        </div>
    </section>
    
    <?php 
    if (file_exists('includes/components/footer.php')) {
        include 'includes/components/footer.php';
    } else {
        echo '<footer class="footer mt-auto py-4 bg-dark text-white">
                <div class="container text-center">
                    <p>&copy; ' . date('Y') . ' ' . SITE_NAME . '. All rights reserved.</p>
                </div>
              </footer>';
    }
    ?>
    
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
            $('[data-bs-toggle="tooltip"]').tooltip();
        });
    </script>
</body>
</html>








<?php
/*
require_once 'config.php'; 

$category = $_GET['category'] ?? '';
$search   = $_GET['search'] ?? '';
$sort     = $_GET['sort'] ?? 'newest';
$max_price = $_GET['max_price'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head> 
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo SITE_NAME; ?> - Global Clothing Marketplace</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Playfair+Display:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/main.css">
</head>
<body>
    <?php include 'includes/components/navbar.php'; ?>
    
    <!-- Hero Section -->
    <section class="hero-section">
        <div class="container">
            <div class="row align-items-center min-vh-80">
                <div class="col-lg-6">
                    <h1 class="mb-3">
                        <?php echo $category ? ($categories[$category]['name'] ?? 'Products') : 'All Products'; ?>
                    </h1>
                    <p class="lead mb-4">Find custom-made traditional and modern outfits from independent tailors across the globe. Connect directly with artisans for personalized designs.</p>
                    <div class="d-flex flex-wrap gap-3">
                        <a href="<?php echo SITE_URL; ?>/pages/products/index.php" class="btn btn-primary btn-lg px-4">
                            <i class="bi bi-bag me-2"></i> Shop Now
                        </a>
                        <a href="<?php echo SITE_URL; ?>/pages/auth/register.php?type=tailor" class="btn btn-outline-primary btn-lg px-4">
                            <i class="bi bi-person-plus me-2"></i> Become a Tailor
                        </a>
                    </div>
                </div>

                <div class="col-lg-6 d-flex align-items-center justify-content-center">
                    <img src="<?php echo SITE_URL; ?>/assets/images/banner/image1.webp" 
                        alt="Fashion Models" 
                        class="img-fluid rounded-3 shadow-lg animate-float highlighted-image"
                        style="animation-delay: 0.2s; width: 220%; max-width: 300px; height: auto; transform: scale(1.1); margin-left: -20px;">
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
                        <p class="text-muted">Talents Tailors</p>
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
        <!-- Featured Categories -->
    <section class="categories-section py-5 bg-light">
        <div class="container">
            <div class="text-center mb-5">
                <h2 class="display-5 fw-bold mb-3">Browse by Category</h2>
                <p class="lead text-muted">Discover unique clothing from different cultures and styles</p>
            </div>
            
            <div class="row g-4">
                <div class="col-md-3">
                    <a href="<?php echo SITE_URL; ?>/pages/products/?category=traditional" class="category-card">
                        <div class="card border-0 shadow-sm h-100 hover-lift">
                            <img src="<?php echo SITE_URL; ?>/assets/images/products/images (2).jpeg"
                                 class="card-img-top" 
                                 alt="Traditional Clothing"
                                 style="height: 250px; object-fit: cover;">
                            <div class="card-body text-center">
                                <h5 class="fw-bold mb-2">Traditional</h5>
                                <p class="text-muted mb-0">Cultural outfits from around the world</p>
                            </div>
                        </div>
                    </a>
                </div>
                <div class="col-md-3">
                    <a href="<?php echo SITE_URL; ?>/pages/products/?category=modern" class="category-card">
                        <div class="card border-0 shadow-sm h-100 hover-lift">
                            <img src="<?php echo SITE_URL; ?>/assets/images/products/image5.jpeg"
                                 class="card-img-top" 
                                 alt="Modern Fashion"
                                 style="height: 260px; object-fit: cover;">
                            <div class="card-body text-center">
                                <h5 class="fw-bold mb-2">Modern</h5>
                                <p class="text-muted mb-0">Contemporary fashion trends</p>
                            </div>
                        </div>
                    </a>
                </div>
                <div class="col-md-3">
                    <a href="<?php echo SITE_URL; ?>/pages/products/?category=formal" class="category-card">
                        <div class="card border-0 shadow-sm h-100 hover-lift">
                            <img src="<?php echo SITE_URL; ?>/assets/images/products/image7.jpeg" 
                                 class="card-img-top" 
                                 alt="Formal Wear"
                                 style="height: 280px; object-fit: cover;">
                            <div class="card-body text-center">
                                <h5 class="fw-bold mb-2">Formal</h5>
                                <p class="text-muted mb-0">Elegant and sophisticated attire</p>
                            </div>
                        </div>
                    </a>
                </div>
                <div class="col-md-3">
                    <a href="<?php echo SITE_URL; ?>/pages/products/?category=custom" class="category-card">
                        <div class="card border-0 shadow-sm h-100 hover-lift">
                            <img src="<?php echo SITE_URL; ?>/assets/images/products/image3.jpeg" 
                                 class="card-img-top" 
                                 alt="Custom Designs"
                                 style="height: 280px; object-fit: cover;">
                            <div class="card-body text-center">
                                <h5 class="fw-bold mb-2">Custom</h5>
                                <p class="text-muted mb-0">Personalized designs just for you</p>
                            </div>
                        </div>
                    </a>
                </div>
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
            
            <div class="row" id="featured-products">

                    <div class="col-md-3 mb-4">
                        <div class="card product-card border-0 shadow-sm h-100">
                            <div class="product-badge" style="position: absolute; top: 10px; left: 10px; background: #d4af37; color: white; padding: 4px 12px; border-radius: 4px; font-size: 12px; z-index: 10;">Popular</div>
                            
                            <img src="<?php echo SITE_URL; ?>/assets/images/products/image11.jpeg" 
                                class="card-img-top" 
                                alt="Product"
                                style="height: 220px; object-fit: cover;">
                            
                            <div class="card-body p-4">
                                <h5 class="fw-bold mb-2">Traditional Nigerian Agbada</h5>
                                <p class="text-muted small mb-3">Hand-embroidered with golden thread</p>
                                
                                <div class="d-flex justify-content-between align-items-center mt-auto">
                                    <span class="h5 text-primary fw-bold mb-0">50,000 CFA</span>
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

                    <div class="col-md-3 mb-4">
                        <div class="card product-card border-0 shadow-sm h-100">
                            
                            <img src="<?php echo SITE_URL; ?>/assets/images/products/image12.jpg" 
                                class="card-img-top" 
                                alt="Product"
                                style="height: 220px; object-fit: cover;">
                            
                            <div class="card-body p-4">
                                <h5 class="fw-bold mb-2">Traditional Nigerien Loincloths</h5>
                                <p class="text-muted small mb-3">Hand-embroidered with golden thread</p>
                                
                                <div class="d-flex justify-content-between align-items-center mt-auto">
                                    <span class="h5 text-primary fw-bold mb-0">9,000 CFA</span>
                                    <div class="text-warning">
                                        <i class="bi bi-star-fill"></i>
                                        <span class="ms-1 text-dark small">4.8</span>
                                    </div>
                                </div>
                            </div>

                            <div class="card-footer bg-white border-0 p-4 pt-0">
                                <a href="<?php echo SITE_URL; ?>/pages/products/view.php?id=2" class="btn btn-primary w-100 py-2">
                                    <i class="bi bi-eye me-2"></i> View Details
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- More featured products will be loaded -->
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
                <div class="col-md-3">
                    <div class="step-card text-center p-4">
                        <div class="step-icon mb-3">
                            <div class="icon-circle">
                                <i class="bi bi-search"></i>
                            </div>
                            <span class="step-number">1</span>
                        </div>
                        <h4 class="fw-bold mb-3">Browse & Select</h4>
                        <p class="text-muted">Explore unique designs from talented tailors worldwide</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="step-card text-center p-4">
                        <div class="step-icon mb-3">
                            <div class="icon-circle">
                                <i class="bi bi-chat-dots"></i>
                            </div>
                            <span class="step-number">2</span>
                        </div>
                        <h4 class="fw-bold mb-3">Customize</h4>
                        <p class="text-muted">Chat directly with the tailor for customizations</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="step-card text-center p-4">
                        <div class="step-icon mb-3">
                            <div class="icon-circle">
                                <i class="bi bi-credit-card"></i>
                            </div>
                            <span class="step-number">3</span>
                        </div>
                        <h4 class="fw-bold mb-3">Secure Payment</h4>
                        <p class="text-muted">Pay securely with multiple payment options</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="step-card text-center p-4">
                        <div class="step-icon mb-3">
                            <div class="icon-circle">
                                <i class="bi bi-truck"></i>
                            </div>
                            <span class="step-number">4</span>
                        </div>
                        <h4 class="fw-bold mb-3">Delivery</h4>
                        <p class="text-muted">Receive your unique outfit with worldwide shipping</p>
                    </div>
                </div>
            </div>
        </div>
    </section>
    
    <!-- Testimonials -->
    <section class="testimonials-section py-5">
        <div class="container">
            <div class="text-center mb-5">
                <h2 class="display-5 fw-bold mb-3">What Our Customers Say</h2>
                <p class="lead text-muted">Join thousands of satisfied customers worldwide</p>
            </div>
            
            <div class="row">
                <div class="col-md-4 mb-4">
                    <div class="testimonial-card p-4 border rounded">
                        <div class="d-flex align-items-center mb-3">
                            <img src="<?php echo SITE_URL; ?>/assets/images/avatars/testimonial1.jpg" 
                                 class="rounded-circle me-3"
                                 width="60"
                                 height="60"
                                 style="object-fit: cover;">
                            <div>
                                <h5 class="fw-bold mb-0">Sarah Johnson</h5>
                                <p class="text-muted mb-0">New York, USA</p>
                            </div>
                        </div>
                        <div class="text-warning mb-3">
                            <i class="bi bi-star-fill"></i>
                            <i class="bi bi-star-fill"></i>
                            <i class="bi bi-star-fill"></i>
                            <i class="bi bi-star-fill"></i>
                            <i class="bi bi-star-fill"></i>
                        </div>
                        <p class="mb-0">"The custom wedding dress I ordered was absolutely perfect! The tailor communicated with me throughout the process and made exactly what I envisioned."</p>
                    </div>
                </div>
                <div class="col-md-4 mb-4">
                    <div class="testimonial-card p-4 border rounded">
                        <div class="d-flex align-items-center mb-3">
                            <img src="<?php echo SITE_URL; ?>/assets/images/avatars/testimonial2.jpg" 
                                 class="rounded-circle me-3"
                                 width="60"
                                 height="60"
                                 style="object-fit: cover;">
                            <div>
                                <h5 class="fw-bold mb-0">Michael Chen</h5>
                                <p class="text-muted mb-0">Singapore</p>
                            </div>
                        </div>
                        <div class="text-warning mb-3">
                            <i class="bi bi-star-fill"></i>
                            <i class="bi bi-star-fill"></i>
                            <i class="bi bi-star-fill"></i>
                            <i class="bi bi-star-fill"></i>
                            <i class="bi bi-star-fill"></i>
                        </div>
                        <p class="mb-0">"Found an amazing tailor from Morocco who created a stunning traditional outfit for my cultural event. The quality exceeded my expectations!"</p>
                    </div>
                </div>
                <div class="col-md-4 mb-4">
                    <div class="testimonial-card p-4 border rounded">
                        <div class="d-flex align-items-center mb-3">
                            <img src="<?php echo SITE_URL; ?>/assets/images/avatars/testimonial3.jpg" 
                                 class="rounded-circle me-3"
                                 width="60"
                                 height="60"
                                 style="object-fit: cover;">
                            <div>
                                <h5 class="fw-bold mb-0">Fatima Ahmed</h5>
                                <p class="text-muted mb-0">Dubai, UAE</p>
                            </div>
                        </div>
                        <div class="text-warning mb-3">
                            <i class="bi bi-star-fill"></i>
                            <i class="bi bi-star-fill"></i>
                            <i class="bi bi-star-fill"></i>
                            <i class="bi bi-star-fill"></i>
                            <i class="bi bi-star-fill"></i>
                        </div>
                        <p class="mb-0">"As a tailor on this platform, I've connected with clients from over 20 countries. It's amazing to share my traditional designs with the world!"</p>
                    </div>
                </div>
            </div>
        </div>
    </section>
    
    <!-- CTA Section -->
    <section class="cta-section py-5 bg-primary text-white">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h2 class="display-5 fw-bold mb-3">Ready to Start Your Fashion Journey?</h2>
                    <p class="lead mb-4">Join thousands of customers and tailors in our global community</p>
                </div>
                <div class="col-md-4 text-end">
                    <a href="<?php echo SITE_URL; ?>/pages/auth/register.php" class="btn btn-light btn-lg px-4">
                        Get Started <i class="bi bi-arrow-right ms-2"></i>
                    </a>
                </div>
            </div>
        </div>
    </section>
    
    <?php include 'includes/components/footer.php'; ?>
    
    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="<?php echo SITE_URL; ?>/assets/js/main.js"></script>
    
    <style>
        .hero-section {
            background: linear-gradient(rgba(255,255,255,0.95), rgba(255,255,255,0.95)), 
                        url('<?php echo SITE_URL; ?>/assets/images/banners/pattern.jpg');
            background-size: cover;
            background-position: center;
            padding: 80px 0;
        }
        
        .product-card {
            transition: all 0.3s ease;
            overflow: hidden;
        }
        
        .product-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
        }
        
        .product-badge {
            position: absolute;
            top: 15px;
            right: 15px;
            background: #667eea;
            color: white;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 0.875rem;
            font-weight: 600;
            z-index: 1;
        }

        #featured-products .product-card {
            min-width: 250px; 
            border-radius: 15px;
            overflow: hidden;
        }

        #featured-products .card-body {
            display: flex;
            flex-direction: column;
        }
        
        .category-card {
            text-decoration: none;
            color: inherit;
        }
        
        .hover-lift {
            transition: all 0.3s ease;
        }
        
        .hover-lift:hover {
            transform: translateY(-10px);
        }
        
        .step-card {
            background: white;
            border-radius: 15px;
            transition: all 0.3s ease;
        }
        
        .step-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }
        
        .icon-circle {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 15px;
            color: white;
            font-size: 1.5rem;
        }
        
        .step-number {
            display: inline-block;
            width: 30px;
            height: 30px;
            background: #10b981;
            color: white;
            border-radius: 50%;
            line-height: 30px;
            font-weight: bold;
            position: relative;
            top: -15px;
        }
        
        .testimonial-card {
            background: white;
            transition: all 0.3s ease;
        }
        
        .testimonial-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }
        
        .cta-section {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        
        .animate-float {
            animation: float 6s ease-in-out infinite;
        }
        
        @keyframes float {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-20px); }
        }
        
        .stat-box h3 {
            font-size: 3.5rem;
        }
        
        @media (max-width: 768px) {
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
    <script>
        $(document).ready(function() {
            // Load featured products via AJAX
            function loadFeaturedProducts() {
                $.ajax({
                    url: '<?php echo SITE_URL; ?>/api/products.php',
                    method: 'GET',
                    data: { action: 'featured', limit: 8 },
                    success: function(response) {
                        if (response.success && response.products.length > 0) {
                            const container = $('#featured-products');
                            container.empty();
                            
                            response.products.forEach(function(product) {
                                const productHtml = `
                                    <div class="col-md-3 mb-4">
                                        <div class="card product-card border-0 shadow-sm h-100">
                                            ${product.is_customizable ? '<div class="product-badge">Custom</div>' : ''}
                                            <img src="${product.images[0]}" 
                                                 class="card-img-top" 
                                                 alt="${product.title}"
                                                 style="height: 200px; object-fit: cover;">
                                            <div class="card-body">
                                                <h5 class="fw-bold mb-2">${product.title}</h5>
                                                <p class="text-muted small mb-2">${product.tailor_name || 'Professional Tailor'}</p> 
                                                <div class="d-flex justify-content-between align-items-center">
                                                    <h1 class="mb-3">
                                                        <?php echo $category ? ($categories[$category]['name'] ?? 'Products') : 'All Products'; ?>
                                                    </h1>
                                                    <div class="text-warning">
                                                        <i class="bi bi-star-fill"></i>
                                                        <span class="ms-1">${product.rating}</span>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="card-footer bg-white border-0">
                                                <a href="<?php echo SITE_URL; ?>/pages/products/view.php?id=${product.id}" class="btn btn-primary w-100">
                                                    <i class="bi bi-eye me-2"></i> View Details
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                `;
                                container.append(productHtml);
                            });
                        }
                    }
                });
            }
            
            // Initialize
            loadFeaturedProducts();
            
            // Animate elements on scroll
            function animateOnScroll() {
                $('.animate-on-scroll').each(function() {
                    const elementTop = $(this).offset().top;
                    const elementBottom = elementTop + $(this).outerHeight();
                    const viewportTop = $(window).scrollTop();
                    const viewportBottom = viewportTop + $(window).height();
                    
                    if (elementBottom > viewportTop && elementTop < viewportBottom) {
                        $(this).addClass('animate__animated animate__fadeInUp');
                    }
                });
            }
            
            // Initialize scroll animation
            $(window).scroll(animateOnScroll);
            animateOnScroll();
            
            // Add hover effects to cards
            $('.product-card, .category-card, .step-card').hover(
                function() {
                    $(this).css('transform', 'translateY(-10px)');
                },
                function() {
                    $(this).css('transform', 'translateY(0)');
                }
            );
             
            // Newsletter subscription
            $('#newsletterForm').submit(function(e) {
                e.preventDefault();
                const email = $(this).find('input[type="email"]').val();
                
                $.ajax({
                    url: '<?php echo SITE_URL; ?>/api/newsletter.php',
                    method: 'POST',
                    data: { email: email, action: 'subscribe' },
                    success: function(response) {
                        if (response.success) {
                            alert('Thank you for subscribing to our newsletter!');
                            $('#newsletterForm')[0].reset();
                        } else {
                            alert('Subscription failed. Please try again.');
                        }
                    }
                });
            });
            
            // Initialize tooltips
            $('[data-bs-toggle="tooltip"]').tooltip();
        });
    </script>
</body>
</html>
*/
