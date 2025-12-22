<?php
define('PAGE_TITLE', 'Forgot Password');
require_once 'includes/config.php';

$feedback = [];
$resetLink = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validate_csrf_token($_POST['csrf_token']);
    $email = sanitize_input($_POST['email']);

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $feedback = ['type' => 'danger', 'message' => 'Invalid email format.'];
    } else {
        try {
            $stmt = $pdo->prepare("SELECT UserID FROM users WHERE Email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if ($user) {
                // Generate Token
                $token = bin2hex(random_bytes(32));
                // Use MySQL time for consistency
                $update = $pdo->prepare("UPDATE users SET ResetToken = ?, ResetTokenExpiry = DATE_ADD(NOW(), INTERVAL 1 HOUR) WHERE UserID = ?");
                $update->execute([$token, $user['UserID']]);

                // SIMULATION: In a real app, send this via email.
                // For this demo, we display the link.
                $resetLink = SITE_URL . "/reset_password.php?token=" . $token;
                
                $feedback = ['type' => 'success', 'message' => 'Password reset link generated (Simulation).'];
            } else {
                // Security: Don't reveal if user exists
                $feedback = ['type' => 'info', 'message' => 'If an account exists for this email, you will receive a reset link.'];
            }
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
                <h2 class="text-center mb-4 text-primary">Forgot Password</h2>
                
                <?php if (!empty($feedback)): ?>
                    <div class="alert alert-<?php echo $feedback['type']; ?>">
                        <?php echo $feedback['message']; ?>
                    </div>
                <?php endif; ?>

                <?php if ($resetLink): ?>
                    <div class="alert alert-warning">
                        <strong>DEMO MODE:</strong><br>
                        Click here to reset: <a href="<?php echo $resetLink; ?>">Reset Password</a>
                    </div>
                <?php endif; ?>

                <form action="forgot_password.php" method="POST">
                    <input type="hidden" name="csrf_token" value="<?php echo get_csrf_token(); ?>">
                    <div class="mb-3">
                        <label for="email" class="form-label">Email Address</label>
                        <input type="email" class="form-control" id="email" name="email" required placeholder="Enter your email">
                    </div>
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary">Send Reset Link</button>
                        <a href="login.php" class="btn btn-outline-secondary">Back to Login</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
