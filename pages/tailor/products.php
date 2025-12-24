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

// Handle actions
if (isset($_GET['action'])) {
    $productId = intval($_GET['id'] ?? 0);
    
    if ($_GET['action'] == 'delete' && $productId) {
        $result = $productObj->deleteProduct($productId);
        if ($result['success']) {
            header('Location: products.php?deleted=1');
        } else {
            header('Location: products.php?error=' . urlencode($result['error']));
        }
        exit();
    } elseif ($_GET['action'] == 'toggle_status' && $productId) {
        $db->query("UPDATE products SET status = IF(status = 'active', 'inactive', 'active') WHERE id = :id AND tailor_id = :tailor_id");
        $db->bind(':id', $productId);
        $db->bind(':tailor_id', $tailorId);
        $db->execute();
        
        header('Location: products.php?status_updated=1');
        exit();
    } elseif ($_GET['action'] == 'toggle_featured' && $productId) {
        $db->query("UPDATE products SET featured = NOT featured WHERE id = :id AND tailor_id = :tailor_id");
        $db->bind(':id', $productId);
        $db->bind(':tailor_id', $tailorId);
        $db->execute();
        
        header('Location: products.php?featured_updated=1');
        exit();
    }
}

// Get filter parameters
$status = $_GET['status'] ?? '';
$category = $_GET['category'] ?? '';
$search = $_GET['search'] ?? '';
$page = max(1, intval($_GET['page'] ?? 1));
$perPage = 12;

// Get products with filters
$filters = ['tailor_id' => $tailorId];
if ($status) $filters['status'] = $status;
if ($category) $filters['category'] = $category;
if ($search) $filters['search'] = $search;

$allProducts = $productObj->getProductsByTailor($tailorId, null, $status ?: null);
$totalProducts = count($allProducts);
$totalPages = ceil($totalProducts / $perPage);
$offset = ($page - 1) * $perPage;

// Get paginated products
$db->query("
    SELECT p.*, 
           (SELECT COUNT(*) FROM order_items oi WHERE oi.product_id = p.id) as total_orders,
           (SELECT SUM(oi.quantity) FROM order_items oi WHERE oi.product_id = p.id) as total_sold
    FROM products p
    WHERE p.tailor_id = :tailor_id
    " . ($status ? " AND p.status = :status" : "") . "
    " . ($category ? " AND (p.category = :category OR p.subcategory = :category)" : "") . "
    " . ($search ? " AND (p.title LIKE :search OR p.description LIKE :search)" : "") . "
    ORDER BY p.created_at DESC
    LIMIT :limit OFFSET :offset
");

$db->bind(':tailor_id', $tailorId);
$db->bind(':limit', $perPage, PDO::PARAM_INT);
$db->bind(':offset', $offset, PDO::PARAM_INT);

if ($status) $db->bind(':status', $status);
if ($category) $db->bind(':category', $category);
if ($search) $db->bind(':search', "%$search%");

$products = $db->resultSet();

// Get product statistics
$stats = $productObj->getProductStatistics($tailorId);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Products - <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">
    <style>
        .products-container {
            min-height: calc(100vh - 200px);
        }
        .stats-card {
            border-radius: 10px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            transition: transform 0.3s ease;
        }
        .stats-card:hover {
            transform: translateY(-5px);
        }
        .stats-icon {
            width: 50px;
            height: 50px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
        }
        .product-card {
            border-radius: 10px;
            overflow: hidden;
            transition: all 0.3s ease;
            height: 100%;
        }
        .product-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
        }
        .product-image {
            height: 180px;
            object-fit: cover;
            width: 100%;
        }
        .status-badge {
            position: absolute;
            top: 10px;
            left: 10px;
            z-index: 1;
        }
        .featured-badge {
            position: absolute;
            top: 10px;
            right: 10px;
            z-index: 1;
        }
        .stock-warning {
            color: #dc3545;
            font-weight: 600;
        }
        .empty-state {
            text-align: center;
            padding: 4rem 1rem;
        }
        .empty-state-icon {
            font-size: 4rem;
            color: #dee2e6;
            margin-bottom: 1rem;
        }
        .dropdown-action {
            padding: 0.25rem 0.5rem;
            border-radius: 5px;
            transition: background-color 0.2s;
        }
        .dropdown-action:hover {
            background-color: #f8f9fa;
        }
    </style>
</head>
<body>
    <?php include '../../includes/components/navbar.php'; ?>
    
    <div class="container products-container py-5">
        <!-- Header -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center">
                    <h1 class="h3 fw-bold">My Products</h1>
                    <div class="d-flex gap-2">
                        <a href="add-product.php" class="btn btn-primary">
                            <i class="bi bi-plus-circle me-2"></i> Add New Product
                        </a>
                        <a href="products-export.php" class="btn btn-outline-secondary">
                            <i class="bi bi-download me-2"></i> Export
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Messages -->
        <?php if (isset($_GET['deleted'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle me-2"></i>Product deleted successfully
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>
        
        <?php if (isset($_GET['status_updated'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle me-2"></i>Product status updated
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>
        
        <?php if (isset($_GET['featured_updated'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle me-2"></i>Featured status updated
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>
        
        <?php if (isset($_GET['error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="bi bi-exclamation-triangle me-2"></i><?php echo htmlspecialchars($_GET['error']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <!-- Stats Cards -->
        <div class="row mb-4">
            <div class="col-xl-3 col-md-6 mb-3">
                <div class="stats-card bg-white shadow-sm border">
                    <div class="d-flex align-items-center">
                        <div class="stats-icon me-3 bg-primary bg-opacity-10 text-primary">
                            <i class="bi bi-box"></i>
                        </div>
                        <div>
                            <h3 class="fw-bold mb-0"><?php echo $stats['total_products']; ?></h3>
                            <p class="text-muted mb-0">Total Products</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6 mb-3">
                <div class="stats-card bg-white shadow-sm border">
                    <div class="d-flex align-items-center">
                        <div class="stats-icon me-3 bg-success bg-opacity-10 text-success">
                            <i class="bi bi-check-circle"></i>
                        </div>
                        <div>
                            <h3 class="fw-bold mb-0"><?php echo $stats['active_products']; ?></h3>
                            <p class="text-muted mb-0">Active Products</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6 mb-3">
                <div class="stats-card bg-white shadow-sm border">
                    <div class="d-flex align-items-center">
                        <div class="stats-icon me-3 bg-warning bg-opacity-10 text-warning">
                            <i class="bi bi-star"></i>
                        </div>
                        <div>
                            <h3 class="fw-bold mb-0"><?php echo $stats['featured_products']; ?></h3>
                            <p class="text-muted mb-0">Featured</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6 mb-3">
                <div class="stats-card bg-white shadow-sm border">
                    <div class="d-flex align-items-center">
                        <div class="stats-icon me-3 bg-danger bg-opacity-10 text-danger">
                            <i class="bi bi-exclamation-triangle"></i>
                        </div>
                        <div>
                            <h3 class="fw-bold mb-0"><?php echo $stats['low_stock']; ?></h3>
                            <p class="text-muted mb-0">Low Stock</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <form method="GET" action="" class="row g-3">
                            <div class="col-md-3">
                                <label class="form-label">Status</label>
                                <select class="form-select" name="status">
                                    <option value="">All Status</option>
                                    <option value="draft" <?php echo $status == 'draft' ? 'selected' : ''; ?>>Draft</option>
                                    <option value="active" <?php echo $status == 'active' ? 'selected' : ''; ?>>Active</option>
                                    <option value="inactive" <?php echo $status == 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                                    <option value="out_of_stock" <?php echo $status == 'out_of_stock' ? 'selected' : ''; ?>>Out of Stock</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Category</label>
                                <select class="form-select" name="category">
                                    <option value="">All Categories</option>
                                    <option value="traditional-wear" <?php echo $category == 'traditional-wear' ? 'selected' : ''; ?>>Traditional Wear</option>
                                    <option value="modern-fashion" <?php echo $category == 'modern-fashion' ? 'selected' : ''; ?>>Modern Fashion</option>
                                    <option value="formal" <?php echo $category == 'formal' ? 'selected' : ''; ?>>Formal Wear</option>
                                    <option value="custom" <?php echo $category == 'custom' ? 'selected' : ''; ?>>Custom Designs</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Search</label>
                                <input type="text" class="form-control" name="search" 
                                       placeholder="Search products..." 
                                       value="<?php echo htmlspecialchars($search); ?>">
                            </div>
                            <div class="col-md-2 d-flex align-items-end">
                                <button type="submit" class="btn btn-primary w-100">
                                    <i class="bi bi-filter me-2"></i> Filter
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Products Grid -->
        <?php if (!empty($products)): ?>
            <div class="row">
                <?php foreach ($products as $product): 
                    $images = json_decode($product['images'] ?? '[]', true);
                    $firstImage = !empty($images) && is_array($images) ? $images[0] : ASSETS_URL . 'images/products/default.jpg';
                ?>
                <div class="col-xl-3 col-lg-4 col-md-6 mb-4">
                    <div class="card product-card border-0 shadow-sm">
                        <div class="position-relative">
                            <img src="<?php echo htmlspecialchars($firstImage); ?>" 
                                 class="product-image"
                                 alt="<?php echo htmlspecialchars($product['title']); ?>"
                                 onerror="this.src='<?php echo ASSETS_URL; ?>images/products/default.jpg'">
                            
                            <!-- Status Badge -->
                            <?php $statusClass = [
                                'draft' => 'secondary',
                                'active' => 'success',
                                'inactive' => 'warning',
                                'out_of_stock' => 'danger'
                            ][$product['status']] ?? 'secondary'; ?>
                            <span class="status-badge badge bg-<?php echo $statusClass; ?>">
                                <?php echo ucfirst(str_replace('_', ' ', $product['status'])); ?>
                            </span>
                            
                            <!-- Featured Badge -->
                            <?php if ($product['featured']): ?>
                            <span class="featured-badge badge bg-warning">
                                <i class="bi bi-star-fill me-1"></i> Featured
                            </span>
                            <?php endif; ?>
                            
                            <!-- Action Dropdown -->
                            <div class="position-absolute top-0 end-0 mt-2 me-2">
                                <div class="dropdown">
                                    <button class="btn btn-light btn-sm rounded-circle" type="button" 
                                            data-bs-toggle="dropdown">
                                        <i class="bi bi-three-dots-vertical"></i>
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-end">
                                        <li>
                                            <a class="dropdown-item dropdown-action" 
                                               href="edit-product.php?id=<?php echo $product['id']; ?>">
                                                <i class="bi bi-pencil me-2"></i> Edit
                                            </a>
                                        </li>
                                        <li>
                                            <a class="dropdown-item dropdown-action" 
                                               href="products.php?action=toggle_status&id=<?php echo $product['id']; ?>">
                                                <i class="bi bi-toggle-on me-2"></i> 
                                                <?php echo $product['status'] == 'active' ? 'Deactivate' : 'Activate'; ?>
                                            </a>
                                        </li>
                                        <li>
                                            <a class="dropdown-item dropdown-action" 
                                               href="products.php?action=toggle_featured&id=<?php echo $product['id']; ?>">
                                                <i class="bi bi-star me-2"></i> 
                                                <?php echo $product['featured'] ? 'Unfeature' : 'Feature'; ?>
                                            </a>
                                        </li>
                                        <li><hr class="dropdown-divider"></li>
                                        <li>
                                            <a class="dropdown-item dropdown-action text-danger" 
                                               href="products.php?action=delete&id=<?php echo $product['id']; ?>"
                                               onclick="return confirm('Are you sure you want to delete this product?')">
                                                <i class="bi bi-trash me-2"></i> Delete
                                            </a>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                        
                        <div class="card-body">
                            <h6 class="fw-bold mb-2">
                                <a href="../products/view.php?id=<?php echo $product['id']; ?>" 
                                   class="text-decoration-none text-dark">
                                    <?php echo htmlspecialchars($product['title']); ?>
                                </a>
                            </h6>
                            
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span class="h5 text-primary fw-bold"><?php echo format_price($product['price']); ?></span>
                                <?php if ($product['compare_price'] && $product['compare_price'] > $product['price']): ?>
                                <small class="text-muted text-decoration-line-through">
                                    <?php echo format_price($product['compare_price']); ?>
                                </small>
                                <?php endif; ?>
                            </div>
                            
                            <div class="row g-2 mb-3">
                                <div class="col-6">
                                    <small class="text-muted d-block">Category</small>
                                    <span class="badge bg-light text-dark"><?php echo $product['category']; ?></span>
                                </div>
                                <div class="col-6">
                                    <small class="text-muted d-block">Stock</small>
                                    <span class="<?php echo $product['stock_quantity'] <= $product['low_stock_threshold'] ? 'stock-warning' : 'text-success'; ?>">
                                        <?php echo $product['stock_quantity']; ?> units
                                    </span>
                                </div>
                            </div>
                            
                            <div class="row g-2">
                                <div class="col-6">
                                    <small class="text-muted d-block">Orders</small>
                                    <span><?php echo $product['total_orders'] ?? 0; ?></span>
                                </div>
                                <div class="col-6">
                                    <small class="text-muted d-block">Sold</small>
                                    <span><?php echo $product['total_sold'] ?? 0; ?></span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="card-footer bg-white border-0 pt-0">
                            <div class="d-grid gap-2">
                                <a href="edit-product.php?id=<?php echo $product['id']; ?>" 
                                   class="btn btn-outline-primary btn-sm">
                                    <i class="bi bi-pencil me-2"></i> Edit Product
                                </a>
                                <a href="../products/view.php?id=<?php echo $product['id']; ?>" 
                                   class="btn btn-outline-secondary btn-sm" target="_blank">
                                    <i class="bi bi-eye me-2"></i> View Live
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
            <nav aria-label="Product pagination" class="mt-4">
                <ul class="pagination justify-content-center">
                    <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                        <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page - 1])); ?>">
                            <i class="bi bi-chevron-left"></i>
                        </a>
                    </li>
                    
                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                    <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                        <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>">
                            <?php echo $i; ?>
                        </a>
                    </li>
                    <?php endfor; ?>
                    
                    <li class="page-item <?php echo $page >= $totalPages ? 'disabled' : ''; ?>">
                        <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page + 1])); ?>">
                            <i class="bi bi-chevron-right"></i>
                        </a>
                    </li>
                </ul>
            </nav>
            <?php endif; ?>
            
        <?php else: ?>
            <!-- Empty State -->
            <div class="empty-state">
                <div class="empty-state-icon">
                    <i class="bi bi-box"></i>
                </div>
                <h3 class="mb-3">No products found</h3>
                <p class="text-muted mb-4">
                    <?php echo $status || $category || $search ? 'Try adjusting your filters' : 'Start by adding your first product'; ?>
                </p>
                <div class="d-flex flex-wrap gap-3 justify-content-center">
                    <a href="add-product.php" class="btn btn-primary btn-lg">
                        <i class="bi bi-plus-circle me-2"></i> Add Your First Product
                    </a>
                    <?php if ($status || $category || $search): ?>
                    <a href="products.php" class="btn btn-outline-primary btn-lg">
                        <i class="bi bi-x-circle me-2"></i> Clear Filters
                    </a>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <?php include '../../includes/components/footer.php'; ?>

    <script>
        // Quick status update
        document.querySelectorAll('.btn-quick-status').forEach(button => {
            button.addEventListener('click', function(e) {
                e.preventDefault();
                const productId = this.dataset.productId;
                const action = this.dataset.action;
                
                fetch('quick-update.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        product_id: productId,
                        action: action
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    } else {
                        alert('Error: ' + data.error);
                    }
                });
            });
        });
        
        // Bulk actions
        document.getElementById('bulkAction').addEventListener('change', function() {
            const action = this.value;
            const selectedProducts = Array.from(document.querySelectorAll('.product-checkbox:checked'))
                .map(cb => cb.value);
            
            if (action && selectedProducts.length > 0) {
                if (confirm(`Are you sure you want to ${action} ${selectedProducts.length} product(s)?`)) {
                    const formData = new FormData();
                    formData.append('action', action);
                    selectedProducts.forEach(id => formData.append('product_ids[]', id));
                    
                    fetch('bulk-actions.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            location.reload();
                        } else {
                            alert('Error: ' + data.error);
                        }
                    });
                }
            }
        });
        
        // Select all products
        document.getElementById('selectAll').addEventListener('change', function() {
            const checkboxes = document.querySelectorAll('.product-checkbox');
            checkboxes.forEach(cb => cb.checked = this.checked);
        });
    </script>
</body>
</html>