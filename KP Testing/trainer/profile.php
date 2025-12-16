<?php
define('PAGE_TITLE', 'My Profile');
require_once '../includes/config.php';
require_trainer();

$userId = $_SESSION['UserID'];
$feedback = [];

// Fetch existing user data
try {
    $stmt = $pdo->prepare("SELECT FullName, Email, Phone, DateOfBirth, Height, Weight, ProfilePicture, Gender, Specialist FROM users WHERE UserID = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch();
} catch (PDOException $e) {
    $feedback = ['type' => 'danger', 'message' => 'Could not fetch your data. Please try again later.'];
    $user = [];
}

// Handle Profile Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_profile'])) {
    validate_csrf_token($_POST['csrf_token']);
    
    $height = !empty($_POST['height']) ? intval($_POST['height']) : null;
    $weight = !empty($_POST['weight']) ? intval($_POST['weight']) : null;
    
    if ($height > 300 || $weight > 300) {
        $feedback = ['type' => 'danger', 'message' => 'Height and Weight cannot exceed 300.'];
    }

    $phone = !empty($_POST['phone']) ? sanitize_input($_POST['phone']) : null;
    $dateOfBirth = !empty($_POST['dateOfBirth']) ? sanitize_input($_POST['dateOfBirth']) : null;
    
    // Handle File Upload
    $profilePicturePath = $user['ProfilePicture'] ?? null;
    if (isset($_FILES['profilePicture']) && $_FILES['profilePicture']['error'] == 0) {
        $target_dir = "../uploads/profile_pictures/";
        if (!is_dir($target_dir)) {
            mkdir($target_dir, 0777, true);
        }
        $target_file = $target_dir . uniqid() . '_' . basename($_FILES["profilePicture"]["name"]);
        $imageFileType = strtolower(pathinfo($target_file,PATHINFO_EXTENSION));

        $check = getimagesize($_FILES["profilePicture"]["tmp_name"]);
        if($check === false) {
            $feedback = ['type' => 'danger', 'message' => 'File is not an image.'];
        } elseif (!in_array($imageFileType, ['jpg', 'png', 'jpeg', 'gif'])) {
            $feedback = ['type' => 'danger', 'message' => 'Sorry, only JPG, JPEG, PNG & GIF files are allowed.'];
        } elseif (move_uploaded_file($_FILES["profilePicture"]["tmp_name"], $target_file)) {
            $profilePicturePath = $target_file;
        } else {
            $feedback = ['type' => 'danger', 'message' => 'Sorry, there was an error uploading your file.'];
        }
    }
    
    if (empty($feedback)) {
        try {
            $stmt = $pdo->prepare("UPDATE users SET Height = ?, Weight = ?, Phone = ?, DateOfBirth = ?, ProfilePicture = ? WHERE UserID = ?");
            if ($stmt->execute([$height, $weight, $phone, $dateOfBirth, $profilePicturePath, $userId])) {
                $feedback = ['type' => 'success', 'message' => 'Profile updated successfully!'];
                // Refresh data
                $stmt = $pdo->prepare("SELECT FullName, Email, Phone, DateOfBirth, Height, Weight, ProfilePicture, Gender, Specialist FROM users WHERE UserID = ?");
                $stmt->execute([$userId]);
                $user = $stmt->fetch();
            } else {
                $feedback = ['type' => 'danger', 'message' => 'Failed to update profile.'];
            }
        } catch (PDOException $e) {
            $feedback = ['type' => 'danger', 'message' => 'Database error: ' . $e->getMessage()];
        }
    }
}

// Handle Password Change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    validate_csrf_token($_POST['csrf_token']);
    $currentPassword = $_POST['currentPassword'];
    $newPassword = $_POST['newPassword'];
    $confirmPassword = $_POST['confirmPassword'];

    if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
        $feedback = ['type' => 'danger', 'message' => 'Please fill in all password fields.'];
    } elseif ($newPassword !== $confirmPassword) {
        $feedback = ['type' => 'danger', 'message' => 'New passwords do not match.'];
    } elseif (strlen($newPassword) < 8) {
        $feedback = ['type' => 'danger', 'message' => 'Password must be at least 8 characters long.'];
    } else {
        try {
            $stmt = $pdo->prepare("SELECT Password FROM users WHERE UserID = ?");
            $stmt->execute([$userId]);
            $hash = $stmt->fetchColumn();

            if (password_verify($currentPassword, $hash)) {
                $newHash = password_hash($newPassword, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE users SET Password = ? WHERE UserID = ?");
                $stmt->execute([$newHash, $userId]);
                $feedback = ['type' => 'success', 'message' => 'Password changed successfully.'];
            } else {
                $feedback = ['type' => 'danger', 'message' => 'Incorrect current password.'];
            }
        } catch (PDOException $e) {
            $feedback = ['type' => 'danger', 'message' => 'Database error: ' . $e->getMessage()];
        }
    }
}

include 'includes/trainer_header.php';
?>

<style>
    /* Profile Specific Styles */
    .profile-container {
        max-width: 800px;
        margin: 0 auto;
    }

    .profile-card {
        background: #2b2b2b;
        border-radius: 8px;
        padding: 3rem 2rem;
        position: relative;
        border: 1px solid rgba(255, 106, 0, 0.49);
    }

    /* Profile Picture Area */
    .profile-pic-wrapper {
        position: relative;
        width: 120px;
        height: 120px;
        margin: -5rem auto 2rem; /* Pull up to overlap if desired, or just margin */
        margin-top: -6rem; /* Pulling it up out of the card slightly for effect, or keep inside */
        margin-top: 0; 
        margin-bottom: 2.5rem;
    }

    .profile-pic {
        width: 120px;
        height: 120px;
        border-radius: 50%;
        object-fit: cover;
        border: 3px solid #fff;
        background-color: #444;
    }

    .camera-icon-btn {
        position: absolute;
        bottom: 0;
        right: 0;
        background: var(--primary-color);
        color: #fff;
        border: 2px solid #2b2b2b;
        border-radius: 50%;
        width: 36px;
        height: 36px;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: transform 0.2s;
    }

    .camera-icon-btn:hover {
        transform: scale(1.1);
    }

    #fileInput {
        display: none;
    }

    /* Form Fields */
    .form-group {
        margin-bottom: 1.5rem;
    }

    .form-label {
        color: #b0b0b0;
        font-size: 0.9rem;
        font-weight: 500;
        margin-bottom: 0.5rem;
    }

    .form-control {
        background-color: #313131ff;
        border: 1px solid #333;
        color: #fff;
        border-radius: 6px;
        padding: 0.75rem 1rem;
    }

    .form-control:focus {
        background-color: #313131ff;
        border-color: var(--primary-color);
        box-shadow: none;
        color: #fff;
    }

    .form-control:disabled {
        background-color: #252525;
        color: #888;
        border-color: transparent;
    }

    .password-display {
        background-color: #1a1a1a;
        border: 1px solid #333;
        border-radius: 6px;
        padding: 0.75rem 1rem;
        display: flex;
        justify-content: space-between;
        align-items: center;
        color: #fff;
    }

    .btn-change-pw {
        background: #333;
        color: #ccc;
        border: none;
        padding: 0.4rem 1rem;
        border-radius: 4px;
        font-size: 0.85rem;
        transition: all 0.2s;
    }

    .btn-change-pw:hover {
        background: #444;
        color: #fff;
    }

    /* Modal Styles override */
    .modal-content {
        background-color: #2b2b2b;
        color: #fff;
        border: 1px solid #444;
    }
    
    .modal-header {
        border-bottom: 1px solid #444;
    }
    
    .modal-footer {
        border-top: 1px solid #444;
    }
    
    .btn-close {
        filter: invert(1);
    }
</style>

<div class="container-fluid pt-4">
    <div class="d-flex align-items-center mb-2">
        <h2 class="h3 mb-0 text-white">My Profile</h2>
        <!-- Optional: Add an Edit button here if the form was read-only initially, but we are making it editable by default to match 'edit profile' request -->
    </div>
    <hr class="border-white opacity-100 mb-4">

    <?php if (!empty($feedback)): ?>
        <div class="alert alert-<?php echo $feedback['type']; ?> alert-dismissible fade show mb-4" role="alert">
            <?php echo $feedback['message']; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="profile-container">
        <!-- Main Form -->
        <form action="profile.php" method="POST" enctype="multipart/form-data" id="profileForm">
            <input type="hidden" name="csrf_token" value="<?php echo get_csrf_token(); ?>">
            
            <div class="profile-card">
                <!-- Profile Picture -->
                <div class="profile-pic-wrapper">
                    <?php 
                        $picPath = !empty($user['ProfilePicture']) ? $user['ProfilePicture'] : '../assets/images/default_avatar.png'; 
                        // Ensure path is correct for display
                        if(strpos($picPath, '../') === 0) {
                            // If path starts with ../, it's relative to execution, but for HTML src we might need adjustment depending on where we are.
                            // Assuming we are in /trainer/, ../uploads is correct.
                        }
                    ?>
                    <img src="<?php echo htmlspecialchars($picPath); ?>" alt="Profile" class="profile-pic" id="profileImagePreview">
                    <label for="fileInput" class="camera-icon-btn">
                        <i class="fas fa-camera"></i>
                    </label>
                    <input type="file" id="fileInput" name="profilePicture" accept="image/*" onchange="previewImage(this)">
                </div>

                <div class="row g-4">
                    <!-- Full Name -->
                    <div class="col-12">
                        <label class="form-label">Full Name</label>
                        <input type="text" class="form-control" value="<?php echo htmlspecialchars($user['FullName'] ?? ''); ?>" disabled>
                    </div>

                    <!-- Email -->
                    <div class="col-12">
                        <label class="form-label">Email</label>
                        <input type="email" class="form-control" value="<?php echo htmlspecialchars($user['Email'] ?? ''); ?>" disabled>
                    </div>

                    <!-- Password (Display Only) -->
                    <div class="col-12">
                        <label class="form-label">Password</label>
                        <div class="password-display">
                            <span>••••••••••••</span>
                            <button type="button" class="btn-change-pw" data-bs-toggle="modal" data-bs-target="#passwordModal">
                                Change Password
                            </button>
                        </div>
                    </div>

                    <!-- Contact -->
                    <div class="col-12">
                        <label class="form-label">Contact (Malaysia Format)</label>
                        <input type="text" class="form-control" name="phone" placeholder="e.g. 01X-XXX XXXX" value="<?php echo htmlspecialchars($user['Phone'] ?? ''); ?>">
                    </div>

                    <!-- Date of Birth -->
                    <div class="col-12">
                        <label class="form-label">Date of Birth</label>
                        <input type="date" class="form-control" name="dateOfBirth" value="<?php echo htmlspecialchars($user['DateOfBirth'] ?? ''); ?>">
                    </div>

                    <!-- Height -->
                    <div class="col-12">
                        <label class="form-label">Height (cm)</label>
                        <input type="number" class="form-control" name="height" max="300" placeholder="170" value="<?php echo htmlspecialchars($user['Height'] ?? ''); ?>">
                    </div>

                    <!-- Weight -->
                    <div class="col-12">
                        <label class="form-label">Weight (kg)</label>
                        <input type="number" class="form-control" name="weight" max="300" placeholder="60" value="<?php echo htmlspecialchars($user['Weight'] ?? ''); ?>">
                    </div>
                </div>
                
                <!-- Save Button (Hidden unless changes? Or always visible? Design implies auto-save or bottom button not shown in snippet. I'll add one at bottom) -->
                <div class="mt-5 text-end">
                    <button type="submit" name="save_profile" class="btn btn-primary px-4">Save Changes</button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Password Change Modal -->
<div class="modal fade" id="passwordModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Change Password</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="profile.php" method="POST">
                <div class="modal-body">
                    <input type="hidden" name="csrf_token" value="<?php echo get_csrf_token(); ?>">
                    <div class="mb-3">
                        <label class="form-label">Current Password</label>
                        <input type="password" class="form-control" name="currentPassword" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">New Password</label>
                        <input type="password" class="form-control" name="newPassword" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Confirm New Password</label>
                        <input type="password" class="form-control" name="confirmPassword" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="change_password" class="btn btn-primary">Update Password</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function previewImage(input) {
    if (input.files && input.files[0]) {
        var reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById('profileImagePreview').src = e.target.result;
        }
        reader.readAsDataURL(input.files[0]);
    }
}
</script>

<?php include 'includes/trainer_footer.php'; ?>
