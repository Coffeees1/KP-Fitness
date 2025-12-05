<?php
require_once '../includes/config.php';
require_client();

$userId = $_SESSION['UserID'];
$error = '';
$success = '';

// Handle booking
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['book'])) {
    $sessionId = intval($_POST['sessionId']);
    
    try {
        // Check if user has active membership
        $stmt = $pdo->prepare("SELECT m.Type, p.Status FROM users u 
                              LEFT JOIN membership m ON u.MembershipID = m.MembershipID 
                              LEFT JOIN payments p ON p.UserID = u.UserID AND p.MembershipID = m.MembershipID 
                              WHERE u.UserID = ?");
        $stmt->execute([$userId]);
        $membership = $stmt->fetch();
        
        if (!$membership || $membership['Status'] !== 'completed') {
            $error = 'You need an active membership to book classes. Please purchase a membership first.';
        } else {
            // Check if session exists and has capacity
            $stmt = $pdo->prepare("SELECT s.*, c.ClassName, COUNT(r.ReservationID) as CurrentBookings 
                                  FROM sessions s 
                                  JOIN classes c ON s.ClassID = c.ClassID 
                                  LEFT JOIN reservations r ON s.SessionID = r.SessionID 
                                  WHERE s.SessionID = ? 
                                  GROUP BY s.SessionID");
            $stmt->execute([$sessionId]);
            $session = $stmt->fetch();
            
            if ($session) {
                // Check capacity
                if ($session['CurrentBookings'] >= $session['MaxCapacity']) {
                    $error = 'This class is fully booked. Please choose another session.';
                } else {
                    // Check if already booked
                    $stmt = $pdo->prepare("SELECT ReservationID FROM reservations WHERE UserID = ? AND SessionID = ?");
                    $stmt->execute([$userId, $sessionId]);
                    if ($stmt->fetch()) {
                        $error = 'You have already booked this class.';
                    } else {
                        // Book the class
                        $stmt = $pdo->prepare("INSERT INTO reservations (UserID, SessionID) VALUES (?, ?)");
                        if ($stmt->execute([$userId, $sessionId])) {
                            // Update session booking count
                            $stmt = $pdo->prepare("UPDATE sessions SET CurrentBookings = CurrentBookings + 1 WHERE SessionID = ?");
                            $stmt->execute([$sessionId]);
                            
                            $success = 'Class booked successfully! Check your dashboard for details.';
                            create_notification($userId, 'Class Booked!', 'You have successfully booked ' . $session['ClassName'] . ' on ' . date('M d', strtotime($session['SessionDate'])), 'success');
                        } else {
                            $error = 'Failed to book class. Please try again.';
                        }
                    }
                }
            } else {
                $error = 'Invalid class session selected.';
            }
        }
    } catch (PDOException $e) {
        $error = 'An error occurred. Please try again.';
    }
}

// Handle cancellation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancel'])) {
    $reservationId = intval($_POST['reservationId']);
    
    try {
        // Get reservation details
        $stmt = $pdo->prepare("SELECT r.*, s.SessionDate FROM reservations r JOIN sessions s ON r.SessionID = s.SessionID WHERE r.ReservationID = ? AND r.UserID = ?");
        $stmt->execute([$reservationId, $userId]);
        $reservation = $stmt->fetch();
        
        if ($reservation) {
            // Check if cancellation is allowed (24 hours before)
            $sessionDate = new DateTime($reservation['SessionDate']);
            $now = new DateTime();
            $interval = $now->diff($sessionDate);
            
            if ($interval->days < 1 && $sessionDate > $now) {
                $error = 'Cancellation must be done at least 24 hours before the class.';
            } else {
                // Cancel reservation
                $stmt = $pdo->prepare("UPDATE reservations SET Status = 'cancelled' WHERE ReservationID = ?");
                if ($stmt->execute([$reservationId])) {
                    // Update session booking count
                    $stmt = $pdo->prepare("UPDATE sessions SET CurrentBookings = CurrentBookings - 1 WHERE SessionID = ?");
                    $stmt->execute([$reservation['SessionID']]);
                    
                    $success = 'Class cancelled successfully.';
                    create_notification($userId, 'Class Cancelled', 'Your class reservation has been cancelled.', 'info');
                } else {
                    $error = 'Failed to cancel reservation. Please try again.';
                }
            }
        } else {
            $error = 'Invalid reservation.';
        }
    } catch (PDOException $e) {
        $error = 'An error occurred. Please try again.';
    }
}

// Get available classes with filters
$classFilter = isset($_GET['classFilter']) ? sanitize_input($_GET['classFilter']) : '';
$dateFilter = isset($_GET['dateFilter']) ? sanitize_input($_GET['dateFilter']) : '';
$trainerFilter = isset($_GET['trainerFilter']) ? sanitize_input($_GET['trainerFilter']) : '';

// Build query with filters
$query = "SELECT s.*, c.ClassName, c.Description, c.DifficultyLevel, u.FullName as TrainerName, 
                 COUNT(r.ReservationID) as CurrentBookings
          FROM sessions s 
          JOIN classes c ON s.ClassID = c.ClassID 
          JOIN users u ON s.TrainerID = u.UserID 
          LEFT JOIN reservations r ON s.SessionID = r.SessionID 
          WHERE s.SessionDate >= CURRENT_DATE AND s.Status = 'scheduled'";

$params = [];

if ($classFilter) {
    $query .= " AND c.ClassName LIKE ?";
    $params[] = "%$classFilter%";
}

if ($dateFilter) {
    $query .= " AND s.SessionDate = ?";
    $params[] = $dateFilter;
}

if ($trainerFilter) {
    $query .= " AND u.FullName LIKE ?";
    $params[] = "%$trainerFilter%";
}

$query .= " GROUP BY s.SessionID ORDER BY s.SessionDate, s.Time";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$availableSessions = $stmt->fetchAll();

// Get user's current bookings
$stmt = $pdo->prepare("SELECT r.*, s.SessionDate, s.Time, s.Room, c.ClassName, u.FullName as TrainerName 
                      FROM reservations r 
                      JOIN sessions s ON r.SessionID = s.SessionID 
                      JOIN classes c ON s.ClassID = c.ClassID 
                      JOIN users u ON s.TrainerID = u.UserID 
                      WHERE r.UserID = ? AND s.SessionDate >= CURRENT_DATE AND r.Status = 'booked' 
                      ORDER BY s.SessionDate, s.Time");
$stmt->execute([$userId]);
$myBookings = $stmt->fetchAll();

// Get popular classes
$stmt = $pdo->prepare("SELECT c.ClassName, COUNT(r.ReservationID) as booking_count 
                      FROM classes c 
                      JOIN sessions s ON c.ClassID = s.ClassID 
                      JOIN reservations r ON s.SessionID = r.SessionID 
                      WHERE s.SessionDate >= DATE_SUB(CURRENT_DATE, INTERVAL 30 DAY) 
                      GROUP BY c.ClassID 
                      ORDER BY booking_count DESC LIMIT 5");
$stmt->execute();
$popularClasses = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book Classes - <?php echo SITE_NAME; ?></title>
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
            padding: 2rem 0;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 2rem;
        }

        .page-header {
            text-align: center;
            margin-bottom: 3rem;
        }

        .page-title {
            font-size: 2.5rem;
            font-weight: 800;
            color: #ff6b00;
            margin-bottom: 1rem;
        }

        .page-subtitle {
            font-size: 1.2rem;
            color: #cccccc;
            max-width: 600px;
            margin: 0 auto;
        }

        .content-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 3rem;
            margin-bottom: 3rem;
        }

        .card {
            background: rgba(45, 45, 45, 0.8);
            border: 1px solid rgba(255, 107, 0, 0.2);
            border-radius: 12px;
            padding: 2rem;
        }

        .card-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: #ff6b00;
            margin-bottom: 1.5rem;
            text-align: center;
        }

        .filters {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }

        .form-input {
            width: 100%;
            padding: 1rem;
            background: rgba(26, 26, 26, 0.9);
            border: 2px solid rgba(255, 107, 0, 0.2);
            border-radius: 8px;
            color: #ffffff;
            font-size: 1rem;
            transition: all 0.3s ease;
        }

        .form-input:focus {
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
            justify-content: center;
            gap: 0.5rem;
            font-size: 0.9rem;
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

        .btn-danger {
            background: rgba(220, 53, 69, 0.8);
            color: #ffffff;
            border: 2px solid rgba(220, 53, 69, 0.5);
        }

        .btn-danger:hover {
            background: #dc3545;
            box-shadow: 0 8px 25px rgba(220, 53, 69, 0.3);
        }

        .btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none !important;
        }

        .classes-grid {
            display: grid;
            gap: 1rem;
            max-height: 600px;
            overflow-y: auto;
        }

        .class-card {
            background: rgba(26, 26, 26, 0.5);
            border: 1px solid rgba(255, 107, 0, 0.2);
            border-radius: 8px;
            padding: 1.5rem;
            transition: all 0.3s ease;
        }

        .class-card:hover {
            border-color: #ff6b00;
            transform: translateY(-2px);
        }

        .class-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 1rem;
        }

        .class-name {
            font-size: 1.2rem;
            font-weight: 600;
            color: #ff6b00;
        }

        .difficulty-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }

        .difficulty-beginner {
            background: rgba(40, 167, 69, 0.2);
            color: #51cf66;
        }

        .difficulty-intermediate {
            background: rgba(255, 193, 7, 0.2);
            color: #ffc107;
        }

        .difficulty-advanced {
            background: rgba(220, 53, 69, 0.2);
            color: #ff6b6b;
        }

        .class-details {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 1rem;
            margin-bottom: 1rem;
        }

        .detail-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: #cccccc;
            font-size: 0.9rem;
        }

        .detail-item i {
            color: #ff6b00;
            width: 16px;
        }

        .capacity-bar {
            background: rgba(255, 107, 0, 0.2);
            height: 6px;
            border-radius: 3px;
            margin-bottom: 1rem;
            overflow: hidden;
        }

        .capacity-fill {
            background: #ff6b00;
            height: 100%;
            border-radius: 3px;
            transition: width 0.3s ease;
        }

        .capacity-text {
            font-size: 0.8rem;
            color: #888888;
            text-align: center;
            margin-bottom: 1rem;
        }

        .class-actions {
            display: flex;
            gap: 0.5rem;
            justify-content: space-between;
            align-items: center;
        }

        .popular-classes {
            margin-bottom: 2rem;
        }

        .popular-list {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
        }

        .popular-item {
            background: rgba(255, 107, 0, 0.2);
            color: #ff6b00;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }

        .my-bookings {
            max-height: 600px;
            overflow-y: auto;
        }

        .booking-card {
            background: rgba(26, 26, 26, 0.5);
            border: 1px solid rgba(255, 107, 0, 0.2);
            border-radius: 8px;
            padding: 1.5rem;
            margin-bottom: 1rem;
        }

        .booking-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 1rem;
        }

        .booking-class {
            font-size: 1.1rem;
            font-weight: 600;
            color: #ff6b00;
        }

        .booking-status {
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }

        .status-booked {
            background: rgba(40, 167, 69, 0.2);
            color: #51cf66;
        }

        .booking-details {
            color: #cccccc;
            font-size: 0.9rem;
            margin-bottom: 1rem;
        }

        .booking-actions {
            display: flex;
            gap: 0.5rem;
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

        .alert-success {
            background: rgba(40, 167, 69, 0.1);
            border: 1px solid rgba(40, 167, 69, 0.3);
            color: #51cf66;
        }

        .alert-info {
            background: rgba(23, 162, 184, 0.1);
            border: 1px solid rgba(23, 162, 184, 0.3);
            color: #17a2b8;
        }

        .no-classes {
            text-align: center;
            padding: 3rem 1rem;
            color: #888888;
        }

        .no-classes i {
            font-size: 3rem;
            margin-bottom: 1rem;
            color: #ff6b00;
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
            .content-grid {
                grid-template-columns: 1fr;
                gap: 2rem;
            }

            .page-title {
                font-size: 2rem;
            }

            .filters {
                grid-template-columns: 1fr;
            }

            .class-details {
                grid-template-columns: 1fr;
            }

            .class-actions {
                flex-direction: column;
                align-items: stretch;
            }
        }
    </style>
</head>
<body>
    <?php include '../includes/header.php'; ?>
    
    <div class="container">
        <div class="page-header">
            <h1 class="page-title">
                <i class="fas fa-calendar-alt"></i> Book Classes
            </h1>
            <p class="page-subtitle">
                Discover and book your favorite fitness classes with our expert trainers.
            </p>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success); ?>
            </div>
        <?php endif; ?>

        <?php if (!isset($membership) || $membership['Status'] !== 'completed'): ?>
            <div class="alert alert-info">
                <i class="fas fa-info-circle"></i> You need an active membership to book classes. 
                <a href="membership.php" style="color: #ff6b00; text-decoration: none;">Purchase a membership here</a>.
            </div>
        <?php endif; ?>

        <div class="content-grid">
            <!-- Available Classes -->
            <div class="card">
                <h3 class="card-title">Available Classes</h3>
                
                <!-- Popular Classes -->
                <?php if (count($popularClasses) > 0): ?>
                    <div class="popular-classes">
                        <h4 style="color: #ff6b00; margin-bottom: 1rem;">🔥 Popular Classes</h4>
                        <div class="popular-list">
                            <?php foreach ($popularClasses as $class): ?>
                                <div class="popular-item"><?php echo htmlspecialchars($class['ClassName']); ?></div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
                
                <!-- Filters -->
                <form method="GET" action="booking.php" class="filters">
                    <input type="text" name="classFilter" class="form-input" placeholder="Filter by class name" 
                           value="<?php echo htmlspecialchars($classFilter); ?>">
                    <input type="date" name="dateFilter" class="form-input" 
                           value="<?php echo htmlspecialchars($dateFilter); ?>">
                    <input type="text" name="trainerFilter" class="form-input" placeholder="Filter by trainer" 
                           value="<?php echo htmlspecialchars($trainerFilter); ?>">
                    <button type="submit" class="btn btn-secondary">
                        <i class="fas fa-search"></i>
                        Filter
                    </button>
                </form>
                
                <!-- Classes List -->
                <div class="classes-grid">
                    <?php if (count($availableSessions) > 0): ?>
                        <?php foreach ($availableSessions as $session): ?>
                            <div class="class-card">
                                <div class="class-header">
                                    <div class="class-name"><?php echo htmlspecialchars($session['ClassName']); ?></div>
                                    <div class="difficulty-badge difficulty-<?php echo $session['DifficultyLevel']; ?>">
                                        <?php echo ucfirst($session['DifficultyLevel']); ?>
                                    </div>
                                </div>
                                
                                <div class="class-details">
                                    <div class="detail-item">
                                        <i class="fas fa-calendar"></i>
                                        <span><?php echo format_date($session['SessionDate']); ?></span>
                                    </div>
                                    <div class="detail-item">
                                        <i class="fas fa-clock"></i>
                                        <span><?php echo format_time($session['Time']); ?></span>
                                    </div>
                                    <div class="detail-item">
                                        <i class="fas fa-user-tie"></i>
                                        <span><?php echo htmlspecialchars($session['TrainerName']); ?></span>
                                    </div>
                                    <div class="detail-item">
                                        <i class="fas fa-door-open"></i>
                                        <span>Room <?php echo htmlspecialchars($session['Room']); ?></span>
                                    </div>
                                </div>
                                
                                <div class="capacity-bar">
                                    <div class="capacity-fill" style="width: <?php echo ($session['CurrentBookings'] / $session['MaxCapacity']) * 100; ?>%"></div>
                                </div>
                                <div class="capacity-text">
                                    <?php echo $session['CurrentBookings']; ?> / <?php echo $session['MaxCapacity']; ?> spots filled
                                </div>
                                
                                <div class="class-actions">
                                    <form method="POST" action="booking.php" style="flex: 1;">
                                        <input type="hidden" name="sessionId" value="<?php echo $session['SessionID']; ?>">
                                        <input type="hidden" name="book" value="1">
                                        <button type="submit" class="btn btn-primary" 
                                                <?php echo $session['CurrentBookings'] >= $session['MaxCapacity'] || (!isset($membership) || $membership['Status'] !== 'completed') ? 'disabled' : ''; ?>>
                                            <i class="fas fa-calendar-plus"></i>
                                            Book Class
                                        </button>
                                    </form>
                                    <button class="btn btn-secondary" onclick="showClassDetails(<?php echo $session['SessionID']; ?>")">
                                        <i class="fas fa-info-circle"></i>
                                        Details
                                    </button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="no-classes">
                            <i class="fas fa-calendar-times"></i>
                            <h3>No classes available</h3>
                            <p>Try adjusting your filters or check back later for new classes.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- My Bookings -->
            <div class="card">
                <h3 class="card-title">My Bookings</h3>
                
                <div class="my-bookings">
                    <?php if (count($myBookings) > 0): ?>
                        <?php foreach ($myBookings as $booking): ?>
                            <div class="booking-card">
                                <div class="booking-header">
                                    <div class="booking-class"><?php echo htmlspecialchars($booking['ClassName']); ?></div>
                                    <div class="booking-status status-booked">Booked</div>
                                </div>
                                
                                <div class="booking-details">
                                    <div class="detail-item">
                                        <i class="fas fa-calendar"></i>
                                        <span><?php echo format_date($booking['SessionDate']); ?> at <?php echo format_time($booking['Time']); ?></span>
                                    </div>
                                    <div class="detail-item">
                                        <i class="fas fa-user-tie"></i>
                                        <span>Trainer: <?php echo htmlspecialchars($booking['TrainerName']); ?></span>
                                    </div>
                                    <div class="detail-item">
                                        <i class="fas fa-door-open"></i>
                                        <span>Room: <?php echo htmlspecialchars($booking['Room']); ?></span>
                                    </div>
                                </div>
                                
                                <div class="booking-actions">
                                    <form method="POST" action="booking.php" style="display: inline;">
                                        <input type="hidden" name="reservationId" value="<?php echo $booking['ReservationID']; ?>">
                                        <input type="hidden" name="cancel" value="1">
                                        <button type="submit" class="btn btn-danger" 
                                                onclick="return confirm('Are you sure you want to cancel this booking?')">
                                            <i class="fas fa-times"></i>
                                            Cancel
                                        </button>
                                    </form>
                                    <button class="btn btn-secondary" onclick="showBookingDetails(<?php echo $booking['ReservationID']; ?>")">
                                        <i class="fas fa-eye"></i>
                                        View
                                    </button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="no-classes">
                            <i class="fas fa-calendar-plus"></i>
                            <h3>No bookings yet</h3>
                            <p>Book your first class from the available classes section!</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script>
        function showClassDetails(sessionId) {
            alert('Class details - Session ID: ' + sessionId);
        }

        function showBookingDetails(bookingId) {
            alert('Booking details - Booking ID: ' + bookingId);
        }

        // Form submission handling
        document.querySelectorAll('form').forEach(form => {
            form.addEventListener('submit', function(e) {
                const btn = this.querySelector('button[type="submit"]');
                if (btn && !btn.disabled) {
                    const originalContent = btn.innerHTML;
                    btn.disabled = true;
                    btn.innerHTML = '<div class="loading"></div> Processing...';
                    
                    setTimeout(() => {
                        btn.innerHTML = originalContent;
                        btn.disabled = false;
                    }, 2000);
                }
            });
        });
    </script>
</body>
</html>