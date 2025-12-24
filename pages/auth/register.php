<?php
require_once dirname(__DIR__, 2) . '/config.php';
require_once ROOT_PATH . '/includes/classes/Database.php';
require_once ROOT_PATH . '/includes/classes/User.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if already logged in
if (isset($_SESSION['user_id'])) {
    header('Location: ../' . $_SESSION['user_type'] . '/dashboard.php');
    exit();
}

$error = '';
$success = '';
$userType = $_GET['type'] ?? 'customer';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize input data
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

    // Validation
    if (empty($data['username']) || empty($data['email']) || empty($data['password']) || 
        empty($data['full_name']) || empty($data['user_type'])) {
        $error = 'Please fill in all required fields';
    } elseif ($data['password'] !== $data['confirm_password']) {
        $error = 'Passwords do not match';
    } elseif (strlen($data['password']) < 6) {
        $error = 'Password must be at least 6 characters long';
    } else {
        $userObj = new User();
        $result = $userObj->register($data);
        
        if (is_numeric($result)) {
            // Registration successful
            header('Location: login.php?success=1');
            exit();
        } else {
            $error = $result;
        }
    }
    
    // Keep form data for re-population
    $userType = $data['user_type'];
}
?>

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
            max-width: 800px;
            width: 100%;
            margin: 0 auto;
            padding: 2.5rem;
        }
        
        .user-type-card {
            border: 2px solid #dee2e6;
            border-radius: 10px;
            padding: 1.5rem;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
            height: 100%;
            background: white;
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
            background: linear-gradient(135deg, #667eea, #764ba2);
            border: none;
            padding: 0.75rem;
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
            transition: all 0.3s ease;
        }
        
        .strength-weak { background: #dc3545; }
        .strength-medium { background: #ffc107; }
        .strength-strong { background: #28a745; }
    </style>
</head>
<body>
    <div class="register-card">
        <div class="text-center mb-4">
            <h2 class="fw-bold">Create Account</h2>
            <p class="text-muted">Join our community of tailors and customers</p>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="bi bi-exclamation-triangle me-2"></i><?php echo htmlspecialchars($error); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="bi bi-check-circle me-2"></i><?php echo htmlspecialchars($success); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <form method="POST" action="" id="registrationForm">
            <!-- User Type Selection -->
            <div class="mb-4">
                <label class="form-label fw-bold mb-3">Join as:</label>
                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="user-type-card <?php echo ($userType == 'customer') ? 'selected' : ''; ?>" 
                             onclick="selectUserType('customer')">
                            <i class="bi bi-person"></i>
                            <h5 class="fw-bold">Customer</h5>
                            <p class="text-muted small">Browse and buy custom clothing</p>
                            <input type="radio" name="user_type" value="customer" 
                                   <?php echo ($userType == 'customer') ? 'checked' : ''; ?> hidden>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="user-type-card <?php echo ($userType == 'tailor') ? 'selected' : ''; ?>" 
                             onclick="selectUserType('tailor')">
                            <i class="bi bi-scissors"></i>
                            <h5 class="fw-bold">Tailor</h5>
                            <p class="text-muted small">Sell your custom designs</p>
                            <input type="radio" name="user_type" value="tailor" 
                                   <?php echo ($userType == 'tailor') ? 'checked' : ''; ?> hidden>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row mb-3">
                <div class="col-md-6">
                    <label class="form-label">Full Name *</label>
                    <input type="text" class="form-control" name="full_name" 
                           value="<?php echo htmlspecialchars($_POST['full_name'] ?? ''); ?>" 
                           required
                           placeholder="Enter your full name">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Username *</label>
                    <input type="text" class="form-control" name="username" 
                           value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>" 
                           required
                           placeholder="Choose a username">
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label">Email Address *</label>
                <input type="email" class="form-control" name="email" 
                       value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" 
                       required
                       placeholder="Enter your email">
            </div>

            <div class="row mb-3">
                <div class="col-md-6">
                    <label class="form-label">Password *</label>
                    <input type="password" class="form-control" name="password" id="password" 
                           required
                           placeholder="Create a password">
                    <div class="password-strength">
                        <div class="password-strength-bar" id="passwordStrength"></div>
                    </div>
                    <small class="text-muted">At least 6 characters</small>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Confirm Password *</label>
                    <input type="password" class="form-control" name="confirm_password" 
                           required
                           placeholder="Confirm your password">
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label">Phone Number</label>
                <input type="tel" class="form-control" name="phone" 
                       value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>"
                       placeholder="Enter your phone number">
            </div>

            <div class="mb-3">
                <label class="form-label">Address</label>
                <textarea class="form-control" name="address" rows="2"
                          placeholder="Enter your address"><?php echo htmlspecialchars($_POST['address'] ?? ''); ?></textarea>
            </div>

            <div id="tailorBioSection" class="mb-3" style="<?php echo ($userType == 'tailor') ? '' : 'display: none;' ?>">
                <label class="form-label">Tailor Bio / Experience</label>
                <textarea class="form-control" name="bio" rows="3" 
                          placeholder="Tell us about your work experience, specialization, etc."><?php echo htmlspecialchars($_POST['bio'] ?? ''); ?></textarea>
            </div>

            <div class="mb-4 form-check">
                <input type="checkbox" class="form-check-input" id="terms" required>
                <label class="form-check-label" for="terms">
                    I agree to the <a href="<?php echo SITE_URL; ?>/pages/help/terms.php" target="_blank">Terms & Conditions</a> and <a href="<?php echo SITE_URL; ?>/pages/help/privacy.php" target="_blank">Privacy Policy</a>
                </label>
            </div>

            <button type="submit" class="btn btn-primary w-100 py-2 mb-3">Create Account</button>

            <div class="text-center">
                <p class="small mb-0">Already have an account? 
                    <a href="login.php" class="fw-bold text-decoration-none">Sign in here</a>
                </p>
            </div>
        </form>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function selectUserType(type) {
            // Update UI
            document.querySelectorAll('.user-type-card').forEach(card => {
                card.classList.remove('selected');
            });
            
            const selectedCard = document.querySelector(`.user-type-card[onclick="selectUserType('${type}')"]`);
            selectedCard.classList.add('selected');
            
            // Update radio button
            selectedCard.querySelector('input').checked = true;
            
            // Show/hide tailor bio section
            const tailorBioSection = document.getElementById('tailorBioSection');
            if (type === 'tailor') {
                tailorBioSection.style.display = 'block';
            } else {
                tailorBioSection.style.display = 'none';
            }
        }

        // Password strength indicator
        document.getElementById('password').addEventListener('input', function() {
            const password = this.value;
            const bar = document.getElementById('passwordStrength');
            let strength = 0;
            
            if (password.length >= 6) strength += 25;
            if (/[A-Z]/.test(password)) strength += 25;
            if (/[0-9]/.test(password)) strength += 25;
            if (/[^A-Za-z0-9]/.test(password)) strength += 25;
            
            bar.style.width = strength + '%';
            
            if (strength < 50) {
                bar.className = 'password-strength-bar strength-weak';
            } else if (strength < 75) {
                bar.className = 'password-strength-bar strength-medium';
            } else {
                bar.className = 'password-strength-bar strength-strong';
            }
        });

        // Form validation
        document.getElementById('registrationForm').addEventListener('submit', function(e) {
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
                alert('Password must be at least 6 characters long');
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