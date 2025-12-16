<?php
define('PAGE_TITLE', 'Trainer Dashboard');
require_once '../includes/config.php';
require_trainer(); // Ensure only trainers can access

$trainerId = $_SESSION['UserID'];
$feedback = [];

// --- Fetch Data for Display ---
try {
    // Today's schedule
    $stmt = $pdo->prepare("
        SELECT s.SessionID, s.Time, c.ClassName, s.Room, s.CurrentBookings, c.MaxCapacity
        FROM sessions s
        JOIN activities c ON s.ClassID = c.ClassID
        WHERE s.TrainerID = ? AND s.SessionDate = CURDATE() AND s.Status = 'scheduled'
        ORDER BY s.Time
    ");
    $stmt->execute([$trainerId]);
    $todaysSchedule = $stmt->fetchAll();
    
    // Upcoming classes (next 5)
    $stmt = $pdo->prepare("
        SELECT s.SessionDate, s.Time, c.ClassName
        FROM sessions s
        JOIN activities c ON s.ClassID = c.ClassID
        WHERE s.TrainerID = ? AND s.SessionDate > CURDATE() AND s.Status = 'scheduled'
        ORDER BY s.SessionDate, s.Time
        LIMIT 5
    ");
    $stmt->execute([$trainerId]);
    $upcomingClasses = $stmt->fetchAll();

    // Stats
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM sessions WHERE TrainerID = ? AND SessionDate < CURDATE()");
    $stmt->execute([$trainerId]);
    $totalSessionsConducted = $stmt->fetchColumn();

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM reservations r JOIN sessions s ON r.SessionID = s.SessionID WHERE s.TrainerID = ?");
    $stmt->execute([$trainerId]);
    $totalClientBookings = $stmt->fetchColumn();

} catch (PDOException $e) {
    $feedback = ['type' => 'danger', 'message' => 'Could not fetch dashboard data: ' . $e->getMessage()];
    $todaysSchedule = $upcomingClasses = [];
    $totalSessionsConducted = $totalClientBookings = 0;
}

include 'includes/trainer_header.php';
?>

<style>
    /* "1-to-1" Custom Styles matching the provided image */
    :root {
        --dash-bg-card: #202020; /* Dark Grey Background for cards */
        --dash-text-headers: #ffffff;
        --dash-text-sub: #b0b0b0;
        --dash-accent: #ff6b00; /* Orange */
        --dash-border: rgba(255, 107, 0, 0.25); /* Slight orange outline */
    }

    .dashboard-container h2, .dashboard-container h4, .dashboard-container h5 {
        color: var(--dash-text-headers);
        font-weight: 600;
    }

    /* Welcome Card */
    .welcome-section {
        background-color: var(--dash-bg-card);
        padding: 2rem;
        border-radius: 8px;
        margin-bottom: 2rem;
        border: 1px solid var(--dash-border);
    }
    
    .welcome-section p {
        color: var(--dash-text-sub);
    }

    /* Stats Cards */
    .dashboard-stat-card {
        background-color: var(--dash-bg-card);
        padding: 1.5rem;
        border-radius: 6px;
        display: flex;
        align-items: center;
        border: 1px solid var(--dash-border);
        height: 100%;
    }

    .stat-icon-wrapper {
        font-size: 2rem;
        color: var(--dash-accent);
        margin-right: 1.5rem;
        width: 50px;
        text-align: center;
    }

    .stat-content .stat-value {
        font-size: 1.8rem;
        font-weight: bold;
        color: #fff;
        line-height: 1.2;
    }

    .stat-content .stat-label {
        font-size: 0.9rem;
        color: #fff; /* White as per image */
        font-weight: 500;
        margin-top: 5px;
    }

    /* Quick Action Buttons */
    .quick-action-btn {
        background-color: var(--dash-bg-card);
        border: 1px solid var(--dash-border);
        border-radius: 6px;
        padding: 2.5rem 1rem;
        text-align: center;
        display: block;
        text-decoration: none;
        transition: transform 0.2s, border-color 0.2s;
        height: 100%;
    }

    .quick-action-btn:hover {
        transform: translateY(-3px);
        border-color: var(--dash-accent);
    }

    .quick-action-btn i {
        font-size: 2.5rem;
        color: var(--dash-accent);
        margin-bottom: 1rem;
        display: block;
    }

    .quick-action-btn span {
        color: var(--dash-accent); /* Orange text as per image */
        font-weight: 600;
        font-size: 1.1rem;
    }

    /* Tables */
    .dashboard-table-card {
        background-color: var(--dash-bg-card);
        border: 1px solid var(--dash-border);
        border-radius: 6px;
    }

    .dashboard-table-header {
        background-color: transparent;
        padding: 1rem 1.5rem;
        border-bottom: 1px solid #333;
        color: #fff;
        font-weight: 600;
        display: flex;
        align-items: center;
    }
    
    .table-dark-custom {
        --bs-table-bg: #202020;
        --bs-table-color: #fff;
        --bs-table-border-color: #333;
    }
    
    .table-dark-custom thead th {
        background-color: #1a1a1a;
        color: #fff;
        border-bottom: 1px solid #444;
        font-size: 0.85rem;
        text-transform: uppercase;
        font-weight: 700;
    }

</style>

<div class="dashboard-container">
    
    <h2 class="mb-2">Trainer Dashboard</h2>
    <hr class="border-white opacity-100 mb-4">

    <?php if (!empty($feedback)): ?>
        <div class="alert alert-<?php echo $feedback['type']; ?> alert-dismissible fade show" role="alert">
            <?php echo $feedback['message']; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <!-- Welcome Section -->
    <div class="welcome-section">
        <h1 class="h3 text-white mb-2">Welcome back, <?php echo htmlspecialchars(explode(' ', $_SESSION['FullName'])[0]); ?>!</h1>
        <p class="mb-4">Ready to inspire your clients today? Here's your training overview.</p>
        <div>
            <a href="attendance.php" class="btn btn-primary me-2 px-4">View Attendance</a>
            <a href="schedule.php" class="btn btn-secondary px-4">My Schedule</a>
        </div>
    </div>

    <!-- Stats Grid -->
    <div class="row g-4 mb-5">
        <div class="col-md-3">
            <div class="dashboard-stat-card">
                <div class="stat-icon-wrapper">
                    <i class="fas fa-calendar-day"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-value"><?php echo count($todaysSchedule); ?></div>
                    <div class="stat-label">Classes Today</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="dashboard-stat-card">
                <div class="stat-icon-wrapper">
                    <i class="fas fa-users"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-value"><?php echo number_format($totalClientBookings); ?></div>
                    <div class="stat-label">Total Bookings</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="dashboard-stat-card">
                <div class="stat-icon-wrapper">
                    <i class="fas fa-clipboard-check"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-value"><?php echo number_format($totalSessionsConducted); ?></div>
                    <div class="stat-label">Sessions Completed</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="dashboard-stat-card">
                <div class="stat-icon-wrapper">
                    <i class="fas fa-star"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-value">4.7 <i class="fas fa-star text-warning" style="font-size: 0.6em; vertical-align: middle;"></i></div>
                    <div class="stat-label">Average Rating</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <h4 class="mb-2">Quick Actions</h4>
    <hr class="border-white opacity-100 mb-4">
    <div class="row g-4 mb-5">
        <div class="col-md-3">
            <a href="attendance.php" class="quick-action-btn">
                <i class="fas fa-clipboard-check"></i>
                <span>Take Attendance</span>
            </a>
        </div>
        <div class="col-md-3">
            <a href="schedule.php" class="quick-action-btn">
                <i class="fas fa-calendar-alt"></i>
                <span>View Schedule</span>
            </a>
        </div>
        <div class="col-md-3">
            <a href="historical_attendance.php" class="quick-action-btn">
                <i class="fas fa-history"></i>
                <span>Attendance History</span>
            </a>
        </div>
        <div class="col-md-3">
            <a href="profile.php" class="quick-action-btn">
                <i class="fas fa-user-edit"></i>
                <span>My Profile</span>
            </a>
        </div>
    </div>

    <!-- Schedule Split Layout -->
    <div class="row g-4">
        <!-- Today's Schedule (Left) -->
        <div class="col-xl-6">
            <div class="dashboard-table-card h-100">
                <div class="dashboard-table-header">
                    <i class="fas fa-calendar-day me-2"></i> Today's Schedule
                </div>
                <div class="table-responsive">
                    <table class="table table-dark-custom mb-0">
                        <thead>
                            <tr>
                                <th class="ps-4">Time</th>
                                <th>Class</th>
                                <th>Room</th>
                                <th>Bookings</th>
                                <th class="text-end pe-4">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(empty($todaysSchedule)): ?>
                                <tr><td colspan="5" class="text-center py-4 text-muted bg-white text-dark">No classes scheduled for today.</td></tr>
                            <?php else: ?>
                                <?php foreach($todaysSchedule as $session): ?>
                                <tr>
                                    <td class="ps-4 py-3"><strong><?php echo format_time($session['Time']); ?></strong></td>
                                    <td class="py-3"><?php echo htmlspecialchars($session['ClassName']); ?></td>
                                    <td class="py-3"><?php echo htmlspecialchars($session['Room']); ?></td>
                                    <td class="py-3">
                                        <?php echo $session['CurrentBookings'] . ' / ' . $session['MaxCapacity']; ?>
                                    </td>
                                    <td class="text-end pe-4 py-3">
                                        <a href="attendance.php?session_id=<?php echo $session['SessionID']; ?>" class="text-decoration-none text-white fw-bold">
                                            Take Attendance <i class="fas fa-arrow-right ms-1"></i>
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Upcoming Classes (Right) -->
        <div class="col-xl-6">
            <div class="dashboard-table-card h-100">
                <div class="dashboard-table-header">
                    <i class="fas fa-calendar-plus me-2"></i> Upcoming Classes
                </div>
                <div class="table-responsive">
                    <table class="table table-dark-custom mb-0">
                        <thead>
                            <tr>
                                <th class="ps-4">Date & Time</th>
                                <th class="text-end pe-4">Class</th>
                            </tr>
                        </thead>
                        <tbody>
                                <?php if(empty($upcomingClasses)): ?>
                                <tr><td colspan="2" class="text-center py-4 text-muted bg-white text-dark">No upcoming classes found.</td></tr>
                            <?php else: ?>
                                <?php foreach($upcomingClasses as $class_item): ?>
                                <tr>
                                    <td class="ps-4 py-3"><?php echo format_date($class_item['SessionDate']) . ' at ' . format_time($class_item['Time']); ?></td>
                                    <td class="text-end pe-4 py-3"><?php echo htmlspecialchars($class_item['ClassName']); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/trainer_footer.php'; ?>
