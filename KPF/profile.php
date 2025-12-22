<?php
define('PAGE_TITLE', 'My Profile');
require_once 'includes/config.php';

if (!is_logged_in()) {
    redirect('login.php');
}

$userId = $_SESSION['UserID'];
$feedback = [];
$user = [];

// Fetch user data
try {
    $stmt = $pdo->prepare("SELECT FullName, Email, Phone, DateOfBirth, Height, Weight, ProfilePicture FROM users WHERE UserID = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch();
} catch (PDOException $e) {
    $feedback = ['type' => 'danger', 'message' => 'Could not fetch user data: ' . $e->getMessage()];
}

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $fullName = sanitize_input($_POST['fullName']);
    $phone = sanitize_input($_POST['phone']);
    $dateOfBirth = sanitize_input($_POST['dateOfBirth']);
    $height = intval($_POST['height']);
    $weight = intval($_POST['weight']);

    // Basic validation
    if (empty($fullName)) {
        $feedback = ['type' => 'danger', 'message' => 'Full Name is required.'];
    } elseif (!empty($dateOfBirth) && !strtotime($dateOfBirth)) {
        $feedback = ['type' => 'danger', 'message' => 'Invalid Date of Birth.'];
    } else {
        try {
            $stmt = $pdo->prepare("UPDATE users SET FullName = ?, Phone = ?, DateOfBirth = ?, Height = ?, Weight = ? WHERE UserID = ?");
            if ($stmt->execute([$fullName, $phone, $dateOfBirth, $height, $weight, $userId])) {
                $_SESSION['FullName'] = $fullName; // Update session with new name
                $feedback = ['type' => 'success', 'message' => 'Profile updated successfully!'];
                // Re-fetch user data to display updated info
                $stmt = $pdo->prepare("SELECT FullName, Email, Phone, DateOfBirth, Height, Weight, ProfilePicture FROM users WHERE UserID = ?");
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

// Handle password update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $currentPassword = $_POST['currentPassword'];
    $newPassword = $_POST['newPassword'];
    $confirmNewPassword = $_POST['confirmNewPassword'];

    try {
        // Fetch current hashed password
        $stmt = $pdo->prepare("SELECT Password FROM users WHERE UserID = ?");
        $stmt->execute([$userId]);
        $dbPassword = $stmt->fetchColumn();

        if (!password_verify($currentPassword, $dbPassword)) {
            $feedback = ['type' => 'danger', 'message' => 'Current password is incorrect.'];
        } elseif (strlen($newPassword) < 8) {
            $feedback = ['type' => 'danger', 'message' => 'New password must be at least 8 characters long.'];
        } elseif ($newPassword !== $confirmNewPassword) {
            $feedback = ['type' => 'danger', 'message' => 'New passwords do not match.'];
        } else {
            $hashedNewPassword = password_hash($newPassword, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE users SET Password = ? WHERE UserID = ?");
            if ($stmt->execute([$hashedNewPassword, $userId])) {
                $feedback = ['type' => 'success', 'message' => 'Password updated successfully!'];
            } else {
                $feedback = ['type' => 'danger', 'message' => 'Failed to update password.'];
            }
        }
    } catch (PDOException $e) {
        $feedback = ['type' => 'danger', 'message' => 'Database error: ' . $e->getMessage()];
    }
}

include 'includes/header.php'; // Use the main site header
?>

<div class="d-flex justify-content-between align-items-center pb-3 mb-4 border-bottom">
    <h1 class="h2">My Profile</h1>
    <p class="lead text-body-secondary m-0">View and update your personal information.</p>
</div>

<?php if (!empty($feedback)): ?>
    <div class="alert alert-<?php echo $feedback['type']; ?> alert-dismissible fade show" role="alert">
        <?php echo $feedback['message']; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<div class="row g-4">
    <!-- Profile Details Card -->
    <div class="col-lg-6">
        <div class="card text-bg-dark mb-4">
            <div class="card-header fw-bold">Personal Details</div>
            <div class="card-body">
                <form action="profile.php" method="POST">
                    <div class="mb-3">
                        <label for="fullName" class="form-label">Full Name</label>
                        <input type="text" class="form-control" id="fullName" name="fullName" value="<?php echo htmlspecialchars($user['FullName'] ?? ''); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="email" class="form-label">Email Address</label>
                        <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($user['Email'] ?? ''); ?>" disabled>
                    </div>
                    <div class="mb-3">
                        <label for="phone" class="form-label">Phone</label>
                        <input type="tel" class="form-control" id="phone" name="phone" value="<?php echo htmlspecialchars($user['Phone'] ?? ''); ?>">
                    </div>
                    <div class="mb-3">
                        <label for="dateOfBirth" class="form-label">Date of Birth</label>
                        <input type="date" class="form-control" id="dateOfBirth" name="dateOfBirth" value="<?php echo htmlspecialchars($user['DateOfBirth'] ?? ''); ?>">
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="height" class="form-label">Height (cm)</label>
                            <input type="number" class="form-control" id="height" name="height" value="<?php echo htmlspecialchars($user['Height'] ?? ''); ?>">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="weight" class="form-label">Weight (kg)</label>
                            <input type="number" class="form-control" id="weight" name="weight" value="<?php echo htmlspecialchars($user['Weight'] ?? ''); ?>">
                        </div>
                    </div>
                    <div class="d-grid">
                        <button type="submit" name="update_profile" class="btn btn-primary">Update Profile</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Change Password Card -->
    <div class="col-lg-6">
        <div class="card text-bg-dark mb-4">
            <div class="card-header fw-bold">Change Password</div>
            <div class="card-body">
                <form action="profile.php" method="POST">
                    <div class="mb-3">
                        <label for="currentPassword" class="form-label">Current Password</label>
                        <input type="password" class="form-control" id="currentPassword" name="currentPassword" required>
                    </div>
                    <div class="mb-3">
                        <label for="newPassword" class="form-label">New Password</label>
                        <input type="password" class="form-control" id="newPassword" name="newPassword" required>
                    </div>
                    <div class="mb-3">
                        <label for="confirmNewPassword" class="form-label">Confirm New Password</label>
                        <input type="password" class="form-control" id="confirmNewPassword" name="confirmNewPassword" required>
                    </div>
                    <div class="d-grid">
                        <button type="submit" name="change_password" class="btn btn-warning">Change Password</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
