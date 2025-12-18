pages/tailor/analytics.php:
php
<?php
require_once '../../config.php';
require_once '../../includes/classes/Database.php';
require_once '../../includes/classes/Order.php';
require_once '../../includes/classes/Product.php';

session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'tailor') {
    header('Location: ../auth/login.php');
    exit();
}

$tailorId = $_SESSION['user_id'];
$order = new Order();
$product = new Product();

// Get analytics data
$revenue = $order->getTotalRevenueByTailor($tailorId);
$totalOrders = $order->getTotalOrdersByTailor($tailorId);
$completedOrders = $order->getCompletedOrdersCount($tailorId);
$pendingOrders = $order->getPendingOrdersCount($tailorId);

// Get monthly statistics
$monthlyStats = $order->getOrdersStatistics($tailorId, 6);

// Prepare data for chart
$months = [];
$revenues = [];
$orderCounts = [];

foreach ($monthlyStats as $stat) {
    $months[] = date('M Y', strtotime($stat['month'] . '-01'));
    $revenues[] = $stat['revenue'] ?? 0;
    $orderCounts[] = $stat['order_count'] ?? 0;
}

// Get top selling products
$topProducts = $product->getTopSellingProducts($tailorId, 5);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Analytics - Tailor Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .stat-card {
            border-radius: 10px;
            padding: 1.5rem;
            margin-bottom: 1rem;
            color: white;
        }
        
        .stat-card.revenue {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        
        .stat-card.orders {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
        }
        
        .stat-card.completed {
            background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);
        }
        
        .stat-card.pending {
            background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);
        }
        
        .chart-container {
            background: white;
            border-radius: 10px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body>
    <?php include '../../includes/components/navbar.php'; ?>
    
    <div class="container-fluid mt-4">
        <div class="row">
            <!-- Stats Cards -->
            <div class="col-md-3">
                <div class="stat-card revenue">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="mb-1">Total Revenue</h6>
                            <h3 class="mb-0">$<?php echo number_format($revenue, 2); ?></h3>
                        </div>
                        <i class="bi bi-currency-dollar display-6"></i>
                    </div>
                </div>
            </div>
            
            <div class="col-md-3">
                <div class="stat-card orders">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="mb-1">Total Orders</h6>
                            <h3 class="mb-0"><?php echo $totalOrders; ?></h3>
                        </div>
                        <i class="bi bi-bag display-6"></i>
                    </div>
                </div>
            </div>
            
            <div class="col-md-3">
                <div class="stat-card completed">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="mb-1">Completed</h6>
                            <h3 class="mb-0"><?php echo $completedOrders; ?></h3>
                        </div>
                        <i class="bi bi-check-circle display-6"></i>
                    </div>
                </div>
            </div>
            
            <div class="col-md-3">
                <div class="stat-card pending">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="mb-1">Pending</h6>
                            <h3 class="mb-0"><?php echo $pendingOrders; ?></h3>
                        </div>
                        <i class="bi bi-clock display-6"></i>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Charts -->
        <div class="row mt-4">
            <div class="col-md-8">
                <div class="chart-container">
                    <h5>Revenue & Orders Overview</h5>
                    <canvas id="revenueChart" height="250"></canvas>
                </div>
            </div>
            <div class="col-md-4">
                <div class="chart-container">
                    <h5>Top Selling Products</h5>
                    <div class="mt-3">
                        <?php if (!empty($topProducts)): ?>
                            <?php foreach ($topProducts as $index => $topProduct): ?>
                                <div class="d-flex align-items-center mb-3">
                                    <div class="flex-shrink-0">
                                        <span class="badge bg-primary me-2">#<?php echo $index + 1; ?></span>
                                    </div>
                                    <div class="flex-grow-1">
                                        <div class="fw-bold"><?php echo htmlspecialchars($topProduct['title']); ?></div>
                                        <div class="small text-muted">
                                            Sold: <?php echo $topProduct['total_sold'] ?? 0; ?> units
                                        </div>
                                    </div>
                                    <div class="flex-shrink-0">
                                        <span class="fw-bold">$<?php echo number_format($topProduct['total_revenue'] ?? 0, 2); ?></span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p class="text-muted text-center">No sales data available</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Monthly Statistics Table -->
        <div class="row mt-4">
            <div class="col-12">
                <div class="chart-container">
                    <h5>Monthly Statistics</h5>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Month</th>
                                    <th>Orders</th>
                                    <th>Revenue</th>
                                    <th>Avg Order Value</th>
                                    <th>Completed</th>
                                    <th>Pending</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($monthlyStats)): ?>
                                    <?php foreach ($monthlyStats as $stat): ?>
                                        <tr>
                                            <td><?php echo date('F Y', strtotime($stat['month'] . '-01')); ?></td>
                                            <td><?php echo $stat['order_count'] ?? 0; ?></td>
                                            <td><strong>$<?php echo number_format($stat['revenue'] ?? 0, 2); ?></strong></td>
                                            <td>$<?php echo number_format($stat['average_order_value'] ?? 0, 2); ?></td>
                                            <td>
                                                <span class="badge bg-success">
                                                    <?php echo round(($stat['order_count'] ?? 0) * 0.8); ?> <!-- Sample data -->
                                                </span>
                                            </td>
                                            <td>
                                                <span class="badge bg-warning">
                                                    <?php echo round(($stat['order_count'] ?? 0) * 0.2); ?> <!-- Sample data -->
                                                </span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="6" class="text-center py-4">
                                            <p class="text-muted">No statistics available</p>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        // Revenue Chart
        const revenueCtx = document.getElementById('revenueChart').getContext('2d');
        const revenueChart = new Chart(revenueCtx, {
            type: 'line',
            data: {
                labels: <?php echo json_encode(array_reverse($months)); ?>,
                datasets: [
                    {
                        label: 'Revenue ($)',
                        data: <?php echo json_encode(array_reverse($revenues)); ?>,
                        borderColor: '#667eea',
                        backgroundColor: 'rgba(102, 126, 234, 0.1)',
                        borderWidth: 3,
                        fill: true,
                        tension: 0.4,
                        yAxisID: 'y'
                    },
                    {
                        label: 'Orders',
                        data: <?php echo json_encode(array_reverse($orderCounts)); ?>,
                        borderColor: '#f5576c',
                        backgroundColor: 'rgba(245, 87, 108, 0.1)',
                        borderWidth: 3,
                        fill: true,
                        tension: 0.4,
                        yAxisID: 'y1'
                    }
                ]
            },
            options: {
                responsive: true,
                interaction: {
                    mode: 'index',
                    intersect: false,
                },
                scales: {
                    y: {
                        type: 'linear',
                        display: true,
                        position: 'left',
                        title: {
                            display: true,
                            text: 'Revenue ($)'
                        },
                        grid: {
                            color: 'rgba(0,0,0,0.05)'
                        }
                    },
                    y1: {
                        type: 'linear',
                        display: true,
                        position: 'right',
                        title: {
                            display: true,
                            text: 'Orders'
                        },
                        grid: {
                            drawOnChartArea: false,
                        },
                    }
                },
                plugins: {
                    legend: {
                        position: 'top',
                    }
                }
            }
        });
    </script>
</body>
</html>