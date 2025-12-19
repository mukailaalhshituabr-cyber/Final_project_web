<?php
require_once '../../config.php';
require_once '../../includes/classes/Database.php';
require_once '../../includes/classes/User.php';

// Only start session if one doesn't exist to avoid the Notice
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'tailor') {
    header('Location: ../auth/login.php');
    exit();
}

$tailorId = $_SESSION['user_id'];
$user = new User();

// Get user data
$userData = $user->getUserById($tailorId);

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullName = $_POST['full_name'] ?? '';
    $email = $_POST['email'] ?? '';
    $phone = $_POST['phone'] ?? '';
    $address = $_POST['address'] ?? '';
    $bio = $_POST['bio'] ?? '';
    
    // Handle profile picture upload
    $profilePic = $userData['profile_pic'] ?? 'default-avatar.png';
    
    // In pages/customer/profile.php - REPLACE the file upload section starting around line 80:

    // --- PROFILE UPDATE ---
    if (isset($_POST['update_profile'])) {
        $profile_pic = $_POST['current_profile_pic'] ?? $userData['profile_pic'];
        
        // DEBUG: Log what's happening
        error_log("Profile update started for user $userId");
        
        // Handle file upload
        if (isset($_FILES['profile_pic']) && $_FILES['profile_pic']['error'] === UPLOAD_ERR_OK) {
            error_log("File upload detected: " . $_FILES['profile_pic']['name']);
            
            $fileTmpPath = $_FILES['profile_pic']['tmp_name'];
            $fileName = $_FILES['profile_pic']['name'];
            $fileSize = $_FILES['profile_pic']['size'];
            
            // Check if temp file exists
            if (!file_exists($fileTmpPath)) {
                $error = "Uploaded file not found in temp directory. Check PHP upload settings.";
                error_log("Temp file doesn't exist: $fileTmpPath");
            } else {
                // Security checks
                $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                $maxFileSize = 5 * 1024 * 1024; // 5MB
                
                // Get file extension
                $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
                
                // Validate file
                if (!in_array($fileExtension, $allowedExtensions)) {
                    $error = "Invalid file type. Allowed: JPG, PNG, GIF, WebP.";
                } elseif ($fileSize > $maxFileSize) {
                    $error = "File too large. Maximum size is 5MB.";
                } else {
                    // Use RELATIVE path for web access
                    $avatarDir = '../../assets/images/avatars/';
                    $newFileName = "user_" . $userId . "_" . time() . "." . $fileExtension;
                    $dest_path = $avatarDir . $newFileName;
                    
                    // Ensure directory exists
                    if (!is_dir($avatarDir)) {
                        if (!mkdir($avatarDir, 0755, true)) {
                            $error = "Failed to create upload directory.";
                            error_log("Failed to create directory: $avatarDir");
                        }
                    }
                    
                    if (empty($error)) {
                        // Check directory permissions
                        $perms = @fileperms($avatarDir);
                        error_log("Directory permissions for $avatarDir: " . ($perms ? substr(sprintf('%o', $perms), -4) : 'UNKNOWN'));
                        
                        // Try to fix permissions if needed
                        if (!is_writable($avatarDir)) {
                            @chmod($avatarDir, 0755);
                            error_log("Attempted to change permissions to 0755");
                        }
                        
                        // Try to move the file
                        if (move_uploaded_file($fileTmpPath, $dest_path)) {
                            error_log("File moved successfully to: $dest_path");
                            
                            // Delete old profile picture if not default
                            $oldProfilePic = $userData['profile_pic'];
                            if ($oldProfilePic && $oldProfilePic != 'default.jpg') {
                                $oldPath = $avatarDir . $oldProfilePic;
                                if (file_exists($oldPath)) {
                                    @unlink($oldPath);
                                }
                            }
                            
                            $profile_pic = $newFileName;
                        } else {
                            // Detailed error analysis
                            $error = "Failed to move uploaded file. ";
                            error_log("move_uploaded_file failed: $fileTmpPath to $dest_path");
                            
                            // Check specific issues
                            if (!is_writable($avatarDir)) {
                                $error .= "Directory not writable. ";
                                error_log("Directory not writable: $avatarDir");
                            }
                            if (!is_writable(dirname($dest_path))) {
                                $error .= "Parent directory not writable. ";
                            }
                            
                            // Try alternative method
                            if (copy($fileTmpPath, $dest_path)) {
                                error_log("Used copy() as alternative - SUCCESS");
                                $profile_pic = $newFileName;
                                $error = ""; // Clear error
                            } else {
                                error_log("Alternative copy() also failed");
                                $error .= "Please check server permissions.";
                            }
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
        
        // If there's an error, log it
        if (!empty($error)) {
            error_log("Profile update error for user $userId: $error");
        }
    }
    /*if (isset($_FILES['profile_pic']) && $_FILES['profile_pic']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = '../../assets/images/avatars/';
        
        // AUTO-FIX: Create the folder if it's missing
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        $fileName = time() . '_' . basename($_FILES['profile_pic']['name']);
        $uploadFile = $uploadDir . $fileName;
        
        if (move_uploaded_file($_FILES['profile_pic']['tmp_name'], $uploadFile)) {
            $profilePic = $fileName;
        }
    }
    
    // NOTE: Ensure this method exists in User.php!
    if (method_exists($user, 'updateTailorProfile')) {
        if ($user->updateTailorProfile($tailorId, $fullName, $email, $phone, $address, $bio, $profilePic)) {
            $success = "Profile updated successfully!";
            $userData = $user->getUserById($tailorId); // Refresh data
        } else {
            $error = "Failed to update profile.";
        }
    } else {
        $error = "Error: updateTailorProfile method is missing in User.php class.";
    }*/
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile - Tailor Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">
    <style>
        .profile-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 3rem 0;
            margin-bottom: -50px;
        }
        
        .profile-avatar {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            border: 5px solid white;
            object-fit: cover;
            box-shadow: 0 10px 20px rgba(0,0,0,0.2);
        }
        
        .profile-stats {
            display: flex;
            justify-content: center;
            gap: 2rem;
            margin-top: 1rem;
        }
        
        .stat-item {
            text-align: center;
        }
        
        .stat-value {
            font-size: 1.5rem;
            font-weight: 700;
            display: block;
        }
        
        .stat-label {
            font-size: 0.875rem;
            opacity: 0.8;
        }
        
        .profile-card {
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        
        .nav-pills .nav-link {
            color: #6c757d;
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            margin-right: 0.5rem;
        }
        
        .nav-pills .nav-link.active {
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
                <div class="col-md-3 text-center">
                    <img src="../../assets/images/avatars/<?php echo $userData['profile_pic'] ?: 'default.jpg'; ?>" 
                         class="profile-avatar" 
                         alt="<?php echo htmlspecialchars($userData['full_name']); ?>">
                </div>
                <div class="col-md-9">
                    <h1 class="fw-bold mb-2"><?php echo htmlspecialchars($userData['full_name']); ?></h1>
                    <p class="lead mb-3">Professional Tailor</p>
                    <div class="profile-stats">
                        <div class="stat-item">
                            <span class="stat-value">4.8</span>
                            <span class="stat-label">Rating</span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-value">156</span>
                            <span class="stat-label">Orders</span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-value">24</span>
                            <span class="stat-label">Products</span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-value">98%</span>
                            <span class="stat-label">Satisfaction</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="container mt-5">
        <div class="row">
            <div class="col-12">
                <div class="profile-card">
                    <!-- Navigation -->
                    <div class="card-header">
                        <ul class="nav nav-pills">
                            <li class="nav-item">
                                <a class="nav-link active" data-bs-toggle="tab" href="#profile">Profile</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" data-bs-toggle="tab" href="#settings">Settings</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" data-bs-toggle="tab" href="#security">Security</a>
                            </li>
                        </ul>
                    </div>
                    
                    <!-- Tab Content -->
                    <div class="card-body">
                        <div class="tab-content">
                            <!-- Profile Tab -->
                            <div class="tab-pane fade show active" id="profile">
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
                                
                                <form method="POST" enctype="multipart/form-data">
                                    <div class="row">
                                        <div class="col-md-4">
                                            <div class="mb-3 text-center">
                                                <div class="mb-3">
                                                    <img src="../../assets/images/avatars/<?php echo $userData['profile_pic'] ?: 'default.jpg'; ?>" 
                                                         class="rounded-circle" 
                                                         width="150" 
                                                         height="150"
                                                         alt="Profile Picture" 
                                                         id="profilePreview">
                                                </div>
                                                <div class="mb-3">
                                                    <input type="file" 
                                                           class="form-control" 
                                                           name="profile_pic" 
                                                           accept="image/*"
                                                           id="profileInput">
                                                    <small class="text-muted">Max 2MB. JPG, PNG, GIF</small>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="col-md-8">
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <div class="mb-3">
                                                        <label class="form-label">Full Name *</label>
                                                        <input type="text" class="form-control" name="full_name" 
                                                               value="<?php echo htmlspecialchars($userData['full_name']); ?>" required>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="mb-3">
                                                        <label class="form-label">Email *</label>
                                                        <input type="email" class="form-control" name="email" 
                                                               value="<?php echo htmlspecialchars($userData['email']); ?>" required>
                                                    </div>
                                                </div>
                                            </div>
                                            
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <div class="mb-3">
                                                        <label class="form-label">Phone</label>
                                                        <input type="tel" class="form-control" name="phone" 
                                                               value="<?php echo htmlspecialchars($userData['phone'] ?? ''); ?>">
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="mb-3">
                                                        <label class="form-label">Business Name</label>
                                                        <input type="text" class="form-control" name="business_name" 
                                                               value="<?php echo htmlspecialchars($userData['business_name'] ?? ''); ?>">
                                                    </div>
                                                </div>
                                            </div>
                                            
                                            <div class="mb-3">
                                                <label class="form-label">Address</label>
                                                <textarea class="form-control" name="address" rows="2"><?php echo htmlspecialchars($userData['address'] ?? ''); ?></textarea>
                                            </div>
                                            
                                            <div class="mb-3">
                                                <label class="form-label">Bio / About</label>
                                                <textarea class="form-control" name="bio" rows="4" 
                                                          placeholder="Tell customers about your tailoring experience, specialties, etc."><?php echo htmlspecialchars($userData['bio'] ?? ''); ?></textarea>
                                            </div>
                                            
                                            <div class="d-flex justify-content-between">
                                                <button type="submit" class="btn btn-primary">Save Changes</button>
                                                <a href="dashboard.php" class="btn btn-outline-secondary">Cancel</a>
                                            </div>
                                        </div>
                                    </div>
                                </form>
                            </div>
                            
                            <!-- Settings Tab -->
                            <div class="tab-pane fade" id="settings">
                                <h5>Business Settings</h5>
                                <form>
                                    <div class="mb-3">
                                        <label class="form-label">Service Areas</label>
                                        <input type="text" class="form-control" 
                                               value="Local Delivery, Shipping Nationwide">
                                        <small class="text-muted">Where do you provide your services?</small>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Delivery Time</label>
                                        <select class="form-select">
                                            <option>Same Day</option>
                                            <option selected>1-3 Days</option>
                                            <option>3-5 Days</option>
                                            <option>1 Week</option>
                                            <option>2 Weeks</option>
                                        </select>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Working Hours</label>
                                        <div class="row">
                                            <div class="col-md-6">
                                                <input type="time" class="form-control" value="09:00">
                                            </div>
                                            <div class="col-md-6">
                                                <input type="time" class="form-control" value="18:00">
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Notification Preferences</label>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" checked>
                                            <label class="form-check-label">Email notifications</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" checked>
                                            <label class="form-check-label">SMS notifications</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox">
                                            <label class="form-check-label">Push notifications</label>
                                        </div>
                                    </div>
                                    
                                    <button type="submit" class="btn btn-primary">Save Settings</button>
                                </form>
                            </div>
                            
                            <!-- Security Tab -->
                            <div class="tab-pane fade" id="security">
                                <h5>Change Password</h5>
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
                                    
                                    <button type="submit" class="btn btn-primary">Change Password</button>
                                </form>
                                
                                <hr class="my-4">
                                
                                <h5>Account Security</h5>
                                <div class="alert alert-warning">
                                    <i class="bi bi-shield-check me-2"></i>
                                    Two-factor authentication is not enabled.
                                    <a href="#" class="alert-link">Enable 2FA</a>
                                </div>
                                
                                <div class="alert alert-danger">
                                    <h6><i class="bi bi-exclamation-triangle me-2"></i>Danger Zone</h6>
                                    <p class="mb-2">Once you delete your account, there is no going back.</p>
                                    <button class="btn btn-outline-danger btn-sm">Delete Account</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Profile picture preview
        document.getElementById('profileInput').addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    document.getElementById('profilePreview').src = e.target.result;
                }
                reader.readAsDataURL(file);
            }
        });
        
        // Tab activation
        document.addEventListener('DOMContentLoaded', function() {
            const hash = window.location.hash;
            if (hash) {
                const tab = new bootstrap.Tab(document.querySelector(`a[href="${hash}"]`));
                tab.show();
            }
        });
    </script>
</body>
</html>