<?php
define('PAGE_TITLE', 'Trainer Dashboard');
require_once '../includes/config.php';
require_trainer(); // Ensure only trainers can access

$trainerId = $_SESSION['UserID'];
$feedback = [];

// --- Fetch Data for Display ---
try {
    // Today's schedule
    $stmt = $pdo->prepare("SELECT s.SessionID, s.Time, c.ClassName, s.Room, s.CurrentBookings, c.MaxCapacity FROM sessions s JOIN classes c ON s.ClassID = c.ClassID WHERE s.TrainerID = ? AND s.SessionDate = CURDATE() AND s.Status = 'scheduled' ORDER BY s.Time");
    $stmt->execute([$trainerId]);
    $todaysSchedule = $stmt->fetchAll();
    
    // Upcoming classes (next 5)
    $stmt = $pdo->prepare("SELECT s.SessionDate, s.Time, c.ClassName FROM sessions s JOIN classes c ON s.ClassID = c.ClassID WHERE s.TrainerID = ? AND s.SessionDate > CURDATE() AND s.Status = 'scheduled' ORDER BY s.SessionDate, s.Time LIMIT 5");
    $stmt->execute([$trainerId]);
    $upcomingClasses = $stmt->fetchAll();

    // Stats
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM sessions WHERE TrainerID = ? AND SessionDate < CURDATE()");
    $stmt->execute([$trainerId]);
    $totalSessionsConducted = $stmt->fetchColumn();

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM reservations r JOIN sessions s ON r.SessionID = s.SessionID WHERE s.TrainerID = ?");
    $stmt->execute([$trainerId]);
    $totalClientBookings = $stmt->fetchColumn();

    // Calculate Average Rating
    $stmt = $pdo->prepare("SELECT COALESCE(AVG(r.Rating), 0) FROM reservations r JOIN sessions s ON r.SessionID = s.SessionID WHERE s.TrainerID = ? AND r.Rating IS NOT NULL");
    $stmt->execute([$trainerId]);
    $avgRating = $stmt->fetchColumn();

} catch (PDOException $e) {
    $feedback = ['type' => 'danger', 'message' => 'Could not fetch dashboard data: ' . $e->getMessage()];
    $todaysSchedule = $upcomingClasses = [];
    $totalSessionsConducted = $totalClientBookings = 0;
    $avgRating = 0; // Default to 0 on error
}

include 'includes/trainer_header.php';
?>

<div class="d-flex justify-content-between align-items-center pb-3 mb-4 border-bottom">
    <h1 class="h2">Dashboard</h1>
    <p class="lead text-body-secondary m-0">Welcome, <?php echo htmlspecialchars($_SESSION['FullName']); ?>. Here is your schedule and summary.</p>
</div>

<?php if (!empty($feedback)): ?>
    <div class="alert alert-<?php echo $feedback['type']; ?> alert-dismissible fade show" role="alert">
        <?php echo $feedback['message']; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<div class="row g-4 mb-4">
    <div class="col-md-6 col-lg-3">
        <div class="card text-bg-dark h-100">
            <div class="card-body text-center">
                <i class="fas fa-calendar-day fa-3x text-primary mb-3"></i>
                <h3 class="display-5 fw-bold text-primary"><?php echo count($todaysSchedule); ?></h3>
                <p class="text-body-secondary">Classes Today</p>
            </div>
        </div>
    </div>
    <div class="col-md-6 col-lg-3">
        <div class="card text-bg-dark h-100">
            <div class="card-body text-center">
                <i class="fas fa-users fa-3x text-info mb-3"></i>
                <h3 class="display-5 fw-bold text-primary"><?php echo $totalClientBookings; ?></h3>
                <p class="text-body-secondary">Total Client Bookings</p>
            </div>
        </div>
    </div>
    <div class="col-md-6 col-lg-3">
        <div class="card text-bg-dark h-100">
            <div class="card-body text-center">
                <i class="fas fa-clipboard-list fa-3x text-success mb-3"></i>
                <h3 class="display-5 fw-bold text-primary"><?php echo $totalSessionsConducted; ?></h3>
                <p class="text-body-secondary">Sessions Conducted</p>
                <a href="historical_attendance.php" class="btn btn-outline-primary btn-sm mt-2">View History</a>
            </div>
        </div>
    </div>
    <div class="col-md-6 col-lg-3">
        <div class="card text-bg-dark h-100">
            <div class="card-body text-center">
                <i class="fas fa-star fa-3x text-warning mb-3"></i>
                <h3 class="display-5 fw-bold text-primary"><?php echo number_format($avgRating, 1); ?></h3>
                <p class="text-body-secondary">Average Rating</p>
                <a href="../profile.php" class="btn btn-outline-primary btn-sm mt-2">My Profile</a>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-6">
        <div class="card text-bg-dark h-100">
            <div class="card-header fw-bold">Today's Schedule</div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-dark table-striped table-hover">
                        <thead>
                            <tr><th>Time</th><th>Class</th><th>Room</th><th>Bookings</th><th>Action</th></tr>
                        </thead>
                        <tbody>
                            <?php if(empty($todaysSchedule)): ?>
                                <tr><td colspan="5" class="text-center text-body-secondary">No classes scheduled for today.</td></tr>
                            <?php else: ?>
                                <?php foreach($todaysSchedule as $session): ?>
                                <tr>
                                    <td class="align-middle"><?php echo format_time($session['Time']); ?></td>
                                    <td class="align-middle"><?php echo htmlspecialchars($session['ClassName']); ?></td>
                                    <td class="align-middle"><?php echo htmlspecialchars($session['Room']); ?></td>
                                    <td class="align-middle"><?php echo $session['CurrentBookings'] . ' / ' . $session['MaxCapacity']; ?></td>
                                    <td><a href="attendance.php?session_id=<?php echo $session['SessionID']; ?>" class="btn btn-primary btn-sm">Take Attendance</a></td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card text-bg-dark h-100">
            <div class="card-header fw-bold">Upcoming Classes</div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-dark table-striped table-hover">
                        <thead>
                            <tr><th>Date & Time</th><th>Class</th></tr>
                        </thead>
                        <tbody>
                            <?php if(empty($upcomingClasses)): ?>
                                <tr><td colspan="2" class="text-center text-body-secondary">No upcoming classes found.</td></tr>
                            <?php else: ?>
                                <?php foreach($upcomingClasses as $class_item): ?>
                                <tr>
                                    <td><?php echo format_date($class_item['SessionDate']) . ' at ' . format_time($class_item['Time']); ?></td>
                                    <td><?php echo htmlspecialchars($class_item['ClassName']); ?></td>
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
