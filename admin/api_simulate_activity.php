<?php
require_once '../includes/config.php';
// Ideally require_admin(), but for a heartbeat called by JS on admin pages, session check is enough.
if (!is_admin()) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

header('Content-Type: application/json');

try {
    // 1. Get 10 Random Clients
    $stmt = $pdo->query("SELECT UserID FROM users WHERE Role = 'client' ORDER BY RAND() LIMIT 10");
    $clients = $stmt->fetchAll(PDO::FETCH_COLUMN);

    // 2. Get Random Future Sessions
    // We want sessions that are NOT full
    $stmt = $pdo->query("
        SELECT SessionID, (SELECT MaxCapacity FROM activities a WHERE a.ClassID = s.ClassID) as MaxCapacity, CurrentBookings 
        FROM sessions s 
        WHERE SessionDate >= CURDATE() AND Status = 'scheduled' 
        HAVING CurrentBookings < MaxCapacity
        ORDER BY RAND() LIMIT 10
    ");
    $sessions = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $bookingsCreated = 0;

    if ($sessions && $clients) {
        $insert = $pdo->prepare("INSERT INTO reservations (UserID, SessionID, Status, BookingDate) VALUES (?, ?, 'booked', NOW())");
        $update = $pdo->prepare("UPDATE sessions SET CurrentBookings = CurrentBookings + 1 WHERE SessionID = ?");
        
        foreach ($clients as $index => $clientId) {
            if (!isset($sessions[$index])) break; // Run out of sessions
            $session = $sessions[$index];
            
            // Check duplicate
            $check = $pdo->prepare("SELECT ReservationID FROM reservations WHERE UserID = ? AND SessionID = ?");
            $check->execute([$clientId, $session['SessionID']]);
            if ($check->fetch()) continue;

            try {
                $insert->execute([$clientId, $session['SessionID']]);
                $update->execute([$session['SessionID']]);
                $bookingsCreated++;
            } catch (Exception $e) {
                // Ignore constraint errors
            }
        }
    }

    echo json_encode([
        'success' => true,
        'message' => "Simulated $bookingsCreated new bookings.",
        'timestamp' => date('Y-m-d H:i:s')
    ]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
