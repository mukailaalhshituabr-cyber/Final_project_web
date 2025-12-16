<?php
require_once '../../config.php';
require_once '../../includes/classes/User.php';
require_once '../../includes/classes/Product.php';
require_once '../../includes/functions/product_functions.php';

// Check authentication and tailor role
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'tailor') {
    header('Location: ../auth/login.php');
    exit();
}

$user = new User();
$product = new Product();
$productFunctions = new ProductFunctions();

$tailorId = $_SESSION['user_id'];
$userData = $user->getUserById($tailorId);

// Handle product actions
$action = $_GET['action'] ?? '';
$productId = $_GET['id'] ?? 0;
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['add_product'])) {
        $result = $productFunctions->addProduct($tailorId, $_POST, $_FILES);
        if ($result['success']) {
            $message = 'Product added successfully!';
        } else {
            $error = implode(', ', $result['errors']);
        }
    } elseif (isset($_POST['update_product'])) {
        $result = $productFunctions->updateProduct($productId, $tailorId, $_POST, $_FILES);
        if ($result['success']) {
            $message = 'Product updated successfully!';
        } else {
            $error = implode(', ', $result['errors']);
        }
    }
}

// Handle delete action
if ($action == 'delete' && $productId) {
    $result = $productFunctions->deleteProduct($productId, $tailorId);
    if ($result['success']) {
        $message = 'Product deleted successfully!';
    } else {
        $error = $result['message'];
    }
}

// Get all tailor products
$products = $product->getTailorProducts($tailorId);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Products - <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        /* Add styles from the dashboard page */
        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            color: #333;
        }
        
        .dashboard-container {
            padding: 2rem 0;
        }
        
        .sidebar {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            height: 100%;
            overflow: hidden;
        }
        
        /* ... (Copy sidebar styles from dashboard.php) ... */
    </style>
</head>
<body>
    <?php include '../../includes/components/navbar.php'; ?>
    
    <div class="container-fluid dashboard-container">
        <div class="row g-4">
            <!-- Sidebar (same as dashboard) -->
            <div class="col-lg-3">
                <?php include '../../includes/components/sidebar.php'; ?>
            </div>
            
            <!-- Main Content -->
            <div class="col-lg-9">
                <div class="main-content">
                    <!-- Header -->
                    <div class="dashboard-header">
                        <div class="row align-items-center">
                            <div class="col-md-8">
                                <div class="dashboard-title">
                                    <h1>Product Management</h1>
                                    <p>Manage your clothing designs and products</p>
                                </div>
                            </div>
                            <div class="col-md-4 text-end">
                                <a href="?action=add" class="btn btn-primary">
                                    <i class="bi bi-plus-circle me-2"></i> Add New Product
                                </a>
                            </div>
                        </div>
                    </div>
                    
                    <?php if ($message): ?>
                        <div class="alert alert-success"><?php echo $message; ?></div>
                    <?php endif; ?>
                    
                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                    <?php endif; ?>
                    
                    <?php if ($action == 'add' || $action == 'edit'): ?>
                        <!-- Product Form -->
                        <div class="card border-0 shadow-sm">
                            <div class="card-body p-4">
                                <h4 class="fw-bold mb-4"><?php echo $action == 'add' ? 'Add New Product' : 'Edit Product'; ?></h4>
                                
                                <form method="POST" enctype="multipart/form-data" id="productForm">
                                    <?php if ($action == 'edit' && $productId): ?>
                                        <input type="hidden" name="update_product" value="1">
                                        <?php 
                                        $productData = $product->getById($productId);
                                        if (!$productData || $productData['tailor_id'] != $tailorId) {
                                            echo '<div class="alert alert-danger">Product not found or access denied</div>';
                                            exit();
                                        }
                                        ?>
                                    <?php else: ?>
                                        <input type="hidden" name="add_product" value="1">
                                    <?php endif; ?>
                                    
                                    <div class="row">
                                        <div class="col-md-8">
                                            <div class="mb-3">
                                                <label class="form-label fw-bold">Product Title *</label>
                                                <input type="text" 
                                                       class="form-control" 
                                                       name="title" 
                                                       value="<?php echo $productData['title'] ?? ''; ?>"
                                                       required>
                                            </div>
                                            
                                            <div class="mb-3">
                                                <label class="form-label fw-bold">Description *</label>
                                                <textarea class="form-control" 
                                                          name="description" 
                                                          rows="4"
                                                          required><?php echo $productData['description'] ?? ''; ?></textarea>
                                            </div>
                                            
                                            <div class="row">
                                                <div class="col-md-6 mb-3">
                                                    <label class="form-label fw-bold">Price ($) *</label>
                                                    <input type="number" 
                                                           class="form-control" 
                                                           name="price" 
                                                           step="0.01"
                                                           value="<?php echo $productData['price'] ?? ''; ?>"
                                                           required>
                                                </div>
                                                <div class="col-md-6 mb-3">
                                                    <label class="form-label fw-bold">Category *</label>
                                                    <select class="form-select" name="category" required>
                                                        <option value="">Select Category</option>
                                                        <option value="traditional" <?php echo ($productData['category'] ?? '') == 'traditional' ? 'selected' : ''; ?>>Traditional</option>
                                                        <option value="modern" <?php echo ($productData['category'] ?? '') == 'modern' ? 'selected' : ''; ?>>Modern</option>
                                                        <option value="formal" <?php echo ($productData['category'] ?? '') == 'formal' ? 'selected' : ''; ?>>Formal</option>
                                                        <option value="casual" <?php echo ($productData['category'] ?? '') == 'casual' ? 'selected' : ''; ?>>Casual</option>
                                                    </select>
                                                </div>
                                            </div>
                                            
                                            <div class="row">
                                                <div class="col-md-6 mb-3">
                                                    <label class="form-label fw-bold">Material</label>
                                                    <input type="text" 
                                                           class="form-control" 
                                                           name="material"
                                                           value="<?php echo $productData['material'] ?? ''; ?>">
                                                </div>
                                                <div class="col-md-6 mb-3">
                                                    <label class="form-label fw-bold">Size</label>
                                                    <select class="form-select" name="size">
                                                        <option value="">Select Size</option>
                                                        <option value="XS" <?php echo ($productData['size'] ?? '') == 'XS' ? 'selected' : ''; ?>>XS</option>
                                                        <option value="S" <?php echo ($productData['size'] ?? '') == 'S' ? 'selected' : ''; ?>>S</option>
                                                        <option value="M" <?php echo ($productData['size'] ?? '') == 'M' ? 'selected' : ''; ?>>M</option>
                                                        <option value="L" <?php echo ($productData['size'] ?? '') == 'L' ? 'selected' : ''; ?>>L</option>
                                                        <option value="XL" <?php echo ($productData['size'] ?? '') == 'XL' ? 'selected' : ''; ?>>XL</option>
                                                        <option value="Custom" <?php echo ($productData['size'] ?? '') == 'Custom' ? 'selected' : ''; ?>>Custom</option>
                                                    </select>
                                                </div>
                                            </div>
                                            
                                            <div class="mb-3">
                                                <label class="form-label fw-bold">Color</label>
                                                <input type="text" 
                                                       class="form-control" 
                                                       name="color"
                                                       value="<?php echo $productData['color'] ?? ''; ?>">
                                            </div>
                                        </div>
                                        
                                        <div class="col-md-4">
                                            <div class="mb-3">
                                                <label class="form-label fw-bold">Product Images *</label>
                                                <input type="file" 
                                                       class="form-control" 
                                                       name="images[]" 
                                                       multiple 
                                                       accept="image/*"
                                                       <?php echo $action == 'add' ? 'required' : ''; ?>>
                                                <small class="text-muted">Upload up to 5 images (first image will be the main display)</small>
                                                
                                                <?php if ($action == 'edit' && !empty($productData['images'])): ?>
                                                    <div class="mt-3">
                                                        <label class="form-label">Current Images:</label>
                                                        <div class="row g-2">
                                                            <?php 
                                                            $images = json_decode($productData['images'], true);
                                                            foreach ($images as $img): ?>
                                                                <div class="col-6">
                                                                    <img src="<?php echo SITE_URL; ?>/assets/uploads/products/<?php echo $img; ?>" 
                                                                         class="img-fluid rounded">
                                                                </div>
                                                            <?php endforeach; ?>
                                                        </div>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                            
                                            <div class="mb-3">
                                                <label class="form-label fw-bold">Stock Quantity</label>
                                                <input type="number" 
                                                       class="form-control" 
                                                       name="stock" 
                                                       min="1"
                                                       value="<?php echo $productData['stock'] ?? 1; ?>">
                                            </div>
                                            
                                            <div class="form-check mb-3">
                                                <input class="form-check-input" 
                                                       type="checkbox" 
                                                       name="is_customizable" 
                                                       id="customizable"
                                                       <?php echo ($productData['is_customizable'] ?? 0) ? 'checked' : ''; ?>>
                                                <label class="form-check-label" for="customizable">
                                                    This product can be customized
                                                </label>
                                            </div>
                                            
                                            <?php if ($action == 'edit'): ?>
                                                <div class="mb-3">
                                                    <label class="form-label fw-bold">Status</label>
                                                    <select class="form-select" name="status">
                                                        <option value="active" <?php echo ($productData['status'] ?? '') == 'active' ? 'selected' : ''; ?>>Active</option>
                                                        <option value="inactive" <?php echo ($productData['status'] ?? '') == 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                                                    </select>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    
                                    <div class="mt-4">
                                        <button type="submit" class="btn btn-primary px-4">
                                            <?php echo $action == 'add' ? 'Add Product' : 'Update Product'; ?>
                                        </button>
                                        <a href="products.php" class="btn btn-outline-secondary ms-2">Cancel</a>
                                    </div>
                                </form>
                            </div>
                        </div>
                        
                    <?php else: ?>
                        <!-- Products Grid -->
                        <div class="row mb-4">
                            <div class="col-md-4">
                                <div class="input-group">
                                    <input type="text" class="form-control" placeholder="Search products..." id="searchInput">
                                    <button class="btn btn-outline-secondary" type="button">
                                        <i class="bi bi-search"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <select class="form-select" id="categoryFilter">
                                    <option value="">All Categories</option>
                                    <option value="traditional">Traditional</option>
                                    <option value="modern">Modern</option>
                                    <option value="formal">Formal</option>
                                    <option value="casual">Casual</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <select class="form-select" id="statusFilter">
                                    <option value="">All Status</option>
                                    <option value="active">Active</option>
                                    <option value="inactive">Inactive</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="row" id="productsGrid">
                            <?php if (!empty($products)): ?>
                                <?php foreach ($products as $product): ?>
                                    <div class="col-md-4 mb-4">
                                        <div class="card product-card border-0 shadow-sm h-100">
                                            <?php if ($product['status'] == 'inactive'): ?>
                                                <div class="product-badge bg-secondary">Inactive</div>
                                            <?php elseif ($product['is_customizable']): ?>
                                                <div class="product-badge bg-warning">Custom</div>
                                            <?php endif; ?>
                                            
                                            <?php if ($product['stock'] <= 3): ?>
                                                <div class="product-badge bg-danger" style="left: 15px;">Low Stock</div>
                                            <?php endif; ?>
                                            
                                            <img src="<?php echo SITE_URL; ?>/assets/images/products/<?php 
                                                $images = json_decode($product['images'], true);
                                                echo $images[0] ?? 'default.jpg'; 
                                            ?>" 
                                                 class="card-img-top" 
                                                 alt="<?php echo htmlspecialchars($product['title']); ?>"
                                                 style="height: 200px; object-fit: cover;">
                                            <div class="card-body">
                                                <h5 class="fw-bold mb-2"><?php echo htmlspecialchars($product['title']); ?></h5>
                                                <p class="text-muted small mb-2"><?php echo htmlspecialchars($product['category']); ?></p>
                                                <div class="d-flex justify-content-between align-items-center mb-3">
                                                    <span class="h5 text-primary mb-0">$<?php echo number_format($product['price'], 2); ?></span>
                                                    <div class="text-warning">
                                                        <i class="bi bi-star-fill"></i>
                                                        <span class="ms-1"><?php echo number_format($product['rating'], 1); ?></span>
                                                    </div>
                                                </div>
                                                <div class="d-flex justify-content-between">
                                                    <span class="badge bg-light text-dark">Stock: <?php echo $product['stock']; ?></span>
                                                    <span class="badge bg-<?php echo $product['status'] == 'active' ? 'success' : 'secondary'; ?>">
                                                        <?php echo ucfirst($product['status']); ?>
                                                    </span>
                                                </div>
                                            </div>
                                            <div class="card-footer bg-white border-0">
                                                <div class="d-flex gap-2">
                                                    <a href="?action=edit&id=<?php echo $product['id']; ?>" class="btn btn-sm btn-outline-primary flex-fill">
                                                        <i class="bi bi-pencil"></i> Edit
                                                    </a>
                                                    <button class="btn btn-sm btn-outline-danger" 
                                                            onclick="confirmDelete(<?php echo $product['id']; ?>)">
                                                        <i class="bi bi-trash"></i>
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="col-12 text-center py-5">
                                    <i class="bi bi-grid display-4 text-muted mb-3"></i>
                                    <h4 class="text-muted mb-3">No products yet</h4>
                                    <p class="text-muted mb-4">Start by adding your first clothing design</p>
                                    <a href="?action=add" class="btn btn-primary">
                                        <i class="bi bi-plus-circle me-2"></i> Add First Product
                                    </a>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Product Statistics -->
                        <div class="row mt-5">
                            <div class="col-md-3">
                                <div class="card border-0 shadow-sm">
                                    <div class="card-body text-center">
                                        <h3 class="fw-bold text-primary"><?php echo count($products); ?></h3>
                                        <p class="text-muted mb-0">Total Products</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card border-0 shadow-sm">
                                    <div class="card-body text-center">
                                        <h3 class="fw-bold text-success"><?php 
                                            $active = array_filter($products, function($p) { return $p['status'] == 'active'; });
                                            echo count($active);
                                        ?></h3>
                                        <p class="text-muted mb-0">Active Products</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card border-0 shadow-sm">
                                    <div class="card-body text-center">
                                        <h3 class="fw-bold text-warning"><?php 
                                            $customizable = array_filter($products, function($p) { return $p['is_customizable']; });
                                            echo count($customizable);
                                        ?></h3>
                                        <p class="text-muted mb-0">Customizable</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card border-0 shadow-sm">
                                    <div class="card-body text-center">
                                        <h3 class="fw-bold text-danger"><?php 
                                            $lowStock = array_filter($products, function($p) { return $p['stock'] <= 3; });
                                            echo count($lowStock);
                                        ?></h3>
                                        <p class="text-muted mb-0">Low Stock</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        function confirmDelete(productId) {
            if (confirm('Are you sure you want to delete this product?')) {
                window.location.href = 'products.php?action=delete&id=' + productId;
            }
        }
        
        // Filter products
        $('#searchInput, #categoryFilter, #statusFilter').on('input change', function() {
            const search = $('#searchInput').val().toLowerCase();
            const category = $('#categoryFilter').val();
            const status = $('#statusFilter').val();
            
            $('.product-card').each(function() {
                const title = $(this).find('h5').text().toLowerCase();
                const productCategory = $(this).find('.text-muted').text().toLowerCase();
                const productStatus = $(this).find('.badge:last-child').text().toLowerCase();
                
                const matchesSearch = title.includes(search) || productCategory.includes(search);
                const matchesCategory = !category || productCategory.includes(category);
                const matchesStatus = !status || productStatus.includes(status);
                
                if (matchesSearch && matchesCategory && matchesStatus) {
                    $(this).closest('.col-md-4').show();
                } else {
                    $(this).closest('.col-md-4').hide();
                }
            });
        });
    </script>
</body>
</html>