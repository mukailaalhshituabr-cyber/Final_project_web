<?php
require_once dirname(__DIR__, 2) . '/config.php';
require_once ROOT_PATH . '/includes/classes/Database.php';
require_once ROOT_PATH . '/includes/classes/User.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'customer') {
    header('Location: ../auth/login.php');
    exit();
}

$db = Database::getInstance();
$userObj = new User();

$userId = $_SESSION['user_id'];
$userData = $userObj->getUserById($userId);
$profilePic = !empty($userData['profile_pic']) 
    ? SITE_URL . '/assets/images/avatars/' . $userData['profile_pic']
    : SITE_URL . '/assets/images/avatars/default.jpg';

$message = '';
$error = '';

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'full_name' => trim($_POST['full_name'] ?? ''),
        'email' => trim($_POST['email'] ?? ''),
        'phone' => trim($_POST['phone'] ?? ''),
        'address' => trim($_POST['address'] ?? ''),
        'bio' => trim($_POST['bio'] ?? '')
    ];

    // Handle profile picture upload
    if (isset($_FILES['profile_pic']) && $_FILES['profile_pic']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['profile_pic'];
        $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
        $maxSize = 5 * 1024 * 1024; // 5MB
        
        if (in_array($file['type'], $allowedTypes) && $file['size'] <= $maxSize) {
            // Generate unique filename
            $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
            $filename = 'user_' . $userId . '_' . time() . '.' . $extension;
            $uploadPath = PROFILE_IMAGES_PATH . $filename;
            
            // Delete old profile picture if exists and not default
            if (!empty($userData['profile_pic']) && $userData['profile_pic'] !== 'default.jpg') {
                $oldPath = PROFILE_IMAGES_PATH . $userData['profile_pic'];
                if (file_exists($oldPath)) {
                    unlink($oldPath);
                }
            }
            
            // Upload new picture
            if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
                $data['profile_pic'] = $filename;
            } else {
                $error = 'Failed to upload profile picture';
            }
        } else {
            $error = 'Invalid file type or size too large (max 5MB)';
        }
    }
    
    // Handle profile picture removal
    if (isset($_POST['remove_picture']) && $_POST['remove_picture'] === '1') {
        if (!empty($userData['profile_pic']) && $userData['profile_pic'] !== 'default.jpg') {
            $oldPath = PROFILE_IMAGES_PATH . $userData['profile_pic'];
            if (file_exists($oldPath)) {
                unlink($oldPath);
            }
        }
        $data['profile_pic'] = 'default.jpg';
    }

    if (!$error) {
        $result = $userObj->updateProfile($userId, $data);
        
        if ($result === true) {
            $message = 'Profile updated successfully';
            $userData = $userObj->getUserById($userId); // Refresh data
            $profilePic = !empty($userData['profile_pic']) 
                ? SITE_URL . '/assets/images/avatars/' . $userData['profile_pic']
                : SITE_URL . '/assets/images/avatars/default.jpg';
            
            // Update session data
            $_SESSION['full_name'] = $userData['full_name'];
            $_SESSION['email'] = $userData['email'];
        } else {
            $error = $result;
        }
    }
}

// Get user profile data
$db->query("SELECT * FROM user_profiles WHERE user_id = :user_id");
$db->bind(':user_id', $userId);
$profileData = $db->single() ?: [];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">
    <style>
        .profile-container {
            min-height: calc(100vh - 200px);
        }
        .profile-card {
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
        }
        .profile-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 3rem 2rem;
            text-align: center;
        }
        .profile-avatar {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            border: 5px solid white;
            object-fit: cover;
            margin-bottom: 1rem;
            cursor: pointer;
            transition: transform 0.3s ease;
        }
        .profile-avatar:hover {
            transform: scale(1.05);
        }
        .upload-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.5);
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            opacity: 0;
            transition: opacity 0.3s ease;
            color: white;
        }
        .avatar-container:hover .upload-overlay {
            opacity: 1;
        }
        .form-label {
            font-weight: 600;
            color: #495057;
        }
        .btn-save {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            padding: 0.75rem 2rem;
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
        <div class="row justify-content-center">
            <div class="col-lg-10">
                <div class="profile-card">
                    <!-- Profile Header -->
                    <div class="profile-header">
                        <div class="position-relative d-inline-block avatar-container">
                            <img src="<?php echo $profilePic; ?>" 
                                 class="profile-avatar" 
                                 id="profileAvatar"
                                 alt="Profile Picture"
                                 onerror="this.src='<?php echo SITE_URL; ?>/assets/images/avatars/default.jpg'">
                            <div class="upload-overlay">
                                <i class="bi bi-camera fs-4"></i>
                            </div>
                        </div>
                        <h3 class="fw-bold mb-2"><?php echo htmlspecialchars($userData['full_name']); ?></h3>
                        <p class="mb-0">Customer since <?php echo date('F Y', strtotime($userData['created_at'])); ?></p>
                    </div>

                    <!-- Profile Form -->
                    <div class="p-4 p-md-5">
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

                        <form method="POST" action="" enctype="multipart/form-data">
                            <!-- Hidden file input for avatar -->
                            <input type="file" name="profile_pic" id="profilePicInput" accept="image/*" class="d-none">
                            <input type="hidden" name="remove_picture" id="removePicture" value="0">
                            
                            <div class="row mb-4">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Full Name</label>
                                    <input type="text" class="form-control" name="full_name" 
                                           value="<?php echo htmlspecialchars($userData['full_name']); ?>" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Email Address</label>
                                    <input type="email" class="form-control" name="email" 
                                           value="<?php echo htmlspecialchars($userData['email']); ?>" required>
                                </div>
                            </div>

                            <div class="row mb-4">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Phone Number</label>
                                    <input type="tel" class="form-control" name="phone" 
                                           value="<?php echo htmlspecialchars($userData['phone'] ?? ''); ?>">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Address</label>
                                    <textarea class="form-control" name="address" rows="1"><?php echo htmlspecialchars($userData['address'] ?? ''); ?></textarea>
                                </div>
                            </div>

                            <div class="mb-4">
                                <label class="form-label">Bio</label>
                                <textarea class="form-control" name="bio" rows="3" 
                                          placeholder="Tell us about yourself..."><?php echo htmlspecialchars($userData['bio'] ?? ''); ?></textarea>
                            </div>

                            <!-- Profile Picture Options -->
                            <div class="mb-4 p-3 border rounded">
                                <label class="form-label fw-bold mb-3">Profile Picture</label>
                                <div class="d-flex flex-wrap gap-3 align-items-center">
                                    <button type="button" class="btn btn-outline-primary" onclick="document.getElementById('profilePicInput').click()">
                                        <i class="bi bi-upload me-2"></i> Upload New Picture
                                    </button>
                                    <?php if ($userData['profile_pic'] && $userData['profile_pic'] !== 'default.jpg'): ?>
                                    <button type="button" class="btn btn-outline-danger" onclick="removeProfilePicture()">
                                        <i class="bi bi-trash me-2"></i> Remove Picture
                                    </button>
                                    <?php endif; ?>
                                    <small class="text-muted">Supported formats: JPG, PNG, GIF, WebP (max 5MB)</small>
                                </div>
                            </div>

                            <!-- Notification Preferences -->
                            <div class="mb-4 p-3 border rounded">
                                <label class="form-label fw-bold mb-3">Notification Preferences</label>
                                <?php 
                                $notifications = isset($profileData['notification_preferences']) 
                                    ? json_decode($profileData['notification_preferences'], true) 
                                    : ['email' => true, 'sms' => false, 'push' => true];
                                ?>
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="checkbox" name="notifications[email]" 
                                           id="emailNotifications" <?php echo $notifications['email'] ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="emailNotifications">
                                        Email notifications
                                    </label>
                                </div>
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="checkbox" name="notifications[sms]" 
                                           id="smsNotifications" <?php echo $notifications['sms'] ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="smsNotifications">
                                        SMS notifications
                                    </label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="notifications[push]" 
                                           id="pushNotifications" <?php echo $notifications['push'] ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="pushNotifications">
                                        Push notifications
                                    </label>
                                </div>
                            </div>

                            <!-- Newsletter Subscription -->
                            <div class="mb-4 p-3 border rounded">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="newsletter" 
                                           id="newsletter" <?php echo ($profileData['newsletter_subscription'] ?? true) ? 'checked' : ''; ?>>
                                    <label class="form-check-label fw-bold" for="newsletter">
                                        Subscribe to newsletter
                                    </label>
                                    <p class="text-muted small mb-0 mt-1">Receive updates about new products, promotions, and fashion tips.</p>
                                </div>
                            </div>

                            <div class="d-flex justify-content-between pt-3">
                                <a href="dashboard.php" class="btn btn-outline-secondary">
                                    <i class="bi bi-arrow-left me-2"></i> Back to Dashboard
                                </a>
                                <button type="submit" class="btn btn-save">
                                    <i class="bi bi-check-circle me-2"></i> Save Changes
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include '../../includes/components/footer.php'; ?>

    <script>
        // Profile picture upload
        document.getElementById('profilePicInput').addEventListener('change', function(e) {
            if (this.files && this.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    document.getElementById('profileAvatar').src = e.target.result;
                }
                reader.readAsDataURL(this.files[0]);
            }
        });

        // Click avatar to trigger file input
        document.getElementById('profileAvatar').addEventListener('click', function() {
            document.getElementById('profilePicInput').click();
        });

        // Remove profile picture
        function removeProfilePicture() {
            if (confirm('Are you sure you want to remove your profile picture?')) {
                document.getElementById('removePicture').value = '1';
                document.getElementById('profileAvatar').src = '<?php echo SITE_URL; ?>/assets/images/avatars/default.jpg';
            }
        }

        // Form validation
        document.querySelector('form').addEventListener('submit', function(e) {
            const email = this.querySelector('input[name="email"]').value;
            const phone = this.querySelector('input[name="phone"]').value;
            
            // Email validation
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(email)) {
                e.preventDefault();
                alert('Please enter a valid email address');
                return false;
            }
            
            // Optional phone validation
            if (phone && !/^[\d\s\-\+\(\)]{10,}$/.test(phone)) {
                e.preventDefault();
                alert('Please enter a valid phone number');
                return false;
            }
        });
    </script>
</body>
</html>