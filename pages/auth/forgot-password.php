<?php
require_once '../../config.php';
require_once '../../includes/functions/auth_functions.php';

// Start session if not started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$auth = new AuthFunctions();
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = trim($_POST['email']);
    
    if (empty($email)) {
        $error = 'Please enter your email address';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address';
    } else {
        $result = $auth->forgotPassword($email);
        if ($result['success']) {
            $message = 'Password reset instructions have been sent to your email. Please check your inbox and spam folder.';
            $_SESSION['reset_email'] = $email; // Store email for confirmation
        } else {
            $error = $result['message'];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Montserrat:wght@500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
    <style>
        :root {
            --primary-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            --secondary-gradient: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            --success-gradient: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);
            --glass-bg: rgba(255, 255, 255, 0.95);
            --glass-border: rgba(255, 255, 255, 0.2);
        }
        
        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            position: relative;
            overflow-x: hidden;
        }
        
        /* Animated background elements */
        .bg-circle {
            position: absolute;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.1);
            z-index: 0;
        }
        
        .bg-circle:nth-child(1) {
            width: 300px;
            height: 300px;
            top: -150px;
            right: -150px;
        }
        
        .bg-circle:nth-child(2) {
            width: 200px;
            height: 200px;
            bottom: -100px;
            left: -100px;
            background: rgba(255, 255, 255, 0.05);
        }
        
        .forgot-container {
            width: 100%;
            max-width: 480px;
            position: relative;
            z-index: 1;
        }
        
        .forgot-card {
            background: var(--glass-bg);
            backdrop-filter: blur(20px);
            border-radius: 24px;
            overflow: hidden;
            box-shadow: 0 25px 75px rgba(0, 0, 0, 0.3);
            border: 1px solid var(--glass-border);
            transform-style: preserve-3d;
            perspective: 1000px;
        }
        
        .card-header {
            background: var(--primary-gradient);
            color: white;
            padding: 2.5rem;
            text-align: center;
            position: relative;
            overflow: hidden;
        }
        
        .card-header::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255,255,255,0.1) 1px, transparent 1px);
            background-size: 30px 30px;
            opacity: 0.3;
            animation: float 20s linear infinite;
        }
        
        @keyframes float {
            0% { transform: rotate(0deg) translate(0, 0); }
            100% { transform: rotate(360deg) translate(20px, 20px); }
        }
        
        .card-icon {
            width: 100px;
            height: 100px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.5rem;
            font-size: 3rem;
            position: relative;
            z-index: 1;
            animation: iconFloat 3s ease-in-out infinite alternate;
        }
        
        @keyframes iconFloat {
            0% { transform: translateY(0) scale(1); }
            100% { transform: translateY(-10px) scale(1.05); }
        }
        
        .card-title {
            font-family: 'Montserrat', sans-serif;
            font-weight: 700;
            font-size: 2.2rem;
            margin-bottom: 0.5rem;
            position: relative;
            z-index: 1;
        }
        
        .card-subtitle {
            opacity: 0.9;
            font-size: 1.1rem;
            position: relative;
            z-index: 1;
        }
        
        .card-body {
            padding: 2.5rem;
        }
        
        .form-group {
            margin-bottom: 1.5rem;
            position: relative;
        }
        
        .form-label {
            font-weight: 600;
            color: #2d3748;
            margin-bottom: 0.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .form-control {
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            padding: 1rem 1.25rem;
            font-size: 1rem;
            transition: all 0.3s ease;
            background: rgba(255, 255, 255, 0.9);
        }
        
        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.15);
            transform: translateY(-2px);
        }
        
        .form-control-icon {
            position: absolute;
            right: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: #94a3b8;
        }
        
        .btn-reset {
            background: var(--primary-gradient);
            border: none;
            color: white;
            padding: 1rem 2rem;
            border-radius: 12px;
            font-weight: 600;
            font-size: 1.1rem;
            width: 100%;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        
        .btn-reset:hover {
            transform: translateY(-3px);
            box-shadow: 0 15px 30px rgba(102, 126, 234, 0.3);
        }
        
        .btn-reset:active {
            transform: translateY(-1px);
        }
        
        .btn-reset i {
            margin-right: 0.5rem;
        }
        
        .instruction-box {
            background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
            border-radius: 16px;
            padding: 1.5rem;
            margin-bottom: 2rem;
            border-left: 5px solid #667eea;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
        }
        
        .instruction-title {
            font-weight: 700;
            color: #2d3748;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .step-list {
            list-style: none;
            padding-left: 0;
            margin-bottom: 0;
        }
        
        .step-item {
            padding: 0.75rem 0;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            border-bottom: 1px solid #e2e8f0;
        }
        
        .step-item:last-child {
            border-bottom: none;
        }
        
        .step-number {
            width: 28px;
            height: 28px;
            background: var(--primary-gradient);
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.875rem;
            font-weight: 700;
            flex-shrink: 0;
        }
        
        .step-text {
            color: #475569;
            font-size: 0.95rem;
        }
        
        .alert-box {
            border-radius: 12px;
            padding: 1.25rem;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 1rem;
            animation: slideIn 0.5s ease;
        }
        
        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .alert-success {
            background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%);
            border: 2px solid #10b981;
            color: #065f46;
        }
        
        .alert-danger {
            background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%);
            border: 2px solid #ef4444;
            color: #991b1b;
        }
        
        .alert-icon {
            font-size: 1.5rem;
            flex-shrink: 0;
        }
        
        .back-link {
            text-align: center;
            margin-top: 2rem;
            padding-top: 1.5rem;
            border-top: 2px dashed #e2e8f0;
        }
        
        .back-link a {
            color: #667eea;
            text-decoration: none;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.3s ease;
        }
        
        .back-link a:hover {
            color: #5a67d8;
            gap: 0.75rem;
        }
        
        .loading-spinner {
            display: none;
            width: 20px;
            height: 20px;
            border: 3px solid rgba(255, 255, 255, 0.3);
            border-radius: 50%;
            border-top-color: white;
            animation: spin 1s linear infinite;
            margin-left: 0.5rem;
        }
        
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        
        /* Floating animation for form */
        @keyframes floatForm {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-10px); }
        }
        
        .forgot-card {
            animation: floatForm 6s ease-in-out infinite;
        }
        
        /* Responsive */
        @media (max-width: 576px) {
            .card-header {
                padding: 2rem 1.5rem;
            }
            
            .card-body {
                padding: 2rem 1.5rem;
            }
            
            .card-title {
                font-size: 1.8rem;
            }
            
            .card-icon {
                width: 80px;
                height: 80px;
                font-size: 2.5rem;
            }
        }
        
        /* Shake animation for error */
        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            10%, 30%, 50%, 70%, 90% { transform: translateX(-5px); }
            20%, 40%, 60%, 80% { transform: translateX(5px); }
        }
        
        .shake {
            animation: shake 0.5s ease-in-out;
        }
    </style>
</head>
<body>
    <!-- Background elements -->
    <div class="bg-circle"></div>
    <div class="bg-circle"></div>
    
    <div class="forgot-container">
        <div class="forgot-card animate__animated animate__fadeInUp">
            <!-- Card Header -->
            <div class="card-header">
                <div class="card-icon">
                    <i class="bi bi-shield-lock"></i>
                </div>
                <h1 class="card-title">Reset Password</h1>
                <p class="card-subtitle">Enter your email to receive reset instructions</p>
            </div>
            
            <!-- Card Body -->
            <div class="card-body">
                <!-- Success Message -->
                <?php if ($message): ?>
                    <div class="alert-box alert-success">
                        <i class="bi bi-check-circle-fill alert-icon"></i>
                        <div>
                            <strong>Success!</strong>
                            <p class="mb-0"><?php echo $message; ?></p>
                            <?php if (isset($_SESSION['reset_email'])): ?>
                                <small class="d-block mt-2">
                                    <i class="bi bi-info-circle"></i>
                                    If you don't see the email, check your spam folder.
                                </small>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>
                
                <!-- Error Message -->
                <?php if ($error): ?>
                    <div class="alert-box alert-danger">
                        <i class="bi bi-exclamation-triangle-fill alert-icon"></i>
                        <div>
                            <strong>Error!</strong>
                            <p class="mb-0"><?php echo $error; ?></p>
                        </div>
                    </div>
                <?php endif; ?>
                
                <!-- Instructions -->
                <div class="instruction-box">
                    <h5 class="instruction-title">
                        <i class="bi bi-info-circle"></i>
                        How to reset your password
                    </h5>
                    <ul class="step-list">
                        <li class="step-item">
                            <span class="step-number">1</span>
                            <span class="step-text">Enter your registered email address below</span>
                        </li>
                        <li class="step-item">
                            <span class="step-number">2</span>
                            <span class="step-text">Check your email for the reset link</span>
                        </li>
                        <li class="step-item">
                            <span class="step-number">3</span>
                            <span class="step-text">Click the link to set a new password</span>
                        </li>
                        <li class="step-item">
                            <span class="step-number">4</span>
                            <span class="step-text">The link expires in 1 hour for security</span>
                        </li>
                    </ul>
                </div>
                
                <!-- Reset Form -->
                <form method="POST" action="" id="resetForm">
                    <div class="form-group">
                        <label for="email" class="form-label">
                            <i class="bi bi-envelope"></i>
                            Email Address
                        </label>
                        <input type="email" 
                               class="form-control" 
                               id="email" 
                               name="email" 
                               placeholder="Enter your email address"
                               required
                               value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
                               autocomplete="email">
                        <i class="bi bi-envelope form-control-icon"></i>
                        <div class="form-text text-muted mt-2">
                            <i class="bi bi-shield-check"></i>
                            Your email is secure and will only be used for password reset
                        </div>
                    </div>
                    
                    <button type="submit" class="btn-reset" id="submitBtn">
                        <i class="bi bi-send"></i>
                        <span id="btnText">Send Reset Instructions</span>
                        <div class="loading-spinner" id="loadingSpinner"></div>
                    </button>
                </form>
                
                <!-- Back to Login -->
                <div class="back-link">
                    <a href="login.php">
                        <i class="bi bi-arrow-left"></i>
                        Back to Login
                    </a>
                    <p class="text-muted small mt-2">
                        Need help? <a href="mailto:support@<?php echo strtolower(str_replace(' ', '', SITE_NAME)); ?>.com" class="text-decoration-none">Contact Support</a>
                    </p>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('resetForm');
            const submitBtn = document.getElementById('submitBtn');
            const btnText = document.getElementById('btnText');
            const loadingSpinner = document.getElementById('loadingSpinner');
            const emailInput = document.getElementById('email');
            
            // Email validation
            emailInput.addEventListener('input', function() {
                const email = this.value.trim();
                const isValid = /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
                
                if (email && !isValid) {
                    this.classList.add('is-invalid');
                } else {
                    this.classList.remove('is-invalid');
                }
            });
            
            // Form submission with AJAX
            form.addEventListener('submit', async function(e) {
                e.preventDefault();
                
                const email = emailInput.value.trim();
                
                if (!email) {
                    showError('Please enter your email address');
                    return;
                }
                
                if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
                    showError('Please enter a valid email address');
                    return;
                }
                
                // Show loading state
                submitBtn.disabled = true;
                btnText.textContent = 'Sending...';
                loadingSpinner.style.display = 'inline-block';
                
                try {
                    // Simulate API call (replace with actual AJAX call)
                    await new Promise(resolve => setTimeout(resolve, 1500));
                    
                    // For demo purposes, show success message
                    showSuccess('Reset instructions sent! Check your email.');
                    
                    // Reset form
                    form.reset();
                    
                    // In real implementation, submit the form
                    // form.submit();
                    
                } catch (error) {
                    showError('Failed to send reset instructions. Please try again.');
                } finally {
                    // Reset button state
                    submitBtn.disabled = false;
                    btnText.textContent = 'Send Reset Instructions';
                    loadingSpinner.style.display = 'none';
                }
            });
            
            function showError(message) {
                // Create error alert
                const alertBox = document.createElement('div');
                alertBox.className = 'alert-box alert-danger';
                alertBox.innerHTML = `
                    <i class="bi bi-exclamation-triangle-fill alert-icon"></i>
                    <div>
                        <strong>Error!</strong>
                        <p class="mb-0">${message}</p>
                    </div>
                `;
                
                // Add shake animation to form
                form.classList.add('shake');
                setTimeout(() => form.classList.remove('shake'), 500);
                
                // Insert alert before form
                form.parentNode.insertBefore(alertBox, form);
                
                // Remove alert after 5 seconds
                setTimeout(() => {
                    alertBox.remove();
                }, 5000);
            }
            
            function showSuccess(message) {
                // Create success alert
                const alertBox = document.createElement('div');
                alertBox.className = 'alert-box alert-success';
                alertBox.innerHTML = `
                    <i class="bi bi-check-circle-fill alert-icon"></i>
                    <div>
                        <strong>Success!</strong>
                        <p class="mb-0">${message}</p>
                    </div>
                `;
                
                // Insert alert before form
                form.parentNode.insertBefore(alertBox, form);
                
                // Remove alert after 5 seconds
                setTimeout(() => {
                    alertBox.remove();
                }, 5000);
            }
            
            // Auto-focus email field
            emailInput.focus();
            
            // Add floating animation to card
            const card = document.querySelector('.forgot-card');
            let mouseX = 0;
            let mouseY = 0;
            
            document.addEventListener('mousemove', (e) => {
                mouseX = (e.clientX - window.innerWidth / 2) / 25;
                mouseY = (e.clientY - window.innerHeight / 2) / 25;
                
                card.style.transform = `
                    perspective(1000px)
                    rotateY(${mouseX}deg)
                    rotateX(${-mouseY}deg)
                    translateY(-10px)
                `;
            });
            
            // Reset transform on mouse leave
            document.addEventListener('mouseleave', () => {
                card.style.transform = 'perspective(1000px) rotateY(0) rotateX(0) translateY(0)';
            });
            
            // Add confetti animation on success (demo)
            function createConfetti() {
                const colors = ['#667eea', '#764ba2', '#f093fb', '#f5576c', '#43e97b', '#38f9d7'];
                for (let i = 0; i < 50; i++) {
                    const confetti = document.createElement('div');
                    confetti.style.cssText = `
                        position: absolute;
                        width: 10px;
                        height: 10px;
                        background: ${colors[Math.floor(Math.random() * colors.length)]};
                        border-radius: 2px;
                        top: -20px;
                        left: ${Math.random() * 100}%;
                        animation: confettiFall ${Math.random() * 2 + 1}s linear forwards;
                        z-index: 1000;
                    `;
                    
                    document.body.appendChild(confetti);
                    
                    setTimeout(() => confetti.remove(), 3000);
                }
            }
            
            // Add CSS for confetti animation
            const style = document.createElement('style');
            style.textContent = `
                @keyframes confettiFall {
                    0% {
                        transform: translateY(0) rotate(0deg);
                        opacity: 1;
                    }
                    100% {
                        transform: translateY(100vh) rotate(720deg);
                        opacity: 0;
                    }
                }
            `;
            document.head.appendChild(style);
            
            // For demo: trigger confetti on button click
            submitBtn.addEventListener('click', () => {
                setTimeout(createConfetti, 1000);
            });
        });
    </script>
</body>
</html>