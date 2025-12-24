<?php
require_once dirname(__DIR__, 2) . '/config.php';
require_once ROOT_PATH . '/includes/classes/Database.php';
require_once ROOT_PATH . '/includes/classes/User.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if already logged in
if (isset($_SESSION['user_id']) && isset($_SESSION['user_type'])) {
    header('Location: ../' . $_SESSION['user_type'] . '/dashboard.php');
    exit();
}

$error = '';

// Handle login submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $selectedType = $_POST['user_type'] ?? 'customer';

    if (empty($email) || empty($password)) {
        $error = 'Please enter both email and password';
    } else {
        $userObj = new User();
        $user = $userObj->login($email, $password);
        
        if ($user && is_array($user)) {
            // Normalize user type
            $dbRole = strtolower($user['user_type']);
            
            // Check if selected type matches database role
            if ($dbRole !== strtolower($selectedType)) {
                $error = "This account is registered as a " . ucfirst($dbRole) . ". Please select the correct login type.";
            } else {
                // Set session variables
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_type'] = $dbRole;
                $_SESSION['username'] = $user['username'];
                $_SESSION['full_name'] = $user['full_name'];
                $_SESSION['email'] = $user['email'];
                
                // Redirect to appropriate dashboard
                header('Location: ../' . $dbRole . '/dashboard.php');
                exit();
            }
        } else {
            $error = 'Invalid email or password';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - <?php echo htmlspecialchars(SITE_NAME); ?></title>
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
        
        .login-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            max-width: 450px;
            width: 100%;
            margin: 0 auto;
            padding: 2.5rem;
        }
        
        .user-type-btn {
            flex: 1;
            border: 2px solid #dee2e6;
            color: #6c757d;
            background: transparent;
            transition: all 0.3s ease;
            padding: 0.75rem;
        }
        
        .user-type-btn.active {
            background: #667eea;
            color: white;
            border-color: #667eea;
        }
        
        .user-type-btn[data-type="tailor"].active {
            background: #212529;
            border-color: #212529;
        }
        
        .user-type-btn[data-type="admin"].active {
            background: #dc3545;
            border-color: #dc3545;
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
        
        .password-toggle {
            cursor: pointer;
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            color: #6c757d;
        }
    </style>
</head>
<body>
    <div class="login-card">
        <div class="text-center mb-4">
            <h2 class="fw-bold">Welcome Back</h2>
            <p class="text-muted">Sign in to your account</p>
        </div>

        <!-- User Type Selector -->
        <div class="user-type-selector d-flex gap-2 mb-4">
            <button type="button" class="btn user-type-btn active" data-type="customer">
                <i class="bi bi-person me-1"></i> Customer
            </button>
            <button type="button" class="btn user-type-btn" data-type="tailor">
                <i class="bi bi-scissors me-1"></i> Tailor
            </button>
            <button type="button" class="btn user-type-btn" data-type="admin">
                <i class="bi bi-shield-lock me-1"></i> Admin
            </button>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="bi bi-exclamation-triangle me-2"></i><?php echo htmlspecialchars($error); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="bi bi-check-circle me-2"></i>Registration successful! Please login.
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['reset'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="bi bi-check-circle me-2"></i>Password reset successful! Please login with your new password.
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <form method="POST" action="" id="loginForm">
            <input type="hidden" name="user_type" id="loginUserType" value="customer">

            <div class="mb-3">
                <label class="form-label">Email Address</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                    <input type="email" class="form-control" name="email" required 
                           value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
                           placeholder="Enter your email">
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label">Password</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-lock"></i></span>
                    <input type="password" class="form-control" name="password" id="passwordField" required
                           placeholder="Enter your password">
                    <span class="input-group-text password-toggle" id="togglePassword">
                        <i class="bi bi-eye"></i>
                    </span>
                </div>
            </div>

            <div class="mb-3 form-check">
                <input type="checkbox" class="form-check-input" id="rememberMe">
                <label class="form-check-label" for="rememberMe">Remember me</label>
                <a href="forgot-password.php" class="float-end text-decoration-none">Forgot Password?</a>
            </div>

            <button type="submit" class="btn btn-primary w-100 py-2 mb-3">Sign In</button>

            <div class="text-center">
                <p class="small mb-0">Don't have an account? 
                    <a href="register.php" class="fw-bold text-decoration-none">Register here</a>
                </p>
            </div>
        </form>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const userTypeBtns = document.querySelectorAll('.user-type-btn');
            const loginUserType = document.getElementById('loginUserType');
            const passwordField = document.getElementById('passwordField');
            const togglePassword = document.getElementById('togglePassword');

            // User type selection
            userTypeBtns.forEach(btn => {
                btn.addEventListener('click', function() {
                    const type = this.dataset.type;
                    
                    // Update active button
                    userTypeBtns.forEach(b => b.classList.remove('active'));
                    this.classList.add('active');
                    
                    // Update hidden input
                    loginUserType.value = type;
                });
            });

            // Password toggle
            togglePassword.addEventListener('click', function() {
                const type = passwordField.type === 'password' ? 'text' : 'password';
                passwordField.type = type;
                this.querySelector('i').classList.toggle('bi-eye');
                this.querySelector('i').classList.toggle('bi-eye-slash');
            });

            // Form validation
            document.getElementById('loginForm').addEventListener('submit', function(e) {
                const email = this.querySelector('input[name="email"]').value;
                const password = this.querySelector('input[name="password"]').value;
                
                if (!email || !password) {
                    e.preventDefault();
                    alert('Please fill in all fields');
                }
            });
        });
    </script>
</body>
</html>