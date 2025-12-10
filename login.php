<?php
define('PAGE_TITLE', 'Login');
require_once 'includes/config.php';

$email = '';
$errors = [];

// If user is already logged in, redirect to their dashboard
if (is_logged_in()) {
    redirect('dashboard.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitize_input($_POST['email']);
    $password = $_POST['password'];

    if (empty($email)) { $errors[] = 'Email is required.'; }
    if (empty($password)) { $errors[] = 'Password is required.'; }

    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("SELECT UserID, FullName, Email, Password, Role, IsActive FROM users WHERE Email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['Password'])) {
                if ($user['IsActive'] == 0) {
                    $errors[] = 'Your account has been deactivated. Please contact support.';
                } else {
                    session_regenerate_id(true);
                    $_SESSION['UserID'] = $user['UserID'];
                    $_SESSION['FullName'] = $user['FullName'];
                    $_SESSION['Role'] = $user['Role'];
                    redirect('dashboard.php');
                }
            } else {
                $errors[] = 'Invalid email or password.';
            }
        } catch (PDOException $e) {
            $errors[] = "Database error. Please try again later.";
        }
    }
}

// We are not including the main header.php because we need to add Bootstrap specifically for this page.
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo PAGE_TITLE . ' - ' . SITE_NAME; ?></title>
    
    <!-- Bootstrap 5.3 CDN -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">

    <!-- Custom Styles to match the dark theme -->
    <style>
        :root {
            --primary-color: #ff6b00;
            --dark-bg: #1a1a1a;
            --light-bg: #2d2d2d;
        }
        body {
            background-color: var(--dark-bg);
            color: #fff;
        }
        .form-container {
            background-color: var(--light-bg);
            border: 1px solid var(--primary-color);
            border-radius: 1rem;
        }
        .form-control {
            background-color: var(--dark-bg);
            border-color: #444;
            color: #fff;
        }
        .form-control:focus {
            background-color: var(--dark-bg);
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.25rem rgba(255, 107, 0, 0.25);
            color: #fff;
        }
        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }
        .btn-primary:hover {
            background-color: #ff8533;
            border-color: #ff8533;
        }
        .form-footer a {
            color: var(--primary-color);
            text-decoration: none;
        }
        .form-footer a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>

<div class="container">
    <div class="row justify-content-center align-items-center vh-100">
        <div class="col-md-6 col-lg-5">
            <div class="form-container p-4 p-sm-5">
                <h1 class="text-center mb-4" style="color: var(--primary-color);">
                    <i class="fas fa-dumbbell"></i> KP Fitness Login
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

                <form action="login.php" method="POST">
                    <div class="mb-3">
                        <label for="email" class="form-label">Email Address</label>
                        <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($email); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label">Password</label>
                        <input type="password" class="form-control" id="password" name="password" required>
                    </div>
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary btn-lg">Login</button>
                    </div>
                </form>
                
                <div class="form-footer text-center mt-4">
                    <p>Don't have an account? <a href="register.php">Register here</a></p>
                    <p><a href="index.php">Back to Home</a></p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Bootstrap 5.3 JS Bundle -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>