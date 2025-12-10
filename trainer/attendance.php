<?php
define('PAGE_TITLE', 'Mark Attendance');
require_once '../includes/config.php';
require_trainer();

$trainerId = $_SESSION['UserID'];
$sessionId = isset($_GET['session_id']) ? intval($_GET['session_id']) : 0;
$feedback = [];

// --- Security Check: Ensure this session belongs to the logged-in trainer ---
try {
    $stmt = $pdo->prepare("SELECT SessionID, ClassID FROM sessions WHERE SessionID = ? AND TrainerID = ?");
    $stmt->execute([$sessionId, $trainerId]);
    $session = $stmt->fetch();
    if (!$session) {
        redirect('dashboard.php'); // Redirect if session doesn't exist or doesn't belong to trainer
    }
} catch (PDOException $e) {
    redirect('dashboard.php'); // Redirect on error
}

// --- Handle Form Submission ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mark_attendance'])) {
    $attendance_data = $_POST['attendance'] ?? [];
    
    try {
        $pdo->beginTransaction();
        foreach ($attendance_data as $reservationId => $status) {
            $reservationId = intval($reservationId);
            $status = sanitize_input($status);

            $stmt = $pdo->prepare("SELECT AttendanceID FROM attendance WHERE SessionID = ? AND UserID = (SELECT UserID FROM reservations WHERE ReservationID = ?)");
            $stmt->execute([$sessionId, $reservationId]);
            $existing_record = $stmt->fetch();

            if ($existing_record) {
                $stmt = $pdo->prepare("UPDATE attendance SET Status = ? WHERE AttendanceID = ?");
                $stmt->execute([$status, $existing_record['AttendanceID']]);
            } else {
                $stmt = $pdo->prepare("INSERT INTO attendance (SessionID, UserID, Status) SELECT ?, UserID, ? FROM reservations WHERE ReservationID = ?");
                $stmt->execute([$sessionId, $status, $reservationId]);
            }
        }
        
        // Mark session as completed
        $stmt = $pdo->prepare("UPDATE sessions SET Status = 'completed' WHERE SessionID = ?");
        $stmt->execute([$sessionId]);
        
        $pdo->commit();
        $feedback = ['type' => 'success', 'message' => 'Attendance has been marked successfully.'];
        
    } catch (PDOException $e) {
        $pdo->rollBack();
        $feedback = ['type' => 'danger', 'message' => 'A database error occurred: ' . $e->getMessage()];
    }
}

// --- Fetch Data for Display ---
try {
    $stmt = $pdo->prepare("SELECT s.SessionDate, s.Time, c.ClassName FROM sessions s JOIN classes c ON s.ClassID = c.ClassID WHERE s.SessionID = ?");
    $stmt->execute([$sessionId]);
    $sessionDetails = $stmt->fetch();
    
    $stmt = $pdo->prepare("SELECT r.ReservationID, u.UserID, u.FullName, a.Status as AttendanceStatus FROM reservations r JOIN users u ON r.UserID = u.UserID LEFT JOIN attendance a ON r.UserID = a.UserID AND r.SessionID = a.SessionID WHERE r.SessionID = ? AND r.Status = 'booked' ORDER BY u.FullName");
    $stmt->execute([$sessionId]);
    $bookedClients = $stmt->fetchAll();

} catch (PDOException $e) {
    $feedback = ['type' => 'danger', 'message' => 'Could not fetch class list: ' . $e->getMessage()];
    $sessionDetails = []; $bookedClients = [];
}

include 'includes/trainer_header.php';
?>

<div class="d-flex justify-content-between align-items-center pb-3 mb-4 border-bottom">
    <h1 class="h2">Mark Attendance</h1>
    <?php if($sessionDetails): ?>
        <p class="lead text-body-secondary m-0">
            <strong>Class:</strong> <?php echo htmlspecialchars($sessionDetails['ClassName']); ?> <br>
            <strong>Date:</strong> <?php echo format_date($sessionDetails['SessionDate']); ?> at <?php echo format_time($sessionDetails['Time']); ?>
        </p>
    <?php endif; ?>
</div>

<?php if (!empty($feedback)): ?>
    <div class="alert alert-<?php echo $feedback['type']; ?> alert-dismissible fade show" role="alert">
        <?php echo $feedback['message']; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<div class="card text-bg-dark mb-4">
    <div class="card-header fw-bold">Client List for Session</div>
    <div class="card-body">
        <form action="attendance.php?session_id=<?php echo $sessionId; ?>" method="POST">
            <div class="table-responsive">
                <table class="table table-dark table-striped table-hover">
                    <thead>
                        <tr>
                            <th>Client Name</th>
                            <th>Attendance Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($bookedClients)): ?>
                            <tr><td colspan="2" class="text-center text-body-secondary">No clients have booked this session.</td></tr>
                        <?php else: ?>
                            <?php foreach ($bookedClients as $client): ?>
                                <tr>
                                    <td class="align-middle"><?php echo htmlspecialchars($client['FullName']); ?></td>
                                    <td>
                                        <select class="form-select" name="attendance[<?php echo $client['ReservationID']; ?>]">
                                            <option value="present" <?php echo ($client['AttendanceStatus'] ?? '') === 'present' ? 'selected' : ''; ?>>Present</option>
                                            <option value="absent" <?php echo ($client['AttendanceStatus'] ?? '') === 'absent' ? 'selected' : ''; ?>>Absent</option>
                                            <option value="late" <?php echo ($client['AttendanceStatus'] ?? '') === 'late' ? 'selected' : ''; ?>>Late</option>
                                        </select>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <?php if (!empty($bookedClients)): ?>
                <div class="d-grid mt-3">
                    <button type="submit" name="mark_attendance" class="btn btn-primary btn-lg">Save Attendance</button>
                </div>
            <?php endif; ?>
        </form>
    </div>
</div>

<?php include 'includes/trainer_footer.php'; ?>