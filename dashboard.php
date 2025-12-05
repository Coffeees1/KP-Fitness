<?php
require_once 'includes/config.php';

// Redirect to login if not authenticated
if (!is_logged_in()) {
    redirect('login.php');
}

// Redirect based on user role
switch (get_user_role()) {
    case 'admin':
        redirect('admin/dashboard.php');
        break;
    case 'trainer':
        redirect('trainer/dashboard.php');
        break;
    case 'client':
        redirect('client/dashboard.php');
        break;
    default:
        // Logout if invalid role
        session_destroy();
        redirect('login.php');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - <?php echo SITE_NAME; ?></title>
    <meta http-equiv="refresh" content="0; url=login.php">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #1a1a1a 0%, #2d2d2d 100%);
            color: #ffffff;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            margin: 0;
        }
        .loading {
            text-align: center;
        }
        .spinner {
            border: 4px solid rgba(255, 107, 0, 0.3);
            border-radius: 50%;
            border-top: 4px solid #ff6b00;
            width: 50px;
            height: 50px;
            animation: spin 1s linear infinite;
            margin: 0 auto 1rem;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <div class="loading">
        <div class="spinner"></div>
        <h2>Redirecting...</h2>
        <p>If you are not redirected automatically, <a href="login.php" style="color: #ff6b00;">click here</a>.</p>
    </div>
</body>
</html>