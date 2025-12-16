<?php
require_once '../../config.php';
require_once '../../includes/classes/Order.php';
require_once '../../includes/classes/Product.php';

// Check authentication
if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit();
}

$orderId = $_GET['id'] ?? null;
$order = new Order();
$product = new Product();

$orderData = $orderId ? $order->getOrderById($orderId) : null;
$orderItems = $orderId ? $order->getOrderItems($orderId) : [];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Confirmed! - <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --success-gradient: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);
            --primary-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        
        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
        }
        
        .success-container {
            max-width: 800px;
            margin: 0 auto;
        }
        
        .success-card {
            background: white;
            border-radius: 25px;
            box-shadow: 0 30px 80px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        
        .success-header {
            background: var(--success-gradient);
            color: white;
            padding: 3rem 2rem;
            text-align: center;
            position: relative;
            overflow: hidden;
        }
        
        .success-header::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255,255,255,0.1) 1px, transparent 1px);
            background-size: 20px 20px;
            animation: float 20s linear infinite;
        }
        
        @keyframes float {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        .success-icon {
            font-size: 5rem;
            margin-bottom: 1.5rem;
            animation: bounce 1s ease infinite alternate;
        }
        
        @keyframes bounce {
            0% { transform: translateY(0); }
            100% { transform: translateY(-20px); }
        }
        
        .confetti {
            position: absolute;
            width: 10px;
            height: 10px;
            background: rgba(255,255,255,0.5);
            border-radius: 50%;
            animation: confetti 3s linear forwards;
        }
        
        @keyframes confetti {
            0% { transform: translateY(0) rotate(0deg); opacity: 1; }
            100% { transform: translateY(100vh) rotate(720deg); opacity: 0; }
        }
        
        .order-details {
            padding: 2.5rem;
        }
        
        .detail-card {
            background: #f8f9fa;
            border-radius: 15px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            border-left: 4px solid #43e97b;
        }
        
        .tracking-steps {
            display: flex;
            justify-content: space-between;
            position: relative;
            margin: 2rem 0;
        }
        
        .tracking-steps::before {
            content: '';
            position: absolute;
            top: 15px;
            left: 0;
            right: 0;
            height: 2px;
            background: #e5e7eb;
            z-index: 1;
        }
        
        .step {
            text-align: center;
            position: relative;
            z-index: 2;
        }
        
        .step-number {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            background: white;
            border: 2px solid #e5e7eb;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 0.5rem;
            font-weight: bold;
        }
        
        .step.active .step-number {
            background: var(--success-gradient);
            border-color: #43e97b;
            color: white;
        }
        
        .order-items {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            margin: 1.5rem 0;
            border: 1px solid #e5e7eb;
        }
        
        .btn-continue {
            background: var(--primary-gradient);
            border: none;
            color: white;
            padding: 1rem 2.5rem;
            border-radius: 50px;
            font-weight: 600;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 10px;
        }
        
        .btn-continue:hover {
            transform: translateY(-3px);
            box-shadow: 0 15px 30px rgba(102, 126, 234, 0.3);
            color: white;
        }
        
        .whats-next {
            background: linear-gradient(135deg, #667eea20, #764ba220);
            border-radius: 15px;
            padding: 2rem;
            margin-top: 2rem;
        }
        
        .order-id {
            font-family: monospace;
            background: #f8f9fa;
            padding: 0.5rem 1rem;
            border-radius: 10px;
            font-size: 1.2rem;
            letter-spacing: 2px;
        }
    </style>
</head>
<body>
    <div class="container success-container">
        <div class="success-card">
            <!-- Success Header -->
            <div class="success-header">
                <i class="bi bi-check-circle success-icon"></i>
                <h1 class="display-5 fw-bold mb-3">Order Confirmed!</h1>
                <p class="lead mb-4">Thank you for your purchase. Your order is being processed.</p>
                <div class="order-id">ORDER #<?php echo $orderData['order_number'] ?? 'N/A'; ?></div>
            </div>
            
            <!-- Order Details -->
            <div class="order-details">
                <div class="row">
                    <div class="col-md-6">
                        <div class="detail-card">
                            <h5 class="fw-bold mb-3"><i class="bi bi-truck me-2"></i> Shipping Details</h5>
                            <p class="mb-1"><strong>Delivery Address:</strong></p>
                            <p class="text-muted"><?php echo nl2br(htmlspecialchars($orderData['shipping_address'] ?? 'Not specified')); ?></p>
                            <p class="mb-0"><strong>Estimated Delivery:</strong> 5-7 business days</p>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="detail-card">
                            <h5 class="fw-bold mb-3"><i class="bi bi-credit-card me-2"></i> Payment Details</h5>
                            <p class="mb-1"><strong>Payment Method:</strong></p>
                            <p class="text-muted"><?php echo $orderData['payment_method'] ?? 'Credit Card'; ?></p>
                            <p class="mb-1"><strong>Amount Paid:</strong></p>
                            <h4 class="text-success">$<?php echo number_format($orderData['total_amount'] * 1.08, 2); ?></h4>
                            <p class="mb-0 text-success small"><i class="bi bi-check-circle"></i> Payment Successful</p>
                        </div>
                    </div>
                </div>
                
                <!-- Order Tracking -->
                <div class="tracking-steps">
                    <div class="step active">
                        <div class="step-number">1</div>
                        <div>
                            <small>Ordered</small>
                            <div class="fw-bold">Today</div>
                        </div>
                    </div>
                    <div class="step">
                        <div class="step-number">2</div>
                        <div>
                            <small>Processing</small>
                            <div class="fw-bold">1-2 days</div>
                        </div>
                    </div>
                    <div class="step">
                        <div class="step-number">3</div>
                        <div>
                            <small>Shipped</small>
                            <div class="fw-bold">2-3 days</div>
                        </div>
                    </div>
                    <div class="step">
                        <div class="step-number">4</div>
                        <div>
                            <small>Delivered</small>
                            <div class="fw-bold">5-7 days</div>
                        </div>
                    </div>
                </div>
                
                <!-- Order Items -->
                <div class="order-items">
                    <h5 class="fw-bold mb-3">Order Items</h5>
                    <?php if (!empty($orderItems)): ?>
                        <?php foreach ($orderItems as $item): ?>
                            <div class="row align-items-center mb-3 pb-3 border-bottom">
                                <div class="col-2">
                                    <img src="<?php echo SITE_URL; ?>/assets/images/products/<?php echo $item['image']; ?>" 
                                         class="img-fluid rounded" 
                                         alt="<?php echo htmlspecialchars($item['title']); ?>"
                                         style="height: 80px; object-fit: cover;">
                                </div>
                                <div class="col-6">
                                    <h6 class="fw-bold mb-1"><?php echo htmlspecialchars($item['title']); ?></h6>
                                    <p class="text-muted small mb-0">Tailor: <?php echo htmlspecialchars($item['tailor_name']); ?></p>
                                    <?php if ($item['customization_details']): ?>
                                        <small class="text-primary">Custom: <?php echo htmlspecialchars($item['customization_details']); ?></small>
                                    <?php endif; ?>
                                </div>
                                <div class="col-2 text-center">
                                    <span class="fw-bold">x<?php echo $item['quantity']; ?></span>
                                </div>
                                <div class="col-2 text-end">
                                    <span class="fw-bold">$<?php echo number_format($item['price'], 2); ?></span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                
                <!-- Next Steps -->
                <div class="whats-next">
                    <h5 class="fw-bold mb-3"><i class="bi bi-info-circle me-2"></i> What's Next?</h5>
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <div class="d-flex align-items-start">
                                <i class="bi bi-envelope text-primary fs-4 me-3"></i>
                                <div>
                                    <h6 class="fw-bold">Order Confirmation</h6>
                                    <p class="small mb-0">Check your email for order details</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <div class="d-flex align-items-start">
                                <i class="bi bi-chat-dots text-success fs-4 me-3"></i>
                                <div>
                                    <h6 class="fw-bold">Chat with Tailor</h6>
                                    <p class="small mb-0">Directly message your tailor</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <div class="d-flex align-items-start">
                                <i class="bi bi-clock-history text-warning fs-4 me-3"></i>
                                <div>
                                    <h6 class="fw-bold">Track Order</h6>
                                    <p class="small mb-0">Monitor order progress</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Actions -->
                <div class="text-center mt-4">
                    <a href="<?php echo SITE_URL; ?>/pages/customer/orders.php" class="btn-continue me-3">
                        <i class="bi bi-eye"></i> View Order Status
                    </a>
                    <a href="<?php echo SITE_URL; ?>/pages/products/" class="btn btn-outline-primary">
                        <i class="bi bi-bag"></i> Continue Shopping
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Confetti Animation -->
    <script>
        // Create confetti
        function createConfetti() {
            const colors = ['#43e97b', '#38f9d7', '#667eea', '#764ba2', '#f093fb', '#f5576c'];
            for (let i = 0; i < 50; i++) {
                const confetti = document.createElement('div');
                confetti.className = 'confetti';
                confetti.style.left = Math.random() * 100 + 'vw';
                confetti.style.background = colors[Math.floor(Math.random() * colors.length)];
                confetti.style.animationDuration = (Math.random() * 2 + 1) + 's';
                confetti.style.width = Math.random() * 10 + 5 + 'px';
                confetti.style.height = confetti.style.width;
                document.body.appendChild(confetti);
                
                // Remove after animation
                setTimeout(() => confetti.remove(), 3000);
            }
        }
        
                // Create confetti on page load
        document.addEventListener('DOMContentLoaded', function() {
            createConfetti();
            
            // Animate success icon
            const successIcon = document.querySelector('.success-icon');
            setInterval(() => {
                successIcon.style.animation = 'none';
                setTimeout(() => {
                    successIcon.style.animation = 'bounce 1s ease infinite alternate';
                }, 10);
            }, 3000);
            
            // Add floating animation to cards
            const cards = document.querySelectorAll('.detail-card, .order-items');
            cards.forEach((card, index) => {
                card.style.animation = `fadeInUp 0.5s ease ${index * 0.2}s both`;
            });
        });
        
        // CSS for fadeInUp animation
        const style = document.createElement('style');
        style.textContent = `
            @keyframes fadeInUp {
                from {
                    opacity: 0;
                    transform: translateY(20px);
                }
                to {
                    opacity: 1;
                    transform: translateY(0);
                }
            }
            
            .order-items {
                animation: fadeInUp 0.5s ease 0.4s both;
            }
            
            .whats-next {
                animation: fadeInUp 0.5s ease 0.6s both;
            }
        `;
        document.head.appendChild(style);
        
        // Share order functionality
        function shareOrder() {
            const orderNumber = '<?php echo $orderData['order_number'] ?? ''; ?>';
            const shareText = `I just ordered custom clothing from <?php echo SITE_NAME; ?>! Order #${orderNumber}`;
            
            if (navigator.share) {
                navigator.share({
                    title: 'My Order from <?php echo SITE_NAME; ?>',
                    text: shareText,
                    url: window.location.href
                });
            } else {
                // Fallback: copy to clipboard
                navigator.clipboard.writeText(shareText);
                alert('Order details copied to clipboard!');
            }
        }
    </script>
    
    <!-- Add confetti on button hover -->
    <script>
        document.querySelector('.btn-continue').addEventListener('mouseenter', function() {
            createConfetti();
        });
    </script>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
    </script>
</body>
</html>