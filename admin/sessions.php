<?php
define('PAGE_TITLE', 'Session Scheduling');
require_once '../includes/config.php';
require_admin();

$feedback = [];

// --- (PHP logic remains the same, with minor feedback type changes for Bootstrap) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_session'])) {
    $classId = intval($_POST['classId']);
    $trainerId = intval($_POST['trainerId']);
    $sessionDate = sanitize_input($_POST['sessionDate']);
    $time = sanitize_input($_POST['time']);
    $room = sanitize_input($_POST['room']);
    if (empty($classId) || empty($trainerId) || empty($sessionDate) || empty($time) || empty($room)) {
        $feedback = ['type' => 'danger', 'message' => 'Please fill in all required fields.'];
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO sessions (ClassID, TrainerID, SessionDate, Time, Room) VALUES (?, ?, ?, ?, ?)");
            if ($stmt->execute([$classId, $trainerId, $sessionDate, $time, $room])) {
                $feedback = ['type' => 'success', 'message' => 'Session scheduled successfully.'];
            }
        } catch (PDOException $e) { $feedback = ['type' => 'danger', 'message' => 'Database error: ' . $e->getMessage()]; }
    }
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && (isset($_POST['cancel_session']) || isset($_POST['reactivate_session']))) {
    $sessionId = intval($_POST['sessionId']);
    $newStatus = isset($_POST['cancel_session']) ? 'cancelled' : 'scheduled';
    $action = $newStatus === 'cancelled' ? 'cancelled' : 'reactivated';
    try {
        $stmt = $pdo->prepare("UPDATE sessions SET Status = ? WHERE SessionID = ?");
        if ($stmt->execute([$newStatus, $sessionId])) {
            $feedback = ['type' => 'success', 'message' => "Session has been $action."];
        }
    } catch (PDOException $e) { $feedback = ['type' => 'danger', 'message' => 'Database error: ' . $e->getMessage()]; }
}
// ---

// Fetch Data (with fix for MaxCapacity)
try {
    $stmt = $pdo->prepare("
        SELECT s.*, c.ClassName, c.MaxCapacity, u.FullName as TrainerName 
        FROM sessions s
        JOIN classes c ON s.ClassID = c.ClassID
        JOIN users u ON s.TrainerID = u.UserID
        WHERE u.Role = 'trainer'
        ORDER BY s.SessionDate DESC, s.Time DESC
    ");
    $stmt->execute();
    $sessions = $stmt->fetchAll();
    $active_classes = $pdo->query("SELECT ClassID, ClassName FROM classes WHERE IsActive = TRUE ORDER BY ClassName")->fetchAll();
    $active_trainers = $pdo->query("SELECT UserID, FullName FROM users WHERE IsActive = TRUE AND Role = 'trainer' ORDER BY FullName")->fetchAll();
} catch (PDOException $e) {
    $feedback = ['type' => 'danger', 'message' => 'Could not fetch data: ' . $e->getMessage()];
    $sessions = $active_classes = $active_trainers = [];
}

include 'includes/admin_header.php';
?>

<div class="d-flex justify-content-between align-items-center pb-3 mb-4 border-bottom">
    <h1 class="h2">Session Scheduling</h1>
    <p class="lead text-body-secondary m-0">Schedule class sessions with trainers.</p>
</div>

<?php if (!empty($feedback)): ?>
    <div class="alert alert-<?php echo $feedback['type']; ?> alert-dismissible fade show" role="alert">
        <?php echo $feedback['message']; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<!-- Schedule Session Form -->
<div class="card text-bg-dark mb-4">
    <div class="card-header fw-bold">Schedule New Session</div>
    <div class="card-body">
        <form action="sessions.php" method="POST">
            <div class="row g-3 align-items-end">
                <div class="col-md-6 col-lg-3">
                    <label for="classId" class="form-label">Class</label>
                    <select class="form-select" id="classId" name="classId" required>
                        <option value="">Select a class...</option>
                        <?php foreach ($active_classes as $class): ?>
                            <option value="<?php echo $class['ClassID']; ?>"><?php echo htmlspecialchars($class['ClassName']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6 col-lg-3">
                    <label for="trainerId" class="form-label">Trainer</label>
                    <select class="form-select" id="trainerId" name="trainerId" required>
                        <option value="">Select a trainer...</option>
                        <?php foreach ($active_trainers as $trainer): ?>
                            <option value="<?php echo $trainer['UserID']; ?>"><?php echo htmlspecialchars($trainer['FullName']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4 col-lg-2">
                    <label for="sessionDate" class="form-label">Date</label>
                    <input type="date" class="form-control" id="sessionDate" name="sessionDate" required>
                </div>
                <div class="col-md-4 col-lg-2">
                    <label for="time" class="form-label">Time</label>
                    <input type="time" class="form-control" id="time" name="time" required>
                </div>
                <div class="col-md-4 col-lg-2">
                    <label for="room" class="form-label">Room</label>
                    <input type="text" class="form-control" id="room" name="room" placeholder="e.g., Studio A" required>
                </div>
                <div class="col-12 col-lg-auto">
                    <button type="submit" name="save_session" class="btn btn-primary w-100">Schedule</button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Scheduled Sessions List -->
<div class="card text-bg-dark">
    <div class="card-header fw-bold">All Scheduled Sessions</div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-dark table-striped table-hover">
                <thead>
                    <tr>
                        <th>Date & Time</th><th>Class</th><th>Trainer</th><th>Room</th><th>Bookings</th><th>Status</th><th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($sessions as $session): ?>
                        <tr>
                            <td><?php echo format_date($session['SessionDate']) . '<br><small>' . format_time($session['Time']) . '</small>'; ?></td>
                            <td><?php echo htmlspecialchars($session['ClassName']); ?></td>
                            <td><?php echo htmlspecialchars($session['TrainerName']); ?></td>
                            <td><?php echo htmlspecialchars($session['Room']); ?></td>
                            <td><?php echo $session['CurrentBookings'] . ' / ' . $session['MaxCapacity']; ?></td>
                            <td>
                                <span class="badge text-bg-<?php echo ($session['Status'] === 'scheduled') ? 'success' : (($session['Status'] === 'cancelled') ? 'danger' : 'secondary'); ?>">
                                    <?php echo htmlspecialchars($session['Status']); ?>
                                </span>
                            </td>
                            <td>
                                <form action="sessions.php" method="POST" class="d-inline">
                                    <input type="hidden" name="sessionId" value="<?php echo $session['SessionID']; ?>">
                                    <?php if ($session['Status'] === 'scheduled'): ?>
                                        <button type="submit" name="cancel_session" class="btn btn-warning btn-sm">Cancel</button>
                                    <?php elseif($session['Status'] === 'cancelled'): ?>
                                        <button type="submit" name="reactivate_session" class="btn btn-success btn-sm">Reactivate</button>
                                    <?php endif; ?>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include 'includes/admin_footer.php'; ?>