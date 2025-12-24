<?php
require_once dirname(__DIR__, 2) . '/config.php';
require_once ROOT_PATH . '/includes/classes/Database.php';
require_once ROOT_PATH . '/includes/classes/User.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'tailor') {
    header('Location: ../auth/login.php');
    exit();
}

$db = Database::getInstance();
$userObj = new User();
$tailorId = $_SESSION['user_id'];

$message = '';
$error = '';

// Get current tailor profile
$tailor = $userObj->getTailorProfile($tailorId);

if (!$tailor) {
    header('Location: dashboard.php');
    exit();
}

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Check which form was submitted
        if (isset($_POST['update_profile'])) {
            // Basic information update
            $required = ['full_name', 'email', 'phone'];
            foreach ($required as $field) {
                if (empty($_POST[$field])) {
                    throw new Exception("Please fill in all required fields");
                }
            }
            
            $profileData = [
                'full_name' => trim($_POST['full_name']),
                'email' => trim($_POST['email']),
                'phone' => trim($_POST['phone']),
                'bio' => trim($_POST['bio'] ?? ''),
                'experience_years' => intval($_POST['experience_years'] ?? 0),
                'specialization' => trim($_POST['specialization'] ?? ''),
                'address' => trim($_POST['address'] ?? ''),
                'city' => trim($_POST['city'] ?? ''),
                'state' => trim($_POST['state'] ?? ''),
                'country' => trim($_POST['country'] ?? ''),
                'zip_code' => trim($_POST['zip_code'] ?? ''),
                'working_hours' => trim($_POST['working_hours'] ?? ''),
                'languages' => !empty($_POST['languages']) ? array_map('trim', explode(',', $_POST['languages'])) : []
            ];
            
            $result = $userObj->updateTailorProfile($tailorId, $profileData);
            
            if ($result) {
                $message = 'Profile updated successfully!';
                // Refresh tailor data
                $tailor = $userObj->getTailorProfile($tailorId);
            } else {
                $error = 'Failed to update profile';
            }
            
        } elseif (isset($_POST['update_social'])) {
            // Social media update
            $socialData = [
                'facebook' => trim($_POST['facebook'] ?? ''),
                'instagram' => trim($_POST['instagram'] ?? ''),
                'twitter' => trim($_POST['twitter'] ?? ''),
                'linkedin' => trim($_POST['linkedin'] ?? ''),
                'website' => trim($_POST['website'] ?? ''),
                'youtube' => trim($_POST['youtube'] ?? '')
            ];
            
            $result = $userObj->updateTailorSocial($tailorId, $socialData);
            
            if ($result) {
                $message = 'Social media updated successfully!';
                $tailor = $userObj->getTailorProfile($tailorId);
            }
            
        } elseif (isset($_POST['update_password'])) {
            // Password update
            $currentPassword = $_POST['current_password'];
            $newPassword = $_POST['new_password'];
            $confirmPassword = $_POST['confirm_password'];
            
            if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
                throw new Exception("Please fill in all password fields");
            }
            
            if ($newPassword !== $confirmPassword) {
                throw new Exception("New passwords do not match");
            }
            
            if (strlen($newPassword) < 6) {
                throw new Exception("Password must be at least 6 characters long");
            }
            
            $result = $userObj->changePassword($tailorId, $currentPassword, $newPassword);
            
            if ($result['success']) {
                $message = 'Password changed successfully!';
            } else {
                $error = $result['error'];
            }
        }
        
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Handle profile picture upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['profile_picture'])) {
    try {
        if ($_FILES['profile_picture']['error'] === UPLOAD_ERR_OK) {
            $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
            $maxSize = 2 * 1024 * 1024; // 2MB
            
            if (!in_array($_FILES['profile_picture']['type'], $allowedTypes)) {
                throw new Exception("Invalid image type. Allowed: JPG, PNG, GIF, WebP");
            }
            
            if ($_FILES['profile_picture']['size'] > $maxSize) {
                throw new Exception("Image size must be less than 2MB");
            }
            
            // Generate unique filename
            $extension = pathinfo($_FILES['profile_picture']['name'], PATHINFO_EXTENSION);
            $filename = 'profile_' . $tailorId . '_' . time() . '.' . $extension;
            $uploadPath = PROFILE_IMAGES_PATH . $filename;
            
            // Delete old profile picture if exists
            if (!empty($tailor['profile_picture']) && file_exists(PROFILE_IMAGES_PATH . $tailor['profile_picture'])) {
                unlink(PROFILE_IMAGES_PATH . $tailor['profile_picture']);
            }
            
            if (move_uploaded_file($_FILES['profile_picture']['tmp_name'], $uploadPath)) {
                // Update database
                $db->update('tailors', ['profile_picture' => $filename], ['id' => $tailorId]);
                $message = 'Profile picture updated successfully!';
                $tailor['profile_picture'] = $filename;
            } else {
                throw new Exception("Failed to upload image");
            }
        } elseif ($_FILES['profile_picture']['error'] !== UPLOAD_ERR_NO_FILE) {
            throw new Exception("Error uploading file: " . $_FILES['profile_picture']['error']);
        }
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Handle profile picture removal
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['remove_picture'])) {
    try {
        if (!empty($tailor['profile_picture'])) {
            $filePath = PROFILE_IMAGES_PATH . $tailor['profile_picture'];
            if (file_exists($filePath)) {
                unlink($filePath);
            }
            
            $db->update('tailors', ['profile_picture' => null], ['id' => $tailorId]);
            $message = 'Profile picture removed successfully!';
            $tailor['profile_picture'] = null;
        }
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile - <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">
    <style>
        .profile-container {
            min-height: calc(100vh - 200px);
        }
        .profile-sidebar {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            box-shadow: 0 5px 20px rgba(0,0,0,0.05);
        }
        .profile-content {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            box-shadow: 0 5px 20px rgba(0,0,0,0.05);
        }
        .profile-picture-container {
            position: relative;
            width: 200px;
            height: 200px;
            margin: 0 auto 2rem;
        }
        .profile-picture {
            width: 100%;
            height: 100%;
            object-fit: cover;
            border-radius: 50%;
            border: 5px solid white;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        }
        .profile-picture-overlay {
            position: absolute;
            bottom: 10px;
            right: 10px;
            background: white;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 3px 10px rgba(0,0,0,0.2);
            cursor: pointer;
        }
        .profile-picture-overlay:hover {
            background: #f8f9fa;
        }
        .stats-card {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            border-radius: 10px;
            padding: 1.5rem;
            margin-bottom: 1rem;
            text-align: center;
        }
        .stats-number {
            font-size: 2rem;
            font-weight: bold;
            margin-bottom: 0.5rem;
        }
        .stats-label {
            font-size: 0.9rem;
            opacity: 0.9;
        }
        .nav-pills .nav-link {
            color: #495057;
            font-weight: 500;
            padding: 0.75rem 1rem;
            margin-bottom: 0.5rem;
            border-radius: 8px;
            display: flex;
            align-items: center;
        }
        .nav-pills .nav-link i {
            margin-right: 10px;
            width: 20px;
        }
        .nav-pills .nav-link.active {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
        }
        .section-title {
            font-weight: 600;
            color: #495057;
            margin-bottom: 1.5rem;
            padding-bottom: 0.75rem;
            border-bottom: 2px solid #667eea;
        }
        .form-label {
            font-weight: 500;
            color: #495057;
        }
        .btn-save {
            background: linear-gradient(135deg, #667eea, #764ba2);
            border: none;
            color: white;
            padding: 0.75rem 2rem;
            font-weight: 600;
            border-radius: 8px;
        }
        .btn-save:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.3);
        }
    </style>
</head>
<body>
    <?php include '../../includes/components/navbar.php'; ?>
    
    <div class="container profile-container py-5">
        <div class="row">
            <div class="col-lg-4 mb-4">
                <div class="profile-sidebar">
                    <!-- Profile Picture -->
                    <div class="profile-picture-container">
                        <?php if (!empty($tailor['profile_picture'])): ?>
                            <img src="<?php echo PROFILE_IMAGES_URL . $tailor['profile_picture']; ?>" 
                                 class="profile-picture" alt="Profile Picture">
                        <?php else: ?>
                            <div class="profile-picture bg-light d-flex align-items-center justify-content-center">
                                <i class="bi bi-person-fill" style="font-size: 4rem; color: #667eea;"></i>
                            </div>
                        <?php endif; ?>
                        
                        <!-- Picture Upload/Remove Overlay -->
                        <div class="profile-picture-overlay" data-bs-toggle="modal" data-bs-target="#pictureModal">
                            <i class="bi bi-camera-fill" style="color: #667eea;"></i>
                        </div>
                    </div>
                    
                    <!-- Tailor Info -->
                    <h3 class="text-center mb-3"><?php echo htmlspecialchars($tailor['full_name']); ?></h3>
                    <?php if (!empty($tailor['specialization'])): ?>
                        <p class="text-center text-muted mb-4">
                            <i class="bi bi-award me-2"></i><?php echo htmlspecialchars($tailor['specialization']); ?>
                        </p>
                    <?php endif; ?>
                    
                    <!-- Stats -->
                    <div class="row">
                        <div class="col-6">
                            <div class="stats-card">
                                <div class="stats-number"><?php echo $tailor['experience_years'] ?? '0'; ?></div>
                                <div class="stats-label">Years Experience</div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="stats-card">
                                <div class="stats-number">
                                    <?php 
                                    $rating = $tailor['avg_rating'] ?? 0;
                                    echo number_format($rating, 1);
                                    ?>
                                </div>
                                <div class="stats-label">Avg Rating</div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Navigation -->
                    <nav class="nav nav-pills flex-column mt-4">
                        <a class="nav-link active" href="#profile" data-bs-toggle="tab">
                            <i class="bi bi-person-fill"></i> Basic Info
                        </a>
                        <a class="nav-link" href="#social" data-bs-toggle="tab">
                            <i class="bi bi-link-45deg"></i> Social Media
                        </a>
                        <a class="nav-link" href="#password" data-bs-toggle="tab">
                            <i class="bi bi-shield-lock"></i> Change Password
                        </a>
                        <a class="nav-link" href="dashboard.php">
                            <i class="bi bi-arrow-left"></i> Back to Dashboard
                        </a>
                    </nav>
                </div>
            </div>
            
            <div class="col-lg-8">
                <div class="profile-content">
                    <?php if ($message): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="bi bi-check-circle me-2"></i><?php echo htmlspecialchars($message); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($error): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="bi bi-exclamation-triangle me-2"></i><?php echo htmlspecialchars($error); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                    <?php endif; ?>
                    
                    <div class="tab-content">
                        <!-- Basic Information Tab -->
                        <div class="tab-pane fade show active" id="profile">
                            <h3 class="section-title">Basic Information</h3>
                            <form method="POST" action="">
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Full Name *</label>
                                        <input type="text" class="form-control" name="full_name" 
                                               value="<?php echo htmlspecialchars($tailor['full_name']); ?>" required>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Email *</label>
                                        <input type="email" class="form-control" name="email" 
                                               value="<?php echo htmlspecialchars($tailor['email']); ?>" required>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Phone *</label>
                                        <input type="tel" class="form-control" name="phone" 
                                               value="<?php echo htmlspecialchars($tailor['phone'] ?? ''); ?>" required>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Experience (Years)</label>
                                        <input type="number" class="form-control" name="experience_years" 
                                               value="<?php echo $tailor['experience_years'] ?? '0'; ?>" min="0" max="50">
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Specialization</label>
                                    <input type="text" class="form-control" name="specialization" 
                                           value="<?php echo htmlspecialchars($tailor['specialization'] ?? ''); ?>"
                                           placeholder="e.g., Traditional Wear, Wedding Dresses">
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Bio/Description</label>
                                    <textarea class="form-control" name="bio" rows="4" 
                                              placeholder="Tell customers about your skills and experience"><?php echo htmlspecialchars($tailor['bio'] ?? ''); ?></textarea>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Languages (comma separated)</label>
                                    <input type="text" class="form-control" name="languages" 
                                           value="<?php echo !empty($tailor['languages']) ? implode(', ', $tailor['languages']) : ''; ?>"
                                           placeholder="e.g., English, French, Arabic">
                                </div>
                                
                                <h4 class="mt-4 mb-3">Address Information</h4>
                                <div class="row">
                                    <div class="col-12 mb-3">
                                        <label class="form-label">Address</label>
                                        <input type="text" class="form-control" name="address" 
                                               value="<?php echo htmlspecialchars($tailor['address'] ?? ''); ?>">
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label">City</label>
                                        <input type="text" class="form-control" name="city" 
                                               value="<?php echo htmlspecialchars($tailor['city'] ?? ''); ?>">
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label">State</label>
                                        <input type="text" class="form-control" name="state" 
                                               value="<?php echo htmlspecialchars($tailor['state'] ?? ''); ?>">
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label">Zip Code</label>
                                        <input type="text" class="form-control" name="zip_code" 
                                               value="<?php echo htmlspecialchars($tailor['zip_code'] ?? ''); ?>">
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Working Hours</label>
                                    <input type="text" class="form-control" name="working_hours" 
                                           value="<?php echo htmlspecialchars($tailor['working_hours'] ?? ''); ?>"
                                           placeholder="e.g., Mon-Fri 9AM-6PM, Sat 10AM-2PM">
                                </div>
                                
                                <button type="submit" name="update_profile" class="btn btn-save mt-3">
                                    <i class="bi bi-save me-2"></i>Save Changes
                                </button>
                            </form>
                        </div>
                        
                        <!-- Social Media Tab -->
                        <div class="tab-pane fade" id="social">
                            <h3 class="section-title">Social Media Links</h3>
                            <form method="POST" action="">
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">
                                            <i class="bi bi-facebook me-2" style="color: #1877F2;"></i>Facebook
                                        </label>
                                        <input type="url" class="form-control" name="facebook" 
                                               value="<?php echo htmlspecialchars($tailor['facebook'] ?? ''); ?>"
                                               placeholder="https://facebook.com/yourpage">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">
                                            <i class="bi bi-instagram me-2" style="color: #E4405F;"></i>Instagram
                                        </label>
                                        <input type="url" class="form-control" name="instagram" 
                                               value="<?php echo htmlspecialchars($tailor['instagram'] ?? ''); ?>"
                                               placeholder="https://instagram.com/yourprofile">
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">
                                            <i class="bi bi-twitter me-2" style="color: #1DA1F2;"></i>Twitter
                                        </label>
                                        <input type="url" class="form-control" name="twitter" 
                                               value="<?php echo htmlspecialchars($tailor['twitter'] ?? ''); ?>"
                                               placeholder="https://twitter.com/yourprofile">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">
                                            <i class="bi bi-linkedin me-2" style="color: #0A66C2;"></i>LinkedIn
                                        </label>
                                        <input type="url" class="form-control" name="linkedin" 
                                               value="<?php echo htmlspecialchars($tailor['linkedin'] ?? ''); ?>"
                                               placeholder="https://linkedin.com/in/yourprofile">
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">
                                            <i class="bi bi-youtube me-2" style="color: #FF0000;"></i>YouTube
                                        </label>
                                        <input type="url" class="form-control" name="youtube" 
                                               value="<?php echo htmlspecialchars($tailor['youtube'] ?? ''); ?>"
                                               placeholder="https://youtube.com/c/yourchannel">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">
                                            <i class="bi bi-globe me-2"></i>Website
                                        </label>
                                        <input type="url" class="form-control" name="website" 
                                               value="<?php echo htmlspecialchars($tailor['website'] ?? ''); ?>"
                                               placeholder="https://yourwebsite.com">
                                    </div>
                                </div>
                                
                                <button type="submit" name="update_social" class="btn btn-save mt-3">
                                    <i class="bi bi-save me-2"></i>Save Social Links
                                </button>
                            </form>
                        </div>
                        
                        <!-- Change Password Tab -->
                        <div class="tab-pane fade" id="password">
                            <h3 class="section-title">Change Password</h3>
                            <form method="POST" action="">
                                <div class="mb-3">
                                    <label class="form-label">Current Password *</label>
                                    <input type="password" class="form-control" name="current_password" required>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">New Password *</label>
                                    <input type="password" class="form-control" name="new_password" required
                                           pattern=".{6,}" title="Password must be at least 6 characters">
                                    <small class="text-muted">At least 6 characters</small>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Confirm New Password *</label>
                                    <input type="password" class="form-control" name="confirm_password" required>
                                </div>
                                
                                <button type="submit" name="update_password" class="btn btn-save mt-3">
                                    <i class="bi bi-shield-lock me-2"></i>Change Password
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Profile Picture Modal -->
    <div class="modal fade" id="pictureModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Profile Picture</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form method="POST" action="" enctype="multipart/form-data" id="pictureForm">
                        <div class="mb-3">
                            <label class="form-label">Upload New Picture</label>
                            <input type="file" class="form-control" name="profile_picture" accept="image/*">
                            <small class="text-muted">Max size: 2MB. Allowed: JPG, PNG, GIF, WebP</small>
                        </div>
                        
                        <?php if (!empty($tailor['profile_picture'])): ?>
                        <div class="text-center mt-4">
                            <p class="text-muted">Or remove current picture</p>
                            <button type="submit" name="remove_picture" class="btn btn-outline-danger">
                                <i class="bi bi-trash me-2"></i>Remove Picture
                            </button>
                        </div>
                        <?php endif; ?>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" form="pictureForm" class="btn btn-save">Upload</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Initialize tab functionality
        document.addEventListener('DOMContentLoaded', function() {
            // Show tab based on URL hash
            if (window.location.hash) {
                const trigger = document.querySelector(`[href="${window.location.hash}"]`);
                if (trigger) {
                    const tab = new bootstrap.Tab(trigger);
                    tab.show();
                }
            }
            
            // Update URL hash when tab changes
            const tabTriggers = document.querySelectorAll('a[data-bs-toggle="tab"]');
            tabTriggers.forEach(trigger => {
                trigger.addEventListener('shown.bs.tab', function(e) {
                    window.location.hash = e.target.getAttribute('href');
                });
            });
            
            // Image preview for profile picture upload
            const fileInput = document.querySelector('input[name="profile_picture"]');
            if (fileInput) {
                fileInput.addEventListener('change', function(e) {
                    if (e.target.files[0]) {
                        const reader = new FileReader();
                        reader.onload = function(e) {
                            document.querySelector('.profile-picture').src = e.target.result;
                        };
                        reader.readAsDataURL(e.target.files[0]);
                    }
                });
            }
        });
    </script>
</body>
</html>