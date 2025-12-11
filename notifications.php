<?php
define('PAGE_TITLE', 'Notifications');
require_once 'includes/config.php';

if (!is_logged_in()) {
    redirect('login.php');
}

$userId = $_SESSION['UserID'];
$feedback = [];

// Handle marking notifications as read
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mark_read'])) {
    $notificationId = intval($_POST['notificationId']);
    try {
        $stmt = $pdo->prepare("UPDATE notifications SET IsRead = 1 WHERE NotificationID = ? AND UserID = ?");
        $stmt->execute([$notificationId, $userId]);
        $feedback = ['type' => 'success', 'message' => 'Notification marked as read.'];
    } catch (PDOException $e) {
        $feedback = ['type' => 'danger', 'message' => 'Error marking notification as read: ' . $e->getMessage()];
    }
}

// Handle marking all notifications as read
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mark_all_read'])) {
    try {
        $stmt = $pdo->prepare("UPDATE notifications SET IsRead = 1 WHERE UserID = ?");
        $stmt->execute([$userId]);
        $feedback = ['type' => 'success', 'message' => 'All notifications marked as read.'];
    } catch (PDOException $e) {
        $feedback = ['type' => 'danger', 'message' => 'Error marking all notifications as read: ' . $e->getMessage()];
    }
}

// Fetch notifications for the current user
try {
    $stmt = $pdo->prepare("SELECT * FROM notifications WHERE UserID = ? ORDER BY CreatedAt DESC");
    $stmt->execute([$userId]);
    $notifications = $stmt->fetchAll();
} catch (PDOException $e) {
    $feedback = ['type' => 'danger', 'message' => 'Could not fetch notifications: ' . $e->getMessage()];
    $notifications = [];
}

include 'includes/header.php'; // Use the main site header
?>

<div class="d-flex justify-content-between align-items-center pb-3 mb-4 border-bottom">
    <h1 class="h2">My Notifications</h1>
    <p class="lead text-body-secondary m-0">Stay updated with important alerts.</p>
</div>

<?php if (!empty($feedback)): ?>
    <div class="alert alert-<?php echo $feedback['type']; ?> alert-dismissible fade show" role="alert">
        <?php echo $feedback['message']; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<div class="card text-bg-dark mb-4">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">All Notifications</h5>
        <form action="notifications.php" method="POST" class="d-inline">
            <button type="submit" name="mark_all_read" class="btn btn-sm btn-outline-secondary">Mark All as Read</button>
        </form>
    </div>
    <div class="list-group list-group-flush">
        <?php if (empty($notifications)): ?>
            <li class="list-group-item text-center text-body-secondary bg-transparent py-4">
                <i class="fas fa-bell-slash fa-2x mb-2"></i><br>
                No notifications found.
            </li>
        <?php else: ?>
            <?php foreach ($notifications as $notification): ?>
                <li class="list-group-item bg-transparent <?php echo $notification['IsRead'] ? 'text-body-secondary' : 'fw-bold'; ?>">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <span class="badge bg-<?php echo $notification['Type'] === 'success' ? 'success' : ($notification['Type'] === 'error' ? 'danger' : 'primary'); ?> me-2">
                                <?php echo htmlspecialchars(ucfirst($notification['Type'])); ?>
                            </span>
                            <?php echo htmlspecialchars($notification['Title']); ?>
                            <p class="mb-0 text-body-secondary fw-normal"><?php echo htmlspecialchars($notification['Message']); ?></p>
                            <small class="text-muted"><?php echo format_date($notification['CreatedAt']) . ' at ' . format_time($notification['CreatedAt']); ?></small>
                        </div>
                        <?php if (!$notification['IsRead']): ?>
                            <form action="notifications.php" method="POST" class="d-inline ms-auto">
                                <input type="hidden" name="notificationId" value="<?php echo $notification['NotificationID']; ?>">
                                <button type="submit" name="mark_read" class="btn btn-sm btn-outline-primary">Mark as Read</button>
                            </form>
                        <?php endif; ?>
                    </div>
                </li>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
