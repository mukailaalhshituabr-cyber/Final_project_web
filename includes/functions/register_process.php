
// ============================================
// REGISTRATION PROCESSING FILE
// ============================================
<?php
session_start();
require_once '../../config.php';
require_once '../../includes/config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../../?page=register&error=Invalid request method");
    exit();
}

// Get form data
$full_name = sanitize_input($_POST['full_name'] ?? '');
$email = sanitize_input($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';
$confirm_password = $_POST['confirm_password'] ?? '';
$user_type = sanitize_input($_POST['user_type'] ?? 'customer');
$phone = sanitize_input($_POST['phone'] ?? '');
$address = sanitize_input($_POST['address'] ?? '');
$bio = sanitize_input($_POST['bio'] ?? '');
$experience = sanitize_input($_POST['experience'] ?? '');
$specialization = sanitize_input($_POST['specialization'] ?? '');
$terms = isset($_POST['terms']);
$newsletter = isset($_POST['newsletter']) ? 1 : 0;

// Validation
$errors = [];

if (empty($full_name)) {
    $errors[] = "Full name is required";
}

if (empty($email)) {
    $errors[] = "Email is required";
} elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = "Invalid email format";
}

if (empty($password)) {
    $errors[] = "Password is required";
} elseif (strlen($password) < 6) {
    $errors[] = "Password must be at least 6 characters";
}

if ($password !== $confirm_password) {
    $errors[] = "Passwords do not match";
}

if (!$terms) {
    $errors[] = "You must agree to the terms and conditions";
}

// Check if email already exists
try {
    $existing = Database::fetch(
        "SELECT id FROM users WHERE email = ?",
        [$email]
    );
    if ($existing) {
        $errors[] = "Email already registered";
    }
} catch (Exception $e) {
    $errors[] = "Database error occurred";
}

// If there are errors, redirect back
if (!empty($errors)) {
    $error_string = implode('|', $errors);
    header("Location: ../../?page=register&type=$user_type&error=" . urlencode($error_string));
    exit();
}

try {
    // Begin transaction
    Database::beginTransaction();
    
    // Generate username from email
    $username = strtolower(explode('@', $email)[0]);
    
    // Hash password
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    
    // Prepare user data
    $user_data = [
        'username' => $username,
        'email' => $email,
        'password' => $hashed_password,
        'user_type' => $user_type,
        'full_name' => $full_name,
        'phone' => $phone,
        'address' => $address,
        'bio' => $bio,
        'status' => 'active',
        'created_at' => date('Y-m-d H:i:s')
    ];
    
    // Add tailor-specific fields
    if ($user_type === 'tailor') {
        $user_data['experience'] = $experience;
        $user_data['specialization'] = $specialization;
    }
    
    // Insert user
    $columns = implode(', ', array_keys($user_data));
    $placeholders = ':' . implode(', :', array_keys($user_data));
    
    $sql = "INSERT INTO users ($columns) VALUES ($placeholders)";
    $stmt = Database::execute($sql, $user_data);
    
    if (!$stmt) {
        throw new Exception("Failed to insert user");
    }
    
    $user_id = Database::lastInsertId();
    
    // Create user profile
    $profile_sql = "INSERT INTO user_profiles (user_id, newsletter_subscription) VALUES (?, ?)";
    Database::execute($profile_sql, [$user_id, $newsletter]);
    
    // Commit transaction
    Database::commit();
    
    // Set session
    $_SESSION['user_id'] = $user_id;
    $_SESSION['username'] = $username;
    $_SESSION['email'] = $email;
    $_SESSION['user_type'] = $user_type;
    $_SESSION['full_name'] = $full_name;
    $_SESSION['logged_in'] = true;
    
    // Send welcome email (optional)
    // sendWelcomeEmail($email, $full_name, $user_type);
    
    // Redirect based on user type
    $redirect_page = 'dashboard';
    $welcome_message = "Welcome to Global Clothing Marketplace, $full_name!";
    
    if ($user_type === 'tailor') {
        $redirect_page = 'tailor-dashboard';
        $welcome_message = "Welcome, Tailor $full_name! Your account has been created.";
    } elseif ($user_type === 'admin') {
        $redirect_page = 'admin-dashboard';
    }
    
    header("Location: ../../?page=$redirect_page&message=" . urlencode($welcome_message));
    exit();
    
} catch (Exception $e) {
    // Rollback on error
    Database::rollback();
    
    error_log("Registration error: " . $e->getMessage());
    header("Location: ../../?page=register&type=$user_type&error=Registration failed. Please try again.");
    exit();
}

// Function to send welcome email
function sendWelcomeEmail($email, $name, $user_type) {
    $subject = "Welcome to Global Clothing Marketplace!";
    
    if ($user_type === 'tailor') {
        $message = "
            <h2>Welcome, Tailor $name!</h2>
            <p>Your tailor account has been successfully created.</p>
            <p>You can now:</p>
            <ul>
                <li>Add your products</li>
                <li>Set up your tailor profile</li>
                <li>Start receiving orders</li>
                <li>Chat with customers</li>
            </ul>
            <p>Login to your dashboard to get started.</p>
        ";
    } else {
        $message = "
            <h2>Welcome to Global Clothing Marketplace, $name!</h2>
            <p>Your account has been successfully created.</p>
            <p>You can now:</p>
            <ul>
                <li>Browse unique clothing from talented tailors</li>
                <li>Place custom orders</li>
                <li>Save items to your wishlist</li>
                <li>Chat directly with tailors</li>
            </ul>
            <p>Start exploring our marketplace today!</p>
        ";
    }
    
    // In a real application, you would use PHPMailer or similar
    // mail($email, $subject, $message, "Content-Type: text/html; charset=UTF-8");
}
?>
