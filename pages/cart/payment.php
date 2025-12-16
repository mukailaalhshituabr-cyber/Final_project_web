<?php
require_once '../../config.php';
require_once '../../includes/classes/Order.php';
require_once '../../includes/classes/Cart.php';
require_once '../../includes/classes/StripePayment.php';

// Check authentication
if (!isset($_SESSION['user_id'])) {
    $_SESSION['redirect_to'] = '/pages/cart/payment.php';
    header('Location: ../auth/login.php');
    exit();
}

$order = new Order();
$cart = new Cart();
$stripe = new StripePayment();

// Get order summary
$orderId = $_SESSION['current_order_id'] ?? null;
$orderData = $orderId ? $order->getOrderById($orderId) : null;

// Handle payment submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['process_payment'])) {
    $paymentMethod = $_POST['payment_method'];
    $paymentResult = $stripe->processPayment([
        'amount' => $orderData['total_amount'] * 100, // Convert to cents
        'currency' => 'usd',
        'payment_method' => $paymentMethod,
        'order_id' => $orderId
    ]);
    
    if ($paymentResult['success']) {
        // Update order status
        $order->updatePaymentStatus($orderId, 'paid', $paymentResult['transaction_id']);
        
        // Clear cart
        $cart->clearCart($_SESSION['user_id']);
        
        // Redirect to success page
        header('Location: order-success.php?id=' . $orderId);
        exit();
    } else {
        $error = $paymentResult['message'];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment - <?php echo SITE_NAME; ?></title>
    <script src="https://js.stripe.com/v3/"></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            --success-gradient: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);
            --warning-gradient: linear-gradient(135deg, #fa709a 0%, #fee140 100%);
        }
        
        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
            color: #333;
        }
        
        .payment-container {
            max-width: 900px;
            margin: 2rem auto;
        }
        
        .payment-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.1);
            overflow: hidden;
            margin-bottom: 2rem;
        }
        
        .payment-header {
            background: var(--primary-gradient);
            color: white;
            padding: 2rem;
            text-align: center;
        }
        
        .payment-steps {
            display: flex;
            justify-content: center;
            gap: 2rem;
            padding: 2rem;
        }
        
        .step {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        .step-number {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #e5e7eb;
            color: #6b7280;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 1.2rem;
        }
        
        .step.active .step-number {
            background: var(--primary-gradient);
            color: white;
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.3);
        }
        
        .payment-methods {
            padding: 2rem;
        }
        
        .method-card {
            border: 2px solid #e5e7eb;
            border-radius: 15px;
            padding: 1.5rem;
            margin-bottom: 1rem;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .method-card:hover {
            border-color: #667eea;
            transform: translateY(-3px);
        }
        
        .method-card.selected {
            border-color: #667eea;
            background: rgba(102, 126, 234, 0.05);
            box-shadow: 0 10px 20px rgba(102, 126, 234, 0.1);
        }
        
        .method-icon {
            width: 60px;
            height: 60px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            margin-bottom: 1rem;
        }
        
        .stripe-icon {
            background: linear-gradient(135deg, #635bff, #8f6ffc);
            color: white;
        }
        
        .paypal-icon {
            background: linear-gradient(135deg, #003087, #009cde);
            color: white;
        }
        
        .card-icon {
            background: linear-gradient(135deg, #ed6a5a, #f4f1bb);
            color: white;
        }
        
        .payment-form {
            padding: 2rem;
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .card-element-container {
            border: 2px solid #e5e7eb;
            border-radius: 10px;
            padding: 1rem;
            transition: all 0.3s ease;
        }
        
        .card-element-container.focused {
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        
        .order-summary {
            background: #f8f9fa;
            border-radius: 15px;
            padding: 2rem;
            margin-top: 2rem;
        }
        
        .summary-item {
            display: flex;
            justify-content: space-between;
            padding: 0.75rem 0;
            border-bottom: 1px dashed #dee2e6;
        }
        
        .btn-pay {
            background: var(--primary-gradient);
            border: none;
            color: white;
            padding: 1rem 3rem;
            border-radius: 12px;
            font-weight: 600;
            font-size: 1.1rem;
            transition: all 0.3s ease;
            width: 100%;
        }
        
        .btn-pay:hover {
            transform: translateY(-3px);
            box-shadow: 0 15px 30px rgba(102, 126, 234, 0.3);
        }
        
        .btn-pay:disabled {
            opacity: 0.7;
            cursor: not-allowed;
        }
        
        .payment-security {
            background: #f8f9fa;
            padding: 1.5rem;
            border-radius: 12px;
            margin-top: 2rem;
            text-align: center;
        }
        
        .payment-error {
            color: #ef4444;
            font-size: 0.875rem;
            margin-top: 0.5rem;
        }
        
        .order-confirmation {
            text-align: center;
            padding: 4rem 2rem;
        }
        
        .confirmation-icon {
            font-size: 5rem;
            background: var(--success-gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 2rem;
        }
    </style>
</head>
<body>
    <?php include '../../includes/components/navbar.php'; ?>
    
    <div class="payment-container">
        <div class="payment-card">
            <!-- Payment Header -->
            <div class="payment-header">
                <h1 class="display-6 fw-bold mb-3">Complete Your Purchase</h1>
                <p class="lead mb-0">Secure payment powered by Stripe</p>
            </div>
            
            <!-- Payment Steps -->
            <div class="payment-steps">
                <div class="step active">
                    <div class="step-number">1</div>
                    <div>
                        <div class="fw-bold">Cart</div>
                        <small class="text-light">Review items</small>
                    </div>
                </div>
                <div class="step active">
                    <div class="step-number">2</div>
                    <div>
                        <div class="fw-bold">Checkout</div>
                        <small class="text-light">Details & shipping</small>
                    </div>
                </div>
                <div class="step active">
                    <div class="step-number">3</div>
                    <div>
                        <div class="fw-bold">Payment</div>
                        <small class="text-light">Secure payment</small>
                    </div>
                </div>
            </div>
            
            <!-- Payment Methods -->
            <div class="payment-methods">
                <h3 class="fw-bold mb-4">Select Payment Method</h3>
                
                <div class="row">
                    <div class="col-md-4">
                        <div class="method-card selected" data-method="stripe">
                            <div class="method-icon stripe-icon">
                                <i class="bi bi-credit-card"></i>
                            </div>
                            <h5 class="fw-bold">Credit Card</h5>
                            <p class="text-muted small">Pay with your credit or debit card</p>
                            <div class="d-flex gap-2">
                                <img src="https://cdn-icons-png.flaticon.com/512/349/349221.png" width="30" alt="Visa">
                                <img src="https://cdn-icons-png.flaticon.com/512/349/349228.png" width="30" alt="Mastercard">
                                <img src="https://cdn-icons-png.flaticon.com/512/349/349230.png" width="30" alt="Amex">
                            </div>
                        </div>
                    </div>
                    
                                            <div class="method-card" data-method="bank">
                            <div class="method-icon card-icon">
                                <i class="bi bi-bank"></i>
                            </div>
                            <h5 class="fw-bold">Bank Transfer</h5>
                            <p class="text-muted small">Direct bank transfer</p>
                            <small>2-3 business days</small>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Credit Card Form -->
            <div id="stripeForm" class="payment-form">
                <h4 class="fw-bold mb-4">Enter Card Details</h4>
                
                <?php if (isset($error)): ?>
                    <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>
                
                <form id="payment-form" method="POST">
                    <input type="hidden" name="payment_method" value="stripe">
                    
                    <div class="row">
                        <div class="col-md-12 mb-3">
                            <label class="form-label fw-bold">Cardholder Name</label>
                            <input type="text" 
                                   class="form-control" 
                                   id="cardholder-name" 
                                   placeholder="Name on card"
                                   required>
                        </div>
                        
                        <div class="col-md-12 mb-3">
                            <label class="form-label fw-bold">Card Number</label>
                            <div class="card-element-container" id="card-element">
                                <!-- Stripe Card Element will be inserted here -->
                            </div>
                            <div id="card-errors" class="payment-error" role="alert"></div>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">Expiry Date</label>
                            <input type="text" 
                                   class="form-control" 
                                   placeholder="MM/YY" 
                                   id="card-expiry"
                                   required>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">CVC</label>
                            <input type="text" 
                                   class="form-control" 
                                   placeholder="123" 
                                   id="card-cvc"
                                   required>
                        </div>
                    </div>
                    
                    <div class="form-check mb-4">
                        <input class="form-check-input" type="checkbox" id="save-card">
                        <label class="form-check-label" for="save-card">
                            Save this card for future purchases
                        </label>
                    </div>
                    
                    <!-- Order Summary -->
                    <div class="order-summary">
                        <h5 class="fw-bold mb-3">Order Summary</h5>
                        <?php if ($orderData): ?>
                            <div class="summary-item">
                                <span>Order #<?php echo $orderData['order_number']; ?></span>
                                <span class="fw-bold">$<?php echo number_format($orderData['total_amount'], 2); ?></span>
                            </div>
                            <div class="summary-item">
                                <span>Items</span>
                                <span><?php echo $orderData['item_count']; ?> items</span>
                            </div>
                            <div class="summary-item">
                                <span>Shipping</span>
                                <span class="text-success">FREE</span>
                            </div>
                            <div class="summary-item">
                                <span>Tax</span>
                                <span>$<?php echo number_format($orderData['total_amount'] * 0.08, 2); ?></span>
                            </div>
                            <div class="summary-item fw-bold fs-5 mt-3">
                                <span>Total</span>
                                <span>$<?php echo number_format($orderData['total_amount'] * 1.08, 2); ?></span>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Security Note -->
                    <div class="payment-security">
                        <div class="d-flex align-items-center justify-content-center gap-3 mb-3">
                            <i class="bi bi-shield-check text-success fs-4"></i>
                            <i class="bi bi-lock text-success fs-4"></i>
                            <i class="bi bi-credit-card-2-front text-success fs-4"></i>
                        </div>
                        <p class="mb-0 small">Your payment is secured with 256-bit SSL encryption. We never store your card details.</p>
                    </div>
                    
                    <!-- Submit Button -->
                    <div class="mt-4">
                        <button type="submit" 
                                name="process_payment" 
                                class="btn-pay" 
                                id="submit-button"
                                data-secret="<?php echo $stripe->getClientSecret($orderData['total_amount'] * 100); ?>">
                            <i class="bi bi-lock me-2"></i> 
                            Pay $<?php echo number_format($orderData['total_amount'] * 1.08, 2); ?>
                            <span id="button-text">Complete Payment</span>
                            <div class="spinner-border spinner-border-sm text-light d-none" id="spinner"></div>
                        </button>
                        
                        <p class="text-center mt-3 small text-muted">
                            By completing your purchase, you agree to our 
                            <a href="#">Terms of Service</a> and 
                            <a href="#">Privacy Policy</a>
                        </p>
                    </div>
                </form>
            </div>
            
            <!-- PayPal Form (Hidden by default) -->
            <div id="paypalForm" class="payment-form d-none">
                <div class="text-center py-5">
                    <div class="paypal-icon d-inline-flex p-4 rounded-circle mb-4">
                        <i class="bi bi-paypal fs-1"></i>
                    </div>
                    <h4 class="fw-bold mb-3">Redirecting to PayPal</h4>
                    <p class="text-muted mb-4">You'll be redirected to PayPal to complete your payment securely.</p>
                    <div id="paypal-button-container"></div>
                </div>
            </div>
            
            <!-- Bank Transfer Form (Hidden by default) -->
            <div id="bankForm" class="payment-form d-none">
                <div class="alert alert-info">
                    <i class="bi bi-info-circle me-2"></i>
                    Please transfer the payment to the account below and include your order number in the reference.
                </div>
                
                <div class="bank-details p-4 border rounded mb-4">
                    <h5 class="fw-bold mb-3">Bank Transfer Details</h5>
                    <div class="row">
                        <div class="col-md-6">
                            <table class="table table-borderless">
                                <tr>
                                    <td><strong>Bank Name:</strong></td>
                                    <td>Global Fashion Bank</td>
                                </tr>
                                <tr>
                                    <td><strong>Account Name:</strong></td>
                                    <td><?php echo SITE_NAME; ?> Inc.</td>
                                </tr>
                                <tr>
                                    <td><strong>Account Number:</strong></td>
                                    <td>1234567890</td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <table class="table table-borderless">
                                <tr>
                                    <td><strong>SWIFT Code:</strong></td>
                                    <td>GFBIUS33</td>
                                </tr>
                                <tr>
                                    <td><strong>Routing Number:</strong></td>
                                    <td>021000021</td>
                                </tr>
                                <tr>
                                    <td><strong>Reference:</strong></td>
                                    <td class="text-primary">ORDER-<?php echo $orderData['order_number']; ?></td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>
                
                <div class="text-center">
                    <button type="button" class="btn btn-outline-primary">
                        <i class="bi bi-download me-2"></i> Download Invoice
                    </button>
                    <button type="button" class="btn btn-outline-secondary ms-2">
                        <i class="bi bi-envelope me-2"></i> Email Details
                    </button>
                </div>
            </div>
        </div>
        
        <!-- Back to Cart -->
        <div class="text-center mt-4">
            <a href="index.php" class="text-decoration-none">
                <i class="bi bi-arrow-left me-2"></i> Return to Cart
            </a>
        </div>
    </div>
    
    <!-- Stripe.js Integration -->
    <script>
        // Stripe configuration
        const stripe = Stripe('<?php echo STRIPE_PUBLISHABLE_KEY; ?>');
        const elements = stripe.elements();
        
        // Create card element
        const cardElement = elements.create('card', {
            style: {
                base: {
                    fontSize: '16px',
                    color: '#32325d',
                    fontFamily: '"Poppins", sans-serif',
                    '::placeholder': {
                        color: '#aab7c4'
                    }
                },
                invalid: {
                    color: '#ef4444'
                }
            }
        });
        
        // Mount card element
        cardElement.mount('#card-element');
        
        // Handle real-time validation errors
        cardElement.addEventListener('change', function(event) {
            const displayError = document.getElementById('card-errors');
            if (event.error) {
                displayError.textContent = event.error.message;
            } else {
                displayError.textContent = '';
            }
        });
        
        // Handle form submission
        const form = document.getElementById('payment-form');
        form.addEventListener('submit', async function(event) {
            event.preventDefault();
            
            // Disable submit button
            const submitButton = document.getElementById('submit-button');
            const buttonText = document.getElementById('button-text');
            const spinner = document.getElementById('spinner');
            
            submitButton.disabled = true;
            buttonText.textContent = 'Processing...';
            spinner.classList.remove('d-none');
            
            try {
                // Create payment method
                const {paymentMethod, error} = await stripe.createPaymentMethod({
                    type: 'card',
                    card: cardElement,
                    billing_details: {
                        name: document.getElementById('cardholder-name').value
                    }
                });
                
                if (error) {
                    // Show error
                    document.getElementById('card-errors').textContent = error.message;
                    submitButton.disabled = false;
                    buttonText.textContent = 'Complete Payment';
                    spinner.classList.add('d-none');
                } else {
                    // Add payment method ID to form
                    const hiddenInput = document.createElement('input');
                    hiddenInput.setAttribute('type', 'hidden');
                    hiddenInput.setAttribute('name', 'stripe_payment_method');
                    hiddenInput.setAttribute('value', paymentMethod.id);
                    form.appendChild(hiddenInput);
                    
                    // Submit the form
                    form.submit();
                }
            } catch (error) {
                console.error('Error:', error);
                submitButton.disabled = false;
                buttonText.textContent = 'Complete Payment';
                spinner.classList.add('d-none');
            }
        });
        
        // Payment method selection
        document.querySelectorAll('.method-card').forEach(card => {
            card.addEventListener('click', function() {
                // Remove selected class from all cards
                document.querySelectorAll('.method-card').forEach(c => {
                    c.classList.remove('selected');
                });
                
                // Add selected class to clicked card
                this.classList.add('selected');
                
                // Get selected method
                const method = this.dataset.method;
                
                // Show corresponding form
                document.querySelectorAll('.payment-form').forEach(form => {
                    form.classList.add('d-none');
                });
                
                document.getElementById(method + 'Form').classList.remove('d-none');
            });
        });
        
        // Format card expiry
        document.getElementById('card-expiry').addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            if (value.length >= 2) {
                value = value.slice(0,2) + '/' + value.slice(2,4);
            }
            e.target.value = value;
        });
        
        // Format CVC
        document.getElementById('card-cvc').addEventListener('input', function(e) {
            e.target.value = e.target.value.replace(/\D/g, '').slice(0,4);
        });
        
        // PayPal Integration
        paypal.Buttons({
            createOrder: function(data, actions) {
                return actions.order.create({
                    purchase_units: [{
                        amount: {
                            value: '<?php echo number_format($orderData['total_amount'] * 1.08, 2); ?>'
                        }
                    }]
                });
            },
            onApprove: function(data, actions) {
                return actions.order.capture().then(function(details) {
                    // Submit form with PayPal details
                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.innerHTML = `
                        <input type="hidden" name="payment_method" value="paypal">
                        <input type="hidden" name="paypal_order_id" value="${data.orderID}">
                        <input type="hidden" name="paypal_payer_id" value="${details.payer.payer_id}">
                        <input type="hidden" name="process_payment" value="1">
                    `;
                    document.body.appendChild(form);
                    form.submit();
                });
            }
        }).render('#paypal-button-container');
        
        // Add animation to payment card
        document.querySelector('.payment-card').classList.add('animate__animated', 'animate__fadeInUp');
    </script>
    
    <!-- Animation Library -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://www.paypal.com/sdk/js?client-id=YOUR_PAYPAL_CLIENT_ID&currency=USD"></script>
</body>
</html>


<?php
/*require_once '../../config.php';
require_once '../../includes/classes/Order.php';
require_once '../../includes/classes/Cart.php';
require_once '../../includes/classes/StripePayment.php';

// Check authentication
if (!isset($_SESSION['user_id'])) {
    $_SESSION['redirect_to'] = '/pages/cart/payment.php';
    header('Location: ../auth/login.php');
    exit();
}

$order = new Order();
$cart = new Cart();
$stripe = new StripePayment();

// Get order summary
$orderId = $_SESSION['current_order_id'] ?? null;
$orderData = $orderId ? $order->getOrderById($orderId) : null;

// Handle payment submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['process_payment'])) {
    $paymentMethod = $_POST['payment_method'];
    $paymentResult = $stripe->processPayment([
        'amount' => $orderData['total_amount'] * 100, // Convert to cents
        'currency' => 'usd',
        'payment_method' => $paymentMethod,
        'order_id' => $orderId
    ]);
    
    if ($paymentResult['success']) {
        // Update order status
        $order->updatePaymentStatus($orderId, 'paid', $paymentResult['transaction_id']);
        
        // Clear cart
        $cart->clearCart($_SESSION['user_id']);
        
        // Redirect to success page
        header('Location: order-success.php?id=' . $orderId);
        exit();
    } else {
        $error = $paymentResult['message'];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment - <?php echo SITE_NAME; ?></title>
    <script src="https://js.stripe.com/v3/"></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            --success-gradient: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);
            --warning-gradient: linear-gradient(135deg, #fa709a 0%, #fee140 100%);
        }
        
        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
            color: #333;
        }
        
        .payment-container {
            max-width: 900px;
            margin: 2rem auto;
        }
        
        .payment-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.1);
            overflow: hidden;
            margin-bottom: 2rem;
        }
        
        .payment-header {
            background: var(--primary-gradient);
            color: white;
            padding: 2rem;
            text-align: center;
        }
        
        .payment-steps {
            display: flex;
            justify-content: center;
            gap: 2rem;
            padding: 2rem;
        }
        
        .step {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        .step-number {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #e5e7eb;
            color: #6b7280;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 1.2rem;
        }
        
        .step.active .step-number {
            background: var(--primary-gradient);
            color: white;
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.3);
        }
        
        .payment-methods {
            padding: 2rem;
        }
        
        .method-card {
            border: 2px solid #e5e7eb;
            border-radius: 15px;
            padding: 1.5rem;
            margin-bottom: 1rem;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .method-card:hover {
            border-color: #667eea;
            transform: translateY(-3px);
        }
        
        .method-card.selected {
            border-color: #667eea;
            background: rgba(102, 126, 234, 0.05);
            box-shadow: 0 10px 20px rgba(102, 126, 234, 0.1);
        }
        
        .method-icon {
            width: 60px;
            height: 60px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            margin-bottom: 1rem;
        }
        
        .stripe-icon {
            background: linear-gradient(135deg, #635bff, #8f6ffc);
            color: white;
        }
        
        .paypal-icon {
            background: linear-gradient(135deg, #003087, #009cde);
            color: white;
        }
        
        .card-icon {
            background: linear-gradient(135deg, #ed6a5a, #f4f1bb);
            color: white;
        }
        
        .payment-form {
            padding: 2rem;
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .card-element-container {
            border: 2px solid #e5e7eb;
            border-radius: 10px;
            padding: 1rem;
            transition: all 0.3s ease;
        }
        
        .card-element-container.focused {
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        
        .order-summary {
            background: #f8f9fa;
            border-radius: 15px;
            padding: 2rem;
            margin-top: 2rem;
        }
        
        .summary-item {
            display: flex;
            justify-content: space-between;
            padding: 0.75rem 0;
            border-bottom: 1px dashed #dee2e6;
        }
        
        .btn-pay {
            background: var(--primary-gradient);
            border: none;
            color: white;
            padding: 1rem 3rem;
            border-radius: 12px;
            font-weight: 600;
            font-size: 1.1rem;
            transition: all 0.3s ease;
            width: 100%;
        }
        
        .btn-pay:hover {
            transform: translateY(-3px);
            box-shadow: 0 15px 30px rgba(102, 126, 234, 0.3);
        }
        
        .btn-pay:disabled {
            opacity: 0.7;
            cursor: not-allowed;
        }
        
        .payment-security {
            background: #f8f9fa;
            padding: 1.5rem;
            border-radius: 12px;
            margin-top: 2rem;
            text-align: center;
        }
        
        .payment-error {
            color: #ef4444;
            font-size: 0.875rem;
            margin-top: 0.5rem;
        }
        
        .order-confirmation {
            text-align: center;
            padding: 4rem 2rem;
        }
        
        .confirmation-icon {
            font-size: 5rem;
            background: var(--success-gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 2rem;
        }
    </style>
</head>
<body>
    <?php include '../../includes/components/navbar.php'; ?>
    
    <div class="payment-container">
        <div class="payment-card">
            <!-- Payment Header -->
            <div class="payment-header">
                <h1 class="display-6 fw-bold mb-3">Complete Your Purchase</h1>
                <p class="lead mb-0">Secure payment powered by Stripe</p>
            </div>
            
            <!-- Payment Steps -->
            <div class="payment-steps">
                <div class="step active">
                    <div class="step-number">1</div>
                    <div>
                        <div class="fw-bold">Cart</div>
                        <small class="text-light">Review items</small>
                    </div>
                </div>
                <div class="step active">
                    <div class="step-number">2</div>
                    <div>
                        <div class="fw-bold">Checkout</div>
                        <small class="text-light">Details & shipping</small>
                    </div>
                </div>
                <div class="step active">
                    <div class="step-number">3</div>
                    <div>
                        <div class="fw-bold">Payment</div>
                        <small class="text-light">Secure payment</small>
                    </div>
                </div>
            </div>
            
            <!-- Payment Methods -->
            <div class="payment-methods">
                <h3 class="fw-bold mb-4">Select Payment Method</h3>
                
                <div class="row">
                    <div class="col-md-4">
                        <div class="method-card selected" data-method="stripe">
                            <div class="method-icon stripe-icon">
                                <i class="bi bi-credit-card"></i>
                            </div>
                            <h5 class="fw-bold">Credit Card</h5>
                            <p class="text-muted small">Pay with your credit or debit card</p>
                            <div class="d-flex gap-2">
                                <img src="https://cdn-icons-png.flaticon.com/512/349/349221.png" width="30" alt="Visa">
                                <img src="https://cdn-icons-png.flaticon.com/512/349/349228.png" width="30" alt="Mastercard">
                                <img src="https://cdn-icons-png.flaticon.com/512/349/349230.png" width="30" alt="Amex">
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-4">
                        <div class="method-card" data-method="paypal">
                            <div class="method-icon paypal-icon">
                                <i class="bi bi-paypal"></i>
                            </div>
                            <h5 class="fw-bold">PayPal</h5>
                            <p class="text-muted small">Fast and secure PayPal payment</p>
                            <img src="https://cdn-icons-png.flaticon.com/512/217/217425.png" width="60" alt="PayPal">
                        </div>
                    </div>
                    
                    <div class="col-md-4">
                        <div class="method-card" data-method="card">
                            <div class="method-icon card-icon">
                                <i class="bi bi-credit-card-2-front"></i>
                            </div>
                            <h5 class="fw-bold">Other Cards</h5>
                            <p class="text-muted small">Visa, Mastercard, Amex, Discover</p>
                            <div class="d-flex gap-2">
                                <img src="https://cdn-icons-png.flaticon.com/512/349/349221.png" width="30" alt="Visa">
                                <img src="https://cdn-icons-png.flaticon.com/512/349/349228.png" width="30" alt="Mastercard">
                                <img src="https://cdn-icons-png.flaticon.com/512/349/349230.png" width="30" alt="Amex">
                                <img src="https://cdn-icons-png.flaticon.com/512/349/349223.png" width="30" alt="Discover">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Payment Form -->
            <div class="payment-form">
                <h3 class="fw-bold mb-4">Payment Details</h3>
                <form id="payment-form" method="POST" action="">
                    <input type="hidden" name="process_payment" value="1">
                    <input type="hidden" id="payment_method" name="payment_method" value="stripe">
                    
                    <div id="card-element-container" class="card-element-container mb-3">
                        <!-- Stripe Card Element will be inserted here -->
                    </div>
                    <div id="card-errors" class="payment-error" role="alert"></div>
                    
                    <button type="submit" class="btn-pay mt-4" id="submit-button">Pay $<?php echo number_format($orderData['total_amount'], 2); ?></button>
                </form>
            </div>
        </div>

        <!-- Order Summary -->
        <div class="order-summary">
            <h4 class="fw-bold mb-4">Order Summary</h4>
            <div class="summary-item">
                <div>Subtotal</div>
                <div>$<?php echo number_format($orderData['subtotal'], 2); ?></div>
            </div>
            <div class="summary-item">
                <div>Shipping</div>
                <div>$<?php echo number_format($orderData['shipping_fee'], 2); ?></div>
            </div>
            <div class="summary-item">
                <div>Tax</div>
                <div>$<?php echo number_format($orderData['tax_amount'], 2); ?></div>
            </div>
            <div class="summary-item fw-bold">
                <div>Total</div>
                <div>$<?php echo number_format($orderData['total_amount'], 2); ?></div>
            </div>
        </div>
    </div>
    <?php include '../../includes/components/footer.php'; ?>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Stripe initialization
        const stripe = Stripe('<?php echo STRIPE_PUBLISHABLE_KEY; ?>');
        const elements = stripe.elements();
        const cardElement = elements.create('card');
        cardElement.mount('#card-element-container');

        // Handle real-time validation errors from the card Element.
        cardElement.on('change', function(event) {
            const displayError = document.getElementById('card-errors');
            if (event.error) {
                displayError.textContent = event.error.message;
            } else {
                displayError.textContent = '';
            }
        });

        // Payment method selection
        $('.method-card').on('click', function() {
            $('.method-card').removeClass('selected');
            $(this).addClass('selected');
            $('#payment_method').val($(this).data('method'));
        });

        // Handle form submission
        const form = document.getElementById('payment-form');
        form.addEventListener('submit', function(event) {
            event.preventDefault();
            $('#submit-button').disabled = true;

            stripe.createToken(cardElement).then(function(result) {
                if (result.error) {
                    // Inform the user if there was an error.
                    const errorElement = document.getElementById('card-errors');
                    errorElement.textContent = result.error.message;
                    $('#submit-button').disabled = false;
                } else {
                    // Send the token to your server.
                    const hiddenInput = document.createElement('input');
                    hiddenInput.setAttribute('type', 'hidden');
                    hiddenInput.setAttribute('name', 'stripeToken');
                    hiddenInput.setAttribute('value', result.token.id);
                    form.appendChild(hiddenInput);

                    // Submit the form
                    form.submit();
                }
            });
        });
    </script>
</body>
</html>
*/
