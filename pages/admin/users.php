<?php
require_once '../../config.php';
require_once '../../includes/classes/Database.php';
require_once '../../includes/classes/User.php';

session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'admin') {
    header('Location: ../auth/login.php');
    exit();
}

$db = Database::getInstance();
$user = new User();

// Handle actions
$action = $_GET['action'] ?? '';
$userId = $_GET['id'] ?? 0;

// Get filter parameters
$userType = $_GET['type'] ?? 'all';
$search = $_GET['search'] ?? '';
$page = $_GET['page'] ?? 1;
$perPage = 10;

// Calculate offset
$offset = ($page - 1) * $perPage;

// Build query
$where = [];
$params = [];

if ($userType !== 'all') {
    $where[] = "u.user_type = :user_type";
    $params[':user_type'] = $userType;
}

if ($search) {
    $where[] = "(u.full_name LIKE :search OR u.email LIKE :search OR u.username LIKE :search)";
    $params[':search'] = "%$search%";
}

$whereClause = $where ? "WHERE " . implode(" AND ", $where) : "";

// Get total count
$countSql = "SELECT COUNT(*) as total FROM users u $whereClause";
$db->query($countSql);
foreach ($params as $key => $value) {
    $db->bind($key, $value);
}
$totalResult = $db->single();
$totalUsers = $totalResult['total'];
$totalPages = ceil($totalUsers / $perPage);

// Get users
$sql = "SELECT u.*, 
               (SELECT COUNT(*) FROM products p WHERE p.tailor_id = u.id) as product_count,
               (SELECT COUNT(*) FROM orders o WHERE o.customer_id = u.id) as order_count
        FROM users u 
        $whereClause 
        ORDER BY u.created_at DESC 
        LIMIT :limit OFFSET :offset";

$db->query($sql);
foreach ($params as $key => $value) {
    $db->bind($key, $value);
}
$db->bind(':limit', $perPage);
$db->bind(':offset', $offset);

$users = $db->resultSet();

// Handle user actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_status'])) {
        $userId = $_POST['user_id'];
        $status = $_POST['status'];
        
        if ($user->updateUserStatus($userId, $status)) {
            $success = "User status updated successfully!";
        } else {
            $error = "Failed to update user status.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users - Admin Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">
    <style>
        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
        }
        
        .badge-user {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.75rem;
        }
        
        .badge-customer { background: #3b82f6; color: white; }
        .badge-tailor { background: #10b981; color: white; }
        .badge-admin { background: #ef4444; color: white; }
        
        .action-dropdown .dropdown-menu {
            min-width: 150px;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar (same as dashboard) -->
            <?php include 'sidebar.php'; ?>
            
            <!-- Main Content -->
            <div class="col-md-10 col-lg-10">
                <!-- Header -->
                <div class="admin-header">
                    <div class="container-fluid">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h3 class="mb-0 fw-bold">User Management</h3>
                                <p class="text-muted mb-0">Manage all users, tailors, and customers</p>
                            </div>
                            <div>
                                <a href="users.php?action=add" class="btn btn-primary">
                                    <i class="bi bi-person-plus"></i> Add New User
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="container-fluid">
                    <!-- Filters -->
                    <div class="card mb-4">
                        <div class="card-body">
                            <form method="GET" class="row g-3">
                                <div class="col-md-4">
                                    <label class="form-label">Search Users</label>
                                    <div class="input-group">
                                        <input type="text" class="form-control" name="search" 
                                               value="<?php echo htmlspecialchars($search); ?>" 
                                               placeholder="Search by name, email, username...">
                                        <button class="btn btn-outline-secondary" type="submit">
                                            <i class="bi bi-search"></i>
                                        </button>
                                    </div>
                                </div>
                                
                                <div class="col-md-3">
                                    <label class="form-label">User Type</label>
                                    <select class="form-select" name="type" onchange="this.form.submit()">
                                        <option value="all" <?php echo $userType === 'all' ? 'selected' : ''; ?>>All Users</option>
                                        <option value="customer" <?php echo $userType === 'customer' ? 'selected' : ''; ?>>Customers</option>
                                        <option value="tailor" <?php echo $userType === 'tailor' ? 'selected' : ''; ?>>Tailors</option>
                                        <option value="admin" <?php echo $userType === 'admin' ? 'selected' : ''; ?>>Admins</option>
                                    </select>
                                </div>
                                
                                <div class="col-md-3">
                                    <label class="form-label">Status</label>
                                    <select class="form-select" name="status" onchange="this.form.submit()">
                                        <option value="all">All Status</option>
                                        <option value="active">Active</option>
                                        <option value="inactive">Inactive</option>
                                        <option value="suspended">Suspended</option>
                                    </select>
                                </div>
                                
                                <div class="col-md-2 d-flex align-items-end">
                                    <a href="users.php" class="btn btn-outline-secondary w-100">
                                        <i class="bi bi-arrow-clockwise"></i> Reset
                                    </a>
                                </div>
                            </form>
                        </div>
                    </div>
                    
                    <!-- Alerts -->
                    <?php if (isset($success)): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <?php echo $success; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (isset($error)): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <?php echo $error; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Users Table -->
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">
                                Users (<?php echo $totalUsers; ?>)
                                <small class="text-muted ms-2">Showing <?php echo count($users); ?> of <?php echo $totalUsers; ?></small>
                            </h5>
                            <div class="d-flex gap-2">
                                <button class="btn btn-sm btn-outline-primary" onclick="exportUsers()">
                                    <i class="bi bi-download"></i> Export
                                </button>
                                <button class="btn btn-sm btn-outline-danger" onclick="bulkDelete()">
                                    <i class="bi bi-trash"></i> Bulk Delete
                                </button>
                            </div>
                        </div>
                        
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover mb-0">
                                    <thead>
                                        <tr>
                                            <th width="50">
                                                <input type="checkbox" class="form-check-input" id="selectAll">
                                            </th>
                                            <th>User</th>
                                            <th>Type</th>
                                            <th>Contact</th>
                                            <th>Stats</th>
                                            <th>Status</th>
                                            <th>Joined</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (!empty($users)): ?>
                                            <?php foreach ($users as $userItem): ?>
                                                <tr>
                                                    <td>
                                                        <input type="checkbox" class="form-check-input user-checkbox" 
                                                               value="<?php echo $userItem['id']; ?>">
                                                    </td>
                                                    <td>
                                                        <div class="d-flex align-items-center">
                                                            <img src="../../assets/images/avatars/<?php echo $userItem['profile_pic'] ?: 'default.jpg'; ?>" 
                                                                 class="user-avatar me-3" 
                                                                 alt="<?php echo htmlspecialchars($userItem['full_name']); ?>">
                                                            <div>
                                                                <div class="fw-bold"><?php echo htmlspecialchars($userItem['full_name']); ?></div>
                                                                <small class="text-muted">@<?php echo htmlspecialchars($userItem['username']); ?></small>
                                                            </div>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <span class="badge-user badge-<?php echo $userItem['user_type']; ?>">
                                                            <?php echo ucfirst($userItem['user_type']); ?>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <div><?php echo htmlspecialchars($userItem['email']); ?></div>
                                                        <small class="text-muted"><?php echo $userItem['phone'] ?? 'No phone'; ?></small>
                                                    </td>
                                                    <td>
                                                        <div class="d-flex gap-3">
                                                            <div>
                                                                <div class="fw-bold"><?php echo $userItem['product_count'] ?? 0; ?></div>
                                                                <small class="text-muted">Products</small>
                                                            </div>
                                                            <div>
                                                                <div class="fw-bold"><?php echo $userItem['order_count'] ?? 0; ?></div>
                                                                <small class="text-muted">Orders</small>
                                                            </div>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <form method="POST" class="d-inline">
                                                            <input type="hidden" name="user_id" value="<?php echo $userItem['id']; ?>">
                                                            <select class="form-select form-select-sm" 
                                                                    name="status" 
                                                                    onchange="this.form.submit()"
                                                                    style="width: auto;">
                                                                <option value="active" <?php echo $userItem['status'] === 'active' ? 'selected' : ''; ?>>Active</option>
                                                                <option value="inactive" <?php echo $userItem['status'] === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                                                                <option value="suspended" <?php echo $userItem['status'] === 'suspended' ? 'selected' : ''; ?>>Suspended</option>
                                                            </select>
                                                            <input type="hidden" name="update_status" value="1">
                                                        </form>
                                                    </td>
                                                    <td>
                                                        <?php echo date('M d, Y', strtotime($userItem['created_at'])); ?>
                                                    </td>
                                                    <td>
                                                        <div class="dropdown action-dropdown">
                                                            <button class="btn btn-sm btn-outline-secondary dropdown-toggle" 
                                                                    type="button" 
                                                                    data-bs-toggle="dropdown">
                                                                <i class="bi bi-three-dots"></i>
                                                            </button>
                                                            <ul class="dropdown-menu">
                                                                <li>
                                                                    <a class="dropdown-item" href="user-details.php?id=<?php echo $userItem['id']; ?>">
                                                                        <i class="bi bi-eye me-2"></i> View Details
                                                                    </a>
                                                                </li>
                                                                <li>
                                                                    <a class="dropdown-item" href="users.php?action=edit&id=<?php echo $userItem['id']; ?>">
                                                                        <i class="bi bi-pencil me-2"></i> Edit
                                                                    </a>
                                                                </li>
                                                                <li>
                                                                    <a class="dropdown-item" href="#" 
                                                                       onclick="sendMessage(<?php echo $userItem['id']; ?>)">
                                                                        <i class="bi bi-chat me-2"></i> Message
                                                                    </a>
                                                                </li>
                                                                <li><hr class="dropdown-divider"></li>
                                                                <li>
                                                                    <a class="dropdown-item text-danger" 
                                                                       href="../../api/user.php?action=delete&id=<?php echo $userItem['id']; ?>" 
                                                                       onclick="return confirm('Are you sure? This action cannot be undone.')">
                                                                        <i class="bi bi-trash me-2"></i> Delete
                                                                    </a>
                                                                </li>
                                                            </ul>
                                                        </div>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="8" class="text-center py-5">
                                                    <i class="bi bi-people display-6 text-muted mb-3"></i>
                                                    <h5>No users found</h5>
                                                    <p class="text-muted">Try adjusting your search or filter criteria</p>
                                                </td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        
                        <!-- Pagination -->
                        <?php if ($totalPages > 1): ?>
                            <div class="card-footer">
                                <nav aria-label="Page navigation">
                                    <ul class="pagination justify-content-center mb-0">
                                        <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                                            <a class="page-link" href="?page=<?php echo $page - 1; ?>&type=<?php echo $userType; ?>&search=<?php echo urlencode($search); ?>">
                                                Previous
                                            </a>
                                        </li>
                                        
                                        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                            <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                                <a class="page-link" href="?page=<?php echo $i; ?>&type=<?php echo $userType; ?>&search=<?php echo urlencode($search); ?>">
                                                    <?php echo $i; ?>
                                                </a>
                                            </li>
                                        <?php endfor; ?>
                                        
                                        <li class="page-item <?php echo $page >= $totalPages ? 'disabled' : ''; ?>">
                                            <a class="page-link" href="?page=<?php echo $page + 1; ?>&type=<?php echo $userType; ?>&search=<?php echo urlencode($search); ?>">
                                                Next
                                            </a>
                                        </li>
                                    </ul>
                                </nav>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- User Details Modal -->
    <div class="modal fade" id="userDetailsModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">User Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="userDetailsContent">
                    <!-- Content loaded via AJAX -->
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Select all checkboxes
        document.getElementById('selectAll').addEventListener('change', function() {
            const checkboxes = document.querySelectorAll('.user-checkbox');
            checkboxes.forEach(checkbox => {
                checkbox.checked = this.checked;
            });
        });
        
        // Bulk delete
        function bulkDelete() {
            const selectedIds = [];
            document.querySelectorAll('.user-checkbox:checked').forEach(checkbox => {
                selectedIds.push(checkbox.value);
            });
            
            if (selectedIds.length === 0) {
                alert('Please select at least one user.');
                return;
            }
            
            if (confirm(`Are you sure you want to delete ${selectedIds.length} selected users?`)) {
                // In a real app, you would make an AJAX call here
                console.log('Deleting users:', selectedIds);
                alert('Bulk delete would be processed here.');
            }
        }
        
        // Export users
        function exportUsers() {
            // In a real app, you would make an AJAX call to generate CSV/Excel
            window.location.href = '../../api/export.php?type=users';
        }
        
        // Send message
        function sendMessage(userId) {
            const message = prompt('Enter your message:');
            if (message) {
                // In a real app, you would make an AJAX call here
                console.log('Sending message to user', userId, ':', message);
                alert('Message sent!');
            }
        }
        
        // Load user details via AJAX
        function loadUserDetails(userId) {
            fetch(`../../api/user.php?action=get_details&id=${userId}`)
                .then(response => response.json())
                .then(data => {
                    document.getElementById('userDetailsContent').innerHTML = data.html;
                    const modal = new bootstrap.Modal(document.getElementById('userDetailsModal'));
                    modal.show();
                });
        }
        
        // Auto-refresh table every 30 seconds
        setInterval(() => {
            if (!document.hidden) {
                // In a real app, you would check for new users via AJAX
                console.log('Checking for new users...');
            }
        }, 30000);
    </script>
</body>
</html>
3. Create pages/admin/sidebar.php:
php
<!-- Admin Sidebar Component -->
<style>
    .admin-sidebar {
        background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%);
        color: white;
        min-height: 100vh;
        padding: 0;
    }
    
    .sidebar-header {
        padding: 1.5rem;
        border-bottom: 1px solid rgba(255,255,255,0.1);
    }
    
    .sidebar-nav {
        padding: 1rem 0;
    }
    
    .nav-link {
        color: rgba(255,255,255,0.8);
        padding: 0.75rem 1.5rem;
        border-left: 4px solid transparent;
        transition: all 0.3s;
        text-decoration: none;
        display: block;
    }
    
    .nav-link:hover, .nav-link.active {
        color: white;
        background: rgba(255,255,255,0.1);
        border-left-color: #667eea;
    }
    
    .nav-link i {
        width: 20px;
        margin-right: 10px;
    }
</style>

<div class="admin-sidebar">
    <div class="sidebar-header text-center">
        <h4 class="fw-bold mb-0">Admin Panel</h4>
        <small class="text-muted">Clothing Marketplace</small>
    </div>
    
    <div class="sidebar-nav">
        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>" 
                   href="dashboard.php">
                    <i class="bi bi-speedometer2"></i> Dashboard
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'users.php' ? 'active' : ''; ?>" 
                   href="users.php">
                    <i class="bi bi-people"></i> Users
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'products.php' ? 'active' : ''; ?>" 
                   href="products.php">
                    <i class="bi bi-box"></i> Products
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'orders.php' ? 'active' : ''; ?>" 
                   href="orders.php">
                    <i class="bi bi-bag"></i> Orders
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'categories.php' ? 'active' : ''; ?>" 
                   href="categories.php">
                    <i class="bi bi-grid"></i> Categories
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'reviews.php' ? 'active' : ''; ?>" 
                   href="reviews.php">
                    <i class="bi bi-star"></i> Reviews
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'messages.php' ? 'active' : ''; ?>" 
                   href="messages.php">
                    <i class="bi bi-chat"></i> Messages
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'settings.php' ? 'active' : ''; ?>" 
                   href="settings.php">
                    <i class="bi bi-gear"></i> Settings
                </a>
            </li>
            <li class="nav-item mt-4">
                <a class="nav-link text-danger" href="../../pages/auth/logout.php">
                    <i class="bi bi-box-arrow-right"></i> Logout
                </a>
            </li>
        </ul>
    </div>
</div>