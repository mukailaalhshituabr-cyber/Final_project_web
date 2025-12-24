<?php
require_once dirname(__DIR__, 2) . '/config.php';
require_once ROOT_PATH . '/includes/classes/Database.php';
require_once ROOT_PATH . '/includes/classes/User.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $selectedRole = $_POST['user_type'] ?? 'customer'; // What the user clicked on the UI

    $db = new Database();
    $conn = $db->getConnection();

    $stmt = $conn->prepare("SELECT * FROM users WHERE email = ? LIMIT 1");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($password, $user['password'])) {
        
        // IMPORTANT: We use the user_type FROM THE DATABASE, not the form
        $actualRole = strtolower($user['user_type']); 
        
        // Validation: Ensure they aren't trying to log into the wrong portal
        if ($actualRole !== strtolower($selectedRole)) {
            $error = "This account is a " . ucfirst($actualRole) . " account, not a " . ucfirst($selectedRole) . " account.";
        } else {
            // LOGIN SUCCESS
            $_SESSION['user_id'] = $user['id']; // This is your auto-generated ID from DB
            $_SESSION['user_type'] = $actualRole;
            $_SESSION['username'] = $user['username'];

            // Force a clean path to the dashboard
            $redirectPath = "../" . $actualRole . "/dashboard.php";
            header("Location: " . $redirectPath);
            exit();
        }
    } else {
        $error = "Invalid email or password";
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
        }
        .user-type-btn.active[data-type="customer"] { background-color: #0d6efd; color: white; border-color: #0d6efd; }
        .user-type-btn.active[data-type="tailor"] { background-color: #212529; color: white; border-color: #212529; }
        .user-type-btn.active[data-type="admin"] { background-color: #dc3545; color: white; border-color: #dc3545; }
        
        .form-control:focus { border-color: #667eea; box-shadow: 0 0 0 0.25rem rgba(102, 126, 234, 0.25); }
    </style>
</head>
<body>

<div class="login-card">
    <div class="text-center mb-4">
        <h2 class="fw-bold">Welcome Back</h2>
        <p class="text-muted">Sign in to your account</p>
    </div>

    <div class="user-type-selector d-flex gap-2 mb-4">
        <button type="button" class="btn user-type-btn active" data-type="customer">
            <i class="bi bi-person me-1"></i>Customer
        </button>
        <button type="button" class="btn user-type-btn" data-type="tailor">
            <i class="bi bi-scissors me-1"></i>Tailor
        </button>
        <button type="button" class="btn user-type-btn" data-type="admin">
            <i class="bi bi-shield-lock me-1"></i>Admin
        </button>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-danger small py-2"><?php echo $error; ?></div>
    <?php endif; ?>

    <?php if (isset($_GET['success'])): ?>
        <div class="alert alert-success small py-2">Registration successful! Please login.</div>
    <?php endif; ?>

    <form method="POST" action="" id="loginForm">
        <input type="hidden" name="user_type" id="loginUserType" value="customer">
        
        <div id="adminWarning" class="alert alert-warning d-none small">
            <i class="bi bi-shield-exclamation me-2"></i>Admin login requires special credentials.
        </div>

        <div class="mb-3">
            <label class="form-label" id="emailLabel">Email Address</label>
            <div class="input-group">
                <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                <input type="email" class="form-control" name="email" required 
                       value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
            </div>
        </div>

        <div class="mb-3">
            <label class="form-label">Password</label>
            <div class="input-group">
                <span class="input-group-text"><i class="bi bi-lock"></i></span>
                <input type="password" class="form-control" name="password" id="passwordField" required>
                <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                    <i class="bi bi-eye"></i>
                </button>
            </div>
        </div>

        <button type="submit" id="submitBtn" class="btn btn-primary w-100 py-2 mb-3">
            Sign In as Customer
        </button>

        <div class="text-center">
            <a href="forgot-password.php" class="text-decoration-none small">Forgot Password?</a>
            <hr>
            <p class="small mb-0">Don't have an account? <a href="register.php" class="fw-bold text-decoration-none">Register</a></p>
        </div>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const userTypeBtns = document.querySelectorAll('.user-type-btn');
    const loginUserType = document.getElementById('loginUserType');
    const submitBtn = document.getElementById('submitBtn');
    const adminWarning = document.getElementById('adminWarning');
    const emailLabel = document.getElementById('emailLabel');
    const passwordField = document.getElementById('passwordField');
    const toggleBtn = document.getElementById('togglePassword');

    // Switch Role Logic
    userTypeBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            const type = this.dataset.type;
            
            userTypeBtns.forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            
            loginUserType.value = type;
            
            if (type === 'admin') {
                submitBtn.className = "btn btn-danger w-100 py-2 mb-3";
                submitBtn.innerText = "Admin Login";
                adminWarning.classList.remove('d-none');
                emailLabel.innerText = "Admin Email";
            } else if (type === 'tailor') {
                submitBtn.className = "btn btn-dark w-100 py-2 mb-3";
                submitBtn.innerText = "Sign In as Tailor";
                adminWarning.classList.add('d-none');
                emailLabel.innerText = "Tailor Email Address";
            } else {
                submitBtn.className = "btn btn-primary w-100 py-2 mb-3";
                submitBtn.innerText = "Sign In as Customer";
                adminWarning.classList.add('d-none');
                emailLabel.innerText = "Email Address";
            }
        });
    });

    // Toggle Password Visibility
    toggleBtn.addEventListener('click', function() {
        const type = passwordField.type === 'password' ? 'text' : 'password';
        passwordField.type = type;
        this.querySelector('i').classList.toggle('bi-eye');
        this.querySelector('i').classList.toggle('bi-eye-slash');
    });
});
</script>

</body>
</html>



<?php
/*require_once dirname(__DIR__, 2) . '/config.php';
require_once ROOT_PATH . '/includes/classes/Database.php';
require_once ROOT_PATH . '/includes/classes/User.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (isset($_SESSION['user_id'])) {
    header('Location: ../' . $_SESSION['user_type'] . '/dashboard.php');
    exit();
}

$error = '';
$success = isset($_GET['success']) ? 'Registration successful! Please login.' : '';

if (password_verify($password, $user['password'])) {
    // Check if the type they selected matches their account
    if ($user['user_type'] !== $userType) {
        $error = "This account is not registered as a " . htmlspecialchars($userType);
    } else {
        // Correct login! Set sessions
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_type'] = $user['user_type'];

        // Redirect based on type
        if ($user['user_type'] === 'admin') {
            header("Location: ../admin/dashboard.php");
        } elseif ($user['user_type'] === 'tailor') {
            header("Location: ../tailor/dashboard.php");
        } else {
            header("Location: ../customer/dashboard.php");
        }
        exit();
    }
}
?>
<div class="user-type-selector d-flex gap-2 mb-4">
<form method="POST" action="" id="loginForm">

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
            overflow: hidden;
            max-width: 900px;
            margin: 0 auto;
        }
        .login-left {
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.1) 0%, rgba(118, 75, 162, 0.1) 100%);
            padding: 3rem;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }
        .login-logo {
            font-size: 2.5rem;
            font-weight: 700;
            color: #667eea;
            margin-bottom: 2rem;
        }
        .feature-list li { margin-bottom: 1rem; display: flex; align-items: center; }
        .feature-list i { color: #667eea; margin-right: 10px; font-size: 1.2rem; }
        .user-type-btn { flex: 1; padding: 0.75rem; border: 2px solid #dee2e6; background: white; color: #6c757d; transition: all 0.3s ease; }
        .user-type-btn.active { background: #667eea; color: white; border-color: #667eea; }
        #adminLoginForm { display: none; }
        @media (max-width: 768px) { .login-left { display: none; } }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-10">
                <div class="login-card">
                    <div class="row g-0">
                        <div class="col-lg-6 d-none d-lg-block">
                            <div class="login-left h-100">
                                <div class="login-logo">
                                    <i class="bi bi-shop me-2"></i><?php echo htmlspecialchars(SITE_NAME); ?>
                                </div>
                                <h2 class="fw-bold mb-4">Welcome Back!</h2>
                                <ul class="feature-list list-unstyled">
                                    <li><i class="bi bi-check-circle-fill"></i>Track your orders</li>
                                    <li><i class="bi bi-check-circle-fill"></i>Manage your profile</li>
                                    <li><i class="bi bi-check-circle-fill"></i>Secure Access</li>
                                </ul>
                                <div class="mt-5">
                                    <p class="text-muted mb-2">New here?</p>
                                    <a href="register.php" class="btn btn-outline-primary">Create Account</a>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-lg-6">
                            <div class="p-4 p-md-5">
                                <div class="text-center mb-4">
                                    <h2 class="fw-bold mb-2">Sign In</h2>
                                    <p class="text-muted">Enter your credentials</p>
                                </div>

                                <?php if ($success): ?>
                                    <div class="alert alert-success alert-dismissible fade show"><?php echo $success; ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
                                <?php endif; ?>
                                <?php if ($error): ?>
                                    <div class="alert alert-danger alert-dismissible fade show"><?php echo $error; ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
                                <?php endif; ?>

                                <div class="user-type-selector d-flex gap-2 mb-4">
                                    <button type="button" class="btn user-type-btn active" data-type="customer">
                                        <i class="bi bi-person me-1"></i>Customer
                                    </button>
                                    <button type="button" class="btn user-type-btn" data-type="tailor">
                                        <i class="bi bi-scissors me-1"></i>Tailor
                                    </button>
                                    <button type="button" class="btn user-type-btn" data-type="admin">
                                        <i class="bi bi-shield-lock me-1"></i>Admin
                                    </button>
                                </div>

                                <form method="POST" action="" id="loginForm">
                                    <input type="hidden" name="user_type" id="loginUserType" value="customer">
                                    
                                    <div id="adminWarning" class="alert alert-warning d-none">
                                        <i class="bi bi-shield-exclamation me-2"></i>Admin login requires special credentials
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label" id="emailLabel">Email Address</label>
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                                            <input type="email" class="form-control" name="email" required 
                                                value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                                        </div>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label">Password</label>
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="bi bi-lock"></i></span>
                                            <input type="password" class="form-control" name="password" id="passwordField" required>
                                            <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                                                <i class="bi bi-eye"></i>
                                            </button>
                                        </div>
                                    </div>

                                    <button type="submit" id="submitBtn" class="btn btn-primary w-100 py-2 mb-3">
                                        Sign In
                                    </button>

                                    <div class="text-center">
                                        <a href="forgot-password.php" class="text-decoration-none small">Forgot Password?</a>
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
        document.addEventListener('DOMContentLoaded', function() {
            const userTypeBtns = document.querySelectorAll('.user-type-btn');
            const loginUserType = document.getElementById('loginUserType');
            const submitBtn = document.getElementById('submitBtn');
            const adminWarning = document.getElementById('adminWarning');
            const emailLabel = document.getElementById('emailLabel');

            // 1. Handle User Type Switching
            userTypeBtns.forEach(btn => {
                btn.addEventListener('click', function() {
                    const type = this.dataset.type;
                    
                    // UI Updates
                    userTypeBtns.forEach(b => b.classList.remove('active'));
                    this.classList.add('active');
                    
                    // Logic Updates
                    loginUserType.value = type;
                    
                    // Change Button Styles & Text
                    if (type === 'admin') {
                        submitBtn.className = "btn btn-danger w-100 py-2 mb-3";
                        submitBtn.innerText = "Admin Login";
                        adminWarning.classList.remove('d-none');
                        emailLabel.innerText = "Admin Email";
                    } else if (type === 't') {
                        submitBtn.className = "btn btn-dark w-100 py-2 mb-3";
                        submitBtn.innerText = "Sign In as T";
                        adminWarning.classList.add('d-none');
                        emailLabel.innerText = "T Email Address";
                    } else {
                        submitBtn.className = "btn btn-primary w-100 py-2 mb-3";
                        submitBtn.innerText = "Sign In as Customer";
                        adminWarning.classList.add('d-none');
                        emailLabel.innerText = "Email Address";
                    }
                });
            });

            // 2. Toggle Password Visibility
            const toggleBtn = document.getElementById('togglePassword');
            const passwordField = document.getElementById('passwordField');
            
            toggleBtn.addEventListener('click', function() {
                const type = passwordField.type === 'password' ? 'text' : 'password';
                passwordField.type = type;
                this.querySelector('i').classList.toggle('bi-eye');
                this.querySelector('i').classList.toggle('bi-eye-slash');
            });
        });
    </script>
</body>
</html>


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
            overflow: hidden;
            max-width: 900px;
            margin: 0 auto;
        }
        .login-left {
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.1) 0%, rgba(118, 75, 162, 0.1) 100%);
            padding: 3rem;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }
        .login-logo {
            font-size: 2.5rem;
            font-weight: 700;
            color: #667eea;
            margin-bottom: 2rem;
        }
        .feature-list li { margin-bottom: 1rem; display: flex; align-items: center; }
        .feature-list i { color: #667eea; margin-right: 10px; font-size: 1.2rem; }
        .user-type-btn { flex: 1; padding: 0.75rem; border: 2px solid #dee2e6; background: white; color: #6c757d; transition: all 0.3s ease; }
        .user-type-btn.active { background: #667eea; color: white; border-color: #667eea; }
        #adminLoginForm { display: none; }
        @media (max-width: 768px) { .login-left { display: none; } }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-10">
                <div class="login-card">
                    <div class="row g-0">
                        <div class="col-lg-6 d-none d-lg-block">
                            <div class="login-left h-100">
                                <div class="login-logo">
                                    <i class="bi bi-shop me-2"></i><?php echo htmlspecialchars(SITE_NAME); ?>
                                </div>
                                <h2 class="fw-bold mb-4">Welcome Back!</h2>
                                <ul class="feature-list list-unstyled">
                                    <li><i class="bi bi-check-circle-fill"></i>Track your orders</li>
                                    <li><i class="bi bi-check-circle-fill"></i>Manage your profile</li>
                                    <li><i class="bi bi-check-circle-fill"></i>Secure Access</li>
                                </ul>
                                <div class="mt-5">
                                    <p class="text-muted mb-2">New here?</p>
                                    <a href="register.php" class="btn btn-outline-primary">Create Account</a>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-lg-6">
                            <div class="p-4 p-md-5">
                                <div class="text-center mb-4">
                                    <h2 class="fw-bold mb-2">Sign In</h2>
                                    <p class="text-muted">Enter your credentials</p>
                                </div>

                                <?php if ($success): ?>
                                    <div class="alert alert-success alert-dismissible fade show"><?php echo $success; ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
                                <?php endif; ?>
                                <?php if ($error): ?>
                                    <div class="alert alert-danger alert-dismissible fade show"><?php echo $error; ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
                                <?php endif; ?>

                                <div class="user-type-selector d-flex gap-2 mb-4">
                                    <button type="button" class="btn user-type-btn active" data-type="customer"><i class="bi bi-person me-1"></i>Customer</button>
                                    <button type="button" class="btn user-type-btn" data-type="t"><i class="bi bi-scissors me-1"></i>T</button>
                                    <button type="button" class="btn user-type-btn" data-type="admin"><i class="bi bi-shield-lock me-1"></i>Admin</button>
                                </div>

                                <form method="POST" action="" id="loginForm">
                                    <input type="hidden" name="user_type" id="loginUserType" value="customer">
                                    
                                    <div id="adminWarning" class="alert alert-warning d-none">
                                        <i class="bi bi-shield-exclamation me-2"></i>Admin login requires special credentials
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label" id="emailLabel">Email Address</label>
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                                            <input type="email" class="form-control" name="email" required 
                                                   value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                                        </div>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label">Password</label>
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="bi bi-lock"></i></span>
                                            <input type="password" class="form-control" name="password" id="passwordField" required>
                                            <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                                                <i class="bi bi-eye"></i>
                                            </button>
                                        </div>
                                    </div>

                                    <button type="submit" id="submitBtn" class="btn btn-primary w-100 py-2 mb-3">
                                        Sign In as Customer
                                    </button>

                                    <div class="text-center">
                                        <a href="forgot-password.php" class="text-decoration-none small">Forgot Password?</a>
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
        document.addEventListener('DOMContentLoaded', function() {
            const userTypeBtns = document.querySelectorAll('.user-type-btn');
            const loginUserType = document.getElementById('loginUserType');
            const submitBtn = document.getElementById('submitBtn');
            const adminWarning = document.getElementById('adminWarning');
            const emailLabel = document.getElementById('emailLabel');

            // 1. Handle User Type Switching
            userTypeBtns.forEach(btn => {
                btn.addEventListener('click', function() {
                    const type = this.dataset.type;
                    
                    // UI Updates
                    userTypeBtns.forEach(b => b.classList.remove('active'));
                    this.classList.add('active');
                    
                    // Logic Updates
                    loginUserType.value = type;
                    
                    // Change Button Styles & Text
                    if (type === 'admin') {
                        submitBtn.className = "btn btn-danger w-100 py-2 mb-3";
                        submitBtn.innerText = "Admin Login";
                        adminWarning.classList.remove('d-none');
                        emailLabel.innerText = "Admin Email";
                    } else if (type === 't') {
                        submitBtn.className = "btn btn-dark w-100 py-2 mb-3";
                        submitBtn.innerText = "Sign In as T";
                        adminWarning.classList.add('d-none');
                        emailLabel.innerText = "T Email Address";
                    } else {
                        submitBtn.className = "btn btn-primary w-100 py-2 mb-3";
                        submitBtn.innerText = "Sign In as Customer";
                        adminWarning.classList.add('d-none');
                        emailLabel.innerText = "Email Address";
                    }
                });
            });

            // 2. Toggle Password Visibility
            const toggleBtn = document.getElementById('togglePassword');
            const passwordField = document.getElementById('passwordField');
            
            toggleBtn.addEventListener('click', function() {
                const type = passwordField.type === 'password' ? 'text' : 'password';
                passwordField.type = type;
                this.querySelector('i').classList.toggle('bi-eye');
                this.querySelector('i').classList.toggle('bi-eye-slash');
            });
        });
    </script>
</body>
</html>


ssssssss
require_once '../../config.php';
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

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $remember = isset($_POST['remember']);
    
    if (empty($email) || empty($password)) {
        $error = 'Please fill in all fields';
    } else {
        $user = new User();
        $userData = $user->login($email, $password);
        
        if ($userData) {
            // Set session variables
            $_SESSION['user_id'] = $userData['id'];
            $_SESSION['user_type'] = $userData['user_type'];
            $_SESSION['full_name'] = $userData['full_name'];
            $_SESSION['email'] = $userData['email'];
            $_SESSION['username'] = $userData['username'];
            
            // Set remember me cookie (30 days)
            if ($remember) {
                $token = bin2hex(random_bytes(32));
                setcookie('remember_token', $token, time() + (30 * 24 * 60 * 60), '/');
                // Store token in database (you'll need to implement this)
            }
            
            // Redirect based on user type
            $redirectUrl = SITE_URL . '/pages/' . $userData['user_type'] . '/dashboard.php';
            header('Location: ' . $redirectUrl);
            exit();
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
    <title>Login - <?php echo SITE_NAME; ?></title>
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
            overflow: hidden;
        }
        
        .login-left {
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.1) 0%, rgba(118, 75, 162, 0.1) 100%);
            padding: 3rem;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }
        
        .login-logo {
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
        
        .social-login .btn {
            width: 100%;
            padding: 0.75rem;
            margin-bottom: 0.5rem;
        }
        
        @media (max-width: 768px) {
            .login-left {
                display: none;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-10">
                <div class="login-card">
                    <div class="row g-0">
                        <!-- Left Side - Branding & Features -->
                        <div class="col-lg-6 d-none d-lg-block">
                            <div class="login-left">
                                <div class="login-logo">
                                    <i class="bi bi-shop me-2"></i><?php echo SITE_NAME; ?>
                                </div>
                                <h2 class="fw-bold mb-4">Welcome Back!</h2>
                                <p class="text-muted mb-4">Sign in to access your account and continue your fashion journey.</p>
                                
                                <ul class="feature-list list-unstyled">
                                    <li>
                                        <i class="bi bi-check-circle-fill"></i>
                                        <span>Access your personalized dashboard</span>
                                    </li>
                                    <li>
                                        <i class="bi bi-check-circle-fill"></i>
                                        <span>Track your orders and measurements</span>
                                    </li>
                                    <li>
                                        <i class="bi bi-check-circle-fill"></i>
                                        <span>Connect with talented ts</span>
                                    </li>
                                    <li>
                                        <i class="bi bi-check-circle-fill"></i>
                                        <span>Get personalized recommendations</span>
                                    </li>
                                </ul>
                                
                                <div class="mt-5">
                                    <p class="text-muted mb-2">New to <?php echo SITE_NAME; ?>?</p>
                                    <a href="register.php" class="btn btn-outline-primary">
                                        Create an Account <i class="bi bi-arrow-right ms-2"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Right Side - Login Form -->
                        <div class="col-lg-6">
                            <div class="p-5">
                                <div class="text-center mb-5">
                                    <h2 class="fw-bold">Sign In</h2>
                                    <p class="text-muted">Enter your credentials to continue</p>
                                </div>
                                
                                <?php if ($error): ?>
                                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                    <?php echo htmlspecialchars($error); ?>
                                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                                </div>
                                <?php endif; ?>
                                
                                <?php if ($success): ?>
                                <div class="alert alert-success alert-dismissible fade show" role="alert">
                                    <?php echo htmlspecialchars($success); ?>
                                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                                </div>
                                <?php endif; ?>
                                
                                <form method="POST" action="">
                                    <div class="mb-4">
                                        <label class="form-label fw-bold">Email Address</label>
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                                            <input type="email" class="form-control" name="email" 
                                                   value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" 
                                                   placeholder="your@email.com" required>
                                        </div>
                                    </div>
                                    
                                    <div class="mb-4">
                                        <label class="form-label fw-bold">Password</label>
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="bi bi-lock"></i></span>
                                            <input type="password" class="form-control" name="password" 
                                                   placeholder="Enter your password" required>
                                            <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                                                <i class="bi bi-eye"></i>
                                            </button>
                                        </div>
                                        <div class="text-end mt-2">
                                            <a href="forgot-password.php" class="text-decoration-none small">
                                                Forgot Password?
                                            </a>
                                        </div>
                                    </div>
                                    
                                    <div class="mb-4 form-check">
                                        <input type="checkbox" class="form-check-input" id="remember" name="remember">
                                        <label class="form-check-label" for="remember">Remember me</label>
                                    </div>
                                    
                                    <button type="submit" class="btn btn-primary w-100 py-2 mb-4">
                                        <i class="bi bi-box-arrow-in-right me-2"></i> Sign In
                                    </button>
                                    
                                    <div class="text-center mb-4">
                                        <span class="text-muted">Or sign in with</span>
                                    </div>
                                    
                                    <div class="social-login mb-4">
                                        <button type="button" class="btn btn-outline-primary">
                                            <i class="bi bi-google me-2"></i> Google
                                        </button>
                                        <button type="button" class="btn btn-outline-primary">
                                            <i class="bi bi-facebook me-2"></i> Facebook
                                        </button>
                                    </div>
                                    
                                    <div class="text-center">
                                        <p class="text-muted mb-0">Don't have an account? 
                                            <a href="register.php" class="text-decoration-none fw-bold">Sign Up</a>
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
        // Toggle password visibility
        document.getElementById('togglePassword').addEventListener('click', function() {
            const passwordInput = document.querySelector('input[name="password"]');
            const icon = this.querySelector('i');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                icon.classList.remove('bi-eye');
                icon.classList.add('bi-eye-slash');
            } else {
                passwordInput.type = 'password';
                icon.classList.remove('bi-eye-slash');
                icon.classList.add('bi-eye');
            }
        });
        
        // Form validation
        document.querySelector('form').addEventListener('submit', function(e) {
            const email = this.querySelector('input[name="email"]').value;
            const password = this.querySelector('input[name="password"]').value;
            
            if (!email || !password) {
                e.preventDefault();
                alert('Please fill in all fields');
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

// Check if user is already logged in
if (isset($_SESSION['user_id'])) {
    $user = new User();
    $userData = $user->getUserById($_SESSION['user_id']);
    
    if ($userData) {
        switch ($userData['user_type']) {
            case 'admin':
                header('Location: ' . SITE_URL . '/pages/admin/dashboard.php');
                break;
            case 't':
                header('Location: ' . SITE_URL . '/pages/t/dashboard.php');
                break;
            default:
                header('Location: ' . SITE_URL . '/index.php');
        }
        exit();
    }
}

$user = new User();
$error = '';
$success = '';

// Check for logout message
if (isset($_GET['logout']) && $_GET['logout'] == 'success') {
    $success = 'You have been successfully logged out.';
}

// Check for registration success
if (isset($_GET['registered']) && $_GET['registered'] == 'true') {
    $success = 'Registration successful! You can now login with your credentials.';
}

// Check for password reset success
if (isset($_GET['reset']) && $_GET['reset'] == 'success') {
    $success = 'Password has been reset successfully! Please login with your new password.';
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $remember = isset($_POST['remember']);
    
    if (empty($email) || empty($password)) {
        $error = 'Please enter both email and password';
    } else {
        $loggedInUser = $user->login($email, $password);
        
        if ($loggedInUser) {
            $_SESSION['user_id'] = $loggedInUser['id'];
            $_SESSION['user_type'] = $loggedInUser['user_type'];
            $_SESSION['username'] = $loggedInUser['username'];
            $_SESSION['full_name'] = $loggedInUser['full_name'];
            
            // Update last login
            $user->updateLastLogin($loggedInUser['id']);
            
            // Set remember me cookie if checked
            if ($remember) {
                $token = bin2hex(random_bytes(32));
                $expires = time() + (30 * 24 * 60 * 60); // 30 days
                setcookie('remember_token', $token, $expires, '/');
                // Store token in database (you'll need to implement this)
            }
            
            // Set success message for display
            $_SESSION['login_success'] = 'Welcome back, ' . $loggedInUser['full_name'] . '!';
            
            // Redirect based on user type
            switch ($loggedInUser['user_type']) {
                case 'admin':
                    header('Location: ' . SITE_URL . '/pages/admin/dashboard.php');
                    break;
                case 't':
                    header('Location: ' . SITE_URL . '/pages/t/dashboard.php');
                    break;
                default:
                    header('Location: ' . SITE_URL . '/index.php');
            }
            exit();
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
    <title>Login - <?php echo SITE_NAME; ?></title>
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
            min-height: 650px;
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
        
        .input-icon {
            position: absolute;
            right: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: #94a3b8;
            z-index: 2;
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
            align-items: center;
            gap: 0.5rem;
        }
        
        .form-check-input {
            width: 18px;
            height: 18px;
            cursor: pointer;
            border: 2px solid #cbd5e1;
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
        
        .btn-login {
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
        
        .btn-login:hover {
            transform: translateY(-3px);
            box-shadow: 0 15px 30px rgba(102, 126, 234, 0.3);
        }
        
        .btn-login:active {
            transform: translateY(-1px);
        }
        
        .btn-login i {
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
        
        .social-login {
            margin: 2rem 0;
            position: relative;
            text-align: center;
        }
        
        .social-divider {
            display: flex;
            align-items: center;
            margin-bottom: 1.5rem;
        }
        
        .social-divider::before,
        .social-divider::after {
            content: '';
            flex: 1;
            height: 1px;
            background: #e2e8f0;
        }
        
        .social-divider span {
            padding: 0 1rem;
            color: #94a3b8;
            font-size: 0.9rem;
        }
        
        .social-buttons {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 1rem;
        }
        
        .btn-social {
            border: 2px solid #e2e8f0;
            background: white;
            border-radius: 12px;
            padding: 0.75rem;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
            color: #4a5568;
            text-decoration: none;
        }
        
        .btn-social:hover {
            border-color: #667eea;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }
        
        .btn-social i {
            font-size: 1.2rem;
        }
        
        .register-link {
            text-align: center;
            margin-top: 2rem;
            padding-top: 1.5rem;
            border-top: 2px dashed #e2e8f0;
        }
        
        .register-link p {
            color: #718096;
            margin-bottom: 0.5rem;
        }
        
        .btn-register {
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
        
        .btn-register:hover {
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
        
        @keyframes float {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-20px); }
        }
        
        .floating-element {
            animation: float 6s ease-in-out infinite;
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
    <!-- Animated background particles -->
    <div class="particles" id="particles"></div>
    
    <div class="login-container animate__animated animate__fadeIn">
        <div class="login-card">
            <!-- Left side - Branding -->
            <div class="login-brand floating-element">
                <div class="brand-pattern"></div>
                
                <a href="<?php echo SITE_URL; ?>/index.php" class="brand-logo">
                    <div class="logo-icon">
                        <i class="bi bi-scissors"></i>
                    </div>
                </a>
                
                <h1 class="brand-title">Welcome Back</h1>
                <p class="brand-subtitle">Sign in to continue to <?php echo SITE_NAME; ?></p>
                
                <ul class="features-list">
                    <li class="feature-item">
                        <div class="feature-icon">
                            <i class="bi bi-globe"></i>
                        </div>
                        <span>Global marketplace connecting ts and customers</span>
                    </li>
                    <li class="feature-item">
                        <div class="feature-icon">
                            <i class="bi bi-shield-check"></i>
                        </div>
                        <span>Secure transactions with encrypted payments</span>
                    </li>
                    <li class="feature-item">
                        <div class="feature-icon">
                            <i class="bi bi-chat-dots"></i>
                        </div>
                        <span>Direct communication with talented ts</span>
                    </li>
                    <li class="feature-item">
                        <div class="feature-icon">
                            <i class="bi bi-truck"></i>
                        </div>
                        <span>Worldwide shipping for unique clothing</span>
                    </li>
                </ul>
            </div>
            
            <!-- Right side - Login Form -->
            <div class="login-form">
                <div class="form-header">
                    <h2 class="form-title">Sign In</h2>
                    <p class="form-subtitle">Enter your credentials to access your account</p>
                </div>
                
                <!-- Success Message -->
                <?php if ($success): ?>
                    <div class="message-box message-success">
                        <i class="bi bi-check-circle-fill message-icon"></i>
                        <div>
                            <strong>Success!</strong>
                            <p class="mb-0"><?php echo $success; ?></p>
                        </div>
                    </div>
                <?php endif; ?>
                
                <!-- Error Message -->
                <?php if ($error): ?>
                    <div class="message-box message-error">
                        <i class="bi bi-exclamation-triangle-fill message-icon"></i>
                        <div>
                            <strong>Error!</strong>
                            <p class="mb-0"><?php echo $error; ?></p>
                        </div>
                    </div>
                <?php endif; ?> 
                
                <!-- Login Form -->
                <form method="POST" action="" id="loginForm">
                    <div class="form-group">
                        <label for="email" class="form-label">
                            <i class="bi bi-envelope"></i>
                            Email Address
                        </label>
                        <div class="input-group">
                            <input type="email" 
                                   class="form-control" 
                                   id="email" 
                                   name="email" 
                                   placeholder="Enter your email address"
                                   required
                                   value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
                                   autocomplete="email">
                            <i class="bi bi-envelope input-icon"></i>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="password" class="form-label">
                            <i class="bi bi-lock"></i>
                            Password
                        </label>
                        <div class="input-group">
                            <input type="password" 
                                   class="form-control" 
                                   id="password" 
                                   name="password" 
                                   placeholder="Enter your password"
                                   required
                                   autocomplete="current-password">
                            <i class="bi bi-eye-slash input-icon password-toggle" id="togglePassword"></i>
                        </div>
                    </div>
                    
                    <div class="form-options">
                        <div class="form-check">
                            <input type="checkbox" 
                                   class="form-check-input" 
                                   id="remember" 
                                   name="remember"
                                   <?php echo isset($_POST['remember']) ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="remember">Remember me</label>
                        </div>
                        <a href="forgot-password.php" class="forgot-link">
                            Forgot Password?
                        </a>
                    </div>
                    
                    <button type="submit" class="btn-login" id="submitBtn">
                        <i class="bi bi-box-arrow-in-right"></i>
                        <span id="btnText">Sign In</span>
                        <div class="loading-spinner" id="loadingSpinner"></div>
                    </button>
                </form>
                
                <!-- Social Login -->
                <div class="social-login">
                    <div class="social-divider">
                        <span>Or continue with</span>
                    </div>
                    <div class="social-buttons">
                        <a href="#" class="btn-social">
                            <i class="bi bi-google"></i>
                        </a>
                        <a href="#" class="btn-social">
                            <i class="bi bi-facebook"></i>
                        </a>
                        <a href="#" class="btn-social">
                            <i class="bi bi-github"></i>
                        </a>
                    </div>
                </div>
                
                <!-- Register Link -->
                <div class="register-link">
                    <p>Don't have an account?</p>
                    <a href="register.php" class="btn-register">
                        <i class="bi bi-person-plus"></i>
                        Create Account
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('loginForm');
            const submitBtn = document.getElementById('submitBtn');
            const btnText = document.getElementById('btnText');
            const loadingSpinner = document.getElementById('loadingSpinner');
            const togglePassword = document.getElementById('togglePassword');
            const passwordInput = document.getElementById('password');
            const emailInput = document.getElementById('email');
            
            // Create animated background particles
            function createParticles() {
                const particlesContainer = document.getElementById('particles');
                for (let i = 0; i < 20; i++) {
                    const particle = document.createElement('div');
                    particle.className = 'particle';
                    const size = Math.random() * 20 + 5;
                    particle.style.width = `${size}px`;
                    particle.style.height = `${size}px`;
                    particle.style.left = `${Math.random() * 100}%`;
                    particle.style.top = `${Math.random() * 100}%`;
                    particle.style.opacity = Math.random() * 0.3 + 0.1;
                    
                    // Animation
                    particle.style.animation = `float ${Math.random() * 10 + 10}s linear infinite`;
                    
                    particlesContainer.appendChild(particle);
                }
            }
            
            createParticles();
            
            // Toggle password visibility
            togglePassword.addEventListener('click', function() {
                const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
                passwordInput.setAttribute('type', type);
                this.classList.toggle('bi-eye');
                this.classList.toggle('bi-eye-slash');
            });
            
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
            
            // Form submission with validation
            form.addEventListener('submit', async function(e) {
                e.preventDefault();
                
                const email = emailInput.value.trim();
                const password = passwordInput.value.trim();
                
                // Basic validation
                if (!email) {
                    showError('Please enter your email address');
                    emailInput.focus();
                    return;
                }
                
                if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
                    showError('Please enter a valid email address');
                    emailInput.focus();
                    return;
                }
                
                if (!password) {
                    showError('Please enter your password');
                    passwordInput.focus();
                    return;
                }
                
                if (password.length < 6) {
                    showError('Password must be at least 6 characters');
                    passwordInput.focus();
                    return;
                }
                
                // Show loading state
                submitBtn.disabled = true;
                btnText.textContent = 'Signing in...';
                loadingSpinner.style.display = 'inline-block';
                
                try {
                    // Simulate API call (remove this in production)
                    await new Promise(resolve => setTimeout(resolve, 1500));
                    
                    // In production, submit the form
                    form.submit();
                    
                } catch (error) {
                    showError('Login failed. Please try again.');
                    submitBtn.disabled = false;
                    btnText.textContent = 'Sign In';
                    loadingSpinner.style.display = 'none';
                }
            });
            
            function showError(message) {
                // Create error message
                const messageBox = document.createElement('div');
                messageBox.className = 'message-box message-error';
                messageBox.innerHTML = `
                    <i class="bi bi-exclamation-triangle-fill message-icon"></i>
                    <div>
                        <strong>Error!</strong>
                        <p class="mb-0">${message}</p>
                    </div>
                `;
                
                // Add shake animation to form
                form.classList.add('shake');
                setTimeout(() => form.classList.remove('shake'), 500);
                
                // Insert message before form
                form.parentNode.insertBefore(messageBox, form);
                
                // Remove message after 5 seconds
                setTimeout(() => {
                    messageBox.remove();
                }, 5000);
            }
            
            // Auto-focus email field if empty
            if (!emailInput.value) {
                emailInput.focus();
            }
            
            // Add mouse parallax effect
            const card = document.querySelector('.login-card');
            let mouseX = 0;
            let mouseY = 0;
            
            document.addEventListener('mousemove', (e) => {
                mouseX = (e.clientX - window.innerWidth / 2) / 50;
                mouseY = (e.clientY - window.innerHeight / 2) / 50;
                
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
            
            // Add keyboard shortcuts
            document.addEventListener('keydown', (e) => {
                // Ctrl + Enter to submit form
                if (e.ctrlKey && e.key === 'Enter') {
                    form.submit();
                }
                
                // Escape to clear form
                if (e.key === 'Escape') {
                    form.reset();
                }
            });
            
            // Demo login credentials (remove in production)
            const demoCredentials = {
                'admin@marketplace.com': 'admin123',
                't@example.com': 't123',
                'customer@example.com': 'customer123'
            };
            
            // Quick fill for demo (remove in production)
            emailInput.addEventListener('keydown', (e) => {
                if (e.ctrlKey && e.key === '1') {
                    emailInput.value = 'admin@marketplace.com';
                    passwordInput.value = 'admin123';
                }
                if (e.ctrlKey && e.key === '2') {
                    emailInput.value = 't@example.com';
                    passwordInput.value = 't123';
                }
                if (e.ctrlKey && e.key === '3') {
                    emailInput.value = 'customer@example.com';
                    passwordInput.value = 'customer123';
                }
            });
        });
    </script>
</body>rotate
</html>
*/