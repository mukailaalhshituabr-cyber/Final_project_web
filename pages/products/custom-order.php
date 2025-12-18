<?php
// CUSTOM ORDER PAGE
require_once '../../config.php';
require_once '../../includes/classes/Database.php';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Collect form data
    $measurements = [
        'customer_name' => $_POST['customer_name'] ?? '',
        'email' => $_POST['email'] ?? '',
        'phone' => $_POST['phone'] ?? '',
        'garment_type' => $_POST['garment_type'] ?? '',
        'fabric_type' => $_POST['fabric_type'] ?? '',
        'color' => $_POST['color'] ?? '',
        
        // Body measurements
        'shoulder' => $_POST['shoulder'] ?? '',
        'chest' => $_POST['chest'] ?? '',
        'waist' => $_POST['waist'] ?? '',
        'hips' => $_POST['hips'] ?? '',
        'arm_length' => $_POST['arm_length'] ?? '',
        'inseam' => $_POST['inseam'] ?? '',
        'neck' => $_POST['neck'] ?? '',
        
        // Additional preferences
        'style_preference' => $_POST['style_preference'] ?? '',
        'special_instructions' => $_POST['special_instructions'] ?? '',
        'budget_range' => $_POST['budget_range'] ?? '',
        'delivery_date' => $_POST['delivery_date'] ?? '',
    ];
    
    // In a real application, you would:
    // 1. Save to database
    // 2. Send email notification
    // 3. Process payment if needed
    
    // For demo purposes, we'll just show success message
    $order_success = true;
    $order_id = 'CUST-' . date('Ymd') . '-' . rand(1000, 9999);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Custom Clothing Order - Tailor Shop</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
    :root {
        --primary: #667eea;
        --secondary: #764ba2;
    }
    
    body {
        background: #f8fafc;
        font-family: 'Segoe UI', system-ui, sans-serif;
    }
    
    .page-header {
        background: linear-gradient(135deg, var(--primary), var(--secondary));
        color: white;
        padding: 3rem 0;
        position: relative;
        overflow: hidden;
    }
    
    .page-header::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: url("data:image/svg+xml,%3Csvg width='100' height='100' viewBox='0 0 100 100' xmlns='http://www.w3.org/2000/svg'%3E%3Cpath d='M11 18c3.866 0 7-3.134 7-7s-3.134-7-7-7-7 3.134-7 7 3.134 7 7 7zm48 25c3.866 0 7-3.134 7-7s-3.134-7-7-7-7 3.134-7 7 3.134 7 7 7zm-43-7c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zm63 31c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zM34 90c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zm56-76c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zM12 86c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm28-65c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm23-11c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zm-6 60c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm29 22c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zM32 63c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zm57-13c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zm-9-21c1.105 0 2-.895 2-2s-.895-2-2-2-2 .895-2 2 .895 2 2 2zM60 91c1.105 0 2-.895 2-2s-.895-2-2-2-2 .895-2 2 .895 2 2 2zM35 41c1.105 0 2-.895 2-2s-.895-2-2-2-2 .895-2 2 .895 2 2 2zM12 60c1.105 0 2-.895 2-2s-.895-2-2-2-2 .895-2 2 .895 2 2 2z' fill='%23ffffff' fill-opacity='0.1' fill-rule='evenodd'/%3E%3C/svg%3E");
        opacity: 0.3;
    }
    
    .custom-order-container {
        max-width: 800px;
        margin: 0 auto;
    }
    
    .order-card {
        background: white;
        border-radius: 20px;
        padding: 2.5rem;
        margin: -2rem auto 2rem;
        box-shadow: 0 15px 40px rgba(0,0,0,0.1);
        position: relative;
        z-index: 10;
    }
    
    .form-step {
        display: none;
    }
    
    .form-step.active {
        display: block;
        animation: fadeIn 0.5s ease;
    }
    
    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(20px); }
        to { opacity: 1; transform: translateY(0); }
    }
    
    .step-indicator {
        display: flex;
        justify-content: space-between;
        margin-bottom: 3rem;
        position: relative;
    }
    
    .step-indicator::before {
        content: '';
        position: absolute;
        top: 15px;
        left: 0;
        right: 0;
        height: 3px;
        background: #e2e8f0;
        z-index: 1;
    }
    
    .step {
        display: flex;
        flex-direction: column;
        align-items: center;
        position: relative;
        z-index: 2;
    }
    
    .step-number {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        background: #e2e8f0;
        color: #64748b;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 600;
        margin-bottom: 0.5rem;
        transition: all 0.3s;
    }
    
    .step.active .step-number {
        background: linear-gradient(135deg, var(--primary), var(--secondary));
        color: white;
        transform: scale(1.1);
    }
    
    .step.completed .step-number {
        background: #10b981;
        color: white;
    }
    
    .step-label {
        font-size: 0.9rem;
        color: #64748b;
        font-weight: 500;
    }
    
    .step.active .step-label {
        color: var(--primary);
        font-weight: 600;
    }
    
    .measurement-guide {
        background: #f8fafc;
        border-radius: 12px;
        padding: 1.5rem;
        margin: 1rem 0;
        border: 1px solid #e2e8f0;
    }
    
    .measurement-guide h6 {
        color: var(--primary);
        margin-bottom: 1rem;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    
    .measurement-image {
        text-align: center;
        margin: 1.5rem 0;
    }
    
    .measurement-image img {
        max-width: 100%;
        border-radius: 10px;
        box-shadow: 0 5px 15px rgba(0,0,0,0.1);
    }
    
    .form-group-enhanced {
        margin-bottom: 1.5rem;
    }
    
    .form-label-enhanced {
        display: flex;
        align-items: center;
        gap: 8px;
        font-weight: 600;
        color: #1e293b;
        margin-bottom: 0.5rem;
    }
    
    .form-label-enhanced i {
        color: var(--primary);
    }
    
    .form-control-enhanced {
        border: 2px solid #e2e8f0;
        border-radius: 10px;
        padding: 0.75rem 1rem;
        font-size: 1rem;
        transition: all 0.3s;
    }
    
    .form-control-enhanced:focus {
        border-color: var(--primary);
        box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
    }
    
    .measurement-unit {
        position: absolute;
        right: 15px;
        top: 50%;
        transform: translateY(-50%);
        color: #64748b;
        font-size: 0.9rem;
    }
    
    .btn-primary-enhanced {
        background: linear-gradient(135deg, var(--primary), var(--secondary));
        color: white;
        border: none;
        border-radius: 10px;
        padding: 1rem 2rem;
        font-weight: 600;
        transition: all 0.3s;
        box-shadow: 0 5px 15px rgba(102, 126, 234, 0.3);
    }
    
    .btn-primary-enhanced:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 20px rgba(102, 126, 234, 0.4);
        color: white;
    }
    
    .btn-secondary-enhanced {
        background: white;
        color: var(--primary);
        border: 2px solid var(--primary);
        border-radius: 10px;
        padding: 1rem 2rem;
        font-weight: 600;
        transition: all 0.3s;
    }
    
    .btn-secondary-enhanced:hover {
        background: var(--primary);
        color: white;
    }
    
    .success-card {
        text-align: center;
        padding: 3rem 2rem;
    }
    
    .success-icon {
        width: 80px;
        height: 80px;
        background: linear-gradient(135deg, var(--primary), var(--secondary));
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 2rem;
        color: white;
        font-size: 2.5rem;
    }
    
    .order-summary {
        background: #f8fafc;
        border-radius: 12px;
        padding: 1.5rem;
        margin: 1.5rem 0;
        text-align: left;
    }
    
    .order-summary-item {
        display: flex;
        justify-content: space-between;
        padding: 0.5rem 0;
        border-bottom: 1px solid #e2e8f0;
    }
    
    .order-summary-item:last-child {
        border-bottom: none;
    }
    
    @media (max-width: 768px) {
        .order-card {
            padding: 1.5rem;
            margin: 1rem auto;
        }
        
        .step-indicator {
            flex-wrap: wrap;
            gap: 1rem;
        }
        
        .step {
            flex: 1;
            min-width: 80px;
        }
    }
    </style>
</head>
<body>
    <!-- Header -->
    <div class="page-header">
        <div class="container">
            <h1 class="mb-3">
                <?php echo isset($order_success) ? 'Order Confirmed!' : 'Custom Clothing Order'; ?>
            </h1>
            <p class="lead mb-0">
                <?php echo isset($order_success) 
                    ? 'Your custom clothing order has been submitted successfully' 
                    : 'Provide your measurements for perfectly tailored clothing'; ?>
            </p>
        </div>
    </div>
    
    <div class="container py-4">
        <?php if (isset($order_success) && $order_success): ?>
            <!-- Success Message -->
            <div class="custom-order-container">
                <div class="order-card">
                    <div class="success-card">
                        <div class="success-icon">
                            <i class="fas fa-check"></i>
                        </div>
                        <h2 class="mb-3">Thank You for Your Order!</h2>
                        <p class="lead mb-4">Your custom clothing order has been received and is being processed.</p>
                        
                        <div class="order-summary">
                            <h5 class="mb-3">Order Details</h5>
                            <div class="order-summary-item">
                                <span>Order ID:</span>
                                <strong><?php echo $order_id; ?></strong>
                            </div>
                            <div class="order-summary-item">
                                <span>Name:</span>
                                <strong><?php echo htmlspecialchars($measurements['customer_name']); ?></strong>
                            </div>
                            <div class="order-summary-item">
                                <span>Garment Type:</span>
                                <strong><?php echo htmlspecialchars($measurements['garment_type']); ?></strong>
                            </div>
                            <div class="order-summary-item">
                                <span>Estimated Delivery:</span>
                                <strong><?php echo htmlspecialchars($measurements['delivery_date']); ?></strong>
                            </div>
                        </div>
                        
                        <p class="text-muted mb-4">
                            <i class="fas fa-info-circle me-2"></i>
                            Our master tailor will contact you within 24 hours to discuss your order details and provide a final quote.
                        </p>
                        
                        <div class="d-flex gap-3 justify-content-center mt-4">
                            <a href="../products/index.php" class="btn-primary-enhanced">
                                <i class="fas fa-shopping-bag me-2"></i> Continue Shopping
                            </a>
                            <a href="order-status.php?id=<?php echo $order_id; ?>" class="btn-secondary-enhanced">
                                <i class="fas fa-truck me-2"></i> Track Order
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <!-- Custom Order Form -->
            <div class="custom-order-container">
                <div class="order-card">
                    <!-- Step Indicator -->
                    <div class="step-indicator">
                        <div class="step active" data-step="1">
                            <div class="step-number">1</div>
                            <div class="step-label">Personal Info</div>
                        </div>
                        <div class="step" data-step="2">
                            <div class="step-number">2</div>
                            <div class="step-label">Garment Details</div>
                        </div>
                        <div class="step" data-step="3">
                            <div class="step-number">3</div>
                            <div class="step-label">Measurements</div>
                        </div>
                        <div class="step" data-step="4">
                            <div class="step-number">4</div>
                            <div class="step-label">Review & Submit</div>
                        </div>
                    </div>
                    
                    <form id="customOrderForm" method="POST">
                        <!-- Step 1: Personal Information -->
                        <div class="form-step active" id="step1">
                            <h3 class="mb-4">Personal Information</h3>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group-enhanced">
                                        <label class="form-label-enhanced">
                                            <i class="fas fa-user"></i> Full Name
                                        </label>
                                        <input type="text" class="form-control-enhanced" name="customer_name" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group-enhanced">
                                        <label class="form-label-enhanced">
                                            <i class="fas fa-phone"></i> Phone Number
                                        </label>
                                        <input type="tel" class="form-control-enhanced" name="phone" required>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="form-group-enhanced">
                                <label class="form-label-enhanced">
                                    <i class="fas fa-envelope"></i> Email Address
                                </label>
                                <input type="email" class="form-control-enhanced" name="email" required>
                            </div>
                            
                            <div class="d-flex justify-content-between mt-5">
                                <div></div>
                                <button type="button" class="btn-primary-enhanced" onclick="nextStep(2)">
                                    Next: Garment Details <i class="fas fa-arrow-right ms-2"></i>
                                </button>
                            </div>
                        </div>
                        
                        <!-- Step 2: Garment Details -->
                        <div class="form-step" id="step2">
                            <h3 class="mb-4">Garment Details</h3>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group-enhanced">
                                        <label class="form-label-enhanced">
                                            <i class="fas fa-tshirt"></i> Garment Type
                                        </label>
                                        <select class="form-control-enhanced" name="garment_type" required>
                                            <option value="">Select garment type</option>
                                            <option value="agbada">Agbada</option>
                                            <option value="senator_suit">Senator Suit</option>
                                            <option value="kaftan">Kaftan</option>
                                            <option value="dashiki">Dashiki</option>
                                            <option value="buba_and_shokoto">Buba & Shokoto</option>
                                            <option value="wedding_gown">Wedding Gown</option>
                                            <option value="evening_gown">Evening Gown</option>
                                            <option value="blazer">Blazer</option>
                                            <option value="trouser">Trouser</option>
                                            <option value="shirt">Shirt</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group-enhanced">
                                        <label class="form-label-enhanced">
                                            <i class="fas fa-fabric"></i> Fabric Type
                                        </label>
                                        <select class="form-control-enhanced" name="fabric_type" required>
                                            <option value="">Select fabric</option>
                                            <option value="cotton">Cotton</option>
                                            <option value="linen">Linen</option>
                                            <option value="silk">Silk</option>
                                            <option value="ankara">Ankara</option>
                                            <option value="brocade">Brocade</option>
                                            <option value="velvet">Velvet</option>
                                            <option value="lace">Lace</option>
                                            <option value="chiffon">Chiffon</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group-enhanced">
                                        <label class="form-label-enhanced">
                                            <i class="fas fa-palette"></i> Color Preference
                                        </label>
                                        <input type="text" class="form-control-enhanced" name="color" placeholder="e.g., Navy Blue, Gold, White" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group-enhanced">
                                        <label class="form-label-enhanced">
                                            <i class="fas fa-calendar-alt"></i> Needed By Date
                                        </label>
                                        <input type="date" class="form-control-enhanced" name="delivery_date" required min="<?php echo date('Y-m-d', strtotime('+7 days')); ?>">
                                    </div>
                                </div>
                            </div>
                            
                            <div class="form-group-enhanced">
                                <label class="form-label-enhanced">
                                    <i class="fas fa-coins"></i> Budget Range (CFA)
                                </label>
                                <select class="form-control-enhanced" name="budget_range" required>
                                    <option value="">Select budget range</option>
                                    <option value="25000-50000">25,000 - 50,000 CFA</option>
                                    <option value="50000-100000">50,000 - 100,000 CFA</option>
                                    <option value="100000-200000">100,000 - 200,000 CFA</option>
                                    <option value="200000-500000">200,000 - 500,000 CFA</option>
                                    <option value="500000+">500,000+ CFA</option>
                                </select>
                            </div>
                            
                            <div class="d-flex justify-content-between mt-5">
                                <button type="button" class="btn-secondary-enhanced" onclick="prevStep(1)">
                                    <i class="fas fa-arrow-left me-2"></i> Previous
                                </button>
                                <button type="button" class="btn-primary-enhanced" onclick="nextStep(3)">
                                    Next: Measurements <i class="fas fa-arrow-right ms-2"></i>
                                </button>
                            </div>
                        </div>
                        
                        <!-- Step 3: Measurements -->
                        <div class="form-step" id="step3">
                            <h3 class="mb-4">Body Measurements (in cm)</h3>
                            
                            <div class="measurement-guide">
                                <h6><i class="fas fa-ruler"></i> How to Take Measurements</h6>
                                <p class="mb-0">Please provide accurate measurements in centimeters. Use a flexible measuring tape and measure while wearing light clothing.</p>
                            </div>
                            
                            <div class="measurement-image">
                                <img src="https://images.unsplash.com/photo-1516567727241-ad6a1a45c76b?w=600&h=400&fit=crop" 
                                     alt="Measurement Guide" 
                                     class="img-fluid">
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group-enhanced">
                                        <label class="form-label-enhanced">
                                            <i class="fas fa-ruler-vertical"></i> Shoulder Width
                                        </label>
                                        <div class="position-relative">
                                            <input type="number" class="form-control-enhanced" name="shoulder" step="0.5" required>
                                            <span class="measurement-unit">cm</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group-enhanced">
                                        <label class="form-label-enhanced">
                                            <i class="fas fa-ruler-vertical"></i> Chest/Bust
                                        </label>
                                        <div class="position-relative">
                                            <input type="number" class="form-control-enhanced" name="chest" step="0.5" required>
                                            <span class="measurement-unit">cm</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group-enhanced">
                                        <label class="form-label-enhanced">
                                            <i class="fas fa-ruler-vertical"></i> Waist
                                        </label>
                                        <div class="position-relative">
                                            <input type="number" class="form-control-enhanced" name="waist" step="0.5" required>
                                            <span class="measurement-unit">cm</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group-enhanced">
                                        <label class="form-label-enhanced">
                                            <i class="fas fa-ruler-vertical"></i> Hips
                                        </label>
                                        <div class="position-relative">
                                            <input type="number" class="form-control-enhanced" name="hips" step="0.5" required>
                                            <span class="measurement-unit">cm</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group-enhanced">
                                        <label class="form-label-enhanced">
                                            <i class="fas fa-ruler-horizontal"></i> Arm Length
                                        </label>
                                        <div class="position-relative">
                                            <input type="number" class="form-control-enhanced" name="arm_length" step="0.5" required>
                                            <span class="measurement-unit">cm</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group-enhanced">
                                        <label class="form-label-enhanced">
                                            <i class="fas fa-ruler-horizontal"></i> Inseam (for trousers)
                                        </label>
                                        <div class="position-relative">
                                            <input type="number" class="form-control-enhanced" name="inseam" step="0.5">
                                            <span class="measurement-unit">cm</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="form-group-enhanced">
                                <label class="form-label-enhanced">
                                    <i class="fas fa-ruler"></i> Neck Circumference
                                </label>
                                <div class="position-relative">
                                    <input type="number" class="form-control-enhanced" name="neck" step="0.5" required>
                                    <span class="measurement-unit">cm</span>
                                </div>
                            </div>
                            
                            <div class="form-group-enhanced">
                                <label class="form-label-enhanced">
                                    <i class="fas fa-edit"></i> Special Instructions
                                </label>
                                <textarea class="form-control-enhanced" name="special_instructions" rows="3" placeholder="Any special requests, style preferences, or additional information..."></textarea>
                            </div>
                            
                            <div class="d-flex justify-content-between mt-5">
                                <button type="button" class="btn-secondary-enhanced" onclick="prevStep(2)">
                                    <i class="fas fa-arrow-left me-2"></i> Previous
                                </button>
                                <button type="button" class="btn-primary-enhanced" onclick="nextStep(4)">
                                    Next: Review <i class="fas fa-arrow-right ms-2"></i>
                                </button>
                            </div>
                        </div>
                        
                        <!-- Step 4: Review and Submit -->
                        <div class="form-step" id="step4">
                            <h3 class="mb-4">Review Your Order</h3>
                            
                            <div class="order-summary">
                                <h5 class="mb-3">Order Summary</h5>
                                
                                <div id="reviewContent">
                                    <!-- Will be populated by JavaScript -->
                                </div>
                            </div>
                            
                            <div class="form-group-enhanced mt-4">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="termsAgreement" required>
                                    <label class="form-check-label" for="termsAgreement">
                                        I agree to the <a href="#" data-bs-toggle="modal" data-bs-target="#termsModal">terms and conditions</a> and understand that final pricing may vary based on fabric selection and complexity.
                                    </label>
                                </div>
                            </div>
                            
                            <div class="d-flex justify-content-between mt-5">
                                <button type="button" class="btn-secondary-enhanced" onclick="prevStep(3)">
                                    <i class="fas fa-arrow-left me-2"></i> Previous
                                </button>
                                <button type="submit" class="btn-primary-enhanced">
                                    <i class="fas fa-paper-plane me-2"></i> Submit Order
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- Terms Modal -->
    <div class="modal fade" id="termsModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Terms and Conditions</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <h6>Custom Order Agreement</h6>
                    <p>1. Measurements provided are used to create your custom garment. We recommend having measurements taken by a professional for best results.</p>
                    <p>2. Final pricing will be confirmed by our tailor after reviewing your specifications. An invoice will be sent for approval before production begins.</p>
                    <p>3. Production time varies from 7-14 days depending on complexity and current workload.</p>
                    <p>4. A 50% deposit is required to begin production, with the balance due upon completion before shipping.</p>
                    <p>5. Minor alterations after delivery are included within 7 days of receipt.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    let currentStep = 1;
    
    function goToStep(step) {
        // Hide all steps
        document.querySelectorAll('.form-step').forEach(stepEl => {
            stepEl.classList.remove('active');
        });
        
        // Show target step
        document.getElementById('step' + step).classList.add('active');
        
        // Update step indicators
        document.querySelectorAll('.step').forEach(stepEl => {
            stepEl.classList.remove('active');
            const stepNumber = parseInt(stepEl.dataset.step);
            
            if (stepNumber < step) {
                stepEl.classList.add('completed');
            } else if (stepNumber === step) {
                stepEl.classList.add('active');
            } else {
                stepEl.classList.remove('completed');
            }
        });
        
        currentStep = step;
        
        // If going to review step, populate review content
        if (step === 4) {
            populateReview();
        }
    }
    
    function nextStep(step) {
        // Validate current step before proceeding
        if (validateStep(currentStep)) {
            goToStep(step);
        }
    }
    
    function prevStep(step) {
        goToStep(step);
    }
    
    function validateStep(step) {
        const form = document.getElementById('customOrderForm');
        const inputs = document.querySelectorAll('#step' + step + ' [required]');
        let isValid = true;
        
        inputs.forEach(input => {
            if (!input.value.trim()) {
                input.classList.add('is-invalid');
                isValid = false;
            } else {
                input.classList.remove('is-invalid');
            }
        });
        
        if (!isValid) {
            alert('Please fill in all required fields before proceeding.');
        }
        
        return isValid;
    }
    
    function populateReview() {
        const form = document.getElementById('customOrderForm');
        const formData = new FormData(form);
        let reviewHTML = '';
        
        // Personal Info
        reviewHTML += `
            <div class="order-summary-item">
                <span>Name:</span>
                <strong>${formData.get('customer_name') || 'Not provided'}</strong>
            </div>
            <div class="order-summary-item">
                <span>Email:</span>
                <strong>${formData.get('email') || 'Not provided'}</strong>
            </div>
            <div class="order-summary-item">
                <span>Phone:</span>
                <strong>${formData.get('phone') || 'Not provided'}</strong>
            </div>
        `;
        
        // Garment Details
        reviewHTML += `
            <div class="order-summary-item">
                <span>Garment Type:</span>
                <strong>${formData.get('garment_type') || 'Not provided'}</strong>
            </div>
            <div class="order-summary-item">
                <span>Fabric:</span>
                <strong>${formData.get('fabric_type') || 'Not provided'}</strong>
            </div>
            <div class="order-summary-item">
                <span>Color:</span>
                <strong>${formData.get('color') || 'Not provided'}</strong>
            </div>
            <div class="order-summary-item">
                <span>Budget Range:</span>
                <strong>${formData.get('budget_range') || 'Not provided'} CFA</strong>
            </div>
        `;
        
        // Measurements
        reviewHTML += `
            <div class="order-summary-item">
                <span>Shoulder:</span>
                <strong>${formData.get('shoulder') || 'Not provided'} cm</strong>
            </div>
            <div class="order-summary-item">
                <span>Chest:</span>
                <strong>${formData.get('chest') || 'Not provided'} cm</strong>
            </div>
            <div class="order-summary-item">
                <span>Waist:</span>
                <strong>${formData.get('waist') || 'Not provided'} cm</strong>
            </div>
            <div class="order-summary-item">
                <span>Delivery Date:</span>
                <strong>${formData.get('delivery_date') || 'Not provided'}</strong>
            </div>
        `;
        
        document.getElementById('reviewContent').innerHTML = reviewHTML;
    }
    
    // Add input validation
    document.addEventListener('DOMContentLoaded', function() {
        const requiredInputs = document.querySelectorAll('[required]');
        
        requiredInputs.forEach(input => {
            input.addEventListener('blur', function() {
                if (!this.value.trim()) {
                    this.classList.add('is-invalid');
                } else {
                    this.classList.remove('is-invalid');
                }
            });
        });
        
        // Add measurement validation
        const measurementInputs = document.querySelectorAll('input[type="number"]');
        measurementInputs.forEach(input => {
            input.addEventListener('change', function() {
                const value = parseFloat(this.value);
                if (value < 0 || value > 200) {
                    this.classList.add('is-invalid');
                    alert('Please enter a valid measurement between 0 and 200 cm.');
                } else {
                    this.classList.remove('is-invalid');
                }
            });
        });
    });
    
    // Form submission
    document.getElementById('customOrderForm').addEventListener('submit', function(e) {
        if (!validateStep(currentStep)) {
            e.preventDefault();
            alert('Please fill in all required fields before submitting.');
            return false;
        }
        
        if (!document.getElementById('termsAgreement').checked) {
            e.preventDefault();
            alert('Please agree to the terms and conditions before submitting.');
            return false;
        }
        
        // Show loading state
        const submitBtn = this.querySelector('button[type="submit"]');
        const originalText = submitBtn.innerHTML;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i> Processing...';
        submitBtn.disabled = true;
        
        // In a real application, you would submit via AJAX here
        // For now, we'll let the form submit normally
    });
    </script>
</body>
</html>