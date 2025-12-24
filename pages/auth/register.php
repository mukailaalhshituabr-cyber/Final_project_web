<?php
require_once dirname(__DIR__, 2) . '/config.php';
require_once ROOT_PATH . '/includes/classes/Database.php';
require_once ROOT_PATH . '/includes/classes/User.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    header('Location: ../' . $_SESSION['user_type'] . '/dashboard.php');
    exit();
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'username' => trim($_POST['username'] ?? ''),
        'email' => trim($_POST['email'] ?? ''),
        'password' => $_POST['password'] ?? '',
        'confirm_password' => $_POST['confirm_password'] ?? '',
        'full_name' => trim($_POST['full_name'] ?? ''),
        'user_type' => $_POST['user_type'] ?? 'customer', // Should be 'customer' or 'tailor'
        'phone' => trim($_POST['phone'] ?? ''),
        'address' => trim($_POST['address'] ?? ''),
        'bio' => trim($_POST['bio'] ?? '')
    ];
    
    // Validation Logic
    if ($data['password'] !== $data['confirm_password']) {
        $error = 'Passwords do not match';
    } else {
        $user = new User();
        $result = $user->register($data);
        if (is_numeric($result)) {
            header('Location: login.php?success=1');
            exit();
        } else {
            $error = $result;
        }
    }
}t
?>
<div class="mb-4">
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - <?php echo htmlspecialchars(SITE_NAME); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            padding: 20px;
        }
        
        .register-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            overflow: hidden;
            max-width: 1000px;
            margin: 0 auto;
        }
        
        .register-left {
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.1) 0%, rgba(118, 75, 162, 0.1) 100%);
            padding: 3rem;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }
        
        .register-logo {
            font-size: 2.5rem;
            font-weight: 700;
            color: #667eea;
            margin-bottom: 2rem;
        }
        
        .user-type-card {
            border: 2px solid #dee2e6;
            border-radius: 10px;
            padding: 1.5rem;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
            height: 100%;
        }
        
        .user-type-card:hover {
            border-color: #667eea;
            transform: translateY(-5px);
        }
        
        .user-type-card.selected {
            border-color: #667eea;
            background: rgba(102, 126, 234, 0.1);
        }
        
        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.25rem rgba(102, 126, 234, 0.25);
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            padding: 0.75rem 2rem;
            transition: all 0.3s ease;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(102, 126, 234, 0.3);
        }
        
        .password-strength {
            height: 5px;
            background: #e9ecef;
            border-radius: 3px;
            margin-top: 5px;
            overflow: hidden;
        }
        
        .password-strength-bar {
            height: 100%;
            width: 0%;
            background: #dc3545;
            transition: all 0.3s ease;
        }
        
        @media (max-width: 768px) {
            .register-left {
                display: none;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-10">
                <div class="register-card">
                    <div class="row g-0">
                        <!-- Left Side -->
                        <div class="col-lg-6 d-none d-lg-block">
                            <div class="register-left">
                                <div class="register-logo">
                                    <i class="bi bi-shop me-2"></i><?php echo htmlspecialchars(SITE_NAME); ?>
                                </div>
                                <h2 class="fw-bold mb-4">Join Our Community</h2>
                                <p class="text-muted mb-4">Create an account to start your fashion journey.</p>
                                
                                <ul class="list-unstyled">
                                    <?php if ($userType == 'customer'): ?>
                                    <li class="mb-3"><i class="bi bi-check-circle-fill text-success me-2"></i>Browse custom designs</li>
                                    <li class="mb-3"><i class="bi bi-check-circle-fill text-success me-2"></i>Track your measurements</li>
                                    <li class="mb-3"><i class="bi bi-check-circle-fill text-success me-2"></i>Get perfect fitting clothes</li>
                                    <li class="mb-3"><i class="bi bi-check-circle-fill text-success me-2"></i>Secure payments</li>
                                    <?php else: ?>
                                    <li class="mb-3"><i class="bi bi-check-circle-fill text-success me-2"></i>Showcase your designs</li>
                                    <li class="mb-3"><i class="bi bi-check-circle-fill text-success me-2"></i>Reach global customers</li>
                                    <li class="mb-3"><i class="bi bi-check-circle-fill text-success me-2"></i>Manage orders easily</li>
                                    <li class="mb-3"><i class="bi bi-check-circle-fill text-success me-2"></i>Get paid securely</li>
                                    <?php endif; ?>
                                </ul>
                                
                                <div class="mt-5">
                                    <p class="text-muted mb-2">Already have an account?</p>
                                    <a href="login.php" class="btn btn-outline-primary">
                                        Sign In <i class="bi bi-arrow-right ms-2"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Right Side -->
                        <div class="col-lg-6">
                            <div class="p-4 p-md-5">
                                <div class="text-center mb-4">
                                    <h2 class="fw-bold mb-2">Create Account</h2>
                                    <p class="text-muted">Join our fashion community today</p>
                                </div>
                                
                                <?php if (!empty($error)): ?>
                                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                    <i class="bi bi-exclamation-triangle me-2"></i>
                                    <?php echo $error; ?>
                                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                                </div>
                                <?php endif; ?>
                                
                                <form method="POST" action="" novalidate>
                                    <!-- User Type Selection -->
                                    <div class="mb-4">
                                        <label class="form-label fw-bold mb-3">I want to join as:</label>
                                        <div class="row g-3">
                                            <div class="col-6">
                                                <div class="user-type-card <?php echo ($userType == 'customer') ? 'selected' : ''; ?>" 
                                                    onclick="selectUserType('customer')">
                                                    <i class="bi bi-person display-6 text-primary mb-3"></i>
                                                    <h6 class="fw-bold mb-2">Customer</h6>
                                                    <p class="small text-muted">Buy custom clothing</p>
                                                    <input type="radio" name="user_type" value="customer" 
                                                        <?php echo ($userType == 'customer') ? 'checked' : ''; ?> hidden>
                                                </div>
                                            </div>
                                            <div class="col-6">
                                                <div class="user-type-card <?php echo ($userType == 'tailor') ? 'selected' : ''; ?>" 
                                                    onclick="selectUserType('tailor')">
                                                    <i class="bi bi-scissors display-6 text-primary mb-3"></i>
                                                    <h6 class="fw-bold mb-2">Tailor</h6>
                                                    <p class="small text-muted">Sell your designs</p>
                                                    <input type="radio" name="user_type" value="tailor" 
                                                        <?php echo ($userType == 'tailor') ? 'checked' : ''; ?> hidden>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div id="tailorBioSection" class="mb-3" style="<?php echo ($userType == 'tailor') ? '' : 'display: none;' ?>">
                                        <label class="form-label">Bio / Introduction</label>
                                        <textarea class="form-control" name="bio" rows="3" 
                                                placeholder="Tell us about your tailoring experience..."><?php echo htmlspecialchars($_POST['bio'] ?? ''); ?></textarea>
                                    </div>
                                    
                                    <!-- Basic Information -->
                                    <div class="row mb-3">
                                        <div class="col-md-6">
                                            <label class="form-label">Full Name *</label>
                                            <input type="text" class="form-control" name="full_name" 
                                                   value="<?php echo htmlspecialchars($_POST['full_name'] ?? ''); ?>" 
                                                   placeholder="John Doe" required>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Username *</label>
                                            <input type="text" class="form-control" name="username" 
                                                   value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>" 
                                                   placeholder="johndoe" required>
                                            <small class="form-text text-muted">This will be your public username</small>
                                        </div>
                                    </div>
                                    
                                    <!-- Contact Information -->
                                    <div class="row mb-3">
                                        <div class="col-md-6">
                                            <label class="form-label">Email Address *</label>
                                            <input type="email" class="form-control" name="email" 
                                                   value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" 
                                                   placeholder="your@email.com" required>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Phone Number</label>
                                            <input type="tel" class="form-control" name="phone" 
                                                   value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>" 
                                                   placeholder="+234 801 234 5678">
                                        </div>
                                    </div>
                                    
                                    <!-- Password -->
                                    <div class="row mb-3">
                                        <div class="col-md-6">
                                            <label class="form-label">Password *</label>
                                            <input type="password" class="form-control" name="password" 
                                                   id="password" placeholder="At least 6 characters" required>
                                            <div class="password-strength">
                                                <div class="password-strength-bar" id="passwordStrength"></div>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Confirm Password *</label>
                                            <input type="password" class="form-control" name="confirm_password" 
                                                   placeholder="Confirm your password" required>
                                        </div>
                                    </div>
                                    
                                    <!-- Address -->
                                    <div class="mb-3">
                                        <label class="form-label">Address</label>
                                        <textarea class="form-control" name="address" rows="2" 
                                                  placeholder="Your address (optional)"><?php echo htmlspecialchars($_POST['address'] ?? ''); ?></textarea>
                                    </div>
                                    
                                    <!-- Tailor Bio (only for tailors) -->
                                    <div id="tailorBioSection" class="mb-3" style="<?php echo $userType == 'tailor' ? '' : 'display: none;' ?>">
                                        <label class="form-label">Bio / Introduction</label>
                                        <textarea class="form-control" name="bio" rows="3" 
                                                  placeholder="Tell us about your tailoring experience..."><?php echo htmlspecialchars($_POST['bio'] ?? ''); ?></textarea>
                                        <small class="form-text text-muted">This will be displayed on your tailor profile</small>
                                    </div>
                                    
                                    <!-- Terms -->
                                    <div class="mb-4 form-check">
                                        <input type="checkbox" class="form-check-input" id="terms" required>
                                        <label class="form-check-label" for="terms">
                                            I agree to the <a href="#" class="text-decoration-none">Terms</a> 
                                            and <a href="#" class="text-decoration-none">Privacy Policy</a>
                                        </label>
                                    </div>
                                    
                                    <button type="submit" class="btn btn-primary w-100 py-2 mb-3">
                                        <i class="bi bi-person-plus me-2"></i> Create Account
                                    </button>
                                    
                                    <div class="text-center">
                                        <p class="text-muted mb-0">Already have an account? 
                                            <a href="login.php" class="text-decoration-none fw-bold">Sign In</a>
                                        </p>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // User type selection
        function selectUserType(type) {
            document.querySelectorAll('.user-type-card').forEach(card => {
                card.classList.remove('selected');
            });
            
            document.querySelector(`.user-type-card[onclick="selectUserType('${type}')"]`).classList.add('selected');
            document.querySelector(`input[name="user_type"][value="${type}"]`).checked = true;
            
            // Show/hide tailor bio section
            const bioSection = document.getElementById('tailorBioSection');
            if (type === 'tailor') {
                bioSection.style.display = 'block';
            } else {
                bioSection.style.display = 'none';
            }
        }
        
        // Password strength indicator
        document.getElementById('password').addEventListener('input', function() {
            const password = this.value;
            const strengthBar = document.getElementById('passwordStrength');
            let strength = 0;
            
            if (password.length >= 6) strength += 25;
            if (/[A-Z]/.test(password)) strength += 25;
            if (/[0-9]/.test(password)) strength += 25;
            if (/[^A-Za-z0-9]/.test(password)) strength += 25;
            
            strengthBar.style.width = strength + '%';
            
            // Change color
            if (strength < 50) {
                strengthBar.style.backgroundColor = '#dc3545';
            } else if (strength < 75) {
                strengthBar.style.backgroundColor = '#ffc107';
            } else {
                strengthBar.style.backgroundColor = '#28a745';
            }
        });
        
        // Form validation
        document.querySelector('form').addEventListener('submit', function(e) {
            const password = document.getElementById('password').value;
            const confirmPassword = document.querySelector('input[name="confirm_password"]').value;
            const terms = document.getElementById('terms').checked;
            
            if (password !== confirmPassword) {
                e.preventDefault();
                alert('Passwords do not match');
                return false;
            }
            
            if (password.length < 6) {
                e.preventDefault();
                alert('Password must be at least 6 characters');
                return false;
            }
            
            if (!terms) {
                e.preventDefault();
                alert('You must agree to the terms and conditions');
                return false;
            }
            
            return true;
        });
    </script>
</body>
</html>



<?php
/*require_once '../../config.php';
require_once '../../includes/classes/User.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    header('Location: ' . SITE_URL . '/pages/' . $_SESSION['user_type'] . '/dashboard.php');
    exit();
}

$error = '';
$success = '';
$userType = $_GET['type'] ?? 'customer'; // Default to customer

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Collect and sanitize form data
    $data = [
        'username' => trim($_POST['username'] ?? ''),
        'email' => trim($_POST['email'] ?? ''),
        'password' => $_POST['password'] ?? '',
        'confirm_password' => $_POST['confirm_password'] ?? '',
        'full_name' => trim($_POST['full_name'] ?? ''),
        'user_type' => $_POST['user_type'] ?? 'customer',
        'phone' => trim($_POST['phone'] ?? ''),
        'address' => trim($_POST['address'] ?? ''),
        'bio' => trim($_POST['bio'] ?? '')
    ];
    
    // Validate required fields
    if (empty($data['username']) || empty($data['email']) || empty($data['password']) || 
        empty($data['confirm_password']) || empty($data['full_name'])) {
        $error = 'Please fill in all required fields';
    } elseif ($data['password'] !== $data['confirm_password']) {
        $error = 'Passwords do not match';
    } elseif (strlen($data['password']) < 6) {
        $error = 'Password must be at least 6 characters';
    } elseif (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
        $error = 'Invalid email address';
    } else {
        $user = new User();
        $result = $user->register($data);
        
        if (is_numeric($result)) {
            // Registration successful - automatically log in
            $userData = $user->getUserById($result);
            
            // Set session variables
            $_SESSION['user_id'] = $userData['id'];
            $_SESSION['user_type'] = $userData['user_type'];
            $_SESSION['full_name'] = $userData['full_name'];
            $_SESSION['email'] = $userData['email'];
            $_SESSION['username'] = $userData['username'];
            
            // Redirect based on user type
            $redirectUrl = SITE_URL . '/pages/' . $userData['user_type'] . '/dashboard.php';
            header('Location: ' . $redirectUrl);
            exit();
        } else {
            $error = $result; // Error message from User class
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            padding: 20px;
        }
        
        .register-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            overflow: hidden;
        }
        
        .register-left {
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.1) 0%, rgba(118, 75, 162, 0.1) 100%);
            padding: 3rem;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }
        
        .register-logo {
            font-size: 2.5rem;
            font-weight: 700;
            color: #667eea;
            margin-bottom: 2rem;
        }
        
        .feature-list li {
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
        }
        
        .feature-list i {
            color: #667eea;
            margin-right: 10px;
            font-size: 1.2rem;
        }
        
        .user-type-card {
            border: 2px solid #dee2e6;
            border-radius: 10px;
            padding: 1.5rem;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .user-type-card:hover {
            border-color: #667eea;
            transform: translateY(-5px);
        }
        
        .user-type-card.selected {
            border-color: #667eea;
            background: rgba(102, 126, 234, 0.1);
        }
        
        .user-type-card i {
            font-size: 2.5rem;
            margin-bottom: 1rem;
            color: #667eea;
        }
        
        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.25rem rgba(102, 126, 234, 0.25);
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            padding: 0.75rem 2rem;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(102, 126, 234, 0.3);
        }
        
        .password-strength {
            height: 5px;
            background: #e9ecef;
            border-radius: 3px;
            margin-top: 5px;
            overflow: hidden;
        }
        
        .password-strength-bar {
            height: 100%;
            width: 0%;
            background: #dc3545;
            transition: all 0.3s ease;
        }
        
        @media (max-width: 768px) {
            .register-left {
                display: none;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-10">
                <div class="register-card">
                    <div class="row g-0">
                        <!-- Left Side - Branding & Features -->
                        <div class="col-lg-6 d-none d-lg-block">
                            <div class="register-left">
                                <div class="register-logo">
                                    <i class="bi bi-shop me-2"></i><?php echo SITE_NAME; ?>
                                </div>
                                <h2 class="fw-bold mb-4">Join Our Community</h2>
                                <p class="text-muted mb-4">Create an account to start your fashion journey with custom-made clothing.</p>
                                
                                <ul class="feature-list list-unstyled">
                                    <li>
                                        <i class="bi bi-check-circle-fill"></i>
                                        <span>Browse unique designs from global tailors</span>
                                    </li>
                                    <li>
                                        <i class="bi bi-check-circle-fill"></i>
                                        <span>Get personalized outfit recommendations</span>
                                    </li>
                                    <li>
                                        <i class="bi bi-check-circle-fill"></i>
                                        <span>Track orders and measurements</span>
                                    </li>
                                    <li>
                                        <i class="bi bi-check-circle-fill"></i>
                                        <span>Secure payment and worldwide shipping</span>
                                    </li>
                                    <?php if ($userType == 'tailor'): ?>
                                    <li>
                                        <i class="bi bi-check-circle-fill"></i>
                                        <span>Showcase your designs to global customers</span>
                                    </li>
                                    <li>
                                        <i class="bi bi-check-circle-fill"></i>
                                        <span>Manage orders and earnings</span>
                                    </li>
                                    <?php endif; ?>
                                </ul>
                                
                                <div class="mt-5">
                                    <p class="text-muted mb-2">Already have an account?</p>
                                    <a href="login.php" class="btn btn-outline-primary">
                                        Sign In <i class="bi bi-arrow-right ms-2"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Right Side - Registration Form -->
                        <div class="col-lg-6">
                            <div class="p-5">
                                <div class="text-center mb-5">
                                    <h2 class="fw-bold">Create Account</h2>
                                    <p class="text-muted">Join our fashion community today</p>
                                </div>
                                
                                <?php if ($error): ?>
                                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                    <?php echo htmlspecialchars($error); ?>
                                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                                </div>
                                <?php endif; ?>
                                
                                <form method="POST" action="">
                                    <!-- User Type Selection -->
                                    <div class="mb-4">
                                        <label class="form-label fw-bold mb-3">I want to join as:</label>
                                        <div class="row g-3">
                                            <div class="col-6">
                                                <div class="user-type-card <?php echo $userType == 'customer' ? 'selected' : ''; ?>" 
                                                     onclick="selectUserType('customer')">
                                                    <i class="bi bi-person"></i>
                                                    <h6 class="fw-bold mb-2">Customer</h6>
                                                    <p class="small text-muted mb-0">Buy custom clothing</p>
                                                    <input type="radio" name="user_type" value="customer" 
                                                           <?php echo $userType == 'customer' ? 'checked' : ''; ?> hidden>
                                                </div>
                                            </div>
                                            <div class="col-6">
                                                <div class="user-type-card <?php echo $userType == 'tailor' ? 'selected' : ''; ?>" 
                                                     onclick="selectUserType('tailor')">
                                                    <i class="bi bi-scissors"></i>
                                                    <h6 class="fw-bold mb-2">Tailor</h6>
                                                    <p class="small text-muted mb-0">Sell your designs</p>
                                                    <input type="radio" name="user_type" value="tailor" 
                                                           <?php echo $userType == 'tailor' ? 'checked' : ''; ?> hidden>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- Personal Information -->
                                    <div class="row mb-3">
                                        <div class="col-md-6">
                                            <label class="form-label fw-bold">Full Name *</label>
                                            <input type="text" class="form-control" name="full_name" 
                                                   value="<?php echo htmlspecialchars($_POST['full_name'] ?? ''); ?>" 
                                                   placeholder="John Doe" required>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label fw-bold">Username *</label>
                                            <input type="text" class="form-control" name="username" 
                                                   value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>" 
                                                   placeholder="johndoe" required>
                                            <div class="form-text">This will be your public username</div>
                                        </div>
                                    </div>
                                    
                                    <!-- Contact Information -->
                                    <div class="row mb-3">
                                        <div class="col-md-6">
                                            <label class="form-label fw-bold">Email Address *</label>
                                            <input type="email" class="form-control" name="email" 
                                                   value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" 
                                                   placeholder="your@email.com" required>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label fw-bold">Phone Number</label>
                                            <input type="tel" class="form-control" name="phone" 
                                                   value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>" 
                                                   placeholder="+234 801 234 5678">
                                        </div>
                                    </div>
                                    
                                    <!-- Password -->
                                    <div class="row mb-3">
                                        <div class="col-md-6">
                                            <label class="form-label fw-bold">Password *</label>
                                            <input type="password" class="form-control" name="password" 
                                                   id="password" placeholder="At least 6 characters" required>
                                            <div class="password-strength">
                                                <div class="password-strength-bar" id="passwordStrength"></div>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label fw-bold">Confirm Password *</label>
                                            <input type="password" class="form-control" name="confirm_password" 
                                                   placeholder="Confirm your password" required>
                                        </div>
                                    </div>
                                    
                                    <!-- Additional Information -->
                                    <div class="mb-4">
                                        <label class="form-label fw-bold">Address</label>
                                        <textarea class="form-control" name="address" rows="2" 
                                                  placeholder="Your address (optional)"><?php echo htmlspecialchars($_POST['address'] ?? ''); ?></textarea>
                                    </div>
                                    
                                    <?php if ($userType == 'tailor'): ?>
                                    <div class="mb-4">
                                        <label class="form-label fw-bold">Bio / Introduction</label>
                                        <textarea class="form-control" name="bio" rows="3" 
                                                  placeholder="Tell us about your tailoring experience and specialties..."><?php echo htmlspecialchars($_POST['bio'] ?? ''); ?></textarea>
                                        <div class="form-text">This will be displayed on your tailor profile</div>
                                    </div>
                                    <?php endif; ?>
                                    
                                    <!-- Terms and Conditions -->
                                    <div class="mb-4 form-check">
                                        <input type="checkbox" class="form-check-input" id="terms" required>
                                        <label class="form-check-label" for="terms">
                                            I agree to the <a href="<?php echo SITE_URL; ?>/pages/help/terms.php" class="text-decoration-none">Terms of Service</a> 
                                            and <a href="<?php echo SITE_URL; ?>/pages/help/privacy.php" class="text-decoration-none">Privacy Policy</a>
                                        </label>
                                    </div>
                                    
                                    <button type="submit" class="btn btn-primary w-100 py-2 mb-4">
                                        <i class="bi bi-person-plus me-2"></i> Create Account
                                    </button>
                                    
                                    <div class="text-center">
                                        <p class="text-muted mb-0">Already have an account? 
                                            <a href="login.php" class="text-decoration-none fw-bold">Sign In</a>
                                        </p>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // User type selection
        function selectUserType(type) {
            document.querySelectorAll('.user-type-card').forEach(card => {
                card.classList.remove('selected');
            });
            
            document.querySelector(`.user-type-card[onclick="selectUserType('${type}')"]`).classList.add('selected');
            document.querySelector(`input[name="user_type"][value="${type}"]`).checked = true;
        }
        
        // Password strength indicator
        document.getElementById('password').addEventListener('input', function() {
            const password = this.value;
            const strengthBar = document.getElementById('passwordStrength');
            let strength = 0;
            
            if (password.length >= 6) strength += 25;
            if (/[A-Z]/.test(password)) strength += 25;
            if (/[0-9]/.test(password)) strength += 25;
            if (/[^A-Za-z0-9]/.test(password)) strength += 25;
            
            strengthBar.style.width = strength + '%';
            
            // Change color based on strength
            if (strength < 50) {
                strengthBar.style.backgroundColor = '#dc3545';
            } else if (strength < 75) {
                strengthBar.style.backgroundColor = '#ffc107';
            } else {
                strengthBar.style.backgroundColor = '#28a745';
            }
        });
        
        // Form validation
        document.querySelector('form').addEventListener('submit', function(e) {
            const password = document.getElementById('password').value;
            const confirmPassword = document.querySelector('input[name="confirm_password"]').value;
            const terms = document.getElementById('terms').checked;
            
            if (password !== confirmPassword) {
                e.preventDefault();
                alert('Passwords do not match');
                return false;
            }
            
            if (password.length < 6) {
                e.preventDefault();
                alert('Password must be at least 6 characters');
                return false;
            }
            
            if (!terms) {
                e.preventDefault();
                alert('You must agree to the terms and conditions');
                return false;
            }
        });
    </script>
</body>
</html>





<?php
require_once '../../config.php';
require_once '../../includes/classes/User.php';

// Start session if not started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Instantiate User class
$user = new User();
$error = '';
$success = '';

// Check if user is already logged in (redirect logic from your style example)
if (isset($_SESSION['user_id'])) {
    $userData = $user->getUserById($_SESSION['user_id']);
    
    if ($userData) {
        switch ($userData['user_type']) {
            case 'admin':
                header('Location: ' . SITE_URL . '/pages/admin/dashboard.php');
                break;
            case 'tailor':
                header('Location: ' . SITE_URL . '/pages/tailor/dashboard.php');
                break;
            default:
                header('Location: ' . SITE_URL . '/index.php');
        }
        exit();
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Basic validation and CSRF check (Assuming functions are defined in config/other includes)
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $error = 'CSRF token mismatch. Please try again.';
    } elseif ($_POST['password'] !== $_POST['confirm_password']) {
        $error = 'Passwords do not match.';
    } elseif (!isset($_POST['terms'])) {
        $error = 'You must agree to the Terms and Conditions.';
    } else {
        // Prepare data
        $data = [
            // === MUST ADD THIS LINE TO FIX THE ERROR ===
            // This generates a username from the email (e.g., 'test@example.com' -> 'test')
            'username' => trim(explode('@', $_POST['email'])[0]), 
            
            // Other fields below:
            'full_name' => trim($_POST['full_name']),
            'email' => trim($_POST['email']),
            'password' => $_POST['password'],
            'user_type' => $_POST['user_type'],
            'phone' => trim($_POST['phone']),
            'address' => trim($_POST['address']),
        ];

        // Add tailor-specific fields if applicable
        if ($data['user_type'] === 'tailor') {
            $data['bio'] = trim($_POST['bio']);
            $data['experience'] = $_POST['experience'];
            $data['specialization'] = $_POST['specialization'];
        }
        
        // Attempt registration (Assuming the register method handles hashing, database insertion, and validation)
        $registrationResult = $user->register($data);

        if ($registrationResult === true) {
            // Success! Redirect to login page and pass a GET parameter for success
            header('Location: ' . SITE_URL . '/pages/auth/login.php?success=registered'); // Changed param to 'success'
            exit();
        } else {
            // Failure: registrationResult contains the error message
            $error = $registrationResult; 
        } 
    }
}

// Set URL-based error/success messages
if (isset($_GET['success']) && $_GET['success'] == 'registered') {
    // 🟢 SUCCESS: Set the green success message.
    $success = 'Registration successful! You can now log in.';
} 
// Check if we were redirected with an error (Note: Our previous PHP logic handles errors 
// on the same page, but this handles future redirect errors if they are added).
elseif (isset($_GET['registered']) && $_GET['registered'] == 'error' && isset($_GET['message'])) {
    // 🔴 ERROR: Set the red error message.
    $error = htmlspecialchars(urldecode($_GET['message']));
}

$user_type = isset($_GET['type']) ? htmlspecialchars(trim($_GET['type'])) : 'customer';
// ...
$page_title = 'Register as ' . ucfirst($user_type) . ' - ' . SITE_NAME;

// Generate CSRF token if not exists
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
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
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
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
        
        .particles {
            position: absolute;
            width: 100%;
            height: 100%;
            z-index: 0;
        }
        
        .particle {
            position: absolute;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
        }
        
        .login-container {
            width: 100%;
            max-width: 1200px;
            position: relative;
            z-index: 1;
        }
        
        .login-card {
            background: var(--glass-bg);
            backdrop-filter: blur(20px);
            border-radius: 30px;
            overflow: hidden;
            box-shadow: 0 30px 80px rgba(0, 0, 0, 0.3);
            border: 1px solid var(--glass-border);
            display: flex;
            min-height: 750px; 
            transform-style: preserve-3d;
            perspective: 1000px;
        }
        
        .login-brand {
            flex: 1;
            background: var(--primary-gradient);
            color: white;
            padding: 3rem;
            display: flex;
            flex-direction: column;
            justify-content: center;
            position: relative;
            overflow: hidden;
        }
        
        .brand-pattern {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: url('data:image/svg+xml,<svg width="20" height="20" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path d="M0 0h20v20H0z" fill="none"/><path d="M1 1h1v1H1zM3 3h1v1H3zM5 5h1v1H5zM7 7h1v1H7zM9 9h1v1H9zM11 11h1v1H11zM13 13h1v1H13zM15 15h1v1H15zM17 17h1v1H17z" fill="rgba(255,255,255,0.1)"/></svg>');
            opacity: 0.3;
        }
        
        .brand-logo {
            text-decoration: none;
            margin-bottom: 2.5rem;
            display: inline-block;
        }
        
        .logo-icon {
            width: 70px;
            height: 70px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2.5rem;
            margin-bottom: 1.5rem;
            animation: iconFloat 3s ease-in-out infinite alternate;
        }
        
        @keyframes iconFloat {
            0% { transform: translateY(0) rotate(0deg); }
            100% { transform: translateY(-10px) rotate(5deg); }
        }
        
        .brand-title {
            font-family: 'Montserrat', sans-serif;
            font-weight: 800;
            font-size: 3rem;
            line-height: 1.1;
            margin-bottom: 1rem;
            position: relative;
            z-index: 1;
        }
        
        .brand-subtitle {
            font-size: 1.2rem;
            opacity: 0.9;
            margin-bottom: 2.5rem;
            position: relative;
            z-index: 1;
        }
        
        .features-list {
            list-style: none;
            padding: 0;
            margin: 2.5rem 0;
            position: relative;
            z-index: 1;
        }
        
        .feature-item {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 1.5rem;
            font-size: 1.05rem;
        }
        
        .feature-icon {
            width: 45px;
            height: 45px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
            flex-shrink: 0;
        }
        
        .login-form {
            flex: 1;
            padding: 3rem;
            display: flex;
            flex-direction: column;
            justify-content: center;
            position: relative;
        }
        
        .form-header {
            margin-bottom: 2.5rem;
        }
        
        .form-title {
            font-family: 'Montserrat', sans-serif;
            font-weight: 700;
            font-size: 2.2rem;
            color: #2d3748;
            margin-bottom: 0.5rem;
        }
        
        .form-subtitle {
            color: #718096;
            font-size: 1.1rem;
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
        
        .input-group {
            position: relative;
        }
        
        .input-group-text {
            background: none;
            border: none;
            color: #94a3b8;
            padding: 0 0.75rem 0 0;
            font-size: 1.1rem;
        }
        
        .input-group-text i {
            display: block;
        }

        .input-group > .form-control {
            border-top-left-radius: 12px;
            border-bottom-left-radius: 12px;
        }

        .input-group:not(.has-validation) > .form-control:not(:last-child) {
             border-top-right-radius: 12px;
             border-bottom-right-radius: 12px;
        }

        .password-toggle {
            cursor: pointer;
            transition: color 0.3s ease;
        }
        
        .password-toggle:hover {
            color: #667eea;
        }
        
        .form-options {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }
        
        .form-check {
            display: flex;
            align-items: flex-start; 
            gap: 0.5rem;
            margin-top: 10px;
        }
        
        .form-check-input {
            width: 18px;
            height: 18px;
            cursor: pointer;
            border: 2px solid #cbd5e1;
            margin-top: 5px;
            flex-shrink: 0;
        }
        
        .form-check-input:checked {
            background-color: #667eea;
            border-color: #667eea;
        }
        
        .form-check-label {
            color: #4a5568;
            font-size: 0.95rem;
            cursor: pointer;
        }
        
        .forgot-link {
            color: #667eea;
            text-decoration: none;
            font-size: 0.95rem;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        
        .forgot-link:hover {
            color: #5a67d8;
            text-decoration: underline;
        }
        
        .btn-register-custom {
            background: var(--success-gradient);
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
            text-decoration: none;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .btn-register-custom:hover {
            transform: translateY(-3px);
            box-shadow: 0 15px 30px rgba(67, 233, 123, 0.3);
            color: white; 
        }
        
        .btn-register-custom:active {
            transform: translateY(-1px);
        }
        
        .btn-register-custom i {
            margin-right: 0.5rem;
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
        
        .login-link {
            text-align: center;
            margin-top: 2rem;
            padding-top: 1.5rem;
            border-top: 2px dashed #e2e8f0;
        }
        
        .login-link p {
            color: #718096;
            margin-bottom: 0.5rem;
        }
        
        .btn-login-small {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            background: transparent;
            border: 2px solid #667eea;
            color: #667eea;
            padding: 0.75rem 2rem;
            border-radius: 12px;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s ease;
        }
        
        .btn-login-small:hover {
            background: #667eea;
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(102, 126, 234, 0.2);
        }
        
        .message-box {
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
        
        .message-success {
            background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%);
            border: 2px solid #10b981;
            color: #065f46;
        }
        
        .message-error {
            background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%);
            border: 2px solid #ef4444;
            color: #991b1b;
        }
        
        .message-icon {
            font-size: 1.5rem;
            flex-shrink: 0;
        }
        
        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            10%, 30%, 50%, 70%, 90% { transform: translateX(-5px); }
            20%, 40%, 60%, 80% { transform: translateX(5px); }
        }
        
        .shake {
            animation: shake 0.5s ease-in-out;
        }

        @media (max-width: 992px) {
            .login-card {
                flex-direction: column;
                min-height: auto;
            }
            
            .login-brand,
            .login-form {
                padding: 2rem;
            }
            
            .brand-title {
                font-size: 2.5rem;
            }
        }
        
        @media (max-width: 576px) {
            .login-container {
                padding: 0;
            }
            
            .login-card {
                border-radius: 20px;
            }
            
            .login-brand,
            .login-form {
                padding: 1.5rem;
            }
            
            .brand-title {
                font-size: 2rem;
            }
            
            .form-title {
                font-size: 1.8rem;
            }
            
            .social-buttons {
                grid-template-columns: 1fr;
            }
            
            .form-options {
                flex-direction: column;
                align-items: flex-start;
                gap: 1rem;
            }
        }
        
        .login-card {
            transition: transform 0.3s ease;
        }
    </style>
</head>
<body>
    
<div class="particles">
    </div>

<div class="login-container">
    <div class="login-card" id="registerCard">
        
        <div class="login-brand">
            <div class="brand-pattern"></div>
            
            <a href="<?php echo SITE_URL; ?>" class="brand-logo">
                <div class="logo-icon floating-element"><i class="bi bi-scissors"></i></div>
            </a>
            
            <h1 class="brand-title animate__animated animate__fadeInDown">
                Start Your <br>Tailoring Journey
            </h1>
            <p class="brand-subtitle animate__animated animate__fadeInUp">
                Create your account in seconds and unlock a world of fashion.
            </p>
            
            <ul class="features-list animate__animated animate__fadeInLeft">
                <li class="feature-item">
                    <div class="feature-icon"><i class="bi bi-shield-lock-fill"></i></div>
                    <span>Secure and Protected Platform</span>
                </li>
                <li class="feature-item">
                    <div class="feature-icon"><i class="bi bi-bar-chart-fill"></i></div>
                    <span>Instant Profile Activation</span>
                </li>
                <li class="feature-item">
                    <div class="feature-icon"><i class="bi bi-people-fill"></i></div>
                    <span>Join Thousands of Users</span>
                </li>
            </ul>
        </div>
        
        <div class="login-form">
            <div class="form-header">
                <h2 class="form-title">
                    <i class="bi bi-person-plus me-2"></i> Register as <?php echo ucfirst($user_type); ?>
                </h2>
                <p class="form-subtitle">Fill in your details to create an account.</p>
            </div>
            
            <?php if ($error): ?>
                <div class="message-box message-error shake">
                    <i class="bi bi-x-octagon-fill message-icon"></i>
                    <span><?php echo htmlspecialchars($error); ?></span>
                </div>
            <?php endif; ?>

            <div class="mb-4">
                <p class="form-label">Account Type:</p>
                <div class="d-flex gap-3">
                    <a href="?page=register&type=customer" 
                       class="btn btn-sm <?php echo $user_type === 'customer' ? 'btn-primary' : 'btn-outline-secondary'; ?>" 
                       style="border-radius: 8px;">
                        <i class="bi bi-person me-1"></i> Customer
                    </a>
                    <a href="?page=register&type=tailor" 
                       class="btn btn-sm <?php echo $user_type === 'tailor' ? 'btn-success' : 'btn-outline-success'; ?>"
                       style="border-radius: 8px;">
                        <i class="bi bi-tools me-1"></i> Tailor
                    </a>
                </div>
            </div>

            <form action="" method="POST" id="registerForm">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                <input type="hidden" name="user_type" value="<?php echo $user_type; ?>">

                <div class="row">
                    <div class="col-md-6 form-group">
                        <label for="full_name" class="form-label"><i class="bi bi-person-circle"></i> Full Name *</label>
                        <div class="input-group">
                             <input type="text" class="form-control" id="full_name" name="full_name" required 
                                   placeholder="John Doe">
                        </div>
                    </div>
                    
                    <div class="col-md-6 form-group">
                        <label for="email" class="form-label"><i class="bi bi-envelope-fill"></i> Email Address *</label>
                        <div class="input-group">
                            <input type="email" class="form-control" id="email" name="email" required 
                                   placeholder="name@example.com">
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 form-group">
                        <label for="password" class="form-label"><i class="bi bi-key-fill"></i> Password *</label>
                        <div class="input-group">
                            <input type="password" class="form-control" id="password" name="password" required 
                                   placeholder="Create a password">
                            <span class="input-icon password-toggle" id="togglePassword1"><i class="bi bi-eye-fill"></i></span>
                        </div>
                        <div class="form-text text-muted">Minimum 6 characters</div>
                    </div>
                    
                    <div class="col-md-6 form-group">
                        <label for="confirm_password" class="form-label"><i class="bi bi-key-fill"></i> Confirm Password *</label>
                        <div class="input-group">
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" required 
                                   placeholder="Confirm your password">
                            <span class="input-icon password-toggle" id="togglePassword2"><i class="bi bi-eye-fill"></i></span>
                        </div>
                        <div class="invalid-feedback d-block" id="passwordMismatchFeedback" style="display:none;">
                            Passwords do not match.
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6 form-group">
                        <label for="phone" class="form-label"><i class="bi bi-telephone-fill"></i> Phone Number</label>
                        <div class="input-group">
                            <input type="tel" class="form-control" id="phone" name="phone" 
                                   placeholder="+233 24 123 4567">
                        </div>
                    </div>
                    
                    <div class="col-md-6 form-group">
                        <label for="address" class="form-label"><i class="bi bi-geo-alt-fill"></i> Address</label>
                        <div class="input-group">
                            <input type="text" class="form-control" id="address" name="address" 
                                   placeholder="123 Example St.">
                        </div>
                    </div>
                </div>
                
                <?php if($user_type == 'tailor'): ?>
                    <hr class="my-4">
                    <h5 class="form-title" style="font-size: 1.5rem; color: #10b981;"><i class="bi bi-briefcase-fill me-1"></i> Tailor Profile</h5>
                    
                    <div class="form-group">
                        <label for="bio" class="form-label"><i class="bi bi-pencil-square"></i> Bio/Description</label>
                        <textarea class="form-control" id="bio" name="bio" rows="3" 
                                  placeholder="Tell customers about your tailoring specialties..."></textarea>
                    </div>

                    <div class="row">
                        <div class="col-md-6 form-group">
                            <label for="experience" class="form-label"><i class="bi bi-clock-history"></i> Experience</label>
                            <select class="form-control" id="experience" name="experience">
                                <option value="">Select experience</option>
                                <option value="0-2">0-2 years</option>
                                <option value="3-5">3-5 years</option>
                                <option value="6-10">6-10 years</option>
                                <option value="10+">10+ years</option>
                            </select>
                        </div>
                        
                        <div class="col-md-6 form-group">
                            <label for="specialization" class="form-label"><i class="bi bi-tag-fill"></i> Specialization</label>
                            <select class="form-control" id="specialization" name="specialization">
                                <option value="">Select specialization</option>
                                <option value="traditional">Traditional Wear</option>
                                <option value="modern">Modern Fashion</option>
                                <option value="wedding">Wedding Attire</option>
                                <option value="casual">Casual Wear</option>
                                <option value="custom">Custom Tailoring</option>
                            </select>
                        </div>
                    </div>
                <?php endif; ?>
                
                <div class="form-options d-block">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="terms" name="terms" required>
                        <label class="form-check-label" for="terms">
                            I agree to the <a href="#" class="forgot-link">Terms and Conditions</a> and <a href="#" class="forgot-link">Privacy Policy</a>
                        </label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="newsletter" name="newsletter" checked>
                        <label class="form-check-label" for="newsletter">
                            Subscribe to our newsletter
                        </label>
                    </div>
                </div>
                
                <div class="d-grid mt-4">
                    <button type="submit" class="btn-register-custom" id="registerSubmitBtn">
                        <i class="bi bi-person-plus-fill me-2"></i> Create Account
                    </button>
                </div>
            </form>
            

            <div class="login-link">
                <p>Already have an account?</p>
                <a href="<?php echo SITE_URL; ?>/pages/auth/login.php" class="btn-login-small">
                    <i class="bi bi-box-arrow-in-right me-1"></i> Log In Now
                </a>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const passwordInput = document.getElementById('password');
        const confirmPasswordInput = document.getElementById('confirm_password');
        const form = document.getElementById('registerForm');
        const togglePassword1 = document.getElementById('togglePassword1');
        const togglePassword2 = document.getElementById('togglePassword2');
        const passwordMismatchFeedback = document.getElementById('passwordMismatchFeedback');
        const card = document.getElementById('registerCard');

        // --- Password Toggle Function (Adjusted for bi-icons) ---
        const setupPasswordToggle = (toggleButton, inputField) => {
            if (toggleButton) {
                toggleButton.addEventListener('click', function() {
                    const icon = this.querySelector('i');
                    const type = inputField.getAttribute('type') === 'password' ? 'text' : 'password';
                    inputField.setAttribute('type', type);
                    // Toggle the eye icon class
                    icon.classList.toggle('bi-eye-fill');
                    icon.classList.toggle('bi-eye-slash-fill');
                });
            }
        };

        setupPasswordToggle(togglePassword1, passwordInput);
        setupPasswordToggle(togglePassword2, confirmPasswordInput);

        // --- Client-Side Password Match Validation ---
        // --- Client-Side Password Match Validation ---
        const validatePasswords = () => {
            const match = passwordInput.value === confirmPasswordInput.value;
            const filled = passwordInput.value && confirmPasswordInput.value;

            // Clear previous states
            passwordInput.classList.remove('is-valid', 'is-invalid');
            confirmPasswordInput.classList.remove('is-valid', 'is-invalid');
            passwordMismatchFeedback.style.display = 'none';

            if (filled && !match) {
                // 🔴 Passwords Mismatch (Filled but unequal)
                passwordInput.classList.add('is-invalid');
                confirmPasswordInput.classList.add('is-invalid');
                passwordMismatchFeedback.style.display = 'block'; // Show red warning text
                return false;
            } else if (match && filled) {
                // 🟢 Passwords Match (Filled and equal)
                passwordInput.classList.add('is-valid');
                confirmPasswordInput.classList.add('is-valid');
                return true;
            } else if (match && !filled) {
                // Passwords match but both are empty - no color yet
                return true;
            } else {
                // One is filled, one is empty - or other neutral state
                return true;
            }
        };
        
        passwordInput.addEventListener('input', validatePasswords);
        confirmPasswordInput.addEventListener('input', validatePasswords);
        
        // Final validation before submission
        form.addEventListener('submit', (e) => {
            if (!validatePasswords()) {
                e.preventDefault();
                // Show the form shake animation on validation error
                card.classList.add('shake');
                setTimeout(() => card.classList.remove('shake'), 500); 
                confirmPasswordInput.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
        });
        
        // --- Mouse Parallax Effect (Copied from login style) ---
        let mouseX = 0;
        let mouseY = 0;
        
        document.addEventListener('mousemove', (e) => {
            // Lowered divisor for a slightly more noticeable effect
            mouseX = (e.clientX - window.innerWidth / 2) / 30;
            mouseY = (e.clientY - window.innerHeight / 2) / 30;
            
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

        // --- Animated Background Particles (Initialization) ---
        const particlesContainer = document.querySelector('.particles');
        const particleCount = 15;

        for (let i = 0; i < particleCount; i++) {
            const particle = document.createElement('div');
            particle.className = 'particle';
            
            const size = Math.random() * 20 + 10; // 10px to 30px
            particle.style.width = `${size}px`;
            particle.style.height = `${size}px`;
            particle.style.left = `${Math.random() * 100}%`;
            particle.style.top = `${Math.random() * 100}%`;
            particle.style.animationDuration = `${Math.random() * 15 + 10}s`; // 10s to 25s
            particle.style.animationDelay = `${Math.random() * 5}s`;
            
            particlesContainer.appendChild(particle);
        }
    });
</script>const
</body>
</html>

*/