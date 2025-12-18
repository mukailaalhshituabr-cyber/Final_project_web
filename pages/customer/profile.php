<?php
require_once '../../config.php';
require_once '../../includes/classes/Database.php';
require_once '../../includes/classes/User.php';
require_once '../../includes/classes/Address.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'customer') {
    header('Location: ../auth/login.php');
    exit();
}

$userId = $_SESSION['user_id'];
$db = Database::getInstance();
$user = new User();
$address = new Address();

// Get user data
$userData = $user->getUserById($userId);

// Get user addresses
$addresses = $address->getUserAddresses($userId);

// Get user measurements
$db->query("SELECT * FROM user_profiles WHERE user_id = :user_id");
$db->bind(':user_id', $userId);
$userProfile = $db->single();
$measurements = $userProfile ? json_decode($userProfile['measurements'] ?? '{}', true) : [];

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_profile'])) {
        
        // --- IMAGE HANDLING LOGIC START ---
        // Fallback to existing picture from hidden input or database
        $profile_pic = $_POST['current_profile_pic'] ?? $userData['profile_pic']; 

        if (isset($_FILES['profile_pic']) && $_FILES['profile_pic']['error'] === UPLOAD_ERR_OK) {
            $fileTmpPath = $_FILES['profile_pic']['tmp_name'];
            $fileName = $_FILES['profile_pic']['name'];
            $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
            $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp'];
            
            if (in_array($fileExtension, $allowedExtensions)) {
                $newFileName = md5(time() . $fileName) . '.' . $fileExtension;
                $uploadFileDir = '../../assets/images/profiles/';
                
                // Create directory if it doesn't exist
                if (!is_dir($uploadFileDir)) {
                    mkdir($uploadFileDir, 0755, true);
                }
                
                $dest_path = $uploadFileDir . $newFileName;
                if (move_uploaded_file($fileTmpPath, $dest_path)) {
                    $profile_pic = $newFileName;
                }
            }
        }

        // Prepare the data array to fix "string offset" error
        $updateData = [
            'full_name'   => $_POST['full_name'],
            'email'       => $_POST['email'] ?? $userData['email'], // Ensure email is passed
            'phone'       => $_POST['phone'],
            'address'     => $_POST['address'],
            'bio'         => $_POST['bio'] ?? '',
            'profile_pic' => $profile_pic
        ];
        // --- IMAGE HANDLING LOGIC END ---

        // Pass the $userId and the $updateData ARRAY
        if ($user->updateProfile($userId, $updateData)) {
            $success = "Profile updated successfully!";
            $userData = $user->getUserById($userId); // Refresh data
        } else {
            $error = "Failed to update profile.";
        }
    }
    
    elseif (isset($_POST['add_address'])) {
        $addressData = [
            'label' => $_POST['label'],
            'full_name' => $_POST['full_name'],
            'phone' => $_POST['phone'],
            'address_line1' => $_POST['address_line1'],
            'address_line2' => $_POST['address_line2'] ?? '',
            'city' => $_POST['city'],
            'state' => $_POST['state'],
            'country' => $_POST['country'],
            'postal_code' => $_POST['postal_code'],
            'is_default' => isset($_POST['is_default']) ? 1 : 0
        ];
        
        if ($address->addAddress($userId, $addressData)) {
            $success = "Address added successfully!";
            $addresses = $address->getUserAddresses($userId);
        } else {
            $error = "Failed to add address.";
        }
    }
    
    elseif (isset($_POST['update_measurements'])) {
        $measurementData = [
            'shoulder' => $_POST['shoulder'] ?? '',
            'chest' => $_POST['chest'] ?? '',
            'waist' => $_POST['waist'] ?? '',
            'hips' => $_POST['hips'] ?? '',
            'arm_length' => $_POST['arm_length'] ?? '',
            'inseam' => $_POST['inseam'] ?? '',
            'neck' => $_POST['neck'] ?? '',
            'height' => $_POST['height'] ?? '',
            'weight' => $_POST['weight'] ?? ''
        ];
        
        $db->query("INSERT INTO user_profiles (user_id, measurements) 
                   VALUES (:user_id, :measurements) 
                   ON DUPLICATE KEY UPDATE measurements = :measurements, updated_at = NOW()");
        $db->bind(':user_id', $userId);
        $db->bind(':measurements', json_encode($measurementData));
        
        if ($db->execute()) {
            $success = "Measurements updated successfully!";
            $measurements = $measurementData;
        } else {
            $error = "Failed to update measurements.";
        }
    }
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
        }
        
        .nav-pills .nav-link {
            color: #6c757d;
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
        }
        
        .nav-pills .nav-link.active {
            background-color: #667eea;
            color: white;
        }
        
        .card {
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
    </style>
</head>
<body>
    <?php include '../../includes/components/navbar.php'; ?>
    
    <!-- Profile Header -->
    <div class="profile-header">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-2 text-center">
                    <img src="../../assets/images/avatars/<?php echo $userData['profile_pic'] ?: 'default.jpg'; ?>" 
                         class="profile-avatar" 
                         alt="<?php echo htmlspecialchars($userData['full_name']); ?>">
                </div>
                <div class="col-md-10">
                    <h1 class="fw-bold mb-2"><?php echo htmlspecialchars($userData['full_name']); ?></h1>
                    <p class="lead mb-3">
                        <i class="bi bi-geo-alt"></i> <?php echo htmlspecialchars($userData['address'] ?? 'No address added'); ?>
                    </p>
                    <div class="d-flex gap-3">
                        <span class="badge bg-light text-dark">
                            <i class="bi bi-person"></i> Customer Account
                        </span>
                        <span class="badge bg-light text-dark">
                            <i class="bi bi-envelope"></i> <?php echo htmlspecialchars($userData['email']); ?>
                        </span>
                        <span class="badge bg-light text-dark">
                            <i class="bi bi-telephone"></i> <?php echo htmlspecialchars($userData['phone'] ?? 'Not provided'); ?>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="container">
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
                                <?php if (isset($success)): ?>
                                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                                        <?php echo $success; ?>
                                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                                    </div>
                                <?php endif; ?>
                                
                                <?php if (isset($error)): ?>
                                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                        <?php echo $error; ?>
                                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                                    </div>
                                <?php endif; ?>
                                
                                <form method="POST">
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Full Name *</label>
                                            <input type="text" class="form-control" name="full_name" 
                                                   value="<?php echo htmlspecialchars($userData['full_name']); ?>" required>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Email Address</label>
                                            <input type="email" class="form-control" 
                                                   value="<?php echo htmlspecialchars($userData['email']); ?>" readonly>
                                            <small class="text-muted">Email cannot be changed</small>
                                        </div>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Phone Number</label>
                                            <input type="tel" class="form-control" name="phone" 
                                                   value="<?php echo htmlspecialchars($userData['phone'] ?? ''); ?>">
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Username</label>
                                            <input type="text" class="form-control" 
                                                   value="<?php echo htmlspecialchars($userData['username']); ?>" readonly>
                                        </div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Address</label>
                                        <textarea class="form-control" name="address" rows="3"><?php echo htmlspecialchars($userData['address'] ?? ''); ?></textarea>
                                    </div>
                                    
                                    <div class="d-flex justify-content-between">
                                        <button type="submit" name="update_profile" class="btn btn-primary">
                                            <i class="bi bi-check-circle me-2"></i> Save Changes
                                        </button>
                                        <a href="#" class="btn btn-outline-secondary">
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
                                                    <p class="mb-1">
                                                        <?php echo htmlspecialchars($addr['address_line1']); ?><br>
                                                        <?php if ($addr['address_line2']): ?>
                                                            <?php echo htmlspecialchars($addr['address_line2']); ?><br>
                                                        <?php endif; ?>
                                                        <?php echo htmlspecialchars($addr['city']) . ', ' . htmlspecialchars($addr['state']) . ' ' . htmlspecialchars($addr['postal_code']); ?><br>
                                                        <?php echo htmlspecialchars($addr['country']); ?>
                                                    </p>
                                                    <p class="mb-2">
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
                                                       value="<?php echo $measurements['height'] ?? ''; ?>" step="0.1">
                                                <span class="measurement-unit">cm</span>
                                            </div>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Weight (kg)</label>
                                            <div class="measurement-input">
                                                <input type="number" class="form-control" name="weight" 
                                                       value="<?php echo $measurements['weight'] ?? ''; ?>" step="0.1">
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
                                                       value="<?php echo $measurements['shoulder'] ?? ''; ?>" step="0.1">
                                                <span class="measurement-unit">cm</span>
                                            </div>
                                        </div>
                                        <div class="col-md-4 mb-3">
                                            <label class="form-label">Chest/Bust</label>
                                            <div class="measurement-input">
                                                <input type="number" class="form-control" name="chest" 
                                                       value="<?php echo $measurements['chest'] ?? ''; ?>" step="0.1">
                                                <span class="measurement-unit">cm</span>
                                            </div>
                                        </div>
                                        <div class="col-md-4 mb-3">
                                            <label class="form-label">Neck Circumference</label>
                                            <div class="measurement-input">
                                                <input type="number" class="form-control" name="neck" 
                                                       value="<?php echo $measurements['neck'] ?? ''; ?>" step="0.1">
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
                                                       value="<?php echo $measurements['waist'] ?? ''; ?>" step="0.1">
                                                <span class="measurement-unit">cm</span>
                                            </div>
                                        </div>
                                        <div class="col-md-4 mb-3">
                                            <label class="form-label">Hips</label>
                                            <div class="measurement-input">
                                                <input type="number" class="form-control" name="hips" 
                                                       value="<?php echo $measurements['hips'] ?? ''; ?>" step="0.1">
                                                <span class="measurement-unit">cm</span>
                                            </div>
                                        </div>
                                        <div class="col-md-4 mb-3">
                                            <label class="form-label">Arm Length</label>
                                            <div class="measurement-input">
                                                <input type="number" class="form-control" name="arm_length" 
                                                       value="<?php echo $measurements['arm_length'] ?? ''; ?>" step="0.1">
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
                                                       value="<?php echo $measurements['inseam'] ?? ''; ?>" step="0.1">
                                                <span class="measurement-unit">cm</span>
                                            </div>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Thigh Circumference</label>
                                            <div class="measurement-input">
                                                <input type="number" class="form-control" name="thigh" 
                                                       value="<?php echo $measurements['thigh'] ?? ''; ?>" step="0.1">
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
                                    <form>
                                        <div class="mb-3">
                                            <label class="form-label">Current Password</label>
                                            <input type="password" class="form-control">
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">New Password</label>
                                            <input type="password" class="form-control">
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Confirm New Password</label>
                                            <input type="password" class="form-control">
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
                                                    <th>Location</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <tr>
                                                    <td><?php echo date('M d, Y H:i'); ?></td>
                                                    <td><?php echo $_SERVER['REMOTE_ADDR']; ?></td>
                                                    <td><?php echo $_SERVER['HTTP_USER_AGENT']; ?></td>
                                                    <td>Current Location</td>
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
                <form method="POST">
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
                    // In a real application, you would make an AJAX call here
                    alert('Account deletion would be processed here.\nIn a real app, this would redirect to deletion endpoint.');
                    window.location.href = '../../api/user.php?action=delete';
                }
            });
            
            // Form validation
            const forms = document.querySelectorAll('form');
            forms.forEach(form => {
                form.addEventListener('submit', function(e) {
                    if (!this.checkValidity()) {
                        e.preventDefault();
                        e.stopPropagation();
                    }
                    this.classList.add('was-validated');
                });
            });
        });
    </script>
</body>
</html>