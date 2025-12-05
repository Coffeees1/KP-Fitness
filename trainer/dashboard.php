<?php
require_once '../includes/config.php';
require_trainer();

$trainerId = $_SESSION['UserID'];

// Get trainer details and statistics
try {
    // Get trainer info
    $stmt = $pdo->prepare("SELECT * FROM users WHERE UserID = ?");
    $stmt->execute([$trainerId]);
    $trainer = $stmt->fetch();
    
    // Total sessions conducted
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM sessions WHERE TrainerID = ? AND SessionDate < CURRENT_DATE");
    $stmt->execute([$trainerId]);
    $totalSessions = $stmt->fetchColumn();
    
    // Total bookings for trainer's classes
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM reservations r JOIN sessions s ON r.SessionID = s.SessionID WHERE s.TrainerID = ?");
    $stmt->execute([$trainerId]);
    $totalBookings = $stmt->fetchColumn();
    
    // Today's classes
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM sessions WHERE TrainerID = ? AND SessionDate = CURRENT_DATE");
    $stmt->execute([$trainerId]);
    $todaysClasses = $stmt->fetchColumn();
    
    // Average rating (mock data for now)
    $avgRating = 4.7;
    
    // Today's schedule
    $stmt = $pdo->prepare("SELECT s.*, c.ClassName, COUNT(r.ReservationID) as BookingCount 
                          FROM sessions s 
                          JOIN classes c ON s.ClassID = c.ClassID 
                          LEFT JOIN reservations r ON s.SessionID = r.SessionID 
                          WHERE s.TrainerID = ? AND s.SessionDate = CURRENT_DATE 
                          GROUP BY s.SessionID 
                          ORDER BY s.Time");
    $stmt->execute([$trainerId]);
    $todaysSchedule = $stmt->fetchAll();
    
    // Upcoming classes
    $stmt = $pdo->prepare("SELECT s.*, c.ClassName, COUNT(r.ReservationID) as BookingCount 
                          FROM sessions s 
                          JOIN classes c ON s.ClassID = c.ClassID 
                          LEFT JOIN reservations r ON s.SessionID = r.SessionID 
                          WHERE s.TrainerID = ? AND s.SessionDate > CURRENT_DATE 
                          GROUP BY s.SessionID 
                          ORDER BY s.SessionDate, s.Time LIMIT 5");
    $stmt->execute([$trainerId]);
    $upcomingClasses = $stmt->fetchAll();
    
    // Recent bookings
    $stmt = $pdo->prepare("SELECT r.*, u.FullName, s.SessionDate, s.Time, c.ClassName 
                          FROM reservations r 
                          JOIN users u ON r.UserID = u.UserID 
                          JOIN sessions s ON r.SessionID = s.SessionID 
                          JOIN classes c ON s.ClassID = c.ClassID 
                          WHERE s.TrainerID = ? 
                          ORDER BY r.BookingDate DESC LIMIT 10");
    $stmt->execute([$trainerId]);
    $recentBookings = $stmt->fetchAll();
    
    // My classes
    $stmt = $pdo->prepare("SELECT DISTINCT c.*, COUNT(s.SessionID) as SessionCount 
                          FROM classes c 
                          JOIN sessions s ON c.ClassID = s.ClassID 
                          WHERE s.TrainerID = ? 
                          GROUP BY c.ClassID");
    $stmt->execute([$trainerId]);
    $myClasses = $stmt->fetchAll();
    
} catch (PDOException $e) {
    $error = 'Error loading dashboard data: ' . $e->getMessage();
}

// Get unread notifications count
$unreadCount = get_unread_notifications_count($trainerId);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Trainer Dashboard - <?php echo SITE_NAME; ?></title>
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

        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
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
            font-size: 2rem;
            color: #ff6b00;
            margin-bottom: 1rem;
        }

        .stat-number {
            font-size: 1.8rem;
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

        .btn-success {
            background: linear-gradient(135deg, #28a745, #20c997);
            color: #ffffff;
        }

        .btn-success:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(40, 167, 69, 0.3);
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

        .list-actions {
            display: flex;
            gap: 0.5rem;
        }

        /* Rating Display */
        .rating {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-top: 0.5rem;
        }

        .stars {
            color: #ffd700;
        }

        .rating-text {
            color: #cccccc;
            font-size: 0.9rem;
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
                grid-template-columns: repeat(2, 1fr);
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
            <p>Trainer Panel</p>
        </div>
        
        <div class="sidebar-nav">
            <a href="dashboard.php" class="nav-item active">
                <i class="fas fa-tachometer-alt"></i>
                Dashboard
            </a>
            <a href="schedule.php" class="nav-item">
                <i class="fas fa-calendar-alt"></i>
                My Schedule
            </a>
            <a href="attendance.php" class="nav-item">
                <i class="fas fa-clipboard-check"></i>
                Mark Attendance
            </a>
            <a href="classes.php" class="nav-item">
                <i class="fas fa-dumbbell"></i>
                My Classes
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
            <h1 class="page-title">Trainer Dashboard</h1>
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
                        <div style="font-size: 0.8rem; color: #888;">Trainer</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Stats Grid -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-chalkboard-teacher"></i></div>
                <span class="stat-number"><?php echo number_format($totalSessions); ?></span>
                <div class="stat-label">Total Sessions</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-users"></i></div>
                <span class="stat-number"><?php echo number_format($totalBookings); ?></span>
                <div class="stat-label">Total Bookings</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-star"></i></div>
                <span class="stat-number"><?php echo number_format($avgRating, 1); ?></span>
                <div class="stat-label">Avg Rating</div>
                <div class="rating">
                    <div class="stars">
                        <?php for ($i = 1; $i <= 5; $i++): ?>
                            <i class="fas fa-star<?php echo $i <= $avgRating ? '' : '-o'; ?>"></i>
                        <?php endfor; ?>
                    </div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-calendar-day"></i></div>
                <span class="stat-number"><?php echo number_format($todaysClasses); ?></span>
                <div class="stat-label">Today's Classes</div>
            </div>
        </div>

        <!-- Content Grid -->
        <div class="content-grid">
            <!-- Today's Schedule -->
            <div class="content-card">
                <div class="card-header">
                    <h3 class="card-title">Today's Schedule</h3>
                    <a href="schedule.php" class="btn btn-primary">
                        <i class="fas fa-eye"></i>
                        View All
                    </a>
                </div>
                <div style="max-height: 300px; overflow-y: auto;">
                    <?php if (count($todaysSchedule) > 0): ?>
                        <?php foreach ($todaysSchedule as $session): ?>
                            <div class="list-item">
                                <div class="list-icon">
                                    <i class="fas fa-clock"></i>
                                </div>
                                <div class="list-content">
                                    <div class="list-title"><?php echo htmlspecialchars($session['ClassName']); ?></div>
                                    <div class="list-subtitle"><?php echo format_time($session['Time']); ?> - Room <?php echo htmlspecialchars($session['Room']); ?></div>
                                    <div class="list-subtitle"><?php echo $session['BookingCount']; ?> bookings</div>
                                </div>
                                <div class="list-actions">
                                    <a href="attendance.php?session=<?php echo $session['SessionID']; ?>" class="btn btn-success">
                                        <i class="fas fa-clipboard-check"></i>
                                        Attendance
                                    </a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div style="text-align: center; padding: 2rem; color: #888;">
                            <i class="fas fa-calendar-times" style="font-size: 2rem; margin-bottom: 1rem;"></i>
                            <p>No classes scheduled for today</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Performance Summary -->
            <div class="content-card">
                <div class="card-header">
                    <h3 class="card-title">Performance Summary</h3>
                </div>
                <div style="text-align: center; padding: 2rem;">
                    <div style="font-size: 2rem; color: #ff6b00; margin-bottom: 1rem;">
                        <i class="fas fa-trophy"></i>
                    </div>
                    <div style="font-size: 1.5rem; font-weight: 700; color: #ffffff; margin-bottom: 0.5rem;">
                        Excellent Performance!
                    </div>
                    <div style="color: #cccccc; margin-bottom: 1.5rem;">
                        You're one of our top trainers
                    </div>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1.5rem;">
                        <div style="text-align: center;">
                            <div style="font-size: 1.2rem; font-weight: 600; color: #ff6b00;">95%</div>
                            <div style="font-size: 0.9rem; color: #cccccc;">Attendance Rate</div>
                        </div>
                        <div style="text-align: center;">
                            <div style="font-size: 1.2rem; font-weight: 600; color: #ff6b00;">4.8</div>
                            <div style="font-size: 0.9rem; color: #cccccc;">Client Rating</div>
                        </div>
                    </div>
                    <a href="classes.php" class="btn btn-primary">
                        <i class="fas fa-chart-line"></i>
                        View Details
                    </a>
                </div>
            </div>
        </div>

        <!-- Content Grid -->
        <div class="content-grid">
            <!-- Upcoming Classes -->
            <div class="content-card">
                <div class="card-header">
                    <h3 class="card-title">Upcoming Classes</h3>
                    <a href="schedule.php" class="btn btn-primary">
                        <i class="fas fa-eye"></i>
                        View All
                    </a>
                </div>
                <div style="max-height: 300px; overflow-y: auto;">
                    <?php if (count($upcomingClasses) > 0): ?>
                        <?php foreach ($upcomingClasses as $session): ?>
                            <div class="list-item">
                                <div class="list-icon">
                                    <i class="fas fa-calendar-alt"></i>
                                </div>
                                <div class="list-content">
                                    <div class="list-title"><?php echo htmlspecialchars($session['ClassName']); ?></div>
                                    <div class="list-subtitle"><?php echo format_date($session['SessionDate']); ?> at <?php echo format_time($session['Time']); ?></div>
                                    <div class="list-subtitle">Room <?php echo htmlspecialchars($session['Room']); ?> - <?php echo $session['BookingCount']; ?> bookings</div>
                                </div>
                                <div class="list-time">
                                    <?php echo date('M d', strtotime($session['SessionDate'])); ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div style="text-align: center; padding: 2rem; color: #888;">
                            <i class="fas fa-calendar-times" style="font-size: 2rem; margin-bottom: 1rem;"></i>
                            <p>No upcoming classes</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Recent Bookings -->
            <div class="content-card">
                <div class="card-header">
                    <h3 class="card-title">Recent Bookings</h3>
                </div>
                <div style="max-height: 300px; overflow-y: auto;">
                    <?php if (count($recentBookings) > 0): ?>
                        <?php foreach ($recentBookings as $booking): ?>
                            <div class="list-item">
                                <div class="list-icon">
                                    <i class="fas fa-user"></i>
                                </div>
                                <div class="list-content">
                                    <div class="list-title"><?php echo htmlspecialchars($booking['FullName']); ?></div>
                                    <div class="list-subtitle"><?php echo htmlspecialchars($booking['ClassName']); ?> - <?php echo format_date($booking['SessionDate']); ?></div>
                                </div>
                                <div class="list-time">
                                    <?php echo date('M d', strtotime($booking['BookingDate'])); ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div style="text-align: center; padding: 2rem; color: #888;">
                            <i class="fas fa-users" style="font-size: 2rem; margin-bottom: 1rem;"></i>
                            <p>No recent bookings</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- My Classes -->
        <div class="content-card">
            <div class="card-header">
                <h3 class="card-title">My Classes</h3>
                <a href="classes.php" class="btn btn-primary">
                    <i class="fas fa-eye"></i>
                    View All
                </a>
            </div>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1rem;">
                <?php if (count($myClasses) > 0): ?>
                    <?php foreach ($myClasses as $class): ?>
                        <div style="background: rgba(26, 26, 26, 0.5); border-radius: 8px; padding: 1.5rem; text-align: center;">
                            <div style="font-size: 2rem; color: #ff6b00; margin-bottom: 1rem;">
                                <i class="fas fa-dumbbell"></i>
                            </div>
                            <div style="font-size: 1.2rem; font-weight: 600; color: #ffffff; margin-bottom: 0.5rem;">
                                <?php echo htmlspecialchars($class['ClassName']); ?>
                            </div>
                            <div style="color: #cccccc; margin-bottom: 1rem;">
                                <?php echo $class['SessionCount']; ?> sessions conducted
                            </div>
                            <a href="schedule.php?class=<?php echo $class['ClassID']; ?>" class="btn btn-primary">
                                <i class="fas fa-calendar"></i>
                                View Schedule
                            </a>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div style="text-align: center; padding: 2rem; color: #888; grid-column: 1 / -1;">
                        <i class="fas fa-dumbbell" style="font-size: 2rem; margin-bottom: 1rem;"></i>
                        <p>No classes assigned yet</p>
                        <p style="font-size: 0.9rem;">Contact admin to assign classes</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>

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