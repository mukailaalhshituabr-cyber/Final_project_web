<?php
require_once dirname(__DIR__, 2) . '/config.php';
require_once ROOT_PATH . '/includes/classes/Database.php';
require_once ROOT_PATH . '/includes/classes/Earning.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'tailor') {
    header('Location: ../auth/login.php');
    exit();
}

$db = Database::getInstance();
$earningObj = new Earning();
$tailorId = $_SESSION['user_id'];

// Get date range
$year = isset($_GET['year']) ? intval($_GET['year']) : date('Y');
$month = isset($_GET['month']) ? intval($_GET['month']) : date('m');
$period = $_GET['period'] ?? 'monthly'; // monthly, quarterly, yearly

// Calculate date range based on period
$date_from = '';
$date_to = '';

if ($period === 'monthly') {
    $date_from = date('Y-m-01', strtotime("$year-$month-01"));
    $date_to = date('Y-m-t', strtotime("$year-$month-01"));
} elseif ($period === 'quarterly') {
    $quarter = ceil($month / 3);
    $start_month = ($quarter - 1) * 3 + 1;
    $end_month = $start_month + 2;
    $date_from = date('Y-m-01', strtotime("$year-$start_month-01"));
    $date_to = date('Y-m-t', strtotime("$year-$end_month-01"));
} else { // yearly
    $date_from = date('Y-01-01', strtotime("$year-01-01"));
    $date_to = date('Y-12-31', strtotime("$year-12-31"));
}

// Get earnings summary
$summary = $earningObj->getEarningsSummary($tailorId, $date_from, $date_to);

// Get earnings history
$history = $earningObj->getEarningsHistory($tailorId, $date_from, $date_to);

// Get payout methods
$payoutMethods = $earningObj->getPayoutMethods($tailorId);

// Get upcoming payouts
$upcomingPayouts = $earningObj->getUpcomingPayouts($tailorId);

// Handle payout request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['request_payout'])) {
    $amount = floatval($_POST['amount']);
    $payoutMethodId = intval($_POST['payout_method_id']);
    
    if ($amount > 0 && $amount <= $summary['available_balance']) {
        $result = $earningObj->requestPayout($tailorId, $amount, $payoutMethodId);
        if ($result) {
            header('Location: earnings.php?payout_requested=1');
            exit();
        }
    }
}

// Handle payout method update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_payout_method'])) {
    $methodData = [
        'method_type' => $_POST['method_type'],
        'account_name' => trim($_POST['account_name']),
        'account_number' => trim($_POST['account_number']),
        'bank_name' => trim($_POST['bank_name'] ?? ''),
        'bank_code' => trim($_POST['bank_code'] ?? ''),
        'phone' => trim($_POST['phone'] ?? ''),
        'provider' => trim($_POST['provider'] ?? ''),
        'email' => trim($_POST['email'] ?? ''),
        'is_default' => isset($_POST['is_default']) ? 1 : 0
    ];
    
    if (isset($_POST['payout_method_id'])) {
        // Update existing
        $earningObj->updatePayoutMethod($_POST['payout_method_id'], $tailorId, $methodData);
    } else {
        // Add new
        $earningObj->addPayoutMethod($tailorId, $methodData);
    }
    
    header('Location: earnings.php?method_updated=1');
    exit();
}

// Handle payout method deletion
if (isset($_GET['delete_method'])) {
    $earningObj->deletePayoutMethod($_GET['delete_method'], $tailorId);
    header('Location: earnings.php?method_deleted=1');
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Earnings - <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .earnings-container {
            min-height: calc(100vh - 200px);
        }
        .stats-card {
            background: white;
            border-radius: 10px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            border-top: 4px solid transparent;
        }
        .stats-card.total {
            border-top-color: #667eea;
        }
        .stats-card.available {
            border-top-color: #28a745;
        }
        .stats-card.pending {
            border-top-color: #ffc107;
        }
        .stats-card.paid {
            border-top-color: #6c757d;
        }
        .stats-icon {
            width: 50px;
            height: 50px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            margin-bottom: 1rem;
        }
        .stats-icon.total {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
        }
        .stats-icon.available {
            background: linear-gradient(135deg, #28a745, #20c997);
            color: white;
        }
        .stats-icon.pending {
            background: linear-gradient(135deg, #ffc107, #fd7e14);
            color: white;
        }
        .stats-icon.paid {
            background: linear-gradient(135deg, #6c757d, #495057);
            color: white;
        }
        .stats-number {
            font-size: 1.8rem;
            font-weight: bold;
            color: #495057;
            margin-bottom: 0.5rem;
        }
        .stats-label {
            color: #6c757d;
            font-size: 0.9rem;
        }
        .chart-container {
            background: white;
            border-radius: 10px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        .table-card {
            background: white;
            border-radius: 10px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        .payout-method-card {
            background: white;
            border-radius: 10px;
            padding: 1.5rem;
            margin-bottom: 1rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            border: 1px solid #dee2e6;
        }
        .payout-method-card.default {
            border-color: #667eea;
            border-width: 2px;
        }
        .method-icon {
            width: 40px;
            height: 40px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
            margin-right: 1rem;
        }
        .method-icon.bank {
            background: linear-gradient(135deg, #007bff, #6610f2);
            color: white;
        }
        .method-icon.mobile {
            background: linear-gradient(135deg, #28a745, #20c997);
            color: white;
        }
        .method-icon.paypal {
            background: linear-gradient(135deg, #003087, #009cde);
            color: white;
        }
        .period-selector {
            display: flex;
            gap: 0.5rem;
            margin-bottom: 1.5rem;
        }
        .btn-period {
            padding: 0.5rem 1rem;
            border: 1px solid #dee2e6;
            border-radius: 5px;
            background: white;
            color: #495057;
            transition: all 0.3s ease;
        }
        .btn-period.active {
            background: #667eea;
            color: white;
            border-color: #667eea;
        }
        .btn-request {
            background: linear-gradient(135deg, #667eea, #764ba2);
            border: none;
            color: white;
            padding: 0.75rem 2rem;
            font-weight: 600;
            border-radius: 8px;
        }
        .btn-request:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.3);
        }
        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
            color: #6c757d;
        }
        .empty-state i {
            font-size: 4rem;
            margin-bottom: 1rem;
            color: #dee2e6;
        }
        .status-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 500;
        }
        .status-pending {
            background: #fff3cd;
            color: #856404;
        }
        .status-approved {
            background: #d4edda;
            color: #155724;
        }
        .status-paid {
            background: #d1ecf1;
            color: #0c5460;
        }
        .status-cancelled {
            background: #f8d7da;
            color: #721c24;
        }
    </style>
</head>
<body>
    <?php include '../../includes/components/navbar.php'; ?>
    
    <div class="container earnings-container py-5">
        <div class="row mb-4">
            <div class="col-12">
                <h1 class="h3 fw-bold mb-0">Earnings & Payouts</h1>
                <p class="text-muted">Track your earnings and request payouts</p>
            </div>
        </div>

        <?php if (isset($_GET['payout_requested'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle me-2"></i>Payout request submitted successfully!
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>
        
        <?php if (isset($_GET['method_updated'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle me-2"></i>Payout method updated successfully!
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>
        
        <?php if (isset($_GET['method_deleted'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle me-2"></i>Payout method deleted successfully!
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <!-- Period Selector -->
        <div class="period-selector">
            <select class="form-select" style="max-width: 150px;" onchange="this.form.submit()" name="period">
                <option value="monthly" <?php echo $period === 'monthly' ? 'selected' : ''; ?>>Monthly</option>
                <option value="quarterly" <?php echo $period === 'quarterly' ? 'selected' : ''; ?>>Quarterly</option>
                <option value="yearly" <?php echo $period === 'yearly' ? 'selected' : ''; ?>>Yearly</option>
            </select>
            
            <select class="form-select" style="max-width: 150px;" onchange="this.form.submit()" name="month">
                <?php for ($i = 1; $i <= 12; $i++): ?>
                <option value="<?php echo $i; ?>" <?php echo $month == $i ? 'selected' : ''; ?>>
                    <?php echo date('F', mktime(0, 0, 0, $i, 1)); ?>
                </option>
                <?php endfor; ?>
            </select>
            
            <select class="form-select" style="max-width: 150px;" onchange="this.form.submit()" name="year">
                <?php for ($i = date('Y'); $i >= 2020; $i--): ?>
                <option value="<?php echo $i; ?>" <?php echo $year == $i ? 'selected' : ''; ?>>
                    <?php echo $i; ?>
                </option>
                <?php endfor; ?>
            </select>
            
            <a href="earnings.php" class="btn btn-outline-secondary">
                <i class="bi bi-calendar-event me-2"></i>Current Month
            </a>
        </div>

        <!-- Earnings Summary -->
        <div class="row mb-4">
            <div class="col-xl-3 col-md-6">
                <div class="stats-card total">
                    <div class="stats-icon total">
                        <i class="bi bi-cash-stack"></i>
                    </div>
                    <div class="stats-number">CFA <?php echo number_format($summary['total_earnings'], 2); ?></div>
                    <div class="stats-label">Total Earnings</div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="stats-card available">
                    <div class="stats-icon available">
                        <i class="bi bi-wallet2"></i>
                    </div>
                    <div class="stats-number">CFA <?php echo number_format($summary['available_balance'], 2); ?></div>
                    <div class="stats-label">Available Balance</div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="stats-card pending">
                    <div class="stats-icon pending">
                        <i class="bi bi-clock-history"></i>
                    </div>
                    <div class="stats-number">CFA <?php echo number_format($summary['pending_payouts'], 2); ?></div>
                    <div class="stats-label">Pending Payouts</div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="stats-card paid">
                    <div class="stats-icon paid">
                        <i class="bi bi-check-circle"></i>
                    </div>
                    <div class="stats-number">CFA <?php echo number_format($summary['total_paid'], 2); ?></div>
                    <div class="stats-label">Total Paid</div>
                </div>
            </div>
        </div>

        <!-- Payout Request Form -->
        <?php if ($summary['available_balance'] > 0 && !empty($payoutMethods)): ?>
        <div class="row mb-4">
            <div class="col-12">
                <div class="table-card">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h5 class="mb-0">Request Payout</h5>
                    </div>
                    <form method="POST" action="" class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">Available Balance</label>
                            <input type="text" class="form-control" value="CFA <?php echo number_format($summary['available_balance'], 2); ?>" readonly>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Amount to Withdraw *</label>
                            <input type="number" class="form-control" name="amount" 
                                   step="0.01" min="10" max="<?php echo $summary['available_balance']; ?>"
                                   placeholder="Enter amount" required>
                            <small class="text-muted">Minimum withdrawal: CFA 10</small>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Payout Method *</label>
                            <select class="form-select" name="payout_method_id" required>
                                <option value="">Select Method</option>
                                <?php foreach ($payoutMethods as $method): ?>
                                <option value="<?php echo $method['id']; ?>">
                                    <?php echo htmlspecialchars($method['account_name']); ?> 
                                    (<?php echo ucfirst($method['method_type']); ?>)
                                    <?php echo $method['is_default'] ? ' - Default' : ''; ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-12">
                            <button type="submit" name="request_payout" class="btn btn-request">
                                <i class="bi bi-send me-2"></i>Request Payout
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <div class="row">
            <!-- Earnings Chart -->
            <div class="col-lg-8 mb-4">
                <div class="chart-container">
                    <h5 class="mb-3">Earnings Overview</h5>
                    <canvas id="earningsChart"></canvas>
                </div>
                
                <!-- Earnings History -->
                <div class="table-card">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h5 class="mb-0">Recent Earnings</h5>
                        <a href="earnings-history.php" class="btn btn-outline-primary btn-sm">
                            View All <i class="bi bi-arrow-right ms-1"></i>
                        </a>
                    </div>
                    
                    <?php if (!empty($history)): ?>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Order #</th>
                                    <th>Customer</th>
                                    <th>Amount</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($history as $earning): ?>
                                <tr>
                                    <td><?php echo date('M d, Y', strtotime($earning['created_at'])); ?></td>
                                    <td>
                                        <a href="orders.php?order_id=<?php echo $earning['order_id']; ?>" 
                                           class="text-decoration-none">
                                            #<?php echo $earning['order_number']; ?>
                                        </a>
                                    </td>
                                    <td><?php echo htmlspecialchars($earning['customer_name']); ?></td>
                                    <td class="fw-bold">CFA <?php echo number_format($earning['amount'], 2); ?></td>
                                    <td>
                                        <?php
                                        $statusClass = '';
                                        switch ($earning['status']) {
                                            case 'pending':
                                                $statusClass = 'status-pending';
                                                break;
                                            case 'approved':
                                                $statusClass = 'status-approved';
                                                break;
                                            case 'paid':
                                                $statusClass = 'status-paid';
                                                break;
                                            case 'cancelled':
                                                $statusClass = 'status-cancelled';
                                                break;
                                        }
                                        ?>
                                        <span class="status-badge <?php echo $statusClass; ?>">
                                            <?php echo ucfirst($earning['status']); ?>
                                        </span>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php else: ?>
                    <div class="empty-state py-4">
                        <i class="bi bi-graph-up"></i>
                        <p class="text-muted">No earnings found for this period</p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Payout Methods & Upcoming Payouts -->
            <div class="col-lg-4">
                <!-- Payout Methods -->
                <div class="table-card mb-4">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h5 class="mb-0">Payout Methods</h5>
                        <button class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addMethodModal">
                            <i class="bi bi-plus-lg me-1"></i>Add Method
                        </button>
                    </div>
                    
                    <?php if (!empty($payoutMethods)): ?>
                        <?php foreach ($payoutMethods as $method): ?>
                        <div class="payout-method-card <?php echo $method['is_default'] ? 'default' : ''; ?>">
                            <div class="d-flex align-items-center mb-3">
                                <div class="method-icon <?php echo $method['method_type']; ?>">
                                    <?php if ($method['method_type'] === 'bank'): ?>
                                        <i class="bi bi-bank"></i>
                                    <?php elseif ($method['method_type'] === 'mobile_money'): ?>
                                        <i class="bi bi-phone"></i>
                                    <?php else: ?>
                                        <i class="bi bi-wallet2"></i>
                                    <?php endif; ?>
                                </div>
                                <div class="flex-grow-1">
                                    <h6 class="mb-1"><?php echo htmlspecialchars($method['account_name']); ?></h6>
                                    <p class="text-muted mb-0">
                                        <?php if ($method['method_type'] === 'bank'): ?>
                                            <?php echo htmlspecialchars($method['bank_name']); ?> • 
                                            <?php echo htmlspecialchars($method['account_number']); ?>
                                        <?php elseif ($method['method_type'] === 'mobile_money'): ?>
                                            <?php echo htmlspecialchars($method['provider']); ?> • 
                                            <?php echo htmlspecialchars($method['phone']); ?>
                                        <?php else: ?>
                                            <?php echo htmlspecialchars($method['email']); ?>
                                        <?php endif; ?>
                                    </p>
                                </div>
                            </div>
                            <div class="d-flex justify-content-between">
                                <?php if ($method['is_default']): ?>
                                    <span class="badge bg-primary">Default</span>
                                <?php else: ?>
                                    <a href="earnings.php?set_default=<?php echo $method['id']; ?>" 
                                       class="btn btn-sm btn-outline-primary">Set Default</a>
                                <?php endif; ?>
                                <div>
                                    <button class="btn btn-sm btn-outline-secondary" 
                                            data-bs-toggle="modal" 
                                            data-bs-target="#editMethodModal"
                                            onclick="editMethod(<?php echo htmlspecialchars(json_encode($method)); ?>)">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <a href="earnings.php?delete_method=<?php echo $method['id']; ?>" 
                                       class="btn btn-sm btn-outline-danger"
                                       onclick="return confirm('Delete this payout method?')">
                                        <i class="bi bi-trash"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                    <div class="empty-state py-4">
                        <i class="bi bi-credit-card"></i>
                        <p class="text-muted">No payout methods added</p>
                        <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addMethodModal">
                            <i class="bi bi-plus-lg me-1"></i>Add Your First Method
                        </button>
                    </div>
                    <?php endif; ?>
                </div>
                
                <!-- Upcoming Payouts -->
                <div class="table-card">
                    <h5 class="mb-3">Upcoming Payouts</h5>
                    
                    <?php if (!empty($upcomingPayouts)): ?>
                        <?php foreach ($upcomingPayouts as $payout): ?>
                        <div class="payout-method-card">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <strong>CFA <?php echo number_format($payout['amount'], 2); ?></strong>
                                <span class="status-badge status-pending">Pending</span>
                            </div>
                            <div class="d-flex justify-content-between align-items-center">
                                <small class="text-muted">
                                    Requested: <?php echo date('M d', strtotime($payout['requested_at'])); ?>
                                </small>
                                <small class="text-muted">
                                    <?php echo $payout['payout_method']; ?>
                                </small>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                    <div class="empty-state py-4">
                        <i class="bi bi-calendar-check"></i>
                        <p class="text-muted">No upcoming payouts</p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Add/Edit Payout Method Modal -->
    <div class="modal fade" id="addMethodModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form method="POST" action="" id="methodForm">
                    <div class="modal-header">
                        <h5 class="modal-title">Add Payout Method</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="payout_method_id" id="payout_method_id">
                        
                        <div class="mb-3">
                            <label class="form-label">Method Type *</label>
                            <select class="form-select" name="method_type" id="method_type" required>
                                <option value="">Select Type</option>
                                <option value="bank">Bank Transfer</option>
                                <option value="mobile_money">Mobile Money</option>
                                <option value="paypal">PayPal</option>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Account Name *</label>
                            <input type="text" class="form-control" name="account_name" id="account_name" required>
                        </div>
                        
                        <!-- Bank Transfer Fields -->
                        <div id="bankFields" style="display: none;">
                            <div class="row mb-3">
                                <div class="col-6">
                                    <label class="form-label">Bank Name *</label>
                                    <input type="text" class="form-control" name="bank_name" id="bank_name">
                                </div>
                                <div class="col-6">
                                    <label class="form-label">Bank Code</label>
                                    <input type="text" class="form-control" name="bank_code" id="bank_code">
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Account Number *</label>
                                <input type="text" class="form-control" name="account_number" id="account_number_bank">
                            </div>
                        </div>
                        
                        <!-- Mobile Money Fields -->
                        <div id="mobileFields" style="display: none;">
                            <div class="row mb-3">
                                <div class="col-6">
                                    <label class="form-label">Provider *</label>
                                    <select class="form-select" name="provider" id="provider">
                                        <option value="">Select Provider</option>
                                        <option value="mtn">MTN Mobile Money</option>
                                        <option value="orange">Orange Money</option>
                                        <option value="moov">Moov Money</option>
                                        <option value="wave">Wave</option>
                                    </select>
                                </div>
                                <div class="col-6">
                                    <label class="form-label">Phone Number *</label>
                                    <input type="tel" class="form-control" name="phone" id="phone">
                                </div>
                            </div>
                        </div>
                        
                        <!-- PayPal Fields -->
                        <div id="paypalFields" style="display: none;">
                            <div class="mb-3">
                                <label class="form-label">PayPal Email *</label>
                                <input type="email" class="form-control" name="email" id="paypal_email">
                            </div>
                        </div>
                        
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" name="is_default" id="is_default">
                            <label class="form-check-label" for="is_default">
                                Set as default payout method
                            </label>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="update_payout_method" class="btn btn-primary">Save Method</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Toggle method type fields
        document.getElementById('method_type').addEventListener('change', function() {
            const method = this.value;
            
            // Hide all fields
            document.getElementById('bankFields').style.display = 'none';
            document.getElementById('mobileFields').style.display = 'none';
            document.getElementById('paypalFields').style.display = 'none';
            
            // Clear required fields
            document.getElementById('bank_name').required = false;
            document.getElementById('account_number_bank').required = false;
            document.getElementById('provider').required = false;
            document.getElementById('phone').required = false;
            document.getElementById('paypal_email').required = false;
            
            // Show selected method fields
            if (method === 'bank') {
                document.getElementById('bankFields').style.display = 'block';
                document.getElementById('bank_name').required = true;
                document.getElementById('account_number_bank').required = true;
            } else if (method === 'mobile_money') {
                document.getElementById('mobileFields').style.display = 'block';
                document.getElementById('provider').required = true;
                document.getElementById('phone').required = true;
            } else if (method === 'paypal') {
                document.getElementById('paypalFields').style.display = 'block';
                document.getElementById('paypal_email').required = true;
            }
        });
        
        // Edit method
        function editMethod(method) {
            document.getElementById('payout_method_id').value = method.id;
            document.getElementById('method_type').value = method.method_type;
            document.getElementById('account_name').value = method.account_name;
            document.getElementById('is_default').checked = method.is_default === '1';
            
            // Trigger change to show correct fields
            document.getElementById('method_type').dispatchEvent(new Event('change'));
            
            // Fill method-specific fields
            setTimeout(() => {
                if (method.method_type === 'bank') {
                    document.getElementById('bank_name').value = method.bank_name || '';
                    document.getElementById('bank_code').value = method.bank_code || '';
                    document.getElementById('account_number_bank').value = method.account_number || '';
                } else if (method.method_type === 'mobile_money') {
                    document.getElementById('provider').value = method.provider || '';
                    document.getElementById('phone').value = method.phone || '';
                } else if (method.method_type === 'paypal') {
                    document.getElementById('paypal_email').value = method.email || '';
                }
            }, 100);
            
            // Update modal title
            document.querySelector('#addMethodModal .modal-title').textContent = 'Edit Payout Method';
            
            // Show modal
            new bootstrap.Modal(document.getElementById('addMethodModal')).show();
        }
        
        // Earnings Chart
        document.addEventListener('DOMContentLoaded', function() {
            const ctx = document.getElementById('earningsChart').getContext('2d');
            
            // Sample data - in real app, fetch from API
            const earningsData = {
                labels: ['Week 1', 'Week 2', 'Week 3', 'Week 4'],
                datasets: [{
                    label: 'Earnings (CFA)',
                    data: [<?php 
                        // Generate sample data based on period
                        if ($period === 'monthly') {
                            echo rand(50000, 150000) . ', ' . rand(80000, 200000) . ', ' . 
                                 rand(120000, 250000) . ', ' . rand(100000, 220000);
                        } elseif ($period === 'quarterly') {
                            echo rand(300000, 600000) . ', ' . rand(400000, 700000) . ', ' . 
                                 rand(500000, 800000) . ', ' . rand(350000, 650000);
                        } else {
                            echo rand(1000000, 3000000) . ', ' . rand(1500000, 3500000) . ', ' . 
                                 rand(2000000, 4000000) . ', ' . rand(1800000, 3800000);
                        }
                    ?>],
                    backgroundColor: 'rgba(102, 126, 234, 0.2)',
                    borderColor: 'rgba(102, 126, 234, 1)',
                    borderWidth: 2,
                    tension: 0.4,
                    fill: true
                }]
            };
            
            const earningsChart = new Chart(ctx, {
                type: 'line',
                data: earningsData,
                options: {
                    responsive: true,
                    plugins: {
                        legend: {
                            display: true
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: function(value) {
                                    return 'CFA ' + value.toLocaleString();
                                }
                            }
                        }
                    }
                }
            });
        });
    </script>
</body>
</html>