<?php
define('PAGE_TITLE', 'Register');
require_once 'includes/config.php';

$fullName = $email = '';
$errors = [];

if (is_logged_in()) {
    redirect('dashboard.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullName = sanitize_input($_POST['fullName'] ?? '');
    $email = sanitize_input($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirmPassword'] ?? '';
    $terms = isset($_POST['terms']);

    if (empty($fullName)) { $errors[] = 'Full Name is required.'; }
    if (empty($email)) { $errors[] = 'Email is required.'; }
    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) { $errors[] = 'Invalid email format.'; }
    if (empty($password)) { $errors[] = 'Password is required.'; }
    elseif (strlen($password) < 8) { $errors[] = 'Password must be at least 8 characters long.'; }
    if ($password !== $confirmPassword) { $errors[] = 'Passwords do not match.'; }
    if (!$terms) { $errors[] = 'You must agree to the Terms and Conditions.'; }

    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("SELECT UserID FROM users WHERE Email = ?");
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                $errors[] = 'An account with this email already exists. Please <a href="login.php" class="alert-link">login</a>.';
            } else {
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("INSERT INTO users (FullName, Email, Password, Role) VALUES (?, ?, ?, 'client')");
                
                if ($stmt->execute([$fullName, $email, $hashedPassword])) {
                    $userId = $pdo->lastInsertId();
                    create_notification($userId, 'Welcome to KP Fitness!', 'Your account has been created successfully.', 'success');
                    
                    // Log the user in
                    $_SESSION['UserID'] = $userId;
                    $_SESSION['FullName'] = $fullName;
                    $_SESSION['Role'] = 'client';
                    
                    // Redirect to dashboard (or a new profile setup page in the future)
                    redirect('dashboard.php');
                } else {
                    $errors[] = 'Failed to create account.';
                }
            }
        } catch (PDOException $e) {
            $errors[] = 'An error occurred. Please try again.';
        }
    }
}

// Don't include the main header, as this is a full-page layout
?>
<!DOCTYPE html>
<html lang="en" data-bs-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo PAGE_TITLE . ' - ' . SITE_NAME; ?></title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        :root { --bs-primary: #ff6b00; }
        body { background-color: #1a1a1a; }
        .form-container { background-color: #2d2d2d; border: 1px solid var(--bs-primary); border-radius: 1rem; }
        .form-control { background-color: #1a1a1a; border-color: #444; color: #fff; }
        .form-control:focus { background-color: #1a1a1a; border-color: var(--bs-primary); box-shadow: 0 0 0 0.25rem rgba(255, 107, 0, 0.25); color: #fff; }
        .btn-primary { --bs-btn-bg: var(--bs-primary); --bs-btn-border-color: var(--bs-primary); }
        .form-check-input:checked { background-color: var(--bs-primary); border-color: var(--bs-primary); }
    </style>
</head>
<body>

<div class="container">
    <div class="row justify-content-center align-items-center min-vh-100">
        <div class="col-md-8 col-lg-6">
            <div class="form-container p-4 p-sm-5">
                <h1 class="text-center mb-4 text-primary">
                    <i class="fas fa-user-plus"></i> Create Your Account
                </h1>

                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            <?php foreach ($errors as $error): ?>
                                <li><?php echo $error; ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <form action="register.php" method="POST">
                    <div class="mb-3">
                        <label for="fullName" class="form-label">Full Name</label>
                        <input type="text" class="form-control" id="fullName" name="fullName" value="<?php echo htmlspecialchars($fullName); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="email" class="form-label">Email Address</label>
                        <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($email); ?>" required>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="password" class="form-label">Password</label>
                            <input type="password" class="form-control" id="password" name="password" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="confirmPassword" class="form-label">Confirm Password</label>
                            <input type="password" class="form-control" id="confirmPassword" name="confirmPassword" required>
                        </div>
                    </div>
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" value="" id="terms" name="terms" required>
                        <label class="form-check-label" for="terms">
                            I agree to the <a href="#" class="text-primary">Terms and Conditions</a> and <a href="#" class="text-primary">Privacy Policy</a>.
                        </label>
                    </div>
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary btn-lg">Register</button>
                    </div>
                </form>
                
                <div class="text-center mt-4">
                    <p>Already have an account? <a href="login.php" class="text-primary">Login here</a></p>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>