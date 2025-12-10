<?php
// This file assumes `config.php` is included from the parent file.
if (session_status() == PHP_SESSION_NONE) { session_start(); }
$current_page = basename($_SERVER['PHP_SELF']);
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

    <!-- Custom Styles -->
    <style>
        :root {
            --bs-primary: #ff6b00;
            --bs-primary-rgb: 255, 107, 0;
            --bs-body-bg: #1a1a1a;
            --bs-tertiary-bg: #2d2d2d;
        }
        body {
            font-family: 'Inter', sans-serif;
            background-color: var(--bs-tertiary-bg);
        }
        .sidebar .nav-link {
            color: #ccc;
        }
        .sidebar .nav-link:hover {
            color: #fff;
            background-color: rgba(255, 107, 0, 0.1);
        }
        .sidebar .nav-link.active {
            color: var(--bs-primary);
            font-weight: 600;
            border-left: 3px solid var(--bs-primary);
        }
        .top-navbar {
            background: rgba(26, 26, 26, 0.9);
            backdrop-filter: blur(10px);
        }
    </style>
</head>
<body>

<!-- Offcanvas Sidebar -->
<div class="offcanvas offcanvas-start text-bg-dark" tabindex="-1" id="sidebar" aria-labelledby="sidebarLabel">
    <div class="offcanvas-header border-bottom border-secondary">
        <h5 class="offcanvas-title text-primary fw-bold" id="sidebarLabel"><i class="fas fa-user-shield"></i> Trainer Panel</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
    <div class="offcanvas-body d-flex flex-column p-0">
        <ul class="nav nav-pills flex-column mb-auto">
            <li class="nav-item">
                <a href="dashboard.php" class="nav-link px-3 <?php echo ($current_page == 'dashboard.php') ? 'active' : ''; ?>">
                    <i class="fas fa-tachometer-alt fa-fw me-2"></i> Dashboard
                </a>
            </li>
            <li>
                <a href="schedule.php" class="nav-link px-3 <?php echo ($current_page == 'schedule.php') ? 'active' : ''; ?>">
                    <i class="fas fa-calendar-alt fa-fw me-2"></i> My Schedule
                </a>
            </li>
            <li>
                <a href="attendance.php" class="nav-link px-3 <?php echo ($current_page == 'attendance.php') ? 'active' : ''; ?>">
                    <i class="fas fa-clipboard-check fa-fw me-2"></i> Mark Attendance
                </a>
            </li>
            <li>
                <a href="historical_attendance.php" class="nav-link px-3 <?php echo ($current_page == 'historical_attendance.php') ? 'active' : ''; ?>">
                    <i class="fas fa-history fa-fw me-2"></i> Historical Attendance
                </a>
            </li>
        </ul>
        <div class="border-top border-secondary p-3">
             <a href="../index.php" class="nav-link px-3">
                <i class="fas fa-home fa-fw me-2"></i> View Main Site
            </a>
            <a href="../logout.php" class="nav-link px-3">
                <i class="fas fa-sign-out-alt fa-fw me-2"></i> Logout
            </a>
        </div>
    </div>
</div>

<!-- Main content wrapper -->
<div class="w-100">
    <!-- Top Navbar -->
    <nav class="navbar navbar-dark top-navbar sticky-top">
        <div class="container-fluid">
            <button class="btn btn-primary" type="button" data-bs-toggle="offcanvas" data-bs-target="#sidebar" aria-controls="sidebar">
                <i class="fas fa-bars"></i>
            </button>
            <span class="navbar-brand mb-0 h1 d-none d-sm-block">
                <?php echo defined('PAGE_TITLE') ? PAGE_TITLE : 'Trainer'; ?>
            </span>
            <div class="d-flex align-items-center">
                 <a href="../notifications.php" class="btn btn-dark position-relative me-2" title="Notifications">
                    <i class="fas fa-bell"></i>
                 </a>
                 <div class="vr"></div>
                 <span class="navbar-text ms-2">
                    Welcome, <?php echo htmlspecialchars($_SESSION['FullName']); ?>
                 </span>
            </div>
        </div>
    </nav>
    
    <!-- Page Content -->
    <main class="p-4">
        <!-- Page content will be injected here -->