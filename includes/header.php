<?php
require_once 'config.php';

// Get unread notifications count if logged in
$unreadCount = 0;
if (is_logged_in()) {
    $unreadCount = get_unread_notifications_count($_SESSION['UserID']);
}

// Determine current page for active navigation
$currentPage = basename($_SERVER['PHP_SELF']);
$currentDir = basename(dirname($_SERVER['PHP_SELF']));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo SITE_NAME; ?></title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #1a1a1a 0%, #2d2d2d 100%);
            color: #ffffff;
            line-height: 1.6;
            padding-top: 80px; /* Account for fixed navbar */
        }

        /* Navigation */
        .navbar {
            position: fixed;
            top: 0;
            width: 100%;
            background: rgba(26, 26, 26, 0.95);
            backdrop-filter: blur(10px);
            z-index: 1000;
            padding: 1rem 0;
            border-bottom: 1px solid rgba(255, 107, 0, 0.2);
        }

        .nav-container {
            max-width: 1200px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0 2rem;
        }

        .logo {
            font-size: 1.8rem;
            font-weight: 800;
            color: #ff6b00;
            text-decoration: none;
        }

        .nav-links {
            display: flex;
            list-style: none;
            gap: 2rem;
            align-items: center;
        }

        .nav-links a {
            color: #ffffff;
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s ease;
            position: relative;
        }

        .nav-links a:hover, .nav-links a.active {
            color: #ff6b00;
        }

        .nav-links a::after {
            content: '';
            position: absolute;
            bottom: -5px;
            left: 0;
            width: 0;
            height: 2px;
            background: #ff6b00;
            transition: width 0.3s ease;
        }

        .nav-links a:hover::after, .nav-links a.active::after {
            width: 100%;
        }

        .auth-buttons {
            display: flex;
            gap: 1rem;
            align-items: center;
        }

        .btn {
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            text-decoration: none;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.9rem;
        }

        .btn-primary {
            background: linear-gradient(135deg, #ff6b00, #ff8533);
            color: #ffffff;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(255, 107, 0, 0.3);
        }

        .btn-secondary {
            background: transparent;
            color: #ffffff;
            border: 2px solid #ff6b00;
        }

        .btn-secondary:hover {
            background: #ff6b00;
            color: #ffffff;
        }

        .btn-small {
            padding: 0.5rem 1rem;
            font-size: 0.8rem;
        }

        .notification-btn {
            position: relative;
            background: rgba(45, 45, 45, 0.8);
            border: 1px solid rgba(255, 107, 0, 0.2);
            border-radius: 8px;
            padding: 0.75rem;
            color: #ffffff;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
        }

        .notification-btn:hover {
            background: rgba(255, 107, 0, 0.1);
            color: #ff6b00;
        }

        .notification-badge {
            position: absolute;
            top: -5px;
            right: -5px;
            background: #ff6b00;
            color: #ffffff;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            font-size: 0.75rem;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            background: rgba(45, 45, 45, 0.8);
            padding: 0.5rem 1rem;
            border-radius: 8px;
            border: 1px solid rgba(255, 107, 0, 0.2);
        }

        .user-avatar {
            width: 35px;
            height: 35px;
            border-radius: 50%;
            background: linear-gradient(135deg, #ff6b00, #ff8533);
            display: flex;
            align-items: center;
            justify-content: center;
            color: #ffffff;
            font-weight: 600;
            font-size: 0.9rem;
        }

        .user-details {
            display: flex;
            flex-direction: column;
        }

        .user-name {
            font-weight: 600;
            font-size: 0.9rem;
        }

        .user-role {
            font-size: 0.7rem;
            color: #888;
        }

        /* Mobile Menu */
        .mobile-menu-toggle {
            display: none;
            background: none;
            border: none;
            color: #ffffff;
            font-size: 1.5rem;
            cursor: pointer;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .nav-links {
                display: none;
                position: absolute;
                top: 100%;
                left: 0;
                width: 100%;
                background: rgba(26, 26, 26, 0.98);
                flex-direction: column;
                padding: 1rem 0;
                border-top: 1px solid rgba(255, 107, 0, 0.2);
            }

            .nav-links.active {
                display: flex;
            }

            .nav-links a {
                padding: 1rem 2rem;
                width: 100%;
                text-align: center;
            }

            .mobile-menu-toggle {
                display: block;
            }

            .auth-buttons {
                gap: 0.5rem;
            }

            .btn {
                padding: 0.5rem 1rem;
                font-size: 0.8rem;
            }

            .user-info {
                padding: 0.5rem;
            }

            .user-avatar {
                width: 30px;
                height: 30px;
                font-size: 0.8rem;
            }

            .user-name {
                font-size: 0.8rem;
            }
        }

        /* Page Content */
        .page-content {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem;
            min-height: calc(100vh - 80px);
        }

        /* Loading Animation */
        .loading {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 3px solid rgba(255, 255, 255, 0.3);
            border-radius: 50%;
            border-top-color: #ffffff;
            animation: spin 1s ease-in-out infinite;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar">
        <div class="nav-container">
            <a href="index.php" class="logo">
                <i class="fas fa-dumbbell"></i> KP FITNESS
            </a>
            
            <ul class="nav-links" id="navLinks">
                <li><a href="index.php" class="<?php echo $currentPage == 'index.php' ? 'active' : ''; ?>">Home</a></li>
                <li><a href="about.php" class="<?php echo $currentPage == 'about.php' ? 'active' : ''; ?>">About</a></li>
                
                <?php if (is_logged_in()): ?>
                    <li><a href="dashboard.php" class="<?php echo $currentPage == 'dashboard.php' ? 'active' : ''; ?>">Dashboard</a></li>
                    
                    <?php if (is_client()): ?>
                        <li><a href="client/booking.php" class="<?php echo $currentDir == 'client' && $currentPage == 'booking.php' ? 'active' : ''; ?>">Book Classes</a></li>
                        <li><a href="client/membership.php" class="<?php echo $currentDir == 'client' && $currentPage == 'membership.php' ? 'active' : ''; ?>">Membership</a></li>
                        <li><a href="client/workout_planner.php" class="<?php echo $currentDir == 'client' && $currentPage == 'workout_planner.php' ? 'active' : ''; ?>">AI Workout</a></li>
                    <?php elseif (is_trainer()): ?>
                        <li><a href="trainer/schedule.php" class="<?php echo $currentDir == 'trainer' && $currentPage == 'schedule.php' ? 'active' : ''; ?>">My Schedule</a></li>
                        <li><a href="trainer/attendance.php" class="<?php echo $currentDir == 'trainer' && $currentPage == 'attendance.php' ? 'active' : ''; ?>">Attendance</a></li>
                        <li><a href="trainer/classes.php" class="<?php echo $currentDir == 'trainer' && $currentPage == 'classes.php' ? 'active' : ''; ?>">My Classes</a></li>
                    <?php elseif (is_admin()): ?>
                        <li><a href="admin/users.php" class="<?php echo $currentDir == 'admin' && $currentPage == 'users.php' ? 'active' : ''; ?>">Users</a></li>
                        <li><a href="admin/classes.php" class="<?php echo $currentDir == 'admin' && $currentPage == 'classes.php' ? 'active' : ''; ?>">Classes</a></li>
                        <li><a href="admin/reports.php" class="<?php echo $currentDir == 'admin' && $currentPage == 'reports.php' ? 'active' : ''; ?>">Reports</a></li>
                    <?php endif; ?>
                <?php else: ?>
                    <li><a href="login.php" class="<?php echo $currentPage == 'login.php' ? 'active' : ''; ?>">Classes</a></li>
                    <li><a href="about.php#pricing" class="">Pricing</a></li>
                    <li><a href="about.php#contact" class="">Contact</a></li>
                <?php endif; ?>
            </ul>
            
            <div class="auth-buttons">
                <?php if (is_logged_in()): ?>
                    <a href="dashboard.php" class="notification-btn" title="Notifications">
                        <i class="fas fa-bell"></i>
                        <?php if ($unreadCount > 0): ?>
                            <span class="notification-badge"><?php echo $unreadCount; ?></span>
                        <?php endif; ?>
                    </a>
                    
                    <div class="user-info">
                        <div class="user-avatar">
                            <?php echo substr($_SESSION['FullName'], 0, 1); ?>
                        </div>
                        <div class="user-details">
                            <div class="user-name"><?php echo htmlspecialchars(explode(' ', $_SESSION['FullName'])[0]); ?></div>
                            <div class="user-role"><?php echo ucfirst($_SESSION['Role']); ?></div>
                        </div>
                    </div>
                    
                    <a href="logout.php" class="btn btn-secondary btn-small" title="Logout">
                        <i class="fas fa-sign-out-alt"></i>
                    </a>
                <?php else: ?>
                    <a href="login.php" class="btn btn-secondary">
                        <i class="fas fa-sign-in-alt"></i>
                        Login
                    </a>
                    <a href="register.php" class="btn btn-primary">
                        <i class="fas fa-user-plus"></i>
                        Sign Up
                    </a>
                <?php endif; ?>
            </div>
            
            <button class="mobile-menu-toggle" id="mobileMenuToggle">
                <i class="fas fa-bars"></i>
            </button>
        </div>
    </nav>

    <script>
        // Mobile menu toggle
        document.getElementById('mobileMenuToggle').addEventListener('click', function() {
            document.getElementById('navLinks').classList.toggle('active');
        });

        // Close mobile menu when clicking outside
        document.addEventListener('click', function(event) {
            const nav = document.querySelector('.navbar');
            const navLinks = document.getElementById('navLinks');
            if (!nav.contains(event.target)) {
                navLinks.classList.remove('active');
            }
        });

        // Auto-hide alerts after 5 seconds
        setTimeout(function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                alert.style.transition = 'opacity 0.5s ease';
                alert.style.opacity = '0';
                setTimeout(() => alert.remove(), 500);
            });
        }, 5000);
    </script>
</body>
</html>