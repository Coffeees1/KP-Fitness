<?php
define('PAGE_TITLE', 'My Profile');
require_once '../includes/config.php';
require_trainer();

$userId = $_SESSION['UserID'];

// Prepare feedback message for toast
$feedbackMessage = '';
$feedbackType = '';

// Handle Profile Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_profile'])) {
    validate_csrf_token($_POST['csrf_token']);
    
    $fullName = sanitize_input($_POST['fullName']);
    $phone = !empty($_POST['phone']) ? sanitize_input($_POST['phone']) : null;
    $dateOfBirth = !empty($_POST['dateOfBirth']) ? sanitize_input($_POST['dateOfBirth']) : null;
    $height = !empty($_POST['height']) ? intval($_POST['height']) : null;
    $weight = !empty($_POST['weight']) ? intval($_POST['weight']) : null;
    $gender = !empty($_POST['gender']) ? sanitize_input($_POST['gender']) : null;

    // Validation
    $validPhone = true; 
    if ($phone) {
        // Malaysian phone number regex: 01X-XXX XXXX or 01X-XXXX XXXX
        if (!preg_match('/^01\d-\d{3,4} \d{4}$/', $phone)) {
            $validPhone = false;
            $feedbackMessage = 'Invalid phone number format. Please use the format 01X-XXX XXXX or 01X-XXXX XXXX.';
            $feedbackType = 'danger';
        }
    }

    // Validation - Add gender validation
    $validGender = true;
    if ($gender && !in_array($gender, ['Male', 'Female', 'Other'])) {
        $validGender = false;
        $feedbackMessage = 'Invalid gender selected.';
        $feedbackType = 'danger';
    }

    if ($validPhone && $validGender) {
        try {
            $stmt = $pdo->prepare("UPDATE users SET FullName = ?, Phone = ?, DateOfBirth = ?, Height = ?, Weight = ?, Gender = ? WHERE UserID = ?");
            if ($stmt->execute([$fullName, $phone, $dateOfBirth, $height, $weight, $gender, $userId])) {
                // Removed notification creation for trainer as they might not have a notification bell or similar UI, 
                // but if they do (shared table), it's fine. I'll leave it in.
                create_notification($userId, 'Profile Updated', 'Your profile information has been successfully updated.', 'success');
                $feedbackMessage = 'Profile updated successfully!';
                $feedbackType = 'success';
            } else {
                $feedbackMessage = 'Failed to update profile.';
                $feedbackType = 'danger';
            }
        } catch (PDOException $e) {
            $feedbackMessage = 'Database error: ' . $e->getMessage();
            $feedbackType = 'danger';
        }
    }
}

// Handle Password Change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    validate_csrf_token($_POST['csrf_token']);
    $currentPassword = $_POST['currentPassword'];
    $newPassword = $_POST['newPassword'];
    $confirmPassword = $_POST['confirmPassword'];

    if ($newPassword !== $confirmPassword) {
        $feedbackMessage = 'New passwords do not match.';
        $feedbackType = 'danger';
    } else {
        try {
            $stmt = $pdo->prepare("SELECT Password FROM users WHERE UserID = ?");
            $stmt->execute([$userId]);
            $user_password_hash = $stmt->fetchColumn();

            if (password_verify($currentPassword, $user_password_hash)) {
                $newHashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE users SET Password = ? WHERE UserID = ?");
                if ($stmt->execute([$newHashedPassword, $userId])) {
                    create_notification($userId, 'Password Changed', 'Your account password was recently changed.', 'warning');
                    $feedbackMessage = 'Password changed successfully.';
                    $feedbackType = 'success';
                } else {
                    $feedbackMessage = 'Failed to change password.';
                    $feedbackType = 'danger';
                }
            } else {
                $feedbackMessage = 'Incorrect current password.';
                $feedbackType = 'danger';
            }
        } catch (PDOException $e) {
            $feedbackMessage = 'Database error: ' . $e->getMessage();
            $feedbackType = 'danger';
        }
    }
}

// Handle Profile Picture Upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['profilePicture'])) {
    validate_csrf_token($_POST['csrf_token']);
    if ($_FILES['profilePicture']['error'] == 0) {
        $allowed = ['jpg', 'jpeg', 'png', 'gif'];
        $filename = $_FILES['profilePicture']['name'];
        $filesize = $_FILES['profilePicture']['size'];
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

        // Validate format
        if (!in_array($ext, $allowed)) {
            $feedbackMessage = 'Invalid file format. Please upload JPG, JPEG, PNG, or GIF.';
            $feedbackType = 'danger';
        } 
        // Validate size (max 2MB)
        elseif ($filesize > 2 * 1024 * 1024) {
            $feedbackMessage = 'File size is too large. Maximum allowed size is 2MB.';
            $feedbackType = 'danger';
        } else {
            $target_dir = "../uploads/profile_pictures/";
            if (!is_dir($target_dir)) mkdir($target_dir, 0777, true);
            
            $newFileName = uniqid() . '.' . $ext;
            $target_file = $target_dir . $newFileName;
            
            if (move_uploaded_file($_FILES["profilePicture"]["tmp_name"], $target_file)) {
                $stmt = $pdo->prepare("UPDATE users SET ProfilePicture = ? WHERE UserID = ?");
                $stmt->execute([$target_file, $userId]);
                create_notification($userId, 'Profile Picture Updated', 'Your profile picture has been updated.', 'success');
                $feedbackMessage = 'Profile picture updated.';
                $feedbackType = 'success';
            } else {
                $feedbackMessage = 'Error uploading file.';
                $feedbackType = 'danger';
            }
        }
    }
}


// Fetch user data for display
$stmt = $pdo->prepare("SELECT FullName, Email, Phone, DateOfBirth, Height, Weight, Gender, ProfilePicture FROM users WHERE UserID = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch();

// If POST request failed, overwrite user data with POST data
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_profile']) && $feedbackType === 'danger') {
    $user['FullName'] = $_POST['fullName'];
    $user['Phone'] = $_POST['phone'];
    $user['DateOfBirth'] = $_POST['dateOfBirth'];
    $user['Height'] = $_POST['height'];
    $user['Weight'] = $_POST['weight'];
    $user['Gender'] = $_POST['gender'];
}

include 'includes/trainer_header.php';
?>

<div class="container mt-4" style="max-width: 800px;">
    <form id="profile-form" action="profile.php" method="POST" enctype="multipart/form-data">
        <input type="hidden" name="csrf_token" value="<?php echo get_csrf_token(); ?>">
        
        <!-- Profile Picture Section -->
        <div class="text-center mb-4">
            <div class="profile-picture-wrapper">
                <img src="<?php echo htmlspecialchars($user['ProfilePicture'] ?? '../assets/images/default-avatar.svg'); ?>" 
                     alt="Profile Picture" 
                     id="profile-pic-preview" 
                     class="profile-img"
                     onerror="this.onerror=null; this.src='../assets/images/default-avatar.svg';">
                <label for="profilePicture" class="profile-pic-btn" title="Change Profile Picture">
                    <i class="fas fa-camera"></i>
                </label>
            </div>
            <input type="file" id="profilePicture" name="profilePicture" class="d-none" accept="image/png, image/jpeg, image/gif">
        </div>

        <!-- Details Section -->
        <div class="card shadow-sm" style="background-color: #2b2b2b; border: 1px solid rgba(255, 107, 0, 0.49); color: #fff;">
            <div class="card-body p-4 position-relative">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h3 class="fw-bold mb-0">My Profile</h3>
                    <button class="btn btn-primary" id="edit-profile-btn" style="position: absolute; top: 20px; right: 20px;">
                        <i class="fas fa-edit me-2"></i>Edit Profile
                    </button>
                </div>

                <div class="mb-3">
                    <label for="fullName" class="form-label fw-bold" style="color: #b0b0b0;">Full Name</label>
                    <input type="text" class="form-control" id="fullName" name="fullName" value="<?php echo htmlspecialchars($user['FullName'] ?? ''); ?>" readonly style="background-color: #313131; border: 1px solid #333; color: #fff;">
                </div>

                <div class="mb-3">
                    <label for="email" class="form-label fw-bold" style="color: #b0b0b0;">Email</label>
                    <input type="email" class="form-control" id="email" value="<?php echo htmlspecialchars($user['Email'] ?? ''); ?>" readonly disabled style="background-color: #252525; border: 1px solid #333; color: #888;">
                </div>

                <div class="mb-3">
                    <label class="form-label fw-bold" style="color: #b0b0b0;">Password</label>
                    <div class="input-group">
                        <input type="password" class="form-control" value="****************" readonly disabled style="background-color: #252525; border: 1px solid #333; color: #888;">
                        <button class="btn btn-outline-secondary" type="button" data-bs-toggle="modal" data-bs-target="#changePasswordModal" style="border-color: #333; color: #ccc;">Change Password</button>
                    </div>
                </div>

                <div class="mb-3">
                    <label for="phone" class="form-label fw-bold" style="color: #b0b0b0;">Contact</label>
                    <input type="text" class="form-control" id="phone" name="phone" value="<?php echo htmlspecialchars(format_phone_display($user['Phone'] ?? '')); ?>" placeholder="e.g. 01X-XXX XXXX" readonly style="background-color: #313131; border: 1px solid #333; color: #fff;">
                    <div id="phone-error" class="invalid-feedback d-none">
                        <i class="fas fa-exclamation-circle"></i> Invalid format. Use: 01X-XXX XXXX (10 digits) or 01X-XXXX XXXX (11 digits)
                    </div>
                </div>

                <div class="mb-3">
                    <label for="gender" class="form-label fw-bold" style="color: #b0b0b0;">Gender</label>
                    <select class="form-select" id="gender" name="gender" disabled style="background-color: #252525; color: #fff; border: 1px solid #333;">
                        <option value="">Select Gender</option>
                        <option value="Male" <?php echo ($user['Gender'] ?? '') == 'Male' ? 'selected' : ''; ?>>Male</option>
                        <option value="Female" <?php echo ($user['Gender'] ?? '') == 'Female' ? 'selected' : ''; ?>>Female</option>
                        <option value="Other" <?php echo ($user['Gender'] ?? '') == 'Other' ? 'selected' : ''; ?>>Other</option>
                    </select>
                </div>

                <div class="mb-3">
                    <label for="dateOfBirth" class="form-label fw-bold" style="color: #b0b0b0;">Date of Birth</label>
                    <input type="date" class="form-control" id="dateOfBirth" name="dateOfBirth" value="<?php echo htmlspecialchars($user['DateOfBirth'] ?? ''); ?>" readonly style="background-color: #313131; border: 1px solid #333; color: #fff;">
                </div>

                <div class="mb-3">
                    <label for="height" class="form-label fw-bold" style="color: #b0b0b0;">Height (cm)</label>
                    <input type="number" class="form-control" id="height" name="height" value="<?php echo htmlspecialchars($user['Height'] ?? ''); ?>" readonly style="background-color: #313131; border: 1px solid #333; color: #fff;">
                </div>

                <div class="mb-3">
                    <label for="weight" class="form-label fw-bold" style="color: #b0b0b0;">Weight (kg)</label>
                    <input type="number" class="form-control" id="weight" name="weight" value="<?php echo htmlspecialchars($user['Weight'] ?? ''); ?>" readonly style="background-color: #313131; border: 1px solid #333; color: #fff;">
                </div>

                <div class="text-end mt-4 d-none" id="save-btn-container">
                    <button type="submit" name="save_profile" class="btn btn-primary px-4">Save Changes</button>
                </div>
            </div>
        </div>
    </form>
</div>

<!-- Change Password Modal -->
<div class="modal fade" id="changePasswordModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form class="modal-content" action="profile.php" method="POST" style="background-color: #2b2b2b; color: #fff; border: 1px solid #444;">
            <input type="hidden" name="csrf_token" value="<?php echo get_csrf_token(); ?>">
            <div class="modal-header" style="border-bottom: 1px solid #444;">
                <h5 class="modal-title">Change Password</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" style="filter: invert(1);"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label for="currentPassword" class="form-label">Current Password</label>
                    <div class="input-group">
                        <input type="password" class="form-control" id="currentPassword" name="currentPassword" required style="background-color: #313131; border: 1px solid #333; color: #fff;">
                        <button class="btn btn-outline-secondary toggle-password" type="button" style="border-color: #333; color: #ccc;"><i class="fas fa-eye"></i></button>
                    </div>
                </div>
                <div class="mb-3">
                    <label for="newPassword" class="form-label">New Password</label>
                    <div class="input-group">
                        <input type="password" class="form-control" id="newPassword" name="newPassword" required style="background-color: #313131; border: 1px solid #333; color: #fff;">
                        <button class="btn btn-outline-secondary toggle-password" type="button" style="border-color: #333; color: #ccc;"><i class="fas fa-eye"></i></button>
                    </div>
                </div>
                <div class="mb-3">
                    <label for="confirmPassword" class="form-label">Confirm New Password</label>
                    <div class="input-group">
                        <input type="password" class="form-control" id="confirmPassword" name="confirmPassword" required style="background-color: #313131; border: 1px solid #333; color: #fff;">
                        <button class="btn btn-outline-secondary toggle-password" type="button" style="border-color: #333; color: #ccc;"><i class="fas fa-eye"></i></button>
                    </div>
                </div>
            </div>
            <div class="modal-footer" style="border-top: 1px solid #444;">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="submit" name="change_password" class="btn btn-primary">Change Password</button>
            </div>
        </form>
    </div>
</div>

<style>
    .profile-picture-wrapper {
        position: relative;
        width: 150px;
        height: 150px;
        margin: 0 auto;
    }
    .profile-img {
        width: 150px;
        height: 150px;
        border-radius: 50%;
        object-fit: cover;
        border: 4px solid #fff; 
        box-shadow: 0 4px 10px rgba(0,0,0,0.1); 
    }
    .profile-pic-btn {
        position: absolute;
        bottom: 5px;
        right: 5px;
        background: #ff6b00; 
        color: white;
        border-radius: 50%;
        width: 45px;
        height: 45px;
        display: flex;
        justify-content: center;
        align-items: center;
        cursor: pointer;
        transition: all 0.3s ease;
        border: 3px solid white;
        box-shadow: 0 4px 8px rgba(0,0,0,0.2);
        font-size: 1.1rem;
    }
    .profile-pic-btn:hover {
        background: #e65c00;
        transform: scale(1.1);
    }
    /* Add specific trainer dark mode overrides inline since we are reusing client structure */
    .form-control:focus {
        background-color: #313131;
        color: #fff;
        border-color: #ff6b00;
        box-shadow: none;
    }
</style>

<script>
    window.profileConfig = {
        feedbackMessage: <?php echo json_encode($feedbackMessage); ?>,
        feedbackType: "<?php echo $feedbackType; ?>"
    };
</script>
<script src="../assets/js/client-profile.js"></script>

<?php include 'includes/trainer_footer.php'; ?>