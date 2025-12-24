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
       .image-upload-area.highlight {
            border-color: #667eea !important;
            background-color: rgba(102, 126, 234, 0.1) !important;
        }
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
        }
        .image-preview-wrapper:hover .remove-image {
            opacity: 1;
        }
        .remove-image:hover {
            background: #c82333;
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
    </style>
</head>


<body>
    <?php include '../../includes/components/navbar.php'; ?>
    
    <div class="container product-form-container py-5">
        <div class="row">
            <div class="col-12">
                <h1 class="h3 fw-bold mb-4">Add New Product</h1>
                <p class="text-muted">Fill in the details below to add your new product to the marketplace</p>
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
                <h3 class="section-title"><i class="bi bi-info-circle me-2"></i>Basic Information</h3>
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
                <h3 class="section-title"><i class="bi bi-tag me-2"></i>Pricing</h3>
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Price (CFA) *</label>
                        <div class="input-group">
                            <span class="input-group-text">CFA</span>
                            <input type="number" class="form-control" name="price" 
                                   step="0.01" min="0" required placeholder="0.00">
                        </div>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Compare Price (CFA)</label>
                        <div class="input-group">
                            <span class="input-group-text">CFA</span>
                            <input type="number" class="form-control" name="compare_price" 
                                   step="0.01" min="0" placeholder="Original price">
                        </div>
                        <small class="text-muted">Original price to show discount</small>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Cost Price (CFA)</label>
                        <div class="input-group">
                            <span class="input-group-text">CFA</span>
                            <input type="number" class="form-control" name="cost_price" 
                                   step="0.01" min="0" placeholder="Your cost">
                        </div>
                        <small class="text-muted">Your cost for this product</small>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Brand</label>
                        <input type="text" class="form-control" name="brand" 
                               placeholder="Your brand name (optional)">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Weight (kg)</label>
                        <div class="input-group">
                            <input type="number" class="form-control" name="weight" 
                                   step="0.01" min="0" placeholder="0.5">
                            <span class="input-group-text">kg</span>
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Dimensions (L×W×H)</label>
                        <input type="text" class="form-control" name="dimensions" 
                               placeholder="e.g., 30×20×5 cm">
                    </div>
                    <div class="col-md-6 mb-3">
                        <div class="form-check mt-4">
                            <input class="form-check-input" type="checkbox" name="featured" value="1" id="featured">
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
                               value="1" min="0">
                        <small class="text-muted">Set to 0 for out of stock</small>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Low Stock Threshold</label>
                        <input type="number" class="form-control" name="low_stock_threshold" 
                               value="5" min="1">
                        <small class="text-muted">Get notified when stock reaches this level</small>
                    </div>
                </div>
            </div>

            <!-- Customization -->
            <div class="form-section">
                <h3 class="section-title"><i class="bi bi-sliders me-2"></i>Customization Options</h3>
                <div class="form-check mb-3">
                    <input class="form-check-input" type="checkbox" name="is_customizable" value="1" id="is_customizable">
                    <label class="form-check-label fw-medium" for="is_customizable">
                        Allow customization for this product
                    </label>
                    <small class="text-muted d-block">Customers can request custom changes</small>
                </div>
                
                <div id="customization-options-container" style="display: none;">
                    <div id="customization-options-list">
                        <!-- Customization options will be added here dynamically -->
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
                    <div class="specification-row row">
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
                        <div class="image-upload-text">Drag & drop your product images here</div>
                        <div class="image-upload-subtext mb-3">or</div>
                        <button type="button" class="btn btn-browse" id="browse-images-btn">
                            <i class="bi bi-folder2-open me-2"></i>Browse Files
                        </button>
                        <p class="text-muted small mt-3 mb-0">Supported: JPG, PNG, GIF, WebP | Max 5MB per image</p>
                        <p class="text-muted small">Upload up to 8 images</p>
                        
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
                        <!-- Image previews will appear here -->
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
                            <option value="draft">Save as Draft</option>
                            <option value="active">Publish Now</option>
                            <option value="inactive">Save as Inactive</option>
                        </select>
                    </div>
                    <div class="col-md-6 mb-3 d-flex align-items-end">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="notify_customers" value="1" id="notify_customers">
                            <label class="form-check-label" for="notify_customers">
                                Notify subscribers about this product
                            </label>
                        </div>
                    </div>
                </div>
                
                <div class="d-flex justify-content-between mt-4">
                    <a href="products.php" class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-left me-2"></i>Cancel
                    </a>
                    <div>
                        <button type="submit" name="save_draft" value="draft" class="btn btn-outline-primary me-2">
                            <i class="bi bi-save me-2"></i>Save Draft
                        </button>
                        <button type="submit" name="publish" value="active" class="btn btn-save">
                            <i class="bi bi-check-circle me-2"></i>Publish Product
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
                ],
                placeholder: 'Describe your product in detail...'
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
            let optionCount = 0;
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
            
            // Add specification row
            let specCount = 1;
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
            
            // ... (keep your existing customization and specification code above this) ...

            // --- IMAGE UPLOAD LOGIC ---
            const uploadArea = document.getElementById('image-upload-area');
            const imageInput = document.getElementById('image-input');
            const browseBtn = document.getElementById('browse-images-btn');
            const previewContainer = document.getElementById('image-preview-container');

            // 1. Click to browse
            browseBtn.addEventListener('click', () => imageInput.click());

            // 2. Handle file selection via browse button
            imageInput.addEventListener('change', function() {
                handleFiles(this.files);
            });

            // 3. Drag & Drop Handlers
            ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
                uploadArea.addEventListener(eventName, (e) => {
                    e.preventDefault();
                    e.stopPropagation();
                }, false);
            });

            ['dragenter', 'dragover'].forEach(eventName => {
                uploadArea.addEventListener(eventName, () => uploadArea.classList.add('highlight'), false);
            });

            ['dragleave', 'drop'].forEach(eventName => {
                uploadArea.addEventListener(eventName, () => uploadArea.classList.remove('highlight'), false);
            });

            uploadArea.addEventListener('drop', function(e) {
                const dt = e.dataTransfer;
                const files = dt.files;
                imageInput.files = files; // Sync files to the hidden input
                handleFiles(files);
            });

            function handleFiles(files) {
                const filesArray = Array.from(files);
                if (filesArray.length > 8) {
                    alert("Maximum 8 images allowed.");
                    return;
                }
                
                // Clear existing previews if you want to replace them, 
                // or leave empty to append
                previewContainer.innerHTML = ''; 

                filesArray.forEach(file => {
                    if (!file.type.startsWith('image/')) return;

                    const reader = new FileReader();
                    reader.onload = function(e) {
                        const wrapper = document.createElement('div');
                        wrapper.className = 'image-preview-wrapper';
                        wrapper.innerHTML = `
                            <img src="${e.target.result}" class="image-preview">
                            <button type="button" class="remove-image">
                                <i class="bi bi-x"></i>
                            </button>
                        `;
                        
                        wrapper.querySelector('.remove-image').onclick = function() {
                            wrapper.remove();
                            // Note: Removing from UI doesn't remove from FileList 
                            // (which is read-only), but for a basic form, this works.
                        };
                        
                        previewContainer.appendChild(wrapper);
                    };
                    reader.readAsDataURL(file);
                });
            }

            // --- SPECIFICATION LOGIC (Add this if not already there) ---
            document.getElementById('add-specification').addEventListener('click', function() {
                const container = document.getElementById('specifications-container');
                const row = container.querySelector('.specification-row').cloneNode(true);
                row.querySelectorAll('input').forEach(input => input.value = '');
                row.querySelector('.remove-specification').style.display = 'block';
                container.appendChild(row);
                
                row.querySelector('.remove-specification').onclick = function() {
                    row.remove();
                };
            });
        });
    </script>
</body>
</html>