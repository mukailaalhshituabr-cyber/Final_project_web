pages/customer/profile.php (make sure user can put any kind of picture to his profile and change if he doesn't longer want it)
pages/tailor/products.php
pages/tailor/orders.php
pages/tailor/messages.php
pages/tailor/add-product.php
pages/tailor/reviews.php
pages/tailor/earnings.php



pages/customer/profile.php (make sure user can put any kind of picture to his profile and change if he doesn't longer want it)
pages/customer/orders.php
pages/customer/messages-product.php
pages/customer/wishlist.php
pages/customer/fix_folder.php






These three files complete the tailor dashboard with:

profile.php - Allows tailors to:

Upload and remove profile pictures

Update basic information and bio

Manage social media links

Change password

Add address and working hours

reviews.php - Enables tailors to:

View all customer reviews with filters

See review statistics and ratings breakdown

Reply to reviews

Show/hide reviews

Delete replies

earnings.php - Provides earnings management:

View earnings summary by period (monthly/quarterly/yearly)

Request payouts

Manage payout methods (bank, mobile money, PayPal)

View upcoming payouts

See earnings history with charts

All files include proper security checks, user authentication, responsive design, and intuitive interfaces for tailors to manage their business effectively.







7. pages/tailor/add-product.php
php
<?php
require_once dirname(__DIR__, 2) . '/config.php';
require_once ROOT_PATH . '/includes/classes/Database.php';
require_once ROOT_PATH . '/includes/classes/Product.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'tailor') {
    header('Location: ../auth/login.php');
    exit();
}

$db = Database::getInstance();
$productObj = new Product();
$tailorId = $_SESSION['user_id'];

$message = '';
$error = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Basic validation
        $required = ['title', 'price', 'category', 'description'];
        foreach ($required as $field) {
            if (empty($_POST[$field])) {
                throw new Exception("Please fill in all required fields");
            }
        }
        
        // Prepare product data
        $productData = [
            'tailor_id' => $tailorId,
            'title' => trim($_POST['title']),
            'description' => trim($_POST['description']),
            'price' => floatval($_POST['price']),
            'compare_price' => !empty($_POST['compare_price']) ? floatval($_POST['compare_price']) : null,
            'cost_price' => !empty($_POST['cost_price']) ? floatval($_POST['cost_price']) : null,
            'sku' => trim($_POST['sku'] ?? ''),
            'category' => $_POST['category'],
            'subcategory' => $_POST['subcategory'] ?? '',
            'material' => trim($_POST['material'] ?? ''),
            'size' => $_POST['size'] ?? null,
            'color' => trim($_POST['color'] ?? ''),
            'brand' => trim($_POST['brand'] ?? ''),
            'is_customizable' => isset($_POST['is_customizable']) ? 1 : 0,
            'stock_quantity' => intval($_POST['stock_quantity'] ?? 1),
            'low_stock_threshold' => intval($_POST['low_stock_threshold'] ?? 5),
            'weight' => !empty($_POST['weight']) ? floatval($_POST['weight']) : null,
            'dimensions' => trim($_POST['dimensions'] ?? ''),
            'status' => $_POST['status'] ?? 'draft',
            'featured' => isset($_POST['featured']) ? 1 : 0
        ];
        
        // Handle tags
        if (!empty($_POST['tags'])) {
            $tags = array_map('trim', explode(',', $_POST['tags']));
            $productData['tags'] = $tags;
        }
        
        // Handle specifications
        $specifications = [];
        if (!empty($_POST['spec_keys']) && !empty($_POST['spec_values'])) {
            $keys = $_POST['spec_keys'];
            $values = $_POST['spec_values'];
            for ($i = 0; $i < count($keys); $i++) {
                if (!empty($keys[$i]) && !empty($values[$i])) {
                    $specifications[trim($keys[$i])] = trim($values[$i]);
                }
            }
        }
        $productData['specifications'] = $specifications;
        
        // Handle customization options
        if ($productData['is_customizable'] && !empty($_POST['customization_options'])) {
            $customizationOptions = [];
            $optionNames = $_POST['custom_option_names'] ?? [];
            $optionTypes = $_POST['custom_option_types'] ?? [];
            $optionValues = $_POST['custom_option_values'] ?? [];
            $optionPrices = $_POST['custom_option_prices'] ?? [];
            
            for ($i = 0; $i < count($optionNames); $i++) {
                if (!empty($optionNames[$i])) {
                    $option = [
                        'name' => trim($optionNames[$i]),
                        'type' => $optionTypes[$i] ?? 'text',
                        'values' => !empty($optionValues[$i]) ? array_map('trim', explode(',', $optionValues[$i])) : [],
                        'price' => !empty($optionPrices[$i]) ? floatval($optionPrices[$i]) : 0
                    ];
                    $customizationOptions[] = $option;
                }
            }
            $productData['customization_options'] = $customizationOptions;
        }
        
        // Handle image uploads
        $uploadedImages = [];
        if (!empty($_FILES['images']['name'][0])) {
            $images = $_FILES['images'];
            $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
            $maxSize = 5 * 1024 * 1024; // 5MB
            
            for ($i = 0; $i < count($images['name']); $i++) {
                if ($images['error'][$i] === UPLOAD_ERR_OK) {
                    if (in_array($images['type'][$i], $allowedTypes) && $images['size'][$i] <= $maxSize) {
                        // Generate unique filename
                        $extension = pathinfo($images['name'][$i], PATHINFO_EXTENSION);
                        $filename = 'product_' . time() . '_' . $i . '.' . $extension;
                        $uploadPath = PRODUCT_IMAGES_PATH . $filename;
                        
                        if (move_uploaded_file($images['tmp_name'][$i], $uploadPath)) {
                            $uploadedImages[] = $filename;
                        }
                    }
                }
            }
            
            if (!empty($uploadedImages)) {
                $productData['images'] = $uploadedImages;
            }
        }
        
        // Create product
        $result = $productObj->createProduct($productData);
        
        if ($result['success']) {
            $message = 'Product created successfully!';
            // Clear form or redirect
            if ($_POST['status'] == 'active') {
                header('Location: products.php?created=1');
                exit();
            }
        } else {
            $error = $result['error'];
        }
        
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Get categories
$categories = $db->getCategories();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Product - <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">
    <link href="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-lite.min.css" rel="stylesheet">
    <style>
        .product-form-container {
            min-height: calc(100vh - 200px);
        }
        .form-section {
            background: white;
            border-radius: 10px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        .section-title {
            font-weight: 600;
            color: #495057;
            margin-bottom: 1.5rem;
            padding-bottom: 0.75rem;
            border-bottom: 2px solid #667eea;
        }
        .image-upload-area {
            border: 2px dashed #dee2e6;
            border-radius: 10px;
            padding: 2rem;
            text-align: center;
            transition: all 0.3s ease;
            cursor: pointer;
        }
        .image-upload-area:hover {
            border-color: #667eea;
            background: #f8f9fa;
        }
        .image-preview {
            width: 100px;
            height: 100px;
            object-fit: cover;
            border-radius: 8px;
            margin: 0.5rem;
        }
        .image-preview-container {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
            margin-top: 1rem;
        }
        .customization-option {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 1rem;
            margin-bottom: 1rem;
            border-left: 4px solid #667eea;
        }
        .btn-add-option {
            background: #667eea;
            color: white;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 5px;
            transition: all 0.3s ease;
        }
        .btn-add-option:hover {
            background: #5a67d8;
            transform: translateY(-2px);
        }
        .specification-row {
            margin-bottom: 0.5rem;
        }
        .btn-save {
            background: linear-gradient(135deg, #667eea, #764ba2);
            border: none;
            padding: 0.75rem 2rem;
            font-weight: 600;
        }
        .btn-save:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.3);
        }
    </style>
</head>
<body>
    <?php include '../../includes/components/navbar.php'; ?>
    
    <div class="container product-form-container py-5">
        <div class="row">
            <div class="col-12">
                <h1 class="h3 fw-bold mb-4">Add New Product</h1>
            </div>
        </div>

        <?php if ($message): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle me-2"></i><?php echo htmlspecialchars($message); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>
        
        <?php if ($error): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="bi bi-exclamation-triangle me-2"></i><?php echo htmlspecialchars($error); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <form method="POST" action="" enctype="multipart/form-data" id="productForm">
            <!-- Basic Information -->
            <div class="form-section">
                <h3 class="section-title">Basic Information</h3>
                <div class="row">
                    <div class="col-md-8 mb-3">
                        <label class="form-label">Product Title *</label>
                        <input type="text" class="form-control" name="title" required 
                               placeholder="Enter product title" maxlength="200">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">SKU</label>
                        <input type="text" class="form-control" name="sku" 
                               placeholder="Product SKU (optional)">
                    </div>
                </div>
                
                <div class="mb-3">
                    <label class="form-label">Description *</label>
                    <textarea class="form-control" id="description" name="description" rows="6" required></textarea>
                </div>
                
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Category *</label>
                        <select class="form-select" name="category" required>
                            <option value="">Select Category</option>
                            <?php foreach ($categories as $cat): ?>
                            <option value="<?php echo $cat['slug']; ?>"><?php echo htmlspecialchars($cat['name']); ?></option>
                            <?php endforeach; ?>
                            <option value="traditional-wear">Traditional Wear</option>
                            <option value="modern-fashion">Modern Fashion</option>
                            <option value="formal">Formal Wear</option>
                            <option value="custom">Custom Designs</option>
                        </select>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Subcategory</label>
                        <select class="form-select" name="subcategory">
                            <option value="">Select Subcategory</option>
                            <option value="mens">Men's Wear</option>
                            <option value="womens">Women's Wear</option>
                            <option value="kids">Kids Wear</option>
                            <option value="accessories">Accessories</option>
                        </select>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Material</label>
                        <input type="text" class="form-control" name="material" 
                               placeholder="e.g., Cotton, Silk, Linen">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Size</label>
                        <select class="form-select" name="size">
                            <option value="">Select Size</option>
                            <option value="XS">XS</option>
                            <option value="S">S</option>
                            <option value="M">M</option>
                            <option value="L">L</option>
                            <option value="XL">XL</option>
                            <option value="XXL">XXL</option>
                            <option value="Custom">Custom</option>
                        </select>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Color</label>
                        <input type="text" class="form-control" name="color" 
                               placeholder="e.g., Red, Blue, Multi-color">
                    </div>
                </div>
                
                <div class="mb-3">
                    <label class="form-label">Tags</label>
                    <input type="text" class="form-control" name="tags" 
                           placeholder="Enter tags separated by commas (e.g., traditional, handmade, premium)">
                    <small class="text-muted">Tags help customers find your product</small>
                </div>
            </div>

            <!-- Pricing -->
            <div class="form-section">
                <h3 class="section-title">Pricing</h3>
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Price (CFA) *</label>
                        <input type="number" class="form-control" name="price" 
                               step="0.01" min="0" required>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Compare Price (CFA)</label>
                        <input type="number" class="form-control" name="compare_price" 
                               step="0.01" min="0">
                        <small class="text-muted">Original price to show discount</small>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Cost Price (CFA)</label>
                        <input type="number" class="form-control" name="cost_price" 
                               step="0.01" min="0">
                        <small class="text-muted">Your cost for this product</small>
                    </div>
                </div>
                
                <div class="form-check mb-3">
                    <input class="form-check-input" type
You're almost done, just continue
Length limit reached. Please start a new chat.


Then it will remain these files:
pages/tailor/profile.php (make sure user can put any kind of picture to his profile and change if he doesn't longer want it)
pages/tailor/reviews.php
pages/tailor/earnings.php
