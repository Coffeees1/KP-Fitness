<?php
define('PAGE_TITLE', 'My Schedule');
require_once '../includes/config.php';
require_trainer();

$trainerId = $_SESSION['UserID'];
$feedback = [];

// --- Fetch Data for Display ---
try {
    $stmt = $pdo->prepare("SELECT s.SessionID, s.SessionDate, s.Time, s.Room, s.Status, s.CurrentBookings, c.ClassName, c.MaxCapacity FROM sessions s JOIN classes c ON s.ClassID = c.ClassID WHERE s.TrainerID = ? ORDER BY s.SessionDate DESC, s.Time DESC");
    $stmt->execute([$trainerId]);
    $allSessions = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $feedback = ['type' => 'danger', 'message' => 'Could not fetch schedule data: ' . $e->getMessage()];
    $allSessions = [];
}

include 'includes/trainer_header.php';
?>

<div class="d-flex justify-content-between align-items-center pb-3 mb-4 border-bottom">
    <h1 class="h2">My Schedule</h1>
    <p class="lead text-body-secondary m-0">A complete overview of all your past, present, and future classes.</p>
</div>

<?php if (!empty($feedback)): ?>
    <div class="alert alert-<?php echo $feedback['type']; ?> alert-dismissible fade show" role="alert">
        <?php echo $feedback['message']; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<div class="card text-bg-dark mb-4">
    <div class="card-header fw-bold">All My Sessions</div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-dark table-striped table-hover">
                <thead>
                    <tr>
                        <th>Date & Time</th><th>Class</th><th>Room</th><th>Bookings</th><th>Status</th><th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($allSessions)): ?>
                        <tr><td colspan="6" class="text-center text-body-secondary">You have no sessions assigned to you.</td></tr>
                    <?php else: ?>
                        <?php foreach($allSessions as $session): ?>
                            <tr>
                                <td class="align-middle"><?php echo format_date($session['SessionDate']); ?> at <?php echo format_time($session['Time']); ?></td>
                                <td class="align-middle"><?php echo htmlspecialchars($session['ClassName']); ?></td>
                                <td class="align-middle"><?php echo htmlspecialchars($session['Room']); ?></td>
                                <td class="align-middle"><?php echo $session['CurrentBookings'] . ' / ' . $session['MaxCapacity']; ?></td>
                                <td>
                                    <span class="badge text-bg-<?php echo ($session['Status'] === 'scheduled') ? 'success' : (($session['Status'] === 'cancelled') ? 'danger' : 'secondary'); ?> text-capitalize">
                                        <?php echo htmlspecialchars($session['Status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($session['Status'] === 'scheduled'): ?>
                                        <a href="attendance.php?session_id=<?php echo $session['SessionID']; ?>" class="btn btn-primary btn-sm">Attendance</a>
                                    <?php else: ?>
                                        <span class="text-body-secondary">N/A</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include 'includes/trainer_footer.php'; ?>