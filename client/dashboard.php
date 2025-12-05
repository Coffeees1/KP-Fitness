<?php
require_once '../includes/config.php';
require_client();

$userId = $_SESSION['UserID'];

// Get user details
try {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE UserID = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch();
    
    // Calculate BMI
    $bmi = calculate_bmi($user['Height'], $user['Weight']);
    $bmiCategory = get_bmi_category($bmi);
    
    // Get current membership
    $stmt = $pdo->prepare("SELECT m.*, p.PaymentDate, p.Status as PaymentStatus 
                          FROM membership m 
                          LEFT JOIN payments p ON m.MembershipID = p.MembershipID AND p.UserID = ? 
                          WHERE m.MembershipID = ?");
    $stmt->execute([$userId, $user['MembershipID']]);
    $membership = $stmt->fetch();
    
    // Get upcoming bookings
    $stmt = $pdo->prepare("SELECT r.*, s.SessionDate, s.Time, s.Room, c.ClassName, u.FullName as TrainerName 
                          FROM reservations r 
                          JOIN sessions s ON r.SessionID = s.SessionID 
                          JOIN classes c ON s.ClassID = c.ClassID 
                          JOIN users u ON s.TrainerID = u.UserID 
                          WHERE r.UserID = ? AND s.SessionDate >= CURRENT_DATE 
                          ORDER BY s.SessionDate, s.Time LIMIT 5");
    $stmt->execute([$userId]);
    $upcomingBookings = $stmt->fetchAll();
    
    // Get recent workout plans
    $stmt = $pdo->prepare("SELECT * FROM workout_plans WHERE UserID = ? ORDER BY CreatedAt DESC LIMIT 3");
    $stmt->execute([$userId]);
    $workoutPlans = $stmt->fetchAll();
    
    // Get payment history
    $stmt = $pdo->prepare("SELECT p.*, m.Type, m.Cost 
                          FROM payments p 
                          JOIN membership m ON p.MembershipID = m.MembershipID 
                          WHERE p.UserID = ? 
                          ORDER BY p.PaymentDate DESC LIMIT 5");
    $stmt->execute([$userId]);
    $paymentHistory = $stmt->fetchAll();
    
} catch (PDOException $e) {
    $error = 'Error loading dashboard data: ' . $e->getMessage();
}

// Get unread notifications count
$unreadCount = get_unread_notifications_count($userId);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Client Dashboard - <?php echo SITE_NAME; ?></title>
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

        /* Welcome Section */
        .welcome-section {
            background: rgba(45, 45, 45, 0.8);
            border: 1px solid rgba(255, 107, 0, 0.2);
            border-radius: 12px;
            padding: 2rem;
            margin-bottom: 2rem;
            text-align: center;
        }

        .welcome-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: #ff6b00;
            margin-bottom: 0.5rem;
        }

        .welcome-subtitle {
            color: #cccccc;
            margin-bottom: 1.5rem;
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

        /* BMI Card */
        .bmi-card {
            text-align: center;
            padding: 2rem;
        }

        .bmi-value {
            font-size: 3rem;
            font-weight: 800;
            color: #ff6b00;
            margin-bottom: 0.5rem;
        }

        .bmi-category {
            font-size: 1.1rem;
            color: #cccccc;
            margin-bottom: 1rem;
        }

        .bmi-info {
            font-size: 0.9rem;
            color: #888888;
        }

        /* Membership Card */
        .membership-card {
            text-align: center;
            padding: 2rem;
        }

        .membership-type {
            font-size: 1.5rem;
            font-weight: 700;
            color: #ff6b00;
            margin-bottom: 0.5rem;
        }

        .membership-status {
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: 600;
            margin-bottom: 1rem;
            display: inline-block;
        }

        .status-active {
            background: rgba(40, 167, 69, 0.2);
            color: #51cf66;
            border: 1px solid rgba(40, 167, 69, 0.3);
        }

        .status-inactive {
            background: rgba(220, 53, 69, 0.2);
            color: #ff6b6b;
            border: 1px solid rgba(220, 53, 69, 0.3);
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
            <p>Client Panel</p>
        </div>
        
        <div class="sidebar-nav">
            <a href="dashboard.php" class="nav-item active">
                <i class="fas fa-tachometer-alt"></i>
                Dashboard
            </a>
            <a href="membership.php" class="nav-item">
                <i class="fas fa-id-card"></i>
                Membership
            </a>
            <a href="booking.php" class="nav-item">
                <i class="fas fa-calendar-alt"></i>
                Book Classes
            </a>
            <a href="workout_planner.php" class="nav-item">
                <i class="fas fa-dumbbell"></i>
                AI Workout Planner
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
            <h1 class="page-title">Client Dashboard</h1>
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
                        <div style="font-size: 0.8rem; color: #888;">Client</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Welcome Section -->
        <div class="welcome-section">
            <h2 class="welcome-title">Welcome back, <?php echo htmlspecialchars(explode(' ', $_SESSION['FullName'])[0]); ?>!</h2>
            <p class="welcome-subtitle">Ready to continue your fitness journey? Check your stats and book your next class.</p>
            <div style="display: flex; gap: 1rem; justify-content: center; flex-wrap: wrap;">
                <a href="booking.php" class="btn btn-primary">
                    <i class="fas fa-calendar-plus"></i>
                    Book a Class
                </a>
                <a href="workout_planner.php" class="btn btn-primary">
                    <i class="fas fa-dumbbell"></i>
                    Get Workout Plan
                </a>
                <a href="membership.php" class="btn btn-primary">
                    <i class="fas fa-id-card"></i>
                    Manage Membership
                </a>
            </div>
        </div>

        <!-- Stats Grid -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-calendar-check"></i></div>
                <span class="stat-number"><?php echo count($upcomingBookings); ?></span>
                <div class="stat-label">Upcoming Classes</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-dumbbell"></i></div>
                <span class="stat-number"><?php echo count($workoutPlans); ?></span>
                <div class="stat-label">Workout Plans</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-credit-card"></i></div>
                <span class="stat-number"><?php echo count($paymentHistory); ?></span>
                <div class="stat-label">Payments Made</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-calendar-alt"></i></div>
                <span class="stat-number"><?php echo date('M d'); ?></span>
                <div class="stat-label">Today's Date</div>
            </div>
        </div>

        <!-- Content Grid -->
        <div class="content-grid">
            <!-- Upcoming Bookings -->
            <div class="content-card">
                <div class="card-header">
                    <h3 class="card-title">Upcoming Classes</h3>
                    <a href="booking.php" class="btn btn-primary">
                        <i class="fas fa-plus"></i>
                        Book More
                    </a>
                </div>
                <div style="max-height: 300px; overflow-y: auto;">
                    <?php if (count($upcomingBookings) > 0): ?>
                        <?php foreach ($upcomingBookings as $booking): ?>
                            <div class="list-item">
                                <div class="list-icon">
                                    <i class="fas fa-calendar-check"></i>
                                </div>
                                <div class="list-content">
                                    <div class="list-title"><?php echo htmlspecialchars($booking['ClassName']); ?></div>
                                    <div class="list-subtitle"><?php echo format_date($booking['SessionDate']); ?> at <?php echo format_time($booking['Time']); ?> - Room <?php echo htmlspecialchars($booking['Room']); ?></div>
                                    <div class="list-subtitle">Trainer: <?php echo htmlspecialchars($booking['TrainerName']); ?></div>
                                </div>
                                <div class="list-time">
                                    <?php echo date('M d', strtotime($booking['SessionDate'])); ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div style="text-align: center; padding: 2rem; color: #888;">
                            <i class="fas fa-calendar-times" style="font-size: 2rem; margin-bottom: 1rem;"></i>
                            <p>No upcoming classes booked</p>
                            <a href="booking.php" class="btn btn-primary" style="margin-top: 1rem;">
                                <i class="fas fa-plus"></i>
                                Book Your First Class
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Health Stats -->
            <div class="content-card">
                <div class="card-header">
                    <h3 class="card-title">Health Stats</h3>
                </div>
                <div class="bmi-card">
                    <div class="bmi-value"><?php echo $bmi; ?></div>
                    <div class="bmi-category"><?php echo $bmiCategory; ?></div>
                    <div class="bmi-info">
                        Height: <?php echo $user['Height']; ?> cm<br>
                        Weight: <?php echo $user['Weight']; ?> kg
                    </div>
                    <a href="../profile.php" class="btn btn-primary" style="margin-top: 1rem;">
                        <i class="fas fa-edit"></i>
                        Update Stats
                    </a>
                </div>
            </div>
        </div>

        <!-- Membership & Workout Plans -->
        <div class="content-grid">
            <!-- Current Membership -->
            <div class="content-card">
                <div class="card-header">
                    <h3 class="card-title">Current Membership</h3>
                    <a href="membership.php" class="btn btn-primary">
                        <i class="fas fa-cog"></i>
                        Manage
                    </a>
                </div>
                <div class="membership-card">
                    <?php if ($membership): ?>
                        <div class="membership-type"><?php echo ucfirst($membership['Type']); ?></div>
                        <div class="membership-status <?php echo $membership['PaymentStatus'] === 'completed' ? 'status-active' : 'status-inactive'; ?>">
                            <?php echo $membership['PaymentStatus'] === 'completed' ? 'Active' : 'Inactive'; ?>
                        </div>
                        <div style="color: #cccccc; margin-bottom: 1rem;">
                            Cost: <?php echo format_currency($membership['Cost']); ?><br>
                            Duration: <?php echo $membership['Duration']; ?> days
                        </div>
                        <div style="color: #888888; font-size: 0.9rem;">
                            <?php echo htmlspecialchars($membership['Benefits']); ?>
                        </div>
                    <?php else: ?>
                        <div style="text-align: center; color: #888;">
                            <i class="fas fa-id-card" style="font-size: 2rem; margin-bottom: 1rem;"></i>
                            <p>No active membership</p>
                            <a href="membership.php" class="btn btn-primary" style="margin-top: 1rem;">
                                <i class="fas fa-plus"></i>
                                Choose Membership
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Recent Workout Plans -->
            <div class="content-card">
                <div class="card-header">
                    <h3 class="card-title">Workout Plans</h3>
                    <a href="workout_planner.php" class="btn btn-primary">
                        <i class="fas fa-plus"></i>
                        Create New
                    </a>
                </div>
                <div style="max-height: 300px; overflow-y: auto;">
                    <?php if (count($workoutPlans) > 0): ?>
                        <?php foreach ($workoutPlans as $plan): ?>
                            <div class="list-item">
                                <div class="list-icon">
                                    <i class="fas fa-dumbbell"></i>
                                </div>
                                <div class="list-content">
                                    <div class="list-title"><?php echo htmlspecialchars($plan['PlanName']); ?></div>
                                    <div class="list-subtitle">Goal: <?php echo ucfirst($plan['Goal']); ?> - Level: <?php echo ucfirst($plan['FitnessLevel']); ?></div>
                                </div>
                                <div class="list-time">
                                    <?php echo date('M d', strtotime($plan['CreatedAt'])); ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div style="text-align: center; padding: 2rem; color: #888;">
                            <i class="fas fa-dumbbell" style="font-size: 2rem; margin-bottom: 1rem;"></i>
                            <p>No workout plans yet</p>
                            <a href="workout_planner.php" class="btn btn-primary" style="margin-top: 1rem;">
                                <i class="fas fa-plus"></i>
                                Create First Plan
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
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