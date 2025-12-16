<?php
require_once '../../config.php';
require_once '../../includes/classes/User.php';
require_once '../../includes/classes/Order.php';
require_once '../../includes/classes/Product.php';

// Check authentication and admin role
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'admin') {
    header('Location: ../auth/login.php');
    exit();
}

$user = new User();
$order = new Order();
$product = new Product();

// Get statistics
$totalUsers = $user->getTotalCount();
$totalTailors = $user->getCountByType('tailor');
$totalCustomers = $user->getCountByType('customer');
$totalProducts = $product->getTotalCount();
$totalOrders = $order->getTotalCount();
$revenue = $order->getTotalRevenue();
$recentOrders = $order->getRecentOrders(null, 5);
$recentUsers = $user->getRecentUsers(5);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Montserrat:wght@500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --admin-primary: #4f46e5;
            --admin-secondary: #7c3aed;
            --admin-success: #10b981;
            --admin-warning: #f59e0b;
            --admin-danger: #ef4444;
            --admin-info: #3b82f6;
        }
        
        body {
            font-family: 'Poppins', sans-serif;
            background: #f8fafc;
            color: #334155;
        }
        
        /* Sidebar */
        .sidebar {
            background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
            color: white;
            min-height: 100vh;
            position: fixed;
            width: 250px;
            box-shadow: 5px 0 15px rgba(0,0,0,0.1);
            z-index: 1000;
        }
        
        .sidebar-header {
            padding: 1.5rem;
            border-bottom: 1px solid #334155;
        }
        
        .sidebar-brand {
            font-family: 'Montserrat', sans-serif;
            font-weight: 700;
            font-size: 1.5rem;
            color: white;
            text-decoration: none;
        }
        
        .sidebar-menu {
            padding: 1rem 0;
        }
        
        .nav-link {
            color: #cbd5e1;
            padding: 0.75rem 1.5rem;
            margin: 0.25rem 1rem;
            border-radius: 10px;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        
        .nav-link:hover {
            color: white;
            background: rgba(255,255,255,0.1);
        }
        
        .nav-link.active {
            color: white;
            background: var(--admin-primary);
            box-shadow: 0 5px 15px rgba(79, 70, 229, 0.3);
        }
        
        .sidebar-footer {
            position: absolute;
            bottom: 0;
            width: 100%;
            padding: 1rem;
            border-top: 1px solid #334155;
        }
        
        /* Main Content */
        .main-content {
            margin-left: 250px;
            padding: 1.5rem;
        }
        
        .top-bar {
            background: white;
            padding: 1rem 1.5rem;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
            margin-bottom: 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .page-title h1 {
            font-family: 'Montserrat', sans-serif;
            font-weight: 700;
            color: #1e293b;
            margin: 0;
        }
        
        .admin-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .stat-card {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
            transition: all 0.3s ease;
            border-left: 5px solid var(--admin-primary);
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 30px rgba(0,0,0,0.1);
        }
        
        .stat-card.revenue { border-left-color: var(--admin-success); }
        .stat-card.users { border-left-color: var(--admin-info); }
        .stat-card.orders { border-left-color: var(--admin-warning); }
        .stat-card.products { border-left-color: var(--admin-danger); }
        
        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            margin-bottom: 1rem;
        }
        
        .stat-card .stat-icon {
            background: linear-gradient(135deg, var(--admin-primary), var(--admin-secondary));
            color: white;
        }
        
        .stat-card.revenue .stat-icon { background: linear-gradient(135deg, var(--admin-success), #34d399); }
        .stat-card.users .stat-icon { background: linear-gradient(135deg, var(--admin-info), #60a5fa); }
        .stat-card.orders .stat-icon { background: linear-gradient(135deg, var(--admin-warning), #fbbf24); }
        .stat-card.products .stat-icon { background: linear-gradient(135deg, var(--admin-danger), #f87171); }
        
        .stat-value {
            font-size: 2rem;
            font-weight: 700;
            color: #1e293b;
            margin: 0.5rem 0;
        }
        
        .dashboard-card {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
            margin-bottom: 1.5rem;
        }
        
        .table-responsive {
            border-radius: 10px;
            overflow: hidden;
            border: 1px solid #e2e8f0;
        }
        
        .table th {
            background: #f8fafc;
            font-weight: 600;
            color: #475569;
            border-bottom: 2px solid #e2e8f0;
        }
        
        .badge-admin { background: var(--admin-primary); }
        .badge-tailor { background: var(--admin-warning); }
        .badge-customer { background: var(--admin-info); }
        
        .order-status {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        
        .status-pending { background: #fef3c7; color: #92400e; }
        .status-confirmed { background: #d1fae5; color: #065f46; }
        .status-completed { background: #dcfce7; color: #166534; }
        .status-cancelled { background: #fee2e2; color: #991b1b; }
        
        .chart-container {
            height: 300px;
            position: relative;
        }
        
        .quick-actions {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-top: 2rem;
        }
        
        .action-btn {
            background: white;
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            padding: 1.5rem;
            text-align: center;
            transition: all 0.3s ease;
            text-decoration: none;
            color: #334155;
        }
        
        .action-btn:hover {
            border-color: var(--admin-primary);
            transform: translateY(-3px);
            box-shadow: 0 10px 20px rgba(79, 70, 229, 0.1);
        }
        
        .action-icon {
            font-size: 2rem;
            color: var(--admin-primary);
            margin-bottom: 0.5rem;
        }
        
        /* Notifications */
        .notification-badge {
            position: absolute;
            top: -5px;
            right: -5px;
            background: var(--admin-danger);
            color: white;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            font-size: 0.75rem;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        /* Responsive */
        @media (max-width: 992px) {
            .sidebar {
                width: 70px;
            }
            
            .sidebar .nav-text {
                display: none;
            }
            
            .main-content {
                margin-left: 70px;
            }
            
            .sidebar-brand span {
                display: none;
            }
        }
        
        @media (max-width: 768px) {
            .admin-stats {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="sidebar-header">
            <a href="dashboard.php" class="sidebar-brand">
                <i class="bi bi-shop"></i>
                <span><?php echo SITE_NAME; ?></span>
            </a>
        </div>
        
        <div class="sidebar-menu">
            <ul class="nav flex-column">
                <li class="nav-item">
                    <a class="nav-link active" href="dashboard.php">
                        <i class="bi bi-speedometer2"></i>
                        <span class="nav-text">Dashboard</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="users.php">
                        <i class="bi bi-people"></i>
                        <span class="nav-text">Users</span>
                        <span class="notification-badge">3</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="products.php">
                        <i class="bi bi-box-seam"></i>
                        <span class="nav-text">Products</span>
                        <span class="notification-badge">5</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="orders.php">
                        <i class="bi bi-bag-check"></i>
                        <span class="nav-text">Orders</span>
                        <span class="notification-badge">12</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="categories.php">
                        <i class="bi bi-tags"></i>
                        <span class="nav-text">Categories</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="reports.php">
                        <i class="bi bi-graph-up"></i>
                        <span class="nav-text">Analytics</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="settings.php">
                        <i class="bi bi-gear"></i>
                        <span class="nav-text">Settings</span>
                    </a>
                </li>
            </ul>
        </div>
        
        <div class="sidebar-footer">
            <div class="d-flex align-items-center">
                <img src="<?php echo SITE_URL; ?>/assets/images/avatars/admin.jpg" 
                     class="rounded-circle me-2" 
                     width="40" 
                     height="40">
                <div>
                    <div class="small fw-bold">Admin User</div>
                    <div class="small text-muted">Administrator</div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Main Content -->
    <div class="main-content">
        <!-- Top Bar -->
        <div class="top-bar">
            <div class="page-title">
                <h1>Dashboard Overview</h1>
                <p class="text-muted mb-0">Welcome back, <?php echo $_SESSION['username']; ?>!</p>
            </div>
            <div class="top-actions">
                <button class="btn btn-primary">
                    <i class="bi bi-plus-circle me-2"></i> Add New
                </button>
                <button class="btn btn-light ms-2">
                    <i class="bi bi-bell"></i>
                </button>
            </div>
        </div>
        
        <!-- Statistics Cards -->
        <div class="admin-stats">
            <div class="stat-card revenue">
                <div class="stat-icon">
                    <i class="bi bi-currency-dollar"></i>
                </div>
                <div class="stat-label">Total Revenue</div>
                <div class="stat-value">$<?php echo number_format($revenue, 2); ?></div>
                <div class="stat-change text-success">
                    <i class="bi bi-arrow-up"></i> 12.5% from last month
                </div>
            </div>
            
            <div class="stat-card users">
                <div class="stat-icon">
                    <i class="bi bi-people"></i>
                </div>
                <div class="stat-label">Total Users</div>
                <div class="stat-value"><?php echo number_format($totalUsers); ?></div>
                <div class="stat-change text-success">
                    <i class="bi bi-arrow-up"></i> 5.2% from last month
                </div>
            </div>
            
            <div class="stat-card orders">
                <div class="stat-icon">
                    <i class="bi bi-bag-check"></i>
                </div>
                <div class="stat-label">Total Orders</div>
                <div class="stat-value"><?php echo number_format($totalOrders); ?></div>
                <div class="stat-change text-success">
                    <i class="bi bi-arrow-up"></i> 8.7% from last month
                </div>
            </div>
            
            <div class="stat-card products">
                <div class="stat-icon">
                    <i class="bi bi-box-seam"></i>
                </div>
                <div class="stat-label">Total Products</div>
                <div class="stat-value"><?php echo number_format($totalProducts); ?></div>
                <div class="stat-change text-danger">
                    <i class="bi bi-arrow-down"></i> 2.1% from last month
                </div>
            </div>
        </div>
        
        <div class="row">
            <!-- Recent Orders -->
            <div class="col-lg-8">
                <div class="dashboard-card">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h4 class="fw-bold mb-0">Recent Orders</h4>
                        <a href="orders.php" class="btn btn-sm btn-outline-primary">View All</a>
                    </div>
                    
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Order ID</th>
                                    <th>Customer</th>
                                    <th>Tailor</th>
                                    <th>Amount</th>
                                    <th>Status</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recentOrders as $order): ?>
                                    <tr>
                                        <td>
                                            <a href="order-details.php?id=<?php echo $order['id']; ?>" class="text-decoration-none">
                                                #<?php echo $order['order_number']; ?>
                                            </a>
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <img src="<?php echo SITE_URL; ?>/assets/images/avatars/<?php echo $order['customer_pic'] ?: 'default.jpg'; ?>" 
                                                     class="rounded-circle me-2" 
                                                     width="30" 
                                                     height="30">
                                                <?php echo htmlspecialchars($order['customer_name']); ?>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <img src="<?php echo SITE_URL; ?>/assets/images/avatars/<?php echo $order['tailor_pic'] ?: 'default.jpg'; ?>" 
                                                     class="rounded-circle me-2" 
                                                     width="30" 
                                                     height="30">
                                                <?php echo htmlspecialchars($order['tailor_name']); ?>
                                            </div>
                                        </td>
                                        <td class="fw-bold">$<?php echo number_format($order['total_amount'], 2); ?></td>
                                        <td>
                                            <span class="order-status status-<?php echo $order['status']; ?>">
                                                <?php echo ucfirst($order['status']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo date('M d, Y', strtotime($order['created_at'])); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <!-- Revenue Chart -->
                <div class="dashboard-card">
                    <h4 class="fw-bold mb-4">Revenue Overview</h4>
                    <div class="chart-container">
                        <canvas id="revenueChart"></canvas>
                    </div>
                </div>
            </div>
            
            <!-- Right Column -->
            <div class="col-lg-4">
                <!-- Recent Users -->
                <div class="dashboard-card">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h4 class="fw-bold mb-0">Recent Users</h4>
                        <a href="users.php" class="btn btn-sm btn-outline-primary">View All</a>
                    </div>
                    
                    <div class="user-list">
                        <?php foreach ($recentUsers as $user): ?>
                            <div class="d-flex align-items-center mb-3">
                                <img src="<?php echo SITE_URL; ?>/assets/images/avatars/<?php echo $user['profile_pic'] ?: 'default.jpg'; ?>" 
                                     class="rounded-circle me-3" 
                                     width="45" 
                                     height="45">
                                <div class="flex-grow-1">
                                    <h6 class="mb-0 fw-bold"><?php echo htmlspecialchars($user['full_name']); ?></h6>
                                    <small class="text-muted">@<?php echo htmlspecialchars($user['username']); ?></small>
                                </div>
                                <span class="badge badge-<?php echo $user['user_type']; ?>">
                                    <?php echo ucfirst($user['user_type']); ?>
                                </span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <!-- User Distribution -->
                <div class="dashboard-card">
                    <h4 class="fw-bold mb-4">User Distribution</h4>
                    <div class="chart-container">
                        <canvas id="userDistributionChart"></canvas>
                    </div>
                </div>
                
                <!-- Quick Actions -->
                <div class="dashboard-card">
                    <h4 class="fw-bold mb-4">Quick Actions</h4>
                    <div class="quick-actions">
                        <a href="users.php?action=add" class="action-btn">
                            <div class="action-icon">
                                <i class="bi bi-person-plus"></i>
                            </div>
                            <div class="fw-bold">Add User</div>
                        </a>
                        
                        <a href="products.php?action=add" class="action-btn">
                            <div class="action-icon">
                                <i class="bi bi-plus-square"></i>
                            </div>
                            <div class="fw-bold">Add Product</div>
                        </a>
                        
                        <a href="reports.php" class="action-btn">
                            <div class="action-icon">
                                <i class="bi bi-file-earmark-bar-graph"></i>
                            </div>
                            <div class="fw-bold">Generate Report</div>
                        </a>
                        
                        <a href="settings.php" class="action-btn">
                            <div class="action-icon">
                                <i class="bi bi-sliders"></i>
                            </div>
                            <div class="fw-bold">Settings</div>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        // Revenue Chart
        const revenueCtx = document.getElementById('revenueChart').getContext('2d');
        const revenueChart = new Chart(revenueCtx, {
            type: 'line',
            data: {
                labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul'],
                datasets: [{
                    label: 'Revenue ($)',
                    data: [12000, 19000, 15000, 25000, 22000, 30000, 28000],
                    borderColor: '#4f46e5',
                    backgroundColor: 'rgba(79, 70, 229, 0.1)',
                    borderWidth: 3,
                    fill: true,
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: 'rgba(0,0,0,0.05)'
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        }
                    }
                }
            }
        });
        
        // User Distribution Chart
        const userCtx = document.getElementById('userDistributionChart').getContext('2d');
        const userChart = new Chart(userCtx, {
            type: 'doughnut',
            data: {
                labels: ['Customers', 'Tailors', 'Admins'],
                datasets: [{
                    data: [<?php echo $totalCustomers; ?>, <?php echo $totalTailors; ?>, 1],
                    backgroundColor: [
                        '#3b82f6',
                        '#f59e0b',
                        '#4f46e5'
                    ],
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });
        
        // Add animations
        $(document).ready(function() {
            $('.stat-card').each(function(index) {
                $(this).css('animation-delay', (index * 0.1) + 's');
                $(this).addClass('animate__animated animate__fadeInUp');
            });
            
            // Update notification counts via AJAX
            function updateNotifications() {
                $.ajax({
                    url: '../../api/admin.php',
                    method: 'GET',
                    data: { action: 'get_notifications' },
                    success: function(response) {
                        if (response.success) {
                            // Update notification badges
                            // You can implement this based on your needs
                        }
                    }
                });
            }
            
            // Update every 30 seconds
            setInterval(updateNotifications, 30000);
        });
    </script>
    
    <!-- Animation Library -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
</body>
</html>