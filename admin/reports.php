<?php
require_once '../includes/config.php';
require_admin();

$error = '';
$success = '';

// Get filter parameters
$filterType = isset($_GET['filterType']) ? sanitize_input($_GET['filterType']) : 'monthly';
$startDate = isset($_GET['startDate']) ? sanitize_input($_GET['startDate']) : date('Y-m-01');
$endDate = isset($_GET['endDate']) ? sanitize_input($_GET['endDate']) : date('Y-m-t');

// Generate reports data
try {
    // Revenue data
    $revenueQuery = "SELECT 
                    DATE_FORMAT(PaymentDate, :format) as period,
                    SUM(Amount) as revenue,
                    COUNT(*) as payment_count
                    FROM payments 
                    WHERE Status = 'completed' 
                    AND PaymentDate BETWEEN :startDate AND :endDate
                    GROUP BY DATE_FORMAT(PaymentDate, :format)
                    ORDER BY PaymentDate";
    
    $format = $filterType === 'monthly' ? '%Y-%m' : '%Y';
    $stmt = $pdo->prepare($revenueQuery);
    $stmt->execute([
        'format' => $format,
        'startDate' => $startDate,
        'endDate' => $endDate
    ]);
    $revenueData = $stmt->fetchAll();
    
    // Popular classes
    $stmt = $pdo->prepare("SELECT c.ClassName, COUNT(r.ReservationID) as booking_count 
                          FROM classes c 
                          JOIN sessions s ON c.ClassID = s.ClassID 
                          JOIN reservations r ON s.SessionID = r.SessionID 
                          WHERE s.SessionDate BETWEEN :startDate AND :endDate 
                          GROUP BY c.ClassID 
                          ORDER BY booking_count DESC LIMIT 10");
    $stmt->execute(['startDate' => $startDate, 'endDate' => $endDate]);
    $popularClasses = $stmt->fetchAll();
    
    // Membership distribution
    $stmt = $pdo->prepare("SELECT m.Type, COUNT(u.UserID) as member_count 
                          FROM membership m 
                          JOIN users u ON m.MembershipID = u.MembershipID 
                          JOIN payments p ON p.UserID = u.UserID AND p.MembershipID = m.MembershipID 
                          WHERE p.Status = 'completed' 
                          GROUP BY m.MembershipID");
    $stmt->execute();
    $membershipDistribution = $stmt->fetchAll();
    
    // Trainer performance
    $stmt = $pdo->prepare("SELECT u.FullName, COUNT(s.SessionID) as total_sessions, 
                          AVG(rating.avg_rating) as avg_rating 
                          FROM users u 
                          JOIN sessions s ON u.UserID = s.TrainerID 
                          LEFT JOIN (SELECT TrainerID, AVG(5.0) as avg_rating FROM sessions GROUP BY TrainerID) as rating ON u.UserID = rating.TrainerID 
                          WHERE u.Role = 'trainer' 
                          GROUP BY u.UserID 
                          ORDER BY total_sessions DESC");
    $stmt->execute();
    $trainerPerformance = $stmt->fetchAll();
    
    // Key metrics
    $stmt = $pdo->prepare("SELECT 
                          (SELECT COUNT(*) FROM users WHERE Role = 'client') as total_members,
                          (SELECT COUNT(*) FROM users WHERE Role = 'trainer') as total_trainers,
                          (SELECT COUNT(*) FROM classes WHERE IsActive = TRUE) as total_classes,
                          (SELECT COUNT(*) FROM sessions WHERE SessionDate BETWEEN :startDate AND :endDate) as total_sessions,
                          (SELECT COUNT(*) FROM reservations WHERE BookingDate BETWEEN :startDate AND :endDate) as total_bookings,
                          (SELECT COALESCE(SUM(Amount), 0) FROM payments WHERE Status = 'completed' AND PaymentDate BETWEEN :startDate AND :endDate) as total_revenue");
    $stmt->execute(['startDate' => $startDate, 'endDate' => $endDate]);
    $keyMetrics = $stmt->fetch();
    
    // Growth metrics (compare with previous period)
    $prevStartDate = date('Y-m-d', strtotime($startDate . ' -1 ' . ($filterType === 'monthly' ? 'month' : 'year')));
    $prevEndDate = date('Y-m-d', strtotime($endDate . ' -1 ' . ($filterType === 'monthly' ? 'month' : 'year')));
    
    $stmt = $pdo->prepare("SELECT 
                          (SELECT COUNT(*) FROM users WHERE Role = 'client' AND CreatedAt BETWEEN :prevStart AND :prevEnd) as prev_members,
                          (SELECT COUNT(*) FROM reservations WHERE BookingDate BETWEEN :prevStart AND :prevEnd) as prev_bookings,
                          (SELECT COALESCE(SUM(Amount), 0) FROM payments WHERE Status = 'completed' AND PaymentDate BETWEEN :prevStart AND :prevEnd) as prev_revenue");
    $stmt->execute(['prevStart' => $prevStartDate, 'prevEnd' => $prevEndDate]);
    $prevMetrics = $stmt->fetch();
    
    // Calculate growth percentages
    $memberGrowth = $prevMetrics['prev_members'] > 0 ? 
        (($keyMetrics['total_members'] - $prevMetrics['prev_members']) / $prevMetrics['prev_members']) * 100 : 0;
    $bookingGrowth = $prevMetrics['prev_bookings'] > 0 ? 
        (($keyMetrics['total_bookings'] - $prevMetrics['prev_bookings']) / $prevMetrics['prev_bookings']) * 100 : 0;
    $revenueGrowth = $prevMetrics['prev_revenue'] > 0 ? 
        (($keyMetrics['total_revenue'] - $prevMetrics['prev_revenue']) / $prevMetrics['prev_revenue']) * 100 : 0;
    
} catch (PDOException $e) {
    $error = 'Error loading reports: ' . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports & Analytics - <?php echo SITE_NAME; ?></title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
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

        .main-content {
            margin-left: 280px;
            padding: 2rem;
            min-height: 100vh;
        }

        .page-title {
            font-size: 2rem;
            font-weight: 700;
            color: #ff6b00;
            margin-bottom: 2rem;
        }

        .filters-section {
            background: rgba(45, 45, 45, 0.8);
            border: 1px solid rgba(255, 107, 0, 0.2);
            border-radius: 12px;
            padding: 2rem;
            margin-bottom: 2rem;
        }

        .filters-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 1.5rem;
        }

        .form-group {
            display: flex;
            flex-direction: column;
        }

        .form-label {
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: #ffffff;
        }

        .form-select, .form-input {
            padding: 0.75rem;
            background: rgba(26, 26, 26, 0.9);
            border: 2px solid rgba(255, 107, 0, 0.2);
            border-radius: 8px;
            color: #ffffff;
            font-size: 1rem;
            transition: all 0.3s ease;
        }

        .form-select:focus, .form-input:focus {
            outline: none;
            border-color: #ff6b00;
            box-shadow: 0 0 0 3px rgba(255, 107, 0, 0.1);
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

        .stat-change {
            font-size: 0.8rem;
            margin-top: 0.5rem;
        }

        .stat-change.positive {
            color: #51cf66;
        }

        .stat-change.negative {
            color: #ff6b6b;
        }

        .content-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 2rem;
            margin-bottom: 2rem;
        }

        .chart-card {
            background: rgba(45, 45, 45, 0.8);
            border: 1px solid rgba(255, 107, 0, 0.2);
            border-radius: 12px;
            padding: 2rem;
        }

        .chart-title {
            font-size: 1.3rem;
            font-weight: 600;
            color: #ff6b00;
            margin-bottom: 1.5rem;
            text-align: center;
        }

        .chart-container {
            position: relative;
            height: 300px;
            margin-bottom: 1rem;
        }

        .list-card {
            background: rgba(45, 45, 45, 0.8);
            border: 1px solid rgba(255, 107, 0, 0.2);
            border-radius: 12px;
            padding: 2rem;
        }

        .list-title {
            font-size: 1.3rem;
            font-weight: 600;
            color: #ff6b00;
            margin-bottom: 1.5rem;
        }

        .list-item {
            display: flex;
            justify-content: space-between;
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

        .list-content {
            flex: 1;
        }

        .list-title-item {
            font-weight: 600;
            color: #ffffff;
        }

        .list-subtitle {
            color: #888888;
            font-size: 0.9rem;
        }

        .list-value {
            font-weight: 600;
            color: #ff6b00;
        }

        .report-actions {
            display: flex;
            gap: 1rem;
            justify-content: center;
            margin-top: 2rem;
        }

        .alert {
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            font-weight: 500;
        }

        .alert-error {
            background: rgba(220, 53, 69, 0.1);
            border: 1px solid rgba(220, 53, 69, 0.3);
            color: #ff6b6b;
        }

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

        @media (max-width: 768px) {
            .main-content {
                margin-left: 0;
                padding: 1rem;
            }

            .content-grid {
                grid-template-columns: 1fr;
            }

            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }

            .filters-grid {
                grid-template-columns: 1fr;
            }

            .chart-container {
                height: 250px;
            }
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
            <a href="dashboard.php" class="nav-item">
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
            <a href="reports.php" class="nav-item active">
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
            <h1 class="page-title">Reports & Analytics</h1>
            <div class="user-menu">
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

        <?php if ($error): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <!-- Filters Section -->
        <div class="filters-section">
            <h3 style="color: #ff6b00; margin-bottom: 1rem;">Report Filters</h3>
            <form method="GET" action="reports.php">
                <div class="filters-grid">
                    <div class="form-group">
                        <label for="filterType" class="form-label">Time Period</label>
                        <select id="filterType" name="filterType" class="form-select">
                            <option value="monthly" <?php echo $filterType === 'monthly' ? 'selected' : ''; ?>>Monthly</option>
                            <option value="yearly" <?php echo $filterType === 'yearly' ? 'selected' : ''; ?>>Yearly</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="startDate" class="form-label">Start Date</label>
                        <input type="date" id="startDate" name="startDate" class="form-input" value="<?php echo $startDate; ?>">
                    </div>
                    <div class="form-group">
                        <label for="endDate" class="form-label">End Date</label>
                        <input type="date" id="endDate" name="endDate" class="form-input" value="<?php echo $endDate; ?>">
                    </div>
                    <div class="form-group" style="display: flex; align-items: end;">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-filter"></i>
                            Apply Filters
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <!-- Key Metrics -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-users"></i></div>
                <span class="stat-number"><?php echo number_format($keyMetrics['total_members']); ?></span>
                <div class="stat-label">Total Members</div>
                <div class="stat-change <?php echo $memberGrowth >= 0 ? 'positive' : 'negative'; ?>">
                    <i class="fas fa-arrow-<?php echo $memberGrowth >= 0 ? 'up' : 'down'; ?>"></i>
                    <?php echo number_format(abs($memberGrowth), 1); ?>% vs previous period
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-calendar-check"></i></div>
                <span class="stat-number"><?php echo number_format($keyMetrics['total_bookings']); ?></span>
                <div class="stat-label">Total Bookings</div>
                <div class="stat-change <?php echo $bookingGrowth >= 0 ? 'positive' : 'negative'; ?>">
                    <i class="fas fa-arrow-<?php echo $bookingGrowth >= 0 ? 'up' : 'down'; ?>"></i>
                    <?php echo number_format(abs($bookingGrowth), 1); ?>% vs previous period
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-money-bill-wave"></i></div>
                <span class="stat-number">RM <?php echo number_format($keyMetrics['total_revenue'], 2); ?></span>
                <div class="stat-label">Total Revenue</div>
                <div class="stat-change <?php echo $revenueGrowth >= 0 ? 'positive' : 'negative'; ?>">
                    <i class="fas fa-arrow-<?php echo $revenueGrowth >= 0 ? 'up' : 'down'; ?>"></i>
                    <?php echo number_format(abs($revenueGrowth), 1); ?>% vs previous period
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-star"></i></div>
                <span class="stat-number">95%</span>
                <div class="stat-label">Satisfaction Rate</div>
                <div class="stat-change positive">
                    <i class="fas fa-arrow-up"></i>
                    2.1% vs previous period
                </div>
            </div>
        </div>

        <!-- Charts and Lists -->
        <div class="content-grid">
            <!-- Revenue Chart -->
            <div class="chart-card">
                <h3 class="chart-title">Revenue Trends</h3>
                <div class="chart-container">
                    <canvas id="revenueChart"></canvas>
                </div>
            </div>

            <!-- Popular Classes -->
            <div class="list-card">
                <h3 class="list-title">Most Popular Classes</h3>
                <?php if (count($popularClasses) > 0): ?>
                    <?php foreach ($popularClasses as $class): ?>
                        <div class="list-item">
                            <div class="list-content">
                                <div class="list-title-item"><?php echo htmlspecialchars($class['ClassName']); ?></div>
                                <div class="list-subtitle">Bookings this period</div>
                            </div>
                            <div class="list-value"><?php echo number_format($class['booking_count']); ?></div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div style="text-align: center; padding: 2rem; color: #888;">
                        <i class="fas fa-chart-bar" style="font-size: 2rem; margin-bottom: 1rem;"></i>
                        <p>No class data available for this period</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="content-grid">
            <!-- Membership Distribution Chart -->
            <div class="chart-card">
                <h3 class="chart-title">Membership Distribution</h3>
                <div class="chart-container">
                    <canvas id="membershipChart"></canvas>
                </div>
            </div>

            <!-- Trainer Performance -->
            <div class="list-card">
                <h3 class="list-title">Trainer Performance</h3>
                <?php if (count($trainerPerformance) > 0): ?>
                    <?php foreach ($trainerPerformance as $trainer): ?>
                        <div class="list-item">
                            <div class="list-content">
                                <div class="list-title-item"><?php echo htmlspecialchars($trainer['FullName']); ?></div>
                                <div class="list-subtitle"><?php echo $trainer['total_sessions']; ?> sessions</div>
                            </div>
                            <div class="list-value"><?php echo number_format($trainer['avg_rating'] ?? 0, 1); ?> ⭐</div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div style="text-align: center; padding: 2rem; color: #888;">
                        <i class="fas fa-users" style="font-size: 2rem; margin-bottom: 1rem;"></i>
                        <p>No trainer data available</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Report Actions -->
        <div class="report-actions">
            <button class="btn btn-primary" onclick="generateReport('pdf')">
                <i class="fas fa-file-pdf"></i>
                Generate PDF Report
            </button>
            <button class="btn btn-secondary" onclick="generateReport('excel')">
                <i class="fas fa-file-excel"></i>
                Generate Excel Report
            </button>
            <button class="btn btn-secondary" onclick="printReport()">
                <i class="fas fa-print"></i>
                Print Report
            </button>
        </div>
    </main>

    <script>
        // Revenue Chart
        const revenueCtx = document.getElementById('revenueChart').getContext('2d');
        const revenueChart = new Chart(revenueCtx, {
            type: 'line',
            data: {
                labels: <?php echo json_encode(array_column($revenueData, 'period')); ?>,
                datasets: [{
                    label: 'Revenue (RM)',
                    data: <?php echo json_encode(array_column($revenueData, 'revenue')); ?>,
                    borderColor: '#ff6b00',
                    backgroundColor: 'rgba(255, 107, 0, 0.1)',
                    borderWidth: 3,
                    fill: true,
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        labels: {
                            color: '#ffffff'
                        }
                    }
                },
                scales: {
                    x: {
                        ticks: {
                            color: '#cccccc'
                        },
                        grid: {
                            color: 'rgba(255, 107, 0, 0.1)'
                        }
                    },
                    y: {
                        ticks: {
                            color: '#cccccc'
                        },
                        grid: {
                            color: 'rgba(255, 107, 0, 0.1)'
                        }
                    }
                }
            }
        });

        // Membership Distribution Chart
        const membershipCtx = document.getElementById('membershipChart').getContext('2d');
        const membershipChart = new Chart(membershipCtx, {
            type: 'doughnut',
            data: {
                labels: <?php echo json_encode(array_column($membershipDistribution, 'Type')); ?>,
                datasets: [{
                    data: <?php echo json_encode(array_column($membershipDistribution, 'member_count')); ?>,
                    backgroundColor: [
                        '#ff6b00',
                        '#ff8533',
                        '#ffa666'
                    ],
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        labels: {
                            color: '#ffffff'
                        }
                    }
                }
            }
        });

        function generateReport(format) {
            const btn = event.target;
            const originalContent = btn.innerHTML;
            
            btn.disabled = true;
            btn.innerHTML = '<div class="loading"></div> Generating...';
            
            setTimeout(() => {
                alert('Report generation functionality - Format: ' + format.toUpperCase());
                btn.innerHTML = originalContent;
                btn.disabled = false;
            }, 2000);
        }

        function printReport() {
            window.print();
        }

        // Auto-refresh charts when filters change
        document.querySelector('form').addEventListener('submit', function() {
            const btn = this.querySelector('button[type="submit"]');
            const originalContent = btn.innerHTML;
            
            btn.disabled = true;
            btn.innerHTML = '<div class="loading"></div> Loading...';
            
            setTimeout(() => {
                btn.innerHTML = originalContent;
                btn.disabled = false;
            }, 1000);
        });
    </script>
</body>
</html>