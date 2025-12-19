<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../classes/Product.php';

class ProductFunctions {
    private $product;
    
    public function __construct() {
        $this->product = new Product();
    }
    
    public function addProduct($tailorId, $data, $files) {
        // Validate input
        $errors = $this->validateProduct($data, $files);
        if (!empty($errors)) {
            return ['success' => false, 'errors' => $errors];
        }
        
        // Handle image uploads
        $imageFilenames = $this->uploadProductImages($files);
        if (!$imageFilenames) {
            return ['success' => false, 'errors' => ['images' => 'Failed to upload images']];
        }
        
        // Prepare product data
        $productData = [
            'tailor_id' => $tailorId,
            'title' => $data['title'],
            'description' => $data['description'],
            'price' => $data['price'],
            'category' => $data['category'],
            'material' => $data['material'] ?? null,
            'size' => $data['size'] ?? null,
            'color' => $data['color'] ?? null,
            'is_customizable' => isset($data['is_customizable']) ? 1 : 0,
            'images' => json_encode($imageFilenames),
            'stock' => $data['stock'] ?? 1
        ];
        
        // Add product
        $productId = $this->product->add($productData);
        
        if ($productId) {
            return ['success' => true, 'product_id' => $productId];
        }
        
        return ['success' => false, 'errors' => ['general' => 'Failed to add product']];
    }
    
    public function updateProduct($productId, $tailorId, $data, $files = null) {
        // Verify ownership
        if (!$this->product->isOwner($productId, $tailorId)) {
            return ['success' => false, 'errors' => ['general' => 'You do not own this product']];
        }
        
        // Validate input
        $errors = $this->validateProduct($data, $files, true);
        if (!empty($errors)) {
            return ['success' => false, 'errors' => $errors];
        }
        
        // Prepare update data
        $updateData = [
            'title' => $data['title'],
            'description' => $data['description'],
            'price' => $data['price'],
            'category' => $data['category'],
            'material' => $data['material'] ?? null,
            'size' => $data['size'] ?? null,
            'color' => $data['color'] ?? null,
            'is_customizable' => isset($data['is_customizable']) ? 1 : 0,
            'stock' => $data['stock'] ?? 1,
            'status' => $data['status'] ?? 'active'
        ];
        
        // Handle new images if provided
        if ($files && !empty($files['images']['name'][0])) {
            $imageFilenames = $this->uploadProductImages($files);
            if ($imageFilenames) {
                $updateData['images'] = json_encode($imageFilenames);
            }
        }
        
        // Update product
        if ($this->product->update($productId, $updateData)) {
            return ['success' => true];
        }
        
        return ['success' => false, 'errors' => ['general' => 'Failed to update product']];
    }
    
    public function deleteProduct($productId, $tailorId) {
        // Verify ownership
        if (!$this->product->isOwner($productId, $tailorId)) {
            return ['success' => false, 'message' => 'You do not own this product'];
        }
        
        // Get product images
        $product = $this->product->getById($productId);
        if ($product && !empty($product['images'])) {
            $images = json_decode($product['images'], true);
            foreach ($images as $image) {
                $filepath = UPLOAD_PATH . PRODUCT_IMAGES . $image;
                if (file_exists($filepath)) {
                    unlink($filepath);
                }
            }
        }
        
        // Delete product
        if ($this->product->delete($productId)) {
            return ['success' => true];
        }
        
        return ['success' => false, 'message' => 'Failed to delete product'];
    }
    
    public function getProducts($filters = [], $page = 1, $perPage = 12) {
        return $this->product->getAll($filters, $page, $perPage);
    }
    
    public function getProductDetails($productId) {
        $product = $this->product->getById($productId);
        if (!$product) {
            return null;
        }
        
        // Get tailor info
        require_once __DIR__ . '/../classes/User.php';
        $user = new User();
        $tailor = $user->getUserById($product['tailor_id']);
        
        // Get reviews
        $reviews = $this->product->getReviews($productId);
        
        // Get related products
        $related = $this->product->getRelated($productId, $product['category'], 4);
        
        return [
            'product' => $product,
            'tailor' => $tailor,
            'reviews' => $reviews,
            'related' => $related
        ];
    }
    
    public function addToWishlist($userId, $productId) {
        return $this->product->addToWishlist($userId, $productId);
    }
    
    public function removeFromWishlist($userId, $productId) {
        return $this->product->removeFromWishlist($userId, $productId);
    }
    
    public function getWishlist($userId) {
        return $this->product->getWishlist($userId);
    }
    
    public function addReview($userId, $productId, $orderId, $rating, $comment) {
        // Check if user can review (must have purchased the product)
        if (!$this->product->canReview($userId, $productId, $orderId)) {
            return ['success' => false, 'message' => 'You can only review products you have purchased'];
        }
        
        // Check if already reviewed
                // Check if already reviewed
        if ($this->product->hasReviewed($userId, $productId, $orderId)) {
            return ['success' => false, 'message' => 'You have already reviewed this product'];
        }
        
        // Validate rating
        if ($rating < 1 || $rating > 5) {
            return ['success' => false, 'message' => 'Rating must be between 1 and 5'];
        }
        
        // Add review
        if ($this->product->addReview($userId, $productId, $orderId, $rating, $comment)) {
            // Update product rating
            $this->product->updateProductRating($productId);
            return ['success' => true];
        }
        
        return ['success' => false, 'message' => 'Failed to add review'];
    }
    
    public function searchProducts($query, $filters = [], $page = 1, $perPage = 12) {
        return $this->product->search($query, $filters, $page, $perPage);
    }
    
    private function validateProduct($data, $files = null, $isUpdate = false) {
        $errors = [];
        
        // Title
        if (empty($data['title'])) {
            $errors['title'] = 'Product title is required';
        } elseif (strlen($data['title']) > 200) {
            $errors['title'] = 'Title must be less than 200 characters';
        }
        
        // Description
        if (empty($data['description'])) {
            $errors['description'] = 'Product description is required';
        }
        
        // Price
        if (empty($data['price'])) {
            $errors['price'] = 'Price is required';
        } elseif (!is_numeric($data['price']) || $data['price'] <= 0) {
            $errors['price'] = 'Price must be a positive number';
        }
        
        // Category
        if (empty($data['category'])) {
            $errors['category'] = 'Category is required';
        }
        
        // Stock
        if (isset($data['stock']) && (!is_numeric($data['stock']) || $data['stock'] < 0)) {
            $errors['stock'] = 'Stock must be a positive number';
        }
        
        // Images (required only for new products)
        if (!$isUpdate && (!$files || empty($files['images']['name'][0]))) {
            $errors['images'] = 'At least one product image is required';
        }
        
        // Validate images if provided
        if ($files && !empty($files['images']['name'][0])) {
            $imageErrors = $this->validateImages($files);
            if (!empty($imageErrors)) {
                $errors['images'] = $imageErrors;
            }
        }
        
        return $errors;
    }
    
    private function validateImages($files) {
        $errors = [];
        $maxFiles = 5;
        $maxSize = 10 * 1024 * 1024; // 10MB
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        
        // Check number of files
        $fileCount = count($files['images']['name']);
        if ($fileCount > $maxFiles) {
            return "Maximum $maxFiles images allowed";
        }
        
        for ($i = 0; $i < $fileCount; $i++) {
            // Check file size
            if ($files['images']['size'][$i] > $maxSize) {
                $errors[] = "Image " . ($i + 1) . " exceeds 10MB limit";
            }
            
            // Check file type
            $fileType = mime_content_type($files['images']['tmp_name'][$i]);
            if (!in_array($fileType, $allowedTypes)) {
                $errors[] = "Image " . ($i + 1) . " must be JPG, PNG, GIF, or WebP";
            }
            
            // Check for upload errors
            if ($files['images']['error'][$i] !== UPLOAD_ERR_OK) {
                $errors[] = "Error uploading image " . ($i + 1);
            }
        }
        
        return empty($errors) ? null : implode(', ', $errors);
    }
    
    private function uploadProductImages($files) {
        $uploadedFilenames = [];
        
        // Create upload directory if it doesn't exist
        $uploadDir = UPLOAD_PATH . PRODUCT_IMAGES;
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        
        for ($i = 0; $i < count($files['images']['name']); $i++) {
            if ($files['images']['error'][$i] === UPLOAD_ERR_OK) {
                // Generate unique filename
                $extension = pathinfo($files['images']['name'][$i], PATHINFO_EXTENSION);
                $filename = uniqid() . '_' . time() . '_' . $i . '.' . $extension;
                $targetPath = $uploadDir . $filename;
                
                // Move uploaded file
                if (move_uploaded_file($files['images']['tmp_name'][$i], $targetPath)) {
                    $targetDir = '/home/mukaila.shittu/public_html/Final_project_web/assets/images/products/';
                    if (!is_dir($targetDir)) {
                        mkdir($targetDir, 0777, true); // PHP creates and owns the folder
                    }
                    // Resize image for optimization
                    $this->resizeProductImage($targetPath);
                    $uploadedFilenames[] = $filename;
                }

                if (move_uploaded_file($_FILES['image']['tmp_name'], $targetPath)) {
                    // Success logic
                } else {
                    // Check if the directory is actually writable
                    if (!is_writable(dirname($targetPath))) {
                        error_log("Directory not writable: " . dirname($targetPath));
                    }
                }
            }
        }
        
        return !empty($uploadedFilenames) ? $uploadedFilenames : false;
    }
    
    private function resizeProductImage($filepath) {
        list($width, $height, $type) = getimagesize($filepath);
        
        // Target dimensions
        $maxWidth = 1200;
        $maxHeight = 1200;
        
        if ($width <= $maxWidth && $height <= $maxHeight) {
            return; // No resizing needed
        }
        
        $ratio = min($maxWidth/$width, $maxHeight/$height);
        $newWidth = round($width * $ratio);
        $newHeight = round($height * $ratio);
        
        // Inside your resizeProductImage function
        if (!function_exists('imagecreatetruecolor')) {
            // If GD is missing, just move the file without resizing as a fallback
            return false; 
        }

        // Create new image
        $newImage = imagecreatetruecolor($newWidth, $newHeight);
        
        // Load original image
        switch ($type) {
            case IMAGETYPE_JPEG:
                $originalImage = imagecreatefromjpeg($filepath);
                break;
            case IMAGETYPE_PNG:
                $originalImage = imagecreatefrompng($filepath);
                // Preserve transparency
                imagecolortransparent($newImage, imagecolorallocatealpha($newImage, 0, 0, 0, 127));
                imagealphablending($newImage, false);
                imagesavealpha($newImage, true);
                break;
            case IMAGETYPE_GIF:
                $originalImage = imagecreatefromgif($filepath);
                break;
            case IMAGETYPE_WEBP:
                $originalImage = imagecreatefromwebp($filepath);
                break;
            default:
                return; // Unsupported image type
        }
        
        // Resize
        imagecopyresampled($newImage, $originalImage, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
        
        // Save resized image
        switch ($type) {
            case IMAGETYPE_JPEG:
                imagejpeg($newImage, $filepath, 85);
                break;
            case IMAGETYPE_PNG:
                imagepng($newImage, $filepath, 8);
                break;
            case IMAGETYPE_GIF:
                imagegif($newImage, $filepath);
                break;
            case IMAGETYPE_WEBP:
                imagewebp($newImage, $filepath, 85);
                break;
        }
        
        // Free memory
        imagedestroy($originalImage);
        imagedestroy($newImage);
    }
}
?>