<?php
// This file assumes `config.php` is included before it.
if (session_status() == PHP_SESSION_NONE) { session_start(); }

$unreadCount = 0;
if (is_logged_in()) {
    $unreadCount = get_unread_notifications_count($_SESSION['UserID']);
}
$currentPage = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en" data-bs-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo defined('PAGE_TITLE') ? PAGE_TITLE . ' - ' . SITE_NAME : SITE_NAME; ?></title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- External Libraries -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">

    <!-- Custom Bootstrap Overrides & Site Styles -->
    <style>
        :root {
            --bs-primary: #ff6b00;
            --bs-primary-rgb: 255, 107, 0;
            --bs-body-bg: #1a1a1a;
            --bs-body-color: #ffffff;
            --bs-link-color: #ff8533;
            --bs-link-hover-color: #ffc499;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #1a1a1a 0%, #2d2d2d 100%);
        }
        
        .navbar {
             background: rgba(26, 26, 26, 0.9);
             backdrop-filter: blur(10px);
        }

        .navbar-brand {
            font-weight: 800;
            color: var(--bs-primary);
        }
        
        .btn-primary {
            --bs-btn-bg: var(--bs-primary);
            --bs-btn-border-color: var(--bs-primary);
            --bs-btn-hover-bg: #ff8533;
            --bs-btn-hover-border-color: #ff8533;
        }
        
        .btn-outline-primary {
            --bs-btn-color: var(--bs-primary);
            --bs-btn-border-color: var(--bs-primary);
            --bs-btn-hover-bg: var(--bs-primary);
            --bs-btn-hover-border-color: var(--bs-primary);
        }

        .user-avatar {
            width: 35px;
            height: 35px;
            border-radius: 50%;
            background-color: var(--bs-primary);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            color: white;
        }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg sticky-top border-bottom border-warning-subtle">
    <div class="container-fluid">
        <a class="navbar-brand fs-4" href="<?php echo SITE_URL; ?>/index.php">
            <i class="fas fa-dumbbell"></i> KP FITNESS
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNavbar" aria-controls="mainNavbar" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="mainNavbar">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                <li class="nav-item">
                    <a class="nav-link <?php echo ($currentPage == 'index.php') ? 'active' : ''; ?>" href="<?php echo SITE_URL; ?>/index.php">Home</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo ($currentPage == 'about.php') ? 'active' : ''; ?>" href="<?php echo SITE_URL; ?>/about.php">About</a>
                </li>
                <?php if (is_logged_in()): ?>
                    <li class="nav-item">
                        <a class="nav-link <?php echo ($currentPage == 'dashboard.php') ? 'active' : ''; ?>" href="<?php echo SITE_URL; ?>/dashboard.php">Dashboard</a>
                    </li>
                <?php else: ?>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo SITE_URL; ?>/login.php">Classes</a>
                    </li>
                <?php endif; ?>
            </ul>
            <div class="d-flex align-items-center">
                <?php if (is_logged_in()): ?>
                    <a href="<?php echo SITE_URL; ?>/notifications.php" class="btn btn-dark position-relative me-3" title="Notifications">
                        <i class="fas fa-bell"></i>
                        <?php if ($unreadCount > 0): ?>
                            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                                <?php echo $unreadCount; ?>
                                <span class="visually-hidden">unread messages</span>
                            </span>
                        <?php endif; ?>
                    </a>
                    
                    <div class="vr me-3"></div>

                    <div class="dropdown">
                        <a href="#" class="d-flex align-items-center text-white text-decoration-none dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                           <div class="user-avatar me-2"><?php echo substr($_SESSION['FullName'], 0, 1); ?></div>
                           <strong><?php echo htmlspecialchars(explode(' ', $_SESSION['FullName'])[0]); ?></strong>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-dark text-small shadow">
                            <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>/dashboard.php">My Dashboard</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>/logout.php">Sign out</a></li>
                        </ul>
                    </div>
                <?php else: ?>
                    <a href="<?php echo SITE_URL; ?>/login.php" class="btn btn-outline-primary me-2">Login</a>
                    <a href="<?php echo SITE_URL; ?>/register.php" class="btn btn-primary">Sign Up</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</nav>

<main class="container mt-4">
    <!-- Page content will be injected here -->