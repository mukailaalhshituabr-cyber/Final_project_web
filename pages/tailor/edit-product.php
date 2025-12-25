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

// Get product ID from URL
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: products.php');
    exit();
}

$productId = intval($_GET['id']);

// Check if product belongs to this tailor
$product = $productObj->getProduct($productId);
if (!$product || $product['tailor_id'] != $tailorId) {
    header('Location: products.php');
    exit();
}

// Get existing images
$existingImages = $productObj->getProductImages($productId);

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
        } else {
            $productData['tags'] = [];
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
        } else {
            $productData['customization_options'] = [];
        }
        
        // Handle image deletions
        $imagesToKeep = $_POST['existing_images'] ?? [];
        $deletedImages = [];
        foreach ($existingImages as $image) {
            if (!in_array($image['filename'], $imagesToKeep)) {
                $deletedImages[] = $image['filename'];
            }
        }
        
        // Delete removed images from server
        foreach ($deletedImages as $imageName) {
            $filePath = PRODUCT_IMAGES_PATH . $imageName;
            if (file_exists($filePath)) {
                unlink($filePath);
            }
        }
        
        // Handle new image uploads
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
        }
        
        // Combine existing and new images
        $allImages = array_merge($imagesToKeep, $uploadedImages);
        $productData['images'] = $allImages;
        
        // Update product
        $result = $productObj->updateProduct($productId, $productData);
        
        if ($result['success']) {
            $message = 'Product updated successfully!';
            // Refresh product data
            $product = $productObj->getProduct($productId);
            $existingImages = $productObj->getProductImages($productId);
            
            if ($_POST['status'] == 'active') {
                header('Location: products.php?updated=1');
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

// Get specifications
$specifications = !empty($product['specifications']) ? json_decode($product['specifications'], true) : [];
if (!is_array($specifications)) {
    $specifications = [];
}

// Get customization options
$customizationOptions = !empty($product['customization_options']) ? json_decode($product['customization_options'], true) : [];
if (!is_array($customizationOptions)) {
    $customizationOptions = [];
}

// Get tags
$tags = !empty($product['tags']) ? json_decode($product['tags'], true) : [];
if (!is_array($tags)) {
    $tags = [];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Product - <?php echo SITE_NAME; ?></title>
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
        .image-upload-container {
            border-radius: 10px;
            overflow: hidden;
        }
        .image-upload-area {
            border: 3px dashed #dee2e6;
            border-radius: 10px;
            padding: 3rem 2rem;
            text-align: center;
            transition: all 0.3s ease;
            cursor: pointer;
            background: #f8f9fa;
            position: relative;
        }
        .image-upload-area.drag-over {
            border-color: #667eea;
            background: rgba(102, 126, 234, 0.05);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.1);
        }
        .image-upload-icon {
            font-size: 4rem;
            color: #667eea;
            margin-bottom: 1rem;
        }
        .image-upload-text {
            font-size: 1.1rem;
            font-weight: 500;
            color: #495057;
            margin-bottom: 0.5rem;
        }
        .image-upload-subtext {
            color: #6c757d;
            font-size: 0.9rem;
        }
        .image-preview {
            width: 120px;
            height: 120px;
            object-fit: cover;
            border-radius: 8px;
            border: 2px solid #dee2e6;
        }
        .image-preview.existing {
            border-color: #28a745;
        }
        .image-preview.new {
            border-color: #007bff;
        }
        .image-preview-container {
            display: flex;
            flex-wrap: wrap;
            gap: 1rem;
            margin-top: 1.5rem;
            min-height: 120px;
        }
        .image-preview-wrapper {
            position: relative;
            transition: all 0.3s ease;
        }
        .image-preview-wrapper:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        .image-preview-wrapper:hover .image-preview {
            border-color: #667eea;
        }
        .remove-image {
            position: absolute;
            top: -8px;
            right: -8px;
            width: 25px;
            height: 25px;
            border-radius: 50%;
            background: #dc3545;
            color: white;
            border: none;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.8rem;
            cursor: pointer;
            opacity: 0;
            transition: opacity 0.3s ease;
            z-index: 2;
        }
        .image-preview-wrapper:hover .remove-image {
            opacity: 1;
        }
        .remove-image:hover {
            background: #c82333;
        }
        .image-type-badge {
            position: absolute;
            top: 5px;
            left: 5px;
            padding: 2px 6px;
            border-radius: 4px;
            font-size: 0.7rem;
            font-weight: 600;
            z-index: 1;
        }
        .badge-existing {
            background: #28a745;
            color: white;
        }
        .badge-new {
            background: #007bff;
            color: white;
        }
        .image-counter {
            position: absolute;
            bottom: 5px;
            right: 5px;
            background: rgba(0,0,0,0.7);
            color: white;
            padding: 2px 8px;
            border-radius: 10px;
            font-size: 0.8rem;
        }
        .customization-option {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 1.5rem;
            margin-bottom: 1rem;
            border-left: 4px solid #667eea;
        }
        .btn-add-option {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        .btn-add-option:hover {
            background: linear-gradient(135deg, #5a67d8, #6b46c1);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.3);
        }
        .specification-row {
            margin-bottom: 1rem;
            padding: 1rem;
            background: #f8f9fa;
            border-radius: 8px;
            transition: all 0.3s ease;
        }
        .specification-row:hover {
            background: #e9ecef;
        }
        .btn-save {
            background: linear-gradient(135deg, #667eea, #764ba2);
            border: none;
            padding: 0.75rem 2.5rem;
            font-weight: 600;
            border-radius: 8px;
        }
        .btn-save:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.3);
        }
        .btn-browse {
            background: white;
            color: #667eea;
            border: 2px solid #667eea;
            padding: 0.5rem 1.5rem;
            border-radius: 8px;
            font-weight: 500;
            margin-top: 1rem;
            transition: all 0.3s ease;
        }
        .btn-browse:hover {
            background: #667eea;
            color: white;
        }
        .upload-progress {
            display: none;
            margin-top: 1rem;
        }
        .upload-progress.active {
            display: block;
        }
        .progress-bar {
            background: linear-gradient(135deg, #667eea, #764ba2);
        }
        .file-info {
            font-size: 0.85rem;
            color: #6c757d;
            margin-top: 0.5rem;
        }
        .existing-image-checkbox {
            position: absolute;
            bottom: 5px;
            left: 5px;
            z-index: 2;
        }
        .existing-image-checkbox input {
            margin-right: 3px;
        }
        .existing-image-checkbox label {
            color: white;
            font-size: 0.8rem;
            margin-bottom: 0;
            cursor: pointer;
            text-shadow: 1px 1px 1px rgba(0,0,0,0.5);
        }
    </style>
</head>
<body>
    <?php include '../../includes/components/navbar.php'; ?>
    
    <div class="container product-form-container py-5">
        <div class="row">
            <div class="col-12">
                <h1 class="h3 fw-bold mb-4">Edit Product: <?php echo htmlspecialchars($product['title']); ?></h1>
                <p class="text-muted">Update your product details below</p>
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
            <input type="hidden" name="product_id" value="<?php echo $productId; ?>">
            
            <!-- Basic Information -->
            <div class="form-section">
                <h3 class="section-title"><i class="bi bi-info-circle me-2"></i>Basic Information</h3>
                <div class="row">
                    <div class="col-md-8 mb-3">
                        <label class="form-label">Product Title *</label>
                        <input type="text" class="form-control" name="title" required 
                               value="<?php echo htmlspecialchars($product['title']); ?>" maxlength="200">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">SKU</label>
                        <input type="text" class="form-control" name="sku" 
                               value="<?php echo htmlspecialchars($product['sku'] ?? ''); ?>"
                               placeholder="Product SKU (optional)">
                    </div>
                </div>
                
                <div class="mb-3">
                    <label class="form-label">Description *</label>
                    <textarea class="form-control" id="description" name="description" rows="6" required><?php echo htmlspecialchars($product['description']); ?></textarea>
                </div>
                
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Category *</label>
                        <select class="form-select" name="category" required>
                            <option value="">Select Category</option>
                            <?php foreach ($categories as $cat): ?>
                            <option value="<?php echo $cat['slug']; ?>" <?php echo $product['category'] == $cat['slug'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($cat['name']); ?>
                            </option>
                            <?php endforeach; ?>
                            <option value="traditional-wear" <?php echo $product['category'] == 'traditional-wear' ? 'selected' : ''; ?>>Traditional Wear</option>
                            <option value="modern-fashion" <?php echo $product['category'] == 'modern-fashion' ? 'selected' : ''; ?>>Modern Fashion</option>
                            <option value="formal" <?php echo $product['category'] == 'formal' ? 'selected' : ''; ?>>Formal Wear</option>
                            <option value="custom" <?php echo $product['category'] == 'custom' ? 'selected' : ''; ?>>Custom Designs</option>
                        </select>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Subcategory</label>
                        <select class="form-select" name="subcategory">
                            <option value="">Select Subcategory</option>
                            <option value="mens" <?php echo ($product['subcategory'] ?? '') == 'mens' ? 'selected' : ''; ?>>Men's Wear</option>
                            <option value="womens" <?php echo ($product['subcategory'] ?? '') == 'womens' ? 'selected' : ''; ?>>Women's Wear</option>
                            <option value="kids" <?php echo ($product['subcategory'] ?? '') == 'kids' ? 'selected' : ''; ?>>Kids Wear</option>
                            <option value="accessories" <?php echo ($product['subcategory'] ?? '') == 'accessories' ? 'selected' : ''; ?>>Accessories</option>
                        </select>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Material</label>
                        <input type="text" class="form-control" name="material" 
                               value="<?php echo htmlspecialchars($product['material'] ?? ''); ?>"
                               placeholder="e.g., Cotton, Silk, Linen">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Size</label>
                        <select class="form-select" name="size">
                            <option value="">Select Size</option>
                            <option value="XS" <?php echo ($product['size'] ?? '') == 'XS' ? 'selected' : ''; ?>>XS</option>
                            <option value="S" <?php echo ($product['size'] ?? '') == 'S' ? 'selected' : ''; ?>>S</option>
                            <option value="M" <?php echo ($product['size'] ?? '') == 'M' ? 'selected' : ''; ?>>M</option>
                            <option value="L" <?php echo ($product['size'] ?? '') == 'L' ? 'selected' : ''; ?>>L</option>
                            <option value="XL" <?php echo ($product['size'] ?? '') == 'XL' ? 'selected' : ''; ?>>XL</option>
                            <option value="XXL" <?php echo ($product['size'] ?? '') == 'XXL' ? 'selected' : ''; ?>>XXL</option>
                            <option value="Custom" <?php echo ($product['size'] ?? '') == 'Custom' ? 'selected' : ''; ?>>Custom</option>
                        </select>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Color</label>
                        <input type="text" class="form-control" name="color" 
                               value="<?php echo htmlspecialchars($product['color'] ?? ''); ?>"
                               placeholder="e.g., Red, Blue, Multi-color">
                    </div>
                </div>
                
                <div class="mb-3">
                    <label class="form-label">Tags</label>
                    <input type="text" class="form-control" name="tags" 
                           value="<?php echo htmlspecialchars(implode(', ', $tags)); ?>"
                           placeholder="Enter tags separated by commas (e.g., traditional, handmade, premium)">
                    <small class="text-muted">Tags help customers find your product</small>
                </div>
            </div>

            <!-- Pricing -->
            <div class="form-section">
                <h3 class="section-title"><i class="bi bi-tag me-2"></i>Pricing</h3>
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Price (CFA) *</label>
                        <div class="input-group">
                            <span class="input-group-text">CFA</span>
                            <input type="number" class="form-control" name="price" 
                                   step="0.01" min="0" required 
                                   value="<?php echo number_format($product['price'], 2); ?>">
                        </div>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Compare Price (CFA)</label>
                        <div class="input-group">
                            <span class="input-group-text">CFA</span>
                            <input type="number" class="form-control" name="compare_price" 
                                   step="0.01" min="0" 
                                   value="<?php echo $product['compare_price'] ? number_format($product['compare_price'], 2) : ''; ?>"
                                   placeholder="Original price">
                        </div>
                        <small class="text-muted">Original price to show discount</small>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Cost Price (CFA)</label>
                        <div class="input-group">
                            <span class="input-group-text">CFA</span>
                            <input type="number" class="form-control" name="cost_price" 
                                   step="0.01" min="0" 
                                   value="<?php echo $product['cost_price'] ? number_format($product['cost_price'], 2) : ''; ?>"
                                   placeholder="Your cost">
                        </div>
                        <small class="text-muted">Your cost for this product</small>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Brand</label>
                        <input type="text" class="form-control" name="brand" 
                               value="<?php echo htmlspecialchars($product['brand'] ?? ''); ?>"
                               placeholder="Your brand name (optional)">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Weight (kg)</label>
                        <div class="input-group">
                            <input type="number" class="form-control" name="weight" 
                                   step="0.01" min="0" 
                                   value="<?php echo $product['weight'] ?? ''; ?>"
                                   placeholder="0.5">
                            <span class="input-group-text">kg</span>
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Dimensions (L×W×H)</label>
                        <input type="text" class="form-control" name="dimensions" 
                               value="<?php echo htmlspecialchars($product['dimensions'] ?? ''); ?>"
                               placeholder="e.g., 30×20×5 cm">
                    </div>
                    <div class="col-md-6 mb-3">
                        <div class="form-check mt-4">
                            <input class="form-check-input" type="checkbox" name="featured" value="1" id="featured" <?php echo $product['featured'] ? 'checked' : ''; ?>>
                            <label class="form-check-label fw-medium" for="featured">
                                <i class="bi bi-star-fill text-warning me-2"></i>Feature this product
                            </label>
                            <small class="text-muted d-block">Featured products appear on homepage</small>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Inventory -->
            <div class="form-section">
                <h3 class="section-title"><i class="bi bi-box-seam me-2"></i>Inventory</h3>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Stock Quantity</label>
                        <input type="number" class="form-control" name="stock_quantity" 
                               value="<?php echo $product['stock_quantity']; ?>" min="0">
                        <small class="text-muted">Set to 0 for out of stock</small>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Low Stock Threshold</label>
                        <input type="number" class="form-control" name="low_stock_threshold" 
                               value="<?php echo $product['low_stock_threshold']; ?>" min="1">
                        <small class="text-muted">Get notified when stock reaches this level</small>
                    </div>
                </div>
            </div>

            <!-- Customization -->
            <div class="form-section">
                <h3 class="section-title"><i class="bi bi-sliders me-2"></i>Customization Options</h3>
                <div class="form-check mb-3">
                    <input class="form-check-input" type="checkbox" name="is_customizable" value="1" id="is_customizable" <?php echo $product['is_customizable'] ? 'checked' : ''; ?>>
                    <label class="form-check-label fw-medium" for="is_customizable">
                        Allow customization for this product
                    </label>
                    <small class="text-muted d-block">Customers can request custom changes</small>
                </div>
                
                <div id="customization-options-container" style="<?php echo $product['is_customizable'] ? 'display: block;' : 'display: none;'; ?>">
                    <div id="customization-options-list">
                        <?php foreach ($customizationOptions as $index => $option): ?>
                        <div class="customization-option" id="option-<?php echo $index + 1; ?>">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h6 class="mb-0">Customization Option</h6>
                                <button type="button" class="btn btn-sm btn-outline-danger remove-option" 
                                        data-option="<?php echo $index + 1; ?>">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </div>
                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">Option Name *</label>
                                    <input type="text" class="form-control" name="custom_option_names[]" 
                                           value="<?php echo htmlspecialchars($option['name']); ?>" required>
                                </div>
                                <div class="col-md-3 mb-3">
                                    <label class="form-label">Type</label>
                                    <select class="form-select" name="custom_option_types[]">
                                        <option value="text" <?php echo $option['type'] == 'text' ? 'selected' : ''; ?>>Text Input</option>
                                        <option value="select" <?php echo $option['type'] == 'select' ? 'selected' : ''; ?>>Dropdown</option>
                                        <option value="radio" <?php echo $option['type'] == 'radio' ? 'selected' : ''; ?>>Radio Buttons</option>
                                        <option value="checkbox" <?php echo $option['type'] == 'checkbox' ? 'selected' : ''; ?>>Checkboxes</option>
                                        <option value="textarea" <?php echo $option['type'] == 'textarea' ? 'selected' : ''; ?>>Text Area</option>
                                    </select>
                                </div>
                                <div class="col-md-3 mb-3">
                                    <label class="form-label">Additional Price (CFA)</label>
                                    <input type="number" class="form-control" name="custom_option_prices[]" 
                                           step="0.01" min="0" value="<?php echo $option['price']; ?>">
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Option Values</label>
                                <input type="text" class="form-control" name="custom_option_values[]" 
                                       value="<?php echo htmlspecialchars(implode(', ', $option['values'])); ?>"
                                       placeholder="Enter comma-separated values for dropdown/radio (optional)">
                                <small class="text-muted">Only for dropdown and radio button types</small>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <button type="button" class="btn btn-add-option" id="add-customization-option">
                        <i class="bi bi-plus-lg me-2"></i>Add Customization Option
                    </button>
                </div>
            </div>

            <!-- Specifications -->
            <div class="form-section">
                <h3 class="section-title"><i class="bi bi-list-check me-2"></i>Specifications</h3>
                <div id="specifications-container">
                    <?php if (empty($specifications)): ?>
                    <div class="specification-row row" id="spec-1">
                        <div class="col-md-5 mb-2">
                            <input type="text" class="form-control" name="spec_keys[]" 
                                   placeholder="Specification name (e.g., Fabric)">
                        </div>
                        <div class="col-md-5 mb-2">
                            <input type="text" class="form-control" name="spec_values[]" 
                                   placeholder="Value (e.g., 100% Cotton)">
                        </div>
                        <div class="col-md-2 mb-2">
                            <button type="button" class="btn btn-outline-danger w-100 remove-specification" style="display: none;">
                                <i class="bi bi-trash"></i>
                            </button>
                        </div>
                    </div>
                    <?php else: ?>
                        <?php $specIndex = 1; ?>
                        <?php foreach ($specifications as $key => $value): ?>
                        <div class="specification-row row" id="spec-<?php echo $specIndex; ?>">
                            <div class="col-md-5 mb-2">
                                <input type="text" class="form-control" name="spec_keys[]" 
                                       value="<?php echo htmlspecialchars($key); ?>"
                                       placeholder="Specification name (e.g., Fabric)">
                            </div>
                            <div class="col-md-5 mb-2">
                                <input type="text" class="form-control" name="spec_values[]" 
                                       value="<?php echo htmlspecialchars($value); ?>"
                                       placeholder="Value (e.g., 100% Cotton)">
                            </div>
                            <div class="col-md-2 mb-2">
                                <button type="button" class="btn btn-outline-danger w-100 remove-specification" 
                                        data-spec="<?php echo $specIndex; ?>">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </div>
                        </div>
                        <?php $specIndex++; ?>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                <button type="button" class="btn btn-outline-primary mt-2" id="add-specification">
                    <i class="bi bi-plus-lg me-2"></i>Add Specification
                </button>
            </div>

            <!-- Images -->
            <div class="form-section">
                <h3 class="section-title"><i class="bi bi-images me-2"></i>Product Images</h3>
                <div class="image-upload-container">
                    <div class="image-upload-area" id="image-upload-area">
                        <div class="image-upload-icon">
                            <i class="bi bi-cloud-arrow-up"></i>
                        </div>
                        <div class="image-upload-text">Drag & drop new product images here</div>
                        <div class="image-upload-subtext mb-3">or</div>
                        <button type="button" class="btn btn-browse" id="browse-images-btn">
                            <i class="bi bi-folder2-open me-2"></i>Browse Files
                        </button>
                        <p class="text-muted small mt-3 mb-0">Supported: JPG, PNG, GIF, WebP | Max 5MB per image</p>
                        <p class="text-muted small">Upload up to 8 images total</p>
                        
                        <input type="file" name="images[]" id="image-input" multiple accept="image/*" 
                               style="display: none;">
                    </div>
                    
                    <!-- Upload Progress -->
                    <div class="upload-progress" id="upload-progress">
                        <div class="progress mb-2">
                            <div class="progress-bar progress-bar-striped progress-bar-animated" 
                                 role="progressbar" style="width: 0%"></div>
                        </div>
                        <div class="file-info" id="file-info"></div>
                    </div>
                    
                    <!-- Image Previews -->
                    <div id="image-preview-container" class="image-preview-container">
                        <!-- Existing images will be loaded here -->
                        <?php foreach ($existingImages as $index => $image): ?>
                        <div class="image-preview-wrapper" data-existing="true" data-filename="<?php echo htmlspecialchars($image['filename']); ?>">
                            <img src="<?php echo PRODUCT_IMAGES_URL . $image['filename']; ?>" 
                                 class="image-preview existing" alt="Existing image">
                            <span class="image-type-badge badge-existing">Existing</span>
                            <div class="existing-image-checkbox">
                                <input type="checkbox" name="existing_images[]" 
                                       value="<?php echo htmlspecialchars($image['filename']); ?>" 
                                       id="existing_<?php echo $index; ?>" checked>
                                <label for="existing_<?php echo $index; ?>">Keep</label>
                            </div>
                            <div class="image-counter"><?php echo $index + 1; ?></div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- Status & Save -->
            <div class="form-section">
                <h3 class="section-title"><i class="bi bi-send-check me-2"></i>Publish</h3>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Status</label>
                        <select class="form-select" name="status">
                            <option value="draft" <?php echo $product['status'] == 'draft' ? 'selected' : ''; ?>>Save as Draft</option>
                            <option value="active" <?php echo $product['status'] == 'active' ? 'selected' : ''; ?>>Publish Now</option>
                            <option value="inactive" <?php echo $product['status'] == 'inactive' ? 'selected' : ''; ?>>Save as Inactive</option>
                        </select>
                    </div>
                    <div class="col-md-6 mb-3 d-flex align-items-end">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="notify_customers" value="1" id="notify_customers">
                            <label class="form-check-label" for="notify_customers">
                                Notify subscribers about updates
                            </label>
                        </div>
                    </div>
                </div>
                
                <div class="d-flex justify-content-between mt-4">
                    <a href="products.php" class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-left me-2"></i>Back to Products
                    </a>
                    <div>
                        <button type="submit" name="save_draft" value="draft" class="btn btn-outline-primary me-2">
                            <i class="bi bi-save me-2"></i>Update Draft
                        </button>
                        <button type="submit" name="publish" value="active" class="btn btn-save">
                            <i class="bi bi-check-circle me-2"></i>Update Product
                        </button>
                    </div>
                </div>
            </div>
        </form>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-lite.min.js"></script>
    <script>
        // Initialize Summernote editor
        document.addEventListener('DOMContentLoaded', function() {
            $('#description').summernote({
                height: 200,
                toolbar: [
                    ['style', ['bold', 'italic', 'underline', 'clear']],
                    ['font', ['strikethrough', 'superscript', 'subscript']],
                    ['para', ['ul', 'ol', 'paragraph']],
                    ['insert', ['link', 'picture']],
                    ['view', ['fullscreen', 'codeview', 'help']]
                ]
            });
            
            // Customization options toggle
            const customizableCheckbox = document.getElementById('is_customizable');
            const optionsContainer = document.getElementById('customization-options-container');
            
            customizableCheckbox.addEventListener('change', function() {
                if (this.checked) {
                    optionsContainer.style.display = 'block';
                } else {
                    optionsContainer.style.display = 'none';
                }
            });
            
            // Add customization option
            let optionCount = <?php echo count($customizationOptions); ?>;
            document.getElementById('add-customization-option').addEventListener('click', function() {
                optionCount++;
                const optionHtml = `
                    <div class="customization-option" id="option-${optionCount}">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h6 class="mb-0">Customization Option</h6>
                            <button type="button" class="btn btn-sm btn-outline-danger remove-option" 
                                    data-option="${optionCount}">
                                <i class="bi bi-trash"></i>
                            </button>
                        </div>
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Option Name *</label>
                                <input type="text" class="form-control" name="custom_option_names[]" 
                                       placeholder="e.g., Color, Size, Material" required>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label class="form-label">Type</label>
                                <select class="form-select" name="custom_option_types[]">
                                    <option value="text">Text Input</option>
                                    <option value="select">Dropdown</option>
                                    <option value="radio">Radio Buttons</option>
                                    <option value="checkbox">Checkboxes</option>
                                    <option value="textarea">Text Area</option>
                                </select>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label class="form-label">Additional Price (CFA)</label>
                                <input type="number" class="form-control" name="custom_option_prices[]" 
                                       step="0.01" min="0" value="0">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Option Values</label>
                            <input type="text" class="form-control" name="custom_option_values[]" 
                                   placeholder="Enter comma-separated values for dropdown/radio (optional)">
                            <small class="text-muted">Only for dropdown and radio button types</small>
                        </div>
                    </div>
                `;
                document.getElementById('customization-options-list').insertAdjacentHTML('beforeend', optionHtml);
                
                // Add event listener for remove button
                document.querySelector(`#option-${optionCount} .remove-option`).addEventListener('click', function() {
                    document.getElementById(`option-${this.dataset.option}`).remove();
                });
            });
            
            // Remove existing customization options
            document.querySelectorAll('.remove-option').forEach(button => {
                button.addEventListener('click', function() {
                    document.getElementById(`option-${this.dataset.option}`).remove();
                });
            });
            
            // Add specification row
            let specCount = <?php echo max(1, count($specifications)); ?>;
            document.getElementById('add-specification').addEventListener('click', function() {
                specCount++;
                const specHtml = `
                    <div class="specification-row row" id="spec-${specCount}">
                        <div class="col-md-5 mb-2">
                            <input type="text" class="form-control" name="spec_keys[]" 
                                   placeholder="Specification name">
                        </div>
                        <div class="col-md-5 mb-2">
                            <input type="text" class="form-control" name="spec_values[]" 
                                   placeholder="Value">
                        </div>
                        <div class="col-md-2 mb-2">
                            <button type="button" class="btn btn-outline-danger w-100 remove-specification" 
                                    data-spec="${specCount}">
                                <i class="bi bi-trash"></i>
                            </button>
                        </div>
                    </div>
                `;
                document.getElementById('specifications-container').insertAdjacentHTML('beforeend', specHtml);
                
                // Show remove button on all rows except first
                document.querySelectorAll('.specification-row').forEach(row => {
                    row.querySelector('.remove-specification').style.display = 'block';
                });
                
                // Add event listener for remove button
                document.querySelector(`#spec-${specCount} .remove-specification`).addEventListener('click', function() {
                    document.getElementById(`spec-${this.dataset.spec}`).remove();
                });
            });
            
            // Remove existing specification rows
            document.querySelectorAll('.remove-specification').forEach(button => {
                button.addEventListener('click', function() {
                    document.getElementById(`spec-${this.dataset.spec}`).remove();
                });
            });
            
            // --- IMAGE UPLOAD LOGIC ---
            const imageUploadArea = document.getElementById('image-upload-area');
            const imageInput = document.getElementById('image-input');
            const browseBtn = document.getElementById('browse-images-btn');
            const previewContainer = document.getElementById('image-preview-container');
            const uploadProgress = document.getElementById('upload-progress');
            const progressBar = uploadProgress.querySelector('.progress-bar');
            const fileInfo = document.getElementById('file-info');
            
            let uploadedFiles = []; // Store new File objects
            
            // Method 1: Click browse button
            browseBtn.addEventListener('click', () => {
                imageInput.click();
            });
            
            // Method 2: Click anywhere in upload area
            imageUploadArea.addEventListener('click', (e) => {
                // Only trigger if not clicking on the browse button
                if (!e.target.closest('#browse-images-btn')) {
                    imageInput.click();
                }
            });
            
            // Handle file selection via file input
            imageInput.addEventListener('change', function(e) {
                handleFiles(this.files);
            });
            
            // Drag & Drop Handlers
            ['dragenter', 'dragover'].forEach(eventName => {
                imageUploadArea.addEventListener(eventName, highlight, false);
            });
            
            ['dragleave', 'drop'].forEach(eventName => {
                imageUploadArea.addEventListener(eventName, unhighlight, false);
            });
            
            function highlight(e) {
                e.preventDefault();
                e.stopPropagation();
                imageUploadArea.classList.add('drag-over');
            }
            
            function unhighlight(e) {
                e.preventDefault();
                e.stopPropagation();
                imageUploadArea.classList.remove('drag-over');
            }
            
            // Handle drop
            imageUploadArea.addEventListener('drop', function(e) {
                e.preventDefault();
                e.stopPropagation();
                
                const dt = e.dataTransfer;
                const files = dt.files;
                handleFiles(files);
            });
            
            function handleFiles(files) {
                const maxFiles = 8;
                const maxSize = 5 * 1024 * 1024; // 5MB
                
                // Count existing images with checked checkboxes
                const existingCheckedCount = document.querySelectorAll('input[name="existing_images[]"]:checked').length;
                const currentTotalImages = existingCheckedCount + uploadedFiles.length;
                
                // Convert FileList to Array
                const filesArray = Array.from(files);
                
                // Check if adding these files would exceed limit
                if (currentTotalImages + filesArray.length > maxFiles) {
                    alert(`You can only have up to ${maxFiles} images total. You currently have ${currentTotalImages} images.`);
                    return;
                }
                
                // Process each file
                let processedFiles = 0;
                const validFiles = [];
                
                // Show upload progress
                uploadProgress.classList.add('active');
                progressBar.style.width = '0%';
                fileInfo.textContent = `Processing images...`;
                
                filesArray.forEach((file, index) => {
                    // Check file size
                    if (file.size > maxSize) {
                        alert(`File "${file.name}" is too large. Maximum size is 5MB.`);
                        return;
                    }
                    
                    // Check file type
                    if (!file.type.match('image/(jpeg|jpg|png|gif|webp)')) {
                        alert(`File "${file.name}" is not a supported image type. Allowed: JPG, PNG, GIF, WebP`);
                        return;
                    }
                    
                    validFiles.push(file);
                    
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        processedFiles++;
                        
                        // Update progress
                        progressBar.style.width = `${(processedFiles / filesArray.length) * 100}%`;
                        fileInfo.textContent = `Processing ${processedFiles} of ${filesArray.length} files...`;
                        
                        // Add file to uploadedFiles array
                        uploadedFiles.push(file);
                        
                        // Count all images
                        const existingImages = document.querySelectorAll('.image-preview-wrapper[data-existing="true"]');
                        const totalCount = existingImages.length + uploadedFiles.length;
                        
                        // Create preview for new image
                        const preview = document.createElement('div');
                        preview.className = 'image-preview-wrapper';
                        preview.innerHTML = `
                            <img src="${e.target.result}" class="image-preview new" alt="New image">
                            <span class="image-type-badge badge-new">New</span>
                            <button type="button" class="remove-image" data-index="${uploadedFiles.length - 1}">
                                <i class="bi bi-x"></i>
                            </button>
                            <div class="image-counter">${totalCount}</div>
                        `;
                        previewContainer.appendChild(preview);
                        
                        // Add remove functionality for new image
                        preview.querySelector('.remove-image').addEventListener('click', function() {
                            const index = parseInt(this.dataset.index);
                            // Remove from uploadedFiles array
                            uploadedFiles.splice(index, 1);
                            // Remove preview
                            preview.remove();
                            // Update all previews and counters
                            updateImageCounters();
                            // Update file input
                            updateFileInput();
                        });
                        
                        // When all files are processed
                        if (processedFiles === filesArray.length) {
                            setTimeout(() => {
                                progressBar.style.width = '100%';
                                fileInfo.textContent = `${processedFiles} new file(s) uploaded successfully!`;
                                setTimeout(() => {
                                    uploadProgress.classList.remove('active');
                                }, 1000);
                                
                                // Update the file input
                                updateFileInput();
                            }, 500);
                        }
                    };
                    reader.readAsDataURL(file);
                });
            }
            
            // Update image counters when checkboxes change
            document.querySelectorAll('input[name="existing_images[]"]').forEach(checkbox => {
                checkbox.addEventListener('change', updateImageCounters);
            });
            
            function updateImageCounters() {
                const existingWrappers = document.querySelectorAll('.image-preview-wrapper[data-existing="true"]');
                const newWrappers = document.querySelectorAll('.image-preview-wrapper:not([data-existing="true"])');
                let counter = 1;
                
                // Update existing image counters
                existingWrappers.forEach(wrapper => {
                    if (wrapper.querySelector('input[type="checkbox"]:checked')) {
                        wrapper.querySelector('.image-counter').textContent = counter;
                        counter++;
                    } else {
                        wrapper.querySelector('.image-counter').textContent = '';
                    }
                });
                
                // Update new image counters
                newWrappers.forEach(wrapper => {
                    wrapper.querySelector('.image-counter').textContent = counter;
                    counter++;
                });
            }
            
            function updateFileInput() {
                // Create a new DataTransfer object
                const dataTransfer = new DataTransfer();
                
                // Add all new files to DataTransfer
                uploadedFiles.forEach(file => {
                    dataTransfer.items.add(file);
                });
                
                // Update the file input
                imageInput.files = dataTransfer.files;
                
                // Debug log
                console.log('File input updated:', imageInput.files.length, 'new files');
            }
            
            // Initialize image counters
            updateImageCounters();
            
            // Form validation
            document.getElementById('productForm').addEventListener('submit', function(e) {
                const priceInput = document.querySelector('input[name="price"]');
                if (parseFloat(priceInput.value) <= 0) {
                    e.preventDefault();
                    alert('Price must be greater than 0');
                    priceInput.focus();
                    return false;
                }
                
                // Check if any required fields are empty
                const requiredFields = this.querySelectorAll('[required]');
                for (let field of requiredFields) {
                    if (!field.value.trim()) {
                        e.preventDefault();
                        alert('Please fill in all required fields');
                        field.focus();
                        return false;
                    }
                }
                
                // Check if at least one image is selected (existing or new)
                const existingChecked = document.querySelectorAll('input[name="existing_images[]"]:checked').length;
                if (existingChecked === 0 && uploadedFiles.length === 0) {
                    e.preventDefault();
                    if (confirm('No images selected. Products without images may get less attention. Continue anyway?')) {
                        return true;
                    }
                    return false;
                }
                
                return true;
            });
        });
    </script>
</body>
</html>


