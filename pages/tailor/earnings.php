<?php
require_once '../../config.php';
require_once '../../includes/classes/Database.php';
require_once '../../includes/classes/Order.php';

session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'tailor') {
    header('Location: ../auth/login.php');
    exit();
}

$tailorId = $_SESSION['user_id'];
$order = new Order();

// Get date range filter
$period = $_GET['period'] ?? 'month';
$month = $_GET['month'] ?? date('Y-m');
$year = $_GET['year'] ?? date('Y');

// Calculate date range based on period
if ($period === 'week') {
    $startDate = date('Y-m-d', strtotime('-7 days'));
    $endDate = date('Y-m-d');
} elseif ($period === 'month') {
    $startDate = date('Y-m-01', strtotime($month . '-01'));
    $endDate = date('Y-m-t', strtotime($month . '-01'));
} elseif ($period === 'year') {
    $startDate = date('Y-01-01', strtotime($year . '-01-01'));
    $endDate = date('Y-12-31', strtotime($year . '-01-01'));
} else {
    $startDate = date('Y-m-d', strtotime('-30 days'));
    $endDate = date('Y-m-d');
}

// Get earnings data
$earningsData = $order->getOrdersByDateRange($tailorId, $startDate, $endDate);

// Calculate totals
$totalEarnings = 0;
$totalOrders = count($earningsData);
$completedOrders = 0;

foreach ($earningsData as $order) {
    if ($order['status'] === 'delivered' || $order['status'] === 'completed') {
        $totalEarnings += $order['total_amount'];
        $completedOrders++;
    }
}

// Get monthly earnings for chart
$monthlyEarnings = $order->getOrdersStatistics($tailorId, 12);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Earnings - Tailor Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .earning-card {
            background: white;
            border-radius: 10px;
            padding: 1.5rem;
            margin-bottom: 1rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .earning-card .icon {
            width: 50px;
            height: 50px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
        }
        
        .earning-card.primary .icon {
            background-color: rgba(102, 126, 234, 0.1);
            color: #667eea;
        }
        
        .earning-card.success .icon {
            background-color: rgba(16, 185, 129, 0.1);
            color: #10b981;
        }
        
        .earning-card.warning .icon {
            background-color: rgba(245, 158, 11, 0.1);
            color: #f59e0b;
        }
        
        .withdrawal-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 10px;
            padding: 1.5rem;
        }
    </style>
</head>
<body>
    <?php include '../../includes/components/navbar.php'; ?>
    
    <div class="container-fluid mt-4">
        <!-- Filters -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">Earnings Overview</h5>
                            <div class="d-flex gap-2">
                                <select class="form-select w-auto" id="periodSelect">
                                    <option value="week" <?php echo $period === 'week' ? 'selected' : ''; ?>>Last 7 Days</option>
                                    <option value="month" <?php echo $period === 'month' ? 'selected' : ''; ?>>This Month</option>
                                    <option value="year" <?php echo $period === 'year' ? 'selected' : ''; ?>>This Year</option>
                                    <option value="custom" <?php echo $period === 'custom' ? 'selected' : ''; ?>>Custom Range</option>
                                </select>
                                <?php if ($period === 'month'): ?>
                                    <input type="month" class="form-control w-auto" value="<?php echo $month; ?>" id="monthSelect">
                                <?php endif; ?>
                                <?php if ($period === 'year'): ?>
                                    <select class="form-select w-auto" id="yearSelect">
                                        <?php for ($y = date('Y'); $y >= date('Y') - 5; $y--): ?>
                                            <option value="<?php echo $y; ?>" <?php echo $year == $y ? 'selected' : ''; ?>>
                                                <?php echo $y; ?>
                                            </option>
                                        <?php endfor; ?>
                                    </select>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Stats Cards -->
        <div class="row mb-4">
            <div class="col-md-4">
                <div class="earning-card primary">
                    <div class="d-flex align-items-center">
                        <div class="icon me-3">
                            <i class="bi bi-currency-dollar"></i>
                        </div>
                        <div>
                            <h5 class="mb-1">Total Earnings</h5>
                            <h2 class="mb-0">$<?php echo number_format($totalEarnings, 2); ?></h2>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="earning-card success">
                    <div class="d-flex align-items-center">
                        <div class="icon me-3">
                            <i class="bi bi-bag-check"></i>
                        </div>
                        <div>
                            <h5 class="mb-1">Completed Orders</h5>
                            <h2 class="mb-0"><?php echo $completedOrders; ?></h2>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="earning-card warning">
                    <div class="d-flex align-items-center">
                        <div class="icon me-3">
                            <i class="bi bi-clock-history"></i>
                        </div>
                        <div>
                            <h5 class="mb-1">Pending Payments</h5>
                            <h2 class="mb-0">$<?php echo number_format($totalOrders - $completedOrders, 2); ?></h2>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Earnings Chart -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Earnings Trend</h5>
                        <canvas id="earningsChart" height="100"></canvas>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Withdrawal & Transactions -->
        <div class="row">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Withdrawal</h5>
                    </div>
                    <div class="card-body">
                        <div class="withdrawal-card mb-3">
                            <h6 class="mb-2">Available Balance</h6>
                            <h2 class="mb-3">$<?php echo number_format($totalEarnings * 0.85, 2); ?></h2>
                            <p class="small mb-0">15% platform fee deducted</p>
                        </div>
                        
                        <form class="mt-3">
                            <div class="mb-3">
                                <label class="form-label">Withdrawal Amount</label>
                                <div class="input-group">
                                    <span class="input-group-text">$</span>
                                    <input type="number" class="form-control" min="10" 
                                           max="<?php echo $totalEarnings * 0.85; ?>"
                                           value="<?php echo number_format($totalEarnings * 0.85, 2); ?>">
                                </div>
                                <small class="text-muted">Minimum withdrawal: $10</small>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Payment Method</label>
                                <select class="form-select">
                                    <option value="paypal">PayPal</option>
                                    <option value="bank">Bank Transfer</option>
                                    <option value="cash">Cash Pickup</option>
                                </select>
                            </div>
                            
                            <button type="submit" class="btn btn-primary w-100">
                                Request Withdrawal
                            </button>
                        </form>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Recent Transactions</h5>
                        <a href="transactions.php" class="btn btn-sm btn-outline-primary">View All</a>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead>
                                    <tr>
                                        <th>Order ID</th>
                                        <th>Date</th>
                                        <th>Amount</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (!empty($earningsData)): ?>
                                        <?php foreach (array_slice($earningsData, 0, 5) as $transaction): ?>
                                            <tr>
                                                <td>#<?php echo $transaction['order_number']; ?></td>
                                                <td><?php echo date('M d, Y', strtotime($transaction['created_at'])); ?></td>
                                                <td><strong>$<?php echo number_format($transaction['total_amount'], 2); ?></strong></td>
                                                <td>
                                                    <?php if ($transaction['status'] === 'delivered' || $transaction['status'] === 'completed'): ?>
                                                        <span class="badge bg-success">Paid</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-warning">Pending</span>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="4" class="text-center py-4">
                                                <p class="text-muted mb-0">No transactions found</p>
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
    </div>
    
    <script>
        // Earnings Chart
        const earningsCtx = document.getElementById('earningsChart').getContext('2d');
        const earningsChart = new Chart(earningsCtx, {
            type: 'bar',
            data: {
                labels: <?php echo json_encode(array_map(function($stat) {
                    return date('M', strtotime($stat['month'] . '-01'));
                }, $monthlyEarnings)); ?>,
                datasets: [{
                    label: 'Monthly Earnings ($)',
                    data: <?php echo json_encode(array_column($monthlyEarnings, 'revenue')); ?>,
                    backgroundColor: 'rgba(102, 126, 234, 0.5)',
                    borderColor: '#667eea',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
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
                        },
                        ticks: {
                            callback: function(value) {
                                return '$' + value;
                            }
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
        
        // Filter handling
        document.getElementById('periodSelect').addEventListener('change', function() {
            const period = this.value;
            if (period === 'month') {
                window.location.href = 'earnings.php?period=month&month=' + new Date().toISOString().slice(0, 7);
            } else if (period === 'year') {
                window.location.href = 'earnings.php?period=year&year=' + new Date().getFullYear();
            } else {
                window.location.href = 'earnings.php?period=' + period;
            }
        });
        
        if (document.getElementById('monthSelect')) {
            document.getElementById('monthSelect').addEventListener('change', function() {
                window.location.href = 'earnings.php?period=month&month=' + this.value;
            });
        }
        
        if (document.getElementById('yearSelect')) {
            document.getElementById('yearSelect').addEventListener('change', function() {
                window.location.href = 'earnings.php?period=year&year=' + this.value;
            });
        }
    </script>
</body>
</html>