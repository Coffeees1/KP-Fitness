<?php
require_once '../includes/config.php';
require_client();

header('Content-Type: application/json');

if (!isset($_GET['date'])) {
    echo json_encode([]);
    exit;
}

$date = $_GET['date'];
$categoryId = $_GET['category_id'] ?? null;
$difficulty = $_GET['difficulty'] ?? null;

$userId = $_SESSION['UserID'];

try {
$sql = "SELECT s.SessionID, s.SessionDate, s.StartTime, a.ClassName as ActivityName, a.MaxCapacity, u.FullName as TrainerName, s.CurrentBookings,
        (SELECT COUNT(*) FROM reservations r WHERE r.SessionID = s.SessionID AND r.UserID = ? AND r.Status = 'booked') as IsBooked
        FROM sessions s
        JOIN activities a ON s.ClassID = a.ClassID
        JOIN users u ON s.TrainerID = u.UserID
        WHERE s.SessionDate = ? AND s.Status != 'cancelled'";

$params = [$userId, $date];

// If requesting for today, filter out past times
if ($date === date('Y-m-d')) {
    $sql .= " AND s.StartTime > CURTIME()";
}

if ($categoryId) {
    $sql .= " AND a.CategoryID = ?";
    $params[] = $categoryId;
}

if ($difficulty) {
    $sql .= " AND a.DifficultyLevel = ?";
    $params[] = $difficulty;
}

$sql .= " ORDER BY s.StartTime";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $sessions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($sessions);
} catch (PDOException $e) {
    http_response_code(500);
    error_log('Database error in get_sessions.php: ' . $e->getMessage());
    echo json_encode(['error' => 'An internal error occurred. Please try again later.']);
}
