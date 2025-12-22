<?php
define('PAGE_TITLE', 'Reset Password');
require_once 'includes/config.php';

$token = $_GET['token'] ?? '';
$feedback = [];
$validToken = false;

// Validate Token
if ($token) {
    $stmt = $pdo->prepare("SELECT UserID FROM users WHERE ResetToken = ? AND ResetTokenExpiry > NOW()");
    $stmt->execute([$token]);
    $user = $stmt->fetch();
    if ($user) {
        $validToken = true;
    } else {
        $feedback = ['type' => 'danger', 'message' => 'Invalid or expired reset link.'];
    }
} else {
    $feedback = ['type' => 'danger', 'message' => 'No token provided.'];
}

// Handle Password Reset
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $validToken) {
    validate_csrf_token($_POST['csrf_token']);
    $password = $_POST['password'];
    $confirmPassword = $_POST['confirmPassword'];

    if (strlen($password) < 6) {
        $feedback = ['type' => 'danger', 'message' => 'Password must be at least 6 characters.'];
    } elseif ($password !== $confirmPassword) {
        $feedback = ['type' => 'danger', 'message' => 'Passwords do not match.'];
    } else {
        $hashed = password_hash($password, PASSWORD_DEFAULT);
        try {
            $stmt = $pdo->prepare("UPDATE users SET Password = ?, ResetToken = NULL, ResetTokenExpiry = NULL WHERE UserID = ?");
            $stmt->execute([$hashed, $user['UserID']]);
            $feedback = ['type' => 'success', 'message' => 'Password reset successfully! You can now <a href="login.php">Login</a>.'];
            $validToken = false; // Hide form
        } catch (PDOException $e) {
            $feedback = ['type' => 'danger', 'message' => 'Database error.'];
        }
    }
}

include 'includes/header.php';
?>

<div class="row justify-content-center mt-5">
    <div class="col-md-6 col-lg-4">
        <div class="card shadow-lg">
            <div class="card-body p-5">
                <h2 class="text-center mb-4 text-primary">Reset Password</h2>
                
                <?php if (!empty($feedback)): ?>
                    <div class="alert alert-<?php echo $feedback['type']; ?>">
                        <?php echo $feedback['message']; ?>
                    </div>
                <?php endif; ?>

                <?php if ($validToken): ?>
                    <form action="reset_password.php?token=<?php echo htmlspecialchars($token); ?>" method="POST">
                        <input type="hidden" name="csrf_token" value="<?php echo get_csrf_token(); ?>">
                        <div class="mb-3">
                            <label for="password" class="form-label">New Password</label>
                            <input type="password" class="form-control" id="password" name="password" required>
                        </div>
                        <div class="mb-3">
                            <label for="confirmPassword" class="form-label">Confirm Password</label>
                            <input type="password" class="form-control" id="confirmPassword" name="confirmPassword" required>
                        </div>
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary">Reset Password</button>
                        </div>
                    </form>
                <?php elseif (empty($feedback) || $feedback['type'] === 'danger'): ?>
                    <div class="text-center">
                        <a href="forgot_password.php" class="btn btn-secondary">Request New Link</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
