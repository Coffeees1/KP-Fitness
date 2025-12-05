<?php
require_once '../includes/config.php';
require_admin();

// Get dashboard statistics
try {
    // Total users
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE Role != 'admin'");
    $stmt->execute();
    $totalUsers = $stmt->fetchColumn();
    
    // Total trainers
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE Role = 'trainer'");
    $stmt->execute();
    $totalTrainers = $stmt->fetchColumn();
    
    // Total clients
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE Role = 'client'");
    $stmt->execute();
    $totalClients = $stmt->fetchColumn();
    
    // Total classes
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM classes WHERE IsActive = TRUE");
    $stmt->execute();
    $totalClasses = $stmt->fetchColumn();
    
    // Total sessions this month
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM sessions WHERE MONTH(SessionDate) = MONTH(CURRENT_DATE) AND YEAR(SessionDate) = YEAR(CURRENT_DATE)");
    $stmt->execute();
    $totalSessions = $stmt->fetchColumn();
    
    // Total revenue this month
    $stmt = $pdo->prepare("SELECT COALESCE(SUM(Amount), 0) FROM payments WHERE MONTH(PaymentDate) = MONTH(CURRENT_DATE) AND YEAR(PaymentDate) = YEAR(CURRENT_DATE) AND Status = 'completed'");
    $stmt->execute();
    $monthlyRevenue = $stmt->fetchColumn();
    
    // Recent bookings
    $stmt = $pdo->prepare("SELECT r.*, u.FullName, s.SessionDate, s.Time, c.ClassName 
                          FROM reservations r 
                          JOIN users u ON r.UserID = u.UserID 
                          JOIN sessions s ON r.SessionID = s.SessionID 
                          JOIN classes c ON s.ClassID = c.ClassID 
                          ORDER BY r.BookingDate DESC LIMIT 10");
    $stmt->execute();
    $recentBookings = $stmt->fetchAll();
    
    // Recent payments
    $stmt = $pdo->prepare("SELECT p.*, u.FullName, m.Type 
                          FROM payments p 
                          JOIN users u ON p.UserID = u.UserID 
                          JOIN membership m ON p.MembershipID = m.MembershipID 
                          ORDER BY p.PaymentDate DESC LIMIT 10");
    $stmt->execute();
    $recentPayments = $stmt->fetchAll();
    
} catch (PDOException $e) {
    $error = 'Error loading dashboard data: ' . $e->getMessage();
}

// Get unread notifications count
$unreadCount = get_unread_notifications_count($_SESSION['UserID']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - <?php echo SITE_NAME; ?></title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.css" rel="stylesheet">
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
            min-height: 100vh;
        }

        /* Sidebar */
        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            width: 280px;
            height: 100vh;
            background: rgba(26, 26, 26, 0.95);
            backdrop-filter: blur(10px);
            border-right: 1px solid rgba(255, 107, 0, 0.2);
            z-index: 1000;
            overflow-y: auto;
        }

        .sidebar-header {
            padding: 2rem 1.5rem;
            border-bottom: 1px solid rgba(255, 107, 0, 0.2);
        }

        .sidebar-header h1 {
            font-size: 1.5rem;
            font-weight: 800;
            color: #ff6b00;
        }

        .sidebar-header p {
            color: #cccccc;
            font-size: 0.9rem;
            margin-top: 0.5rem;
        }

        .sidebar-nav {
            padding: 1rem 0;
        }

        .nav-item {
            display: flex;
            align-items: center;
            padding: 1rem 1.5rem;
            color: #ffffff;
            text-decoration: none;
            transition: all 0.3s ease;
            border-left: 3px solid transparent;
        }

        .nav-item:hover, .nav-item.active {
            background: rgba(255, 107, 0, 0.1);
            border-left-color: #ff6b00;
            color: #ff6b00;
        }

        .nav-item i {
            width: 20px;
            margin-right: 1rem;
        }

        /* Main Content */
        .main-content {
            margin-left: 280px;
            padding: 2rem;
            min-height: 100vh;
        }

        .top-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid rgba(255, 107, 0, 0.2);
        }

        .page-title {
            font-size: 2rem;
            font-weight: 700;
            color: #ff6b00;
        }

        .user-menu {
            display: flex;
            align-items: center;
            gap: 1rem;
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
            padding: 0.75rem 1rem;
            border-radius: 8px;
            border: 1px solid rgba(255, 107, 0, 0.2);
        }

        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: linear-gradient(135deg, #ff6b00, #ff8533);
            display: flex;
            align-items: center;
            justify-content: center;
            color: #ffffff;
            font-weight: 600;
        }

        /* Stats Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: rgba(45, 45, 45, 0.8);
            border: 1px solid rgba(255, 107, 0, 0.2);
            border-radius: 12px;
            padding: 1.5rem;
            text-align: center;
            transition: transform 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-5px);
        }

        .stat-icon {
            font-size: 2.5rem;
            color: #ff6b00;
            margin-bottom: 1rem;
        }

        .stat-number {
            font-size: 2rem;
            font-weight: 800;
            color: #ffffff;
            display: block;
        }

        .stat-label {
            color: #cccccc;
            font-size: 0.9rem;
            margin-top: 0.5rem;
        }

        /* Content Grid */
        .content-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 2rem;
            margin-bottom: 2rem;
        }

        .content-card {
            background: rgba(45, 45, 45, 0.8);
            border: 1px solid rgba(255, 107, 0, 0.2);
            border-radius: 12px;
            padding: 1.5rem;
        }

        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid rgba(255, 107, 0, 0.2);
        }

        .card-title {
            font-size: 1.25rem;
            font-weight: 600;
            color: #ff6b00;
        }

        .btn {
            padding: 0.5rem 1rem;
            border: none;
            border-radius: 6px;
            font-weight: 600;
            text-decoration: none;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .btn-primary {
            background: linear-gradient(135deg, #ff6b00, #ff8533);
            color: #ffffff;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(255, 107, 0, 0.3);
        }

        /* Lists */
        .list-item {
            display: flex;
            align-items: center;
            padding: 1rem;
            border-bottom: 1px solid rgba(255, 107, 0, 0.1);
            transition: background 0.3s ease;
        }

        .list-item:hover {
            background: rgba(255, 107, 0, 0.05);
        }

        .list-item:last-child {
            border-bottom: none;
        }

        .list-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: rgba(255, 107, 0, 0.2);
            display: flex;
            align-items: center;
            justify-content: center;
            color: #ff6b00;
            margin-right: 1rem;
        }

        .list-content {
            flex: 1;
        }

        .list-title {
            font-weight: 600;
            color: #ffffff;
        }

        .list-subtitle {
            color: #cccccc;
            font-size: 0.9rem;
        }

        .list-time {
            color: #888888;
            font-size: 0.8rem;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .sidebar {
                width: 100%;
                height: auto;
                position: relative;
            }

            .main-content {
                margin-left: 0;
            }

            .content-grid {
                grid-template-columns: 1fr;
            }

            .stats-grid {
                grid-template-columns: 1fr;
            }
        }

        /* Loading animation */
        .loading {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 3px solid rgba(255, 107, 0, 0.3);
            border-radius: 50%;
            border-top-color: #ff6b00;
            animation: spin 1s ease-in-out infinite;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <nav class="sidebar">
        <div class="sidebar-header">
            <h1><i class="fas fa-dumbbell"></i> KP FITNESS</h1>
            <p>Admin Panel</p>
        </div>
        
        <div class="sidebar-nav">
            <a href="dashboard.php" class="nav-item active">
                <i class="fas fa-tachometer-alt"></i>
                Dashboard
            </a>
            <a href="users.php" class="nav-item">
                <i class="fas fa-users"></i>
                User Management
            </a>
            <a href="classes.php" class="nav-item">
                <i class="fas fa-dumbbell"></i>
                Class Management
            </a>
            <a href="reports.php" class="nav-item">
                <i class="fas fa-chart-bar"></i>
                Reports & Analytics
            </a>
            <a href="../dashboard.php" class="nav-item">
                <i class="fas fa-home"></i>
                Main Site
            </a>
            <a href="../logout.php" class="nav-item">
                <i class="fas fa-sign-out-alt"></i>
                Logout
            </a>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="main-content">
        <!-- Top Bar -->
        <div class="top-bar">
            <h1 class="page-title">Admin Dashboard</h1>
            <div class="user-menu">
                <div class="notification-btn" onclick="showNotifications()">
                    <i class="fas fa-bell"></i>
                    <?php if ($unreadCount > 0): ?>
                        <span class="notification-badge"><?php echo $unreadCount; ?></span>
                    <?php endif; ?>
                </div>
                <div class="user-info">
                    <div class="user-avatar">
                        <?php echo substr($_SESSION['FullName'], 0, 1); ?>
                    </div>
                    <div>
                        <div style="font-weight: 600;"><?php echo htmlspecialchars($_SESSION['FullName']); ?></div>
                        <div style="font-size: 0.8rem; color: #888;">Administrator</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Stats Grid -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-users"></i></div>
                <span class="stat-number"><?php echo number_format($totalUsers); ?></span>
                <div class="stat-label">Total Users</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-user-tie"></i></div>
                <span class="stat-number"><?php echo number_format($totalTrainers); ?></span>
                <div class="stat-label">Trainers</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-user-friends"></i></div>
                <span class="stat-number"><?php echo number_format($totalClients); ?></span>
                <div class="stat-label">Clients</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-dumbbell"></i></div>
                <span class="stat-number"><?php echo number_format($totalClasses); ?></span>
                <div class="stat-label">Classes</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-calendar-alt"></i></div>
                <span class="stat-number"><?php echo number_format($totalSessions); ?></span>
                <div class="stat-label">This Month Sessions</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-money-bill-wave"></i></div>
                <span class="stat-number">RM <?php echo number_format($monthlyRevenue, 2); ?></span>
                <div class="stat-label">Monthly Revenue</div>
            </div>
        </div>

        <!-- Content Grid -->
        <div class="content-grid">
            <!-- Recent Bookings -->
            <div class="content-card">
                <div class="card-header">
                    <h3 class="card-title">Recent Bookings</h3>
                    <a href="reports.php" class="btn btn-primary">
                        <i class="fas fa-eye"></i>
                        View All
                    </a>
                </div>
                <div style="max-height: 400px; overflow-y: auto;">
                    <?php if (count($recentBookings) > 0): ?>
                        <?php foreach ($recentBookings as $booking): ?>
                            <div class="list-item">
                                <div class="list-icon">
                                    <i class="fas fa-calendar-check"></i>
                                </div>
                                <div class="list-content">
                                    <div class="list-title"><?php echo htmlspecialchars($booking['FullName']); ?></div>
                                    <div class="list-subtitle"><?php echo htmlspecialchars($booking['ClassName']); ?> - <?php echo format_date($booking['SessionDate']); ?> at <?php echo format_time($booking['Time']); ?></div>
                                </div>
                                <div class="list-time">
                                    <?php echo date('M d, H:i', strtotime($booking['BookingDate'])); ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div style="text-align: center; padding: 2rem; color: #888;">
                            <i class="fas fa-calendar-times" style="font-size: 2rem; margin-bottom: 1rem;"></i>
                            <p>No recent bookings</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Recent Payments -->
            <div class="content-card">
                <div class="card-header">
                    <h3 class="card-title">Recent Payments</h3>
                    <a href="reports.php" class="btn btn-primary">
                        <i class="fas fa-eye"></i>
                        View All
                    </a>
                </div>
                <div style="max-height: 400px; overflow-y: auto;">
                    <?php if (count($recentPayments) > 0): ?>
                        <?php foreach ($recentPayments as $payment): ?>
                            <div class="list-item">
                                <div class="list-icon">
                                    <i class="fas fa-credit-card"></i>
                                </div>
                                <div class="list-content">
                                    <div class="list-title"><?php echo htmlspecialchars($payment['FullName']); ?></div>
                                    <div class="list-subtitle"><?php echo ucfirst($payment['Type']); ?> Membership - <?php echo format_currency($payment['Amount']); ?></div>
                                </div>
                                <div class="list-time">
                                    <?php echo date('M d, H:i', strtotime($payment['PaymentDate'])); ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div style="text-align: center; padding: 2rem; color: #888;">
                            <i class="fas fa-credit-card" style="font-size: 2rem; margin-bottom: 1rem;"></i>
                            <p>No recent payments</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="content-card">
            <div class="card-header">
                <h3 class="card-title">Quick Actions</h3>
            </div>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;">
                <a href="users.php?action=add" class="btn btn-primary">
                    <i class="fas fa-user-plus"></i>
                    Add New Trainer
                </a>
                <a href="classes.php?action=add" class="btn btn-primary">
                    <i class="fas fa-plus-circle"></i>
                    Create New Class
                </a>
                <a href="reports.php" class="btn btn-primary">
                    <i class="fas fa-file-alt"></i>
                    Generate Report
                </a>
                <a href="../client/booking.php" class="btn btn-primary">
                    <i class="fas fa-calendar-plus"></i>
                    Schedule Session
                </a>
            </div>
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
    <script>
        function showNotifications() {
            alert('Notifications feature coming soon!');
        }

        // Auto-hide alerts after 5 seconds
        setTimeout(function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                alert.style.transition = 'opacity 0.5s ease';
                alert.style.opacity = '0';
                setTimeout(() => alert.remove(), 500);
            });
        }, 5000);

        // Add loading state to buttons
        document.querySelectorAll('.btn').forEach(btn => {
            btn.addEventListener('click', function() {
                if (this.href && !this.href.includes('#')) {
                    const originalContent = this.innerHTML;
                    this.innerHTML = '<div class="loading"></div> Loading...';
                    this.disabled = true;
                    
                    setTimeout(() => {
                        this.innerHTML = originalContent;
                        this.disabled = false;
                    }, 2000);
                }
            });
        });
    </script>
</body>
</html>