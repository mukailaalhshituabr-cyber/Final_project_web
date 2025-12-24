<?php
// Start session at the very beginning
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Security check - must be logged in as customer
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'customer') {
    header('Location: ../auth/login.php');
    exit();
}

require_once '../../config.php';
require_once '../../includes/classes/Database.php';
require_once '../../includes/classes/User.php';
require_once '../../includes/classes/Address.php';

$userId = $_SESSION['user_id'];
$db = Database::getInstance();
$user = new User();
$address = new Address();

// Define upload paths RELATIVELY
$uploadBaseDir = dirname(__DIR__, 3) . '/assets/images/'; // Goes up 3 levels from pages/customer/
$avatarDir = $uploadBaseDir . 'avatars/';
$tempDir = sys_get_temp_dir() . '/';

// Ensure directories exist with proper permissions
$directories = [$avatarDir];
foreach ($directories as $dir) {
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
}

// Alternative: Use relative path for web access
$webAvatarPath = '../../assets/images/avatars/';

// Set PHP temp directory
ini_set('upload_tmp_dir', $tempDir);

// Get user data
$userData = $user->getUserById($userId);
if (!$userData) {
    die("User not found.");
}

// Initialize messages
$success = $error = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // --- PROFILE UPDATE ---
    if (isset($_POST['update_profile'])) {
        $profile_pic = $_POST['current_profile_pic'] ?? $userData['profile_pic'];
        
        // Handle file upload
        if (isset($_FILES['profile_pic']) && $_FILES['profile_pic']['error'] === UPLOAD_ERR_OK) {
            $fileTmpPath = $_FILES['profile_pic']['tmp_name'];
            $fileName = $_FILES['profile_pic']['name'];
            $fileSize = $_FILES['profile_pic']['size'];
            $fileType = $_FILES['profile_pic']['type'];
            
            // Security checks
            $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            $allowedMimeTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            $maxFileSize = 5 * 1024 * 1024; // 5MB
            
            // Get file extension
            $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
            
            // Validate file
            if (!in_array($fileExtension, $allowedExtensions)) {
                $error = "Invalid file type. Allowed: JPG, PNG, GIF, WebP.";
            } elseif ($fileSize > $maxFileSize) {
                $error = "File too large. Maximum size is 5MB.";
            } elseif (!in_array($fileType, $allowedMimeTypes)) {
                $error = "Invalid file MIME type.";
            } else {
                // Verify it's a real image
                $imageInfo = @getimagesize($fileTmpPath);
                if ($imageInfo === false) {
                    $error = "Uploaded file is not a valid image.";
                } else {
                    // Generate unique filename
                    $newFileName = "user_" . $userId . "_" . time() . "." . $fileExtension;
                    $dest_path = $avatarDir . $newFileName;
                    
                    // Sanitize filename
                    $newFileName = preg_replace("/[^a-zA-Z0-9\._-]/", "", $newFileName);
                    
                    // Check if destination directory is writable
                    if (!is_writable($avatarDir)) {
                        $error = "Upload directory is not writable. Please contact administrator.";
                    } else {
                        // Move uploaded file
                        if (move_uploaded_file($fileTmpPath, $dest_path)) {
                            // Set proper permissions
                            @chmod($dest_path, 0644);
                            
                            // Delete old profile picture if not default
                            $oldProfilePic = $userData['profile_pic'];
                            if ($oldProfilePic && $oldProfilePic != 'default.jpg' && file_exists($avatarDir . $oldProfilePic)) {
                                @unlink($avatarDir . $oldProfilePic);
                            }
                            
                            $profile_pic = $newFileName;
                        } else {
                            // Detailed error handling
                            $uploadError = $_FILES['profile_pic']['error'];
                            $errorMessages = [
                                0 => 'There was an unexpected upload error.',
                                1 => 'The uploaded file exceeds the upload_max_filesize directive in php.ini.',
                                2 => 'The uploaded file exceeds the MAX_FILE_SIZE directive in the HTML form.',
                                3 => 'The uploaded file was only partially uploaded.',
                                4 => 'No file was uploaded.',
                                6 => 'Missing a temporary folder.',
                                7 => 'Failed to write file to disk.',
                                8 => 'A PHP extension stopped the file upload.'
                            ];
                            $error = $errorMessages[$uploadError] ?? 'File upload failed. Check directory permissions.';
                        }
                    }
                }
            }
        }
        
        // Only proceed with database update if no upload error
        if (empty($error)) {
            $updateData = [
                'full_name'   => trim($_POST['full_name']),
                'phone'       => trim($_POST['phone'] ?? ''),
                'address'     => trim($_POST['address'] ?? ''),
                'bio'         => trim($_POST['bio'] ?? ''),
                'profile_pic' => $profile_pic
            ];
            
            // Validate required fields
            if (empty($updateData['full_name'])) {
                $error = "Full name is required.";
            } elseif ($user->updateProfile($userId, $updateData)) {
                $success = "Profile updated successfully!";
                $userData = $user->getUserById($userId); // Refresh data
            } else {
                $error = "Profile update failed in the database.";
            }
        }
    }
    
    // --- ADDRESS HANDLING ---
    elseif (isset($_POST['add_address'])) {
        $addressData = [
            'label' => trim($_POST['label']),
            'full_name' => trim($_POST['full_name']),
            'phone' => trim($_POST['phone']),
            'address_line1' => trim($_POST['address_line1']),
            'address_line2' => trim($_POST['address_line2'] ?? ''),
            'city' => trim($_POST['city']),
            'state' => trim($_POST['state']),
            'country' => trim($_POST['country']),
            'postal_code' => trim($_POST['postal_code']),
            'is_default' => isset($_POST['is_default']) ? 1 : 0
        ];
        
        // Validate required fields
        $required = ['label', 'full_name', 'phone', 'address_line1', 'city', 'state', 'country', 'postal_code'];
        $missing = [];
        foreach ($required as $field) {
            if (empty($addressData[$field])) {
                $missing[] = $field;
            }
        }
        
        if (!empty($missing)) {
            $error = "Missing required fields: " . implode(', ', $missing);
        } elseif ($address->addAddress($userId, $addressData)) {
            $success = "Address added successfully!";
        } else {
            $error = "Failed to add address.";
        }
    }
    
    // --- MEASUREMENTS HANDLING ---
    elseif (isset($_POST['update_measurements'])) {
        $measurementData = [
            'shoulder' => floatval($_POST['shoulder'] ?? 0),
            'chest' => floatval($_POST['chest'] ?? 0),
            'waist' => floatval($_POST['waist'] ?? 0),
            'hips' => floatval($_POST['hips'] ?? 0),
            'arm_length' => floatval($_POST['arm_length'] ?? 0),
            'inseam' => floatval($_POST['inseam'] ?? 0),
            'neck' => floatval($_POST['neck'] ?? 0),
            'height' => floatval($_POST['height'] ?? 0),
            'weight' => floatval($_POST['weight'] ?? 0)
        ];
        
        try {
            $db->query("INSERT INTO user_profiles (user_id, measurements) 
                       VALUES (:user_id, :measurements) 
                       ON DUPLICATE KEY UPDATE measurements = :measurements2, updated_at = NOW()");
            $db->bind(':user_id', $userId);
            $db->bind(':measurements', json_encode($measurementData));
            $db->bind(':measurements2', json_encode($measurementData));
            
            if ($db->execute()) {
                $success = "Measurements updated successfully!";
            } else {
                $error = "Failed to update measurements.";
            }
        } catch (Exception $e) {
            $error = "Database error: " . $e->getMessage();
        }
    }
}

// Fetch fresh data for display
$addresses = $address->getUserAddresses($userId);

// Get measurements
$measurements = [];
try {
    $db->query("SELECT * FROM user_profiles WHERE user_id = :user_id");
    $db->bind(':user_id', $userId);
    $userProfile = $db->single();
    if ($userProfile && isset($userProfile['measurements'])) {
        $measurements = json_decode($userProfile['measurements'], true) ?: [];
    }
} catch (Exception $e) {
    // Silently fail - measurements will just be empty
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - Clothing Marketplace</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">
    <style>
        body {
            background-color: #f8f9fa;
        }
        
        .profile-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 3rem 0;
            margin-bottom: 2rem;
        }
        
        .profile-avatar {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            border: 4px solid white;
            object-fit: cover;
            background-color: #f0f0f0;
        }
        
        .nav-pills .nav-link {
            color: #6c757d;
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            transition: all 0.3s;
        }
        
        .nav-pills .nav-link:hover {
            background-color: #e9ecef;
        }
        
        .nav-pills .nav-link.active {
            background-color: #667eea;
            color: white;
        }
        
        .card {
            background: 100%;
            border: none;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 1.5rem;
        }

        .measurement-input {
            position: relative;
        }
        
        .measurement-unit {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            color: #6c757d;
            pointer-events: none;
        }
        
        .address-card {
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 1.5rem;
            margin-bottom: 1rem;
            transition: all 0.3s;
        }
        
        .address-card:hover {
            border-color: #667eea;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .address-card.default {
            border-color: #667eea;
            background-color: rgba(102, 126, 234, 0.05);
        }
        
        .badge-default {
            background-color: #667eea;
            color: white;
        }
        
        .alert {
            border: none;
            border-radius: 8px;
        }
        
        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.25rem rgba(102, 126, 234, 0.25);
        }
        
        .btn-primary {
            background-color: #667eea;
            border-color: #667eea;
        }
        
        .btn-primary:hover {
            background-color: #5a6fd8;
            border-color: #5a6fd8;
        }
    </style>
</head>
<body>
    <?php include '../../includes/components/navbar.php'; ?>
    
    <!-- Profile Header -->
    <div class="profile-header">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-2 text-center">
                    <?php 
                    $profileImage = !empty($userData['profile_pic']) ? 
                        '../../assets/images/avatars/' . htmlspecialchars($userData['profile_pic']) : 
                        '../../assets/images/avatars/default.jpg';
                    ?>
                    <img src="<?php echo $profileImage; ?>" 
                         class="profile-avatar" 
                         alt="<?php echo htmlspecialchars($userData['full_name']); ?>"
                         onerror="this.src='../../assets/images/avatars/default.jpg'">
                </div>
                <div class="col-md-10">
                    <h1 class="fw-bold mb-2"><?php echo htmlspecialchars($userData['full_name']); ?></h1>
                    <p class="lead mb-3">
                        <i class="bi bi-geo-alt"></i> <?php echo htmlspecialchars($userData['address'] ?? 'No address added'); ?>
                    </p>
                    <div class="d-flex flex-wrap gap-3">
                        <span class="badge bg-light text-dark">
                            <i class="bi bi-person"></i> Customer Account
                        </span>
                        <span class="badge bg-light text-dark">
                            <i class="bi bi-envelope"></i> <?php echo htmlspecialchars($userData['email']); ?>
                        </span>
                        <?php if (!empty($userData['phone'])): ?>
                        <span class="badge bg-light text-dark">
                            <i class="bi bi-telephone"></i> <?php echo htmlspecialchars($userData['phone']); ?>
                        </span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="container">
        <?php if (!empty($success) || !empty($error)): ?>
        <div class="row mb-3">
            <div class="col-12">
                <?php if (!empty($success)): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="bi bi-check-circle me-2"></i>
                        <?php echo htmlspecialchars($success); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                
                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="bi bi-exclamation-triangle me-2"></i>
                        <?php echo htmlspecialchars($error); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
        
        <div class="row">
            <!-- Sidebar Navigation -->
            <div class="col-md-3">
                <div class="card">
                    <div class="card-body">
                        <ul class="nav nav-pills flex-column">
                            <li class="nav-item">
                                <a class="nav-link active" data-bs-toggle="tab" href="#profile">
                                    <i class="bi bi-person me-2"></i> Profile
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" data-bs-toggle="tab" href="#addresses">
                                    <i class="bi bi-house me-2"></i> Addresses
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" data-bs-toggle="tab" href="#measurements">
                                    <i class="bi bi-rulers me-2"></i> Measurements
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" data-bs-toggle="tab" href="#security">
                                    <i class="bi bi-shield-lock me-2"></i> Security
                                </a>
                            </li>
                            <li class="nav-item mt-3">
                                <a href="dashboard.php" class="nav-link text-primary">
                                    <i class="bi bi-arrow-left me-2"></i> Back to Dashboard
                                </a>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
            
            <!-- Main Content -->
            <div class="col-md-9">
                <div class="tab-content">
                    <!-- Profile Tab -->
                    <div class="tab-pane fade show active" id="profile">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0"><i class="bi bi-person me-2"></i>Personal Information</h5>
                            </div>
                            <div class="card-body">
                                <form action="profile.php" method="POST" enctype="multipart/form-data" novalidate>
                                    <input type="hidden" name="current_profile_pic" value="<?php echo htmlspecialchars($userData['profile_pic'] ?? ''); ?>">

                                    <div class="row mb-4">
                                        <div class="col-12">
                                            <label class="form-label fw-bold">Profile Picture</label>
                                            <div class="input-group">
                                                <span class="input-group-text"><i class="bi bi-image"></i></span>
                                                <input type="file" class="form-control" name="profile_pic" 
                                                       accept="image/jpeg,image/png,image/gif,image/webp">
                                            </div>
                                            <div class="form-text text-muted">
                                                Maximum file size: 5MB. Allowed formats: JPG, PNG, GIF, WebP
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label fw-bold">Full Name *</label>
                                            <input type="text" class="form-control" name="full_name" 
                                                value="<?php echo htmlspecialchars($userData['full_name']); ?>" required>
                                            <div class="invalid-feedback">
                                                Please provide your full name.
                                            </div>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label fw-bold">Email Address</label>
                                            <input type="email" class="form-control bg-light" 
                                                value="<?php echo htmlspecialchars($userData['email']); ?>" readonly>
                                            <small class="text-muted">Contact support to change email</small>
                                        </div>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label fw-bold">Phone Number</label>
                                            <input type="tel" class="form-control" name="phone" 
                                                value="<?php echo htmlspecialchars($userData['phone'] ?? ''); ?>"
                                                pattern="[0-9+\-\s()]{10,20}"
                                                title="Enter a valid phone number">
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label fw-bold">Username</label>
                                            <input type="text" class="form-control bg-light" 
                                                value="<?php echo htmlspecialchars($userData['username']); ?>" readonly>
                                        </div>
                                    </div>
                                    
                                    <div class="mb-4">
                                        <label class="form-label fw-bold">Address</label>
                                        <textarea class="form-control" name="address" rows="3"><?php echo htmlspecialchars($userData['address'] ?? ''); ?></textarea>
                                    </div>
                                    
                                    <div class="d-flex justify-content-between">
                                        <button type="submit" name="update_profile" class="btn btn-primary px-4">
                                            <i class="bi bi-check-circle me-2"></i> Save Changes
                                        </button>
                                        <a href="profile.php" class="btn btn-outline-secondary px-4">
                                            <i class="bi bi-arrow-clockwise me-2"></i> Reset
                                        </a>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Addresses Tab -->
                    <div class="tab-pane fade" id="addresses">
                        <div class="card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h5 class="mb-0"><i class="bi bi-house me-2"></i>My Addresses</h5>
                                <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addAddressModal">
                                    <i class="bi bi-plus"></i> Add New Address
                                </button>
                            </div>
                            <div class="card-body">
                                <?php if (!empty($addresses)): ?>
                                    <div class="row">
                                        <?php foreach ($addresses as $addr): ?>
                                            <div class="col-md-6 mb-3">
                                                <div class="address-card <?php echo $addr['is_default'] ? 'default' : ''; ?>">
                                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                                        <h6 class="fw-bold mb-0"><?php echo htmlspecialchars($addr['label']); ?></h6>
                                                        <?php if ($addr['is_default']): ?>
                                                            <span class="badge badge-default">Default</span>
                                                        <?php endif; ?>
                                                    </div>
                                                    
                                                    <p class="mb-1">
                                                        <strong><?php echo htmlspecialchars($addr['full_name']); ?></strong>
                                                    </p>
                                                    <p class="mb-1 small">
                                                        <?php echo htmlspecialchars($addr['address_line1']); ?><br>
                                                        <?php if ($addr['address_line2']): ?>
                                                            <?php echo htmlspecialchars($addr['address_line2']); ?><br>
                                                        <?php endif; ?>
                                                        <?php echo htmlspecialchars($addr['city']) . ', ' . htmlspecialchars($addr['state']) . ' ' . htmlspecialchars($addr['postal_code']); ?><br>
                                                        <?php echo htmlspecialchars($addr['country']); ?>
                                                    </p>
                                                    <p class="mb-2 small">
                                                        <i class="bi bi-telephone"></i> <?php echo htmlspecialchars($addr['phone']); ?>
                                                    </p>
                                                    
                                                    <div class="btn-group btn-group-sm">
                                                        <button class="btn btn-outline-primary" 
                                                                data-bs-toggle="modal" 
                                                                data-bs-target="#editAddressModal<?php echo $addr['id']; ?>">
                                                            <i class="bi bi-pencil"></i> Edit
                                                        </button>
                                                        <a href="../../api/address.php?action=delete&id=<?php echo $addr['id']; ?>" 
                                                           class="btn btn-outline-danger"
                                                           onclick="return confirm('Are you sure you want to delete this address?')">
                                                            <i class="bi bi-trash"></i> Delete
                                                        </a>
                                                        <?php if (!$addr['is_default']): ?>
                                                            <a href="../../api/address.php?action=set_default&id=<?php echo $addr['id']; ?>" 
                                                               class="btn btn-outline-success">
                                                                <i class="bi bi-check-circle"></i> Set Default
                                                            </a>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php else: ?>
                                    <div class="text-center py-5">
                                        <i class="bi bi-house display-6 text-muted mb-3"></i>
                                        <h5>No addresses saved</h5>
                                        <p class="text-muted mb-4">Add your first address for faster checkout</p>
                                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addAddressModal">
                                            <i class="bi bi-plus-circle me-2"></i> Add Address
                                        </button>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Measurements Tab -->
                    <div class="tab-pane fade" id="measurements">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0"><i class="bi bi-rulers me-2"></i>Body Measurements</h5>
                            </div>
                            <div class="card-body">
                                <div class="alert alert-info">
                                    <i class="bi bi-info-circle me-2"></i>
                                    These measurements help tailors create perfectly fitting clothes for you. 
                                    All measurements should be in centimeters (cm).
                                </div>
                                
                                <form method="POST">
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Height (cm)</label>
                                            <div class="measurement-input">
                                                <input type="number" class="form-control" name="height" 
                                                       value="<?php echo htmlspecialchars($measurements['height'] ?? ''); ?>" 
                                                       step="0.1" min="50" max="250">
                                                <span class="measurement-unit">cm</span>
                                            </div>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Weight (kg)</label>
                                            <div class="measurement-input">
                                                <input type="number" class="form-control" name="weight" 
                                                       value="<?php echo htmlspecialchars($measurements['weight'] ?? ''); ?>" 
                                                       step="0.1" min="20" max="200">
                                                <span class="measurement-unit">kg</span>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <h6 class="mb-3 border-bottom pb-2">Upper Body</h6>
                                    <div class="row">
                                        <div class="col-md-4 mb-3">
                                            <label class="form-label">Shoulder Width</label>
                                            <div class="measurement-input">
                                                <input type="number" class="form-control" name="shoulder" 
                                                       value="<?php echo htmlspecialchars($measurements['shoulder'] ?? ''); ?>" 
                                                       step="0.1" min="20" max="80">
                                                <span class="measurement-unit">cm</span>
                                            </div>
                                        </div>
                                        <div class="col-md-4 mb-3">
                                            <label class="form-label">Chest/Bust</label>
                                            <div class="measurement-input">
                                                <input type="number" class="form-control" name="chest" 
                                                       value="<?php echo htmlspecialchars($measurements['chest'] ?? ''); ?>" 
                                                       step="0.1" min="50" max="150">
                                                <span class="measurement-unit">cm</span>
                                            </div>
                                        </div>
                                        <div class="col-md-4 mb-3">
                                            <label class="form-label">Neck Circumference</label>
                                            <div class="measurement-input">
                                                <input type="number" class="form-control" name="neck" 
                                                       value="<?php echo htmlspecialchars($measurements['neck'] ?? ''); ?>" 
                                                       step="0.1" min="20" max="60">
                                                <span class="measurement-unit">cm</span>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <h6 class="mb-3 border-bottom pb-2">Mid Body</h6>
                                    <div class="row">
                                        <div class="col-md-4 mb-3">
                                            <label class="form-label">Waist</label>
                                            <div class="measurement-input">
                                                <input type="number" class="form-control" name="waist" 
                                                       value="<?php echo htmlspecialchars($measurements['waist'] ?? ''); ?>" 
                                                       step="0.1" min="40" max="150">
                                                <span class="measurement-unit">cm</span>
                                            </div>
                                        </div>
                                        <div class="col-md-4 mb-3">
                                            <label class="form-label">Hips</label>
                                            <div class="measurement-input">
                                                <input type="number" class="form-control" name="hips" 
                                                       value="<?php echo htmlspecialchars($measurements['hips'] ?? ''); ?>" 
                                                       step="0.1" min="50" max="150">
                                                <span class="measurement-unit">cm</span>
                                            </div>
                                        </div>
                                        <div class="col-md-4 mb-3">
                                            <label class="form-label">Arm Length</label>
                                            <div class="measurement-input">
                                                <input type="number" class="form-control" name="arm_length" 
                                                       value="<?php echo htmlspecialchars($measurements['arm_length'] ?? ''); ?>" 
                                                       step="0.1" min="20" max="100">
                                                <span class="measurement-unit">cm</span>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <h6 class="mb-3 border-bottom pb-2">Lower Body (for trousers)</h6>
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Inseam</label>
                                            <div class="measurement-input">
                                                <input type="number" class="form-control" name="inseam" 
                                                       value="<?php echo htmlspecialchars($measurements['inseam'] ?? ''); ?>" 
                                                       step="0.1" min="40" max="120">
                                                <span class="measurement-unit">cm</span>
                                            </div>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Thigh Circumference</label>
                                            <div class="measurement-input">
                                                <input type="number" class="form-control" name="thigh" 
                                                       value="<?php echo htmlspecialchars($measurements['thigh'] ?? ''); ?>" 
                                                       step="0.1" min="30" max="80">
                                                <span class="measurement-unit">cm</span>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="d-flex justify-content-between">
                                        <button type="submit" name="update_measurements" class="btn btn-primary">
                                            <i class="bi bi-check-circle me-2"></i> Save Measurements
                                        </button>
                                        <a href="#" class="btn btn-outline-secondary" onclick="window.print()">
                                            <i class="bi bi-printer me-2"></i> Print
                                        </a>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Security Tab -->
                    <div class="tab-pane fade" id="security">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0"><i class="bi bi-shield-lock me-2"></i>Account Security</h5>
                            </div>
                            <div class="card-body">
                                <div class="mb-4">
                                    <h6>Change Password</h6>
                                    <form action="../../api/user.php" method="POST">
                                        <input type="hidden" name="action" value="change_password">
                                        <div class="mb-3">
                                            <label class="form-label">Current Password</label>
                                            <input type="password" class="form-control" name="current_password" required>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">New Password</label>
                                            <input type="password" class="form-control" name="new_password" required 
                                                   pattern="(?=.*\d)(?=.*[a-z])(?=.*[A-Z]).{8,}"
                                                   title="Must contain at least 8 characters, including uppercase, lowercase, and number">
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Confirm New Password</label>
                                            <input type="password" class="form-control" name="confirm_password" required>
                                        </div>
                                        <button type="submit" class="btn btn-primary">
                                            <i class="bi bi-key me-2"></i> Change Password
                                        </button>
                                    </form>
                                </div>
                                
                                <hr>
                                
                                <div class="mb-4">
                                    <h6>Login History</h6>
                                    <div class="table-responsive">
                                        <table class="table table-sm">
                                            <thead>
                                                <tr>
                                                    <th>Date & Time</th>
                                                    <th>IP Address</th>
                                                    <th>Device</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <tr>
                                                    <td><?php echo date('M d, Y H:i'); ?></td>
                                                    <td><?php echo htmlspecialchars($_SERVER['REMOTE_ADDR']); ?></td>
                                                    <td><?php echo htmlspecialchars(substr($_SERVER['HTTP_USER_AGENT'], 0, 50)); ?>...</td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                                
                                <hr>
                                
                                <div>
                                    <h6 class="text-danger">Danger Zone</h6>
                                    <div class="alert alert-danger">
                                        <i class="bi bi-exclamation-triangle me-2"></i>
                                        <strong>Delete Account</strong>
                                        <p class="mb-2">Once you delete your account, there is no going back. Please be certain.</p>
                                        <button class="btn btn-danger btn-sm" data-bs-toggle="modal" data-bs-target="#deleteAccountModal">
                                            <i class="bi bi-trash me-2"></i> Delete Account
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Add Address Modal -->
    <div class="modal fade" id="addAddressModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" novalidate>
                    <div class="modal-header">
                        <h5 class="modal-title">Add New Address</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Address Label *</label>
                            <input type="text" class="form-control" name="label" 
                                   placeholder="e.g., Home, Office, Mom's House" required>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Full Name *</label>
                                <input type="text" class="form-control" name="full_name" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Phone Number *</label>
                                <input type="tel" class="form-control" name="phone" required>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Address Line 1 *</label>
                            <input type="text" class="form-control" name="address_line1" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Address Line 2</label>
                            <input type="text" class="form-control" name="address_line2">
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">City *</label>
                                <input type="text" class="form-control" name="city" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">State *</label>
                                <input type="text" class="form-control" name="state" required>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Country *</label>
                                <input type="text" class="form-control" name="country" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Postal Code *</label>
                                <input type="text" class="form-control" name="postal_code" required>
                            </div>
                        </div>
                        
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" name="is_default" id="isDefault">
                            <label class="form-check-label" for="isDefault">
                                Set as default address
                            </label>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="add_address" class="btn btn-primary">Save Address</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Delete Account Modal -->
    <div class="modal fade" id="deleteAccountModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title text-danger">Delete Account</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-danger">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i>
                        <strong>Warning: This action cannot be undone!</strong>
                    </div>
                    
                    <p>Are you absolutely sure you want to delete your account? This will:</p>
                    <ul>
                        <li>Permanently delete your profile</li>
                        <li>Remove all your orders and history</li>
                        <li>Delete all your addresses and measurements</li>
                        <li>Cancel any pending orders</li>
                    </ul>
                    
                    <div class="mb-3">
                        <label class="form-label">To confirm, type "DELETE" below:</label>
                        <input type="text" class="form-control" id="deleteConfirmation">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-danger" id="confirmDelete" disabled>
                        <i class="bi bi-trash me-2"></i> Delete Account
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Tab switching from URL hash
        document.addEventListener('DOMContentLoaded', function() {
            const hash = window.location.hash;
            if (hash) {
                const tab = new bootstrap.Tab(document.querySelector(`a[href="${hash}"]`));
                tab.show();
            }
            
            // Delete account confirmation
            const deleteInput = document.getElementById('deleteConfirmation');
            const deleteBtn = document.getElementById('confirmDelete');
            
            deleteInput.addEventListener('input', function() {
                deleteBtn.disabled = this.value !== 'DELETE';
            });
            
            deleteBtn.addEventListener('click', function() {
                if (confirm('Are you 100% sure? This action is permanent!')) {
                    window.location.href = '../../api/user.php?action=delete';
                }
            });
            
            // Form validation
            const forms = document.querySelectorAll('form[novalidate]');
            forms.forEach(form => {
                form.addEventListener('submit', function(e) {
                    if (!this.checkValidity()) {
                        e.preventDefault();
                        e.stopPropagation();
                    }
                    this.classList.add('was-validated');
                });
            });
            
            // Image preview for profile picture
            const profilePicInput = document.querySelector('input[name="profile_pic"]');
            if (profilePicInput) {
                profilePicInput.addEventListener('change', function(e) {
                    const file = this.files[0];
                    if (file && file.type.startsWith('image/')) {
                        const reader = new FileReader();
                        reader.onload = function(e) {
                            const img = document.querySelector('.profile-avatar');
                            if (img) {
                                img.src = e.target.result;
                            }
                        };
                        reader.readAsDataURL(file);
                    }
                });
            }
        });
    </script>
</body>
