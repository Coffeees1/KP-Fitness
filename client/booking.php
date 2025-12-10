<?php
define('PAGE_TITLE', 'Book Classes');
require_once '../includes/config.php';

// Removed require_client() to allow guest booking

$userId = $_SESSION['UserID'] ?? null;
$isLoggedIn = is_logged_in();
$hasActiveMembership = false;
$onetimeMembershipCost = 0; // Cost for a single session for non-members

$feedback = [];

// Fetch onetime membership details
try {
    $stmt = $pdo->query("SELECT MembershipID, Cost FROM membership WHERE Type = 'onetime' AND IsActive = TRUE");
    $onetimePlan = $stmt->fetch();
    if ($onetimePlan) {
        $onetimeMembershipCost = $onetimePlan['Cost'];
    }
} catch (PDOException $e) {
    // Handle error or log it
    error_log("Error fetching onetime plan: " . $e->getMessage());
}

// Check if logged-in user has an active membership
if ($isLoggedIn) {
    try {
        $stmt = $pdo->prepare("
            SELECT u.MembershipID, p.Status as PaymentStatus
            FROM users u
            LEFT JOIN payments p ON u.UserID = p.UserID AND u.MembershipID = p.MembershipID
            WHERE u.UserID = ? AND p.Status = 'completed'
            ORDER BY p.PaymentDate DESC LIMIT 1
        ");
        $stmt->execute([$userId]);
        $userMembership = $stmt->fetch();
        if ($userMembership && $userMembership['MembershipID'] && $userMembership['PaymentStatus'] === 'completed') {
            $hasActiveMembership = true;
        }
    } catch (PDOException $e) {
        error_log("Error checking user membership: " . $e->getMessage());
    }
}


// --- Handle Actions ---

// Handle Booking
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['book_session'])) {
    $sessionId = intval($_POST['sessionId']);
    $guestFullName = sanitize_input($_POST['guestFullName'] ?? '');
    $guestEmail = sanitize_input($_POST['guestEmail'] ?? '');
    $guestPhone = sanitize_input($_POST['guestPhone'] ?? '');
    $isGuestBooking = isset($_POST['isGuestBooking']);

    // Check session capacity (already done in display, but re-validate)
    $stmt = $pdo->prepare("SELECT CurrentBookings, MaxCapacity FROM sessions s JOIN classes c ON s.ClassID = c.ClassID WHERE s.SessionID = ? FOR UPDATE");
    $stmt->execute([$sessionId]);
    $session = $stmt->fetch();
    if ($session['CurrentBookings'] >= $session['MaxCapacity']) {
        $feedback = ['type' => 'danger', 'message' => 'This class is already full.'];
    } elseif ($isGuestBooking && (empty($guestFullName) || empty($guestEmail) || !filter_var($guestEmail, FILTER_VALIDATE_EMAIL))) {
         $feedback = ['type' => 'danger', 'message' => 'Guest booking requires Full Name and a valid Email.'];
    } else {
        try {
            $pdo->beginTransaction();
            
            $bookingUserId = null; // Will store the actual UserID for the reservation
            $bookingStatus = 'booked'; // Default for members
            $paymentRecorded = false; // Flag for guest payment

            if ($isLoggedIn && $hasActiveMembership) {
                // Member booking
                $bookingUserId = $userId;
                // Check for duplicate booking
                $stmt = $pdo->prepare("SELECT ReservationID FROM reservations WHERE UserID = ? AND SessionID = ? AND Status != 'cancelled'");
                $stmt->execute([$bookingUserId, $sessionId]);
                if ($stmt->fetch()) {
                    $feedback = ['type' => 'warning', 'message' => 'You have already booked this session.'];
                }
            } elseif ($isGuestBooking) {
                // Guest booking (create a temporary user or link to 'onetime' logic)
                // For simplicity, we'll create a new "guest" user for each unique guest booking (can be refined)
                $stmt = $pdo->prepare("SELECT UserID FROM users WHERE Email = ? AND Role = 'guest'");
                $stmt->execute([$guestEmail]);
                $guestUser = $stmt->fetch();

                if (!$guestUser) {
                    $guestPassword = password_hash(uniqid(), PASSWORD_DEFAULT); // Auto-generate password
                    $stmt = $pdo->prepare("INSERT INTO users (FullName, Email, Phone, Password, Role, IsActive) VALUES (?, ?, ?, ?, 'guest', 1)");
                    $stmt->execute([$guestFullName, $guestEmail, $guestPhone, $guestPassword, 'guest', 1]);
                    $bookingUserId = $pdo->lastInsertId();
                } else {
                    $bookingUserId = $guestUser['UserID'];
                }
                
                // Record payment for guest booking (on-the-spot simulation)
                if ($onetimeMembershipCost > 0 && $onetimePlan) {
                    $stmt = $pdo->prepare("INSERT INTO payments (UserID, MembershipID, Amount, PaymentMethod, Status) VALUES (?, ?, ?, 'cash', 'completed')");
                    $stmt->execute([$bookingUserId, $onetimePlan['MembershipID'], $onetimeMembershipCost]);
                    $paymentRecorded = true;
                    $bookingStatus = 'paid_on_spot'; // Custom status for guest booking with immediate payment
                } else {
                    $feedback = ['type' => 'danger', 'message' => 'One-time membership cost not found. Cannot proceed with guest booking.'];
                }

            } else {
                // Not logged in and not explicitly guest booking, or logged in without membership and not guest booking
                $feedback = ['type' => 'warning', 'message' => 'You need an active membership or must book as a guest.'];
            }

            if (empty($feedback)) { // Proceed if no earlier feedback
                // Create reservation
                $stmt = $pdo->prepare("INSERT INTO reservations (UserID, SessionID, Status) VALUES (?, ?, ?)");
                $stmt->execute([$bookingUserId, $sessionId, $bookingStatus]);
                // Update count
                $stmt = $pdo->prepare("UPDATE sessions SET CurrentBookings = CurrentBookings + 1 WHERE SessionID = ?");
                $stmt->execute([$sessionId]);
                $pdo->commit();
                $feedback = ['type' => 'success', 'message' => 'Class booked successfully!'];
            } else {
                $pdo->rollBack(); // Rollback if any feedback was generated
            }

        } catch(PDOException $e) { 
            $pdo->rollBack(); 
            $feedback = ['type' => 'danger', 'message' => 'A database error occurred during booking: ' . $e->getMessage()]; 
        }
    }
}


// Handle Cancellation (logic remains the same)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancel_booking'])) {
    $reservationId = intval($_POST['reservationId']);
     try {
        $pdo->beginTransaction();
        $stmt = $pdo->prepare("SELECT SessionID FROM reservations WHERE ReservationID = ? AND UserID = ? AND Status != 'cancelled'");
        $stmt->execute([$reservationId, $userId]);
        $reservation = $stmt->fetch();
        if ($reservation) {
            $stmt = $pdo->prepare("UPDATE reservations SET Status = 'cancelled' WHERE ReservationID = ?");
            $stmt->execute([$reservationId]);
            $stmt = $pdo->prepare("UPDATE sessions SET CurrentBookings = GREATEST(0, CurrentBookings - 1) WHERE SessionID = ?");
            $stmt->execute([$reservation['SessionID']]);
            $pdo->commit();
            $feedback = ['type' => 'info', 'message' => 'Your booking has been cancelled.'];
        } else {
             $feedback = ['type' => 'danger', 'message' => 'Reservation not found or already cancelled.'];
        }
    } catch(PDOException $e) { $pdo->rollBack(); $feedback = ['type' => 'danger', 'message' => 'Could not cancel booking.']; }
}

// Handle Rating Submission (logic remains the same)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_rating'])) {
    $reservationId = intval($_POST['reservationId']);
    $rating = floatval($_POST['rating']);
    $comment = sanitize_input($_POST['comment']);
    if ($rating < 1 || $rating > 5) { $feedback = ['type' => 'danger', 'message' => 'Rating must be between 1 and 5.']; }
    else {
        try {
            $stmt = $pdo->prepare("UPDATE reservations SET Rating = ?, RatingComment = ?, Status = 'attended' WHERE ReservationID = ? AND UserID = ?");
            if ($stmt->execute([$rating, $comment, $reservationId, $userId])) {
                $feedback = ['type' => 'success', 'message' => 'Thank you for your rating!'];
            } else { $feedback = ['type' => 'danger', 'message' => 'Failed to submit rating.']; }
        } catch (PDOException $e) { $feedback = ['type' => 'danger', 'message' => 'Database error: ' . $e->getMessage()]; }
    }
}


// --- Fetch Data for Display ---
// Fetch available sessions (with DifficultyLevel)
$stmt = $pdo->prepare("
    SELECT s.*, c.ClassName, c.MaxCapacity, c.DifficultyLevel, u.FullName as TrainerName 
    FROM sessions s JOIN classes c ON s.ClassID = c.ClassID JOIN users u ON s.TrainerID = u.UserID
    WHERE s.SessionDate >= CURDATE() AND s.Status = 'scheduled'
    ORDER BY s.SessionDate, s.Time
");
$stmt->execute();
$availableSessions = $stmt->fetchAll();

// Fetch user's upcoming bookings
$myUpcomingBookings = [];
if ($isLoggedIn) {
    $stmt = $pdo->prepare("
        SELECT r.ReservationID, s.SessionDate, s.Time, c.ClassName, u.FullName as TrainerName, r.Status
        FROM reservations r JOIN sessions s ON r.SessionID = s.SessionID JOIN classes c ON s.ClassID = c.ClassID JOIN users u ON s.TrainerID = u.UserID
        WHERE r.UserID = ? AND r.Status != 'cancelled' AND s.SessionDate >= CURDATE()
        ORDER BY s.SessionDate, s.Time
    ");
    $stmt->execute([$userId]);
    $myUpcomingBookings = $stmt->fetchAll();
}

// Fetch user's past bookings that can be rated
$myPastBookingsToRate = [];
if ($isLoggedIn) {
    $stmt = $pdo->prepare("
        SELECT r.ReservationID, s.SessionDate, s.Time, c.ClassName, u.FullName as TrainerName, r.Rating
        FROM reservations r JOIN sessions s ON r.SessionID = s.SessionID JOIN classes c ON s.ClassID = c.ClassID JOIN users u ON s.TrainerID = u.UserID
        WHERE r.UserID = ? AND s.SessionDate < CURDATE() AND r.Status = 'booked' AND r.Rating IS NULL
        ORDER BY s.SessionDate DESC, s.Time DESC
    ");
    $stmt->execute([$userId]);
    $myPastBookingsToRate = $stmt->fetchAll();
}

// Fetch user's past rated bookings
$myPastRatedBookings = [];
if ($isLoggedIn) {
    $stmt = $pdo->prepare("
        SELECT r.ReservationID, s.SessionDate, s.Time, c.ClassName, u.FullName as TrainerName, r.Rating, r.RatingComment
        FROM reservations r JOIN sessions s ON r.SessionID = s.SessionID JOIN classes c ON s.ClassID = c.ClassID JOIN users u ON s.TrainerID = u.UserID
        WHERE r.UserID = ? AND s.SessionDate < CURDATE() AND r.Rating IS NOT NULL
        ORDER BY s.SessionDate DESC, s.Time DESC
    ");
    $stmt->execute([$userId]);
    $myPastRatedBookings = $stmt->fetchAll();
}


include 'includes/client_header.php';
?>

<div class="d-flex justify-content-between align-items-center pb-3 mb-4 border-bottom">
    <h1 class="h2">Class Booking</h1>
    <p class="lead text-body-secondary m-0">Browse and book your spot.</p>
</div>

<?php if (!empty($feedback)): ?>
    <div class="alert alert-<?php echo $feedback['type']; ?> alert-dismissible fade show" role="alert">
        <?php echo $feedback['message']; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<?php if ($isLoggedIn && !$hasActiveMembership): ?>
    <div class="alert alert-info border-info alert-dismissible fade show" role="alert">
        <h4 class="alert-heading">No Active Membership!</h4>
        <p>You currently do not have an active membership. You can purchase a plan from the <a href="membership.php" class="alert-link">Membership page</a> to book unlimited classes, or proceed with a guest booking for a single session.</p>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php elseif (!$isLoggedIn): ?>
     <div class="alert alert-info border-info alert-dismissible fade show" role="alert">
        <h4 class="alert-heading">Not Logged In!</h4>
        <p>You are not logged in. You can <a href="../login.php" class="alert-link">Log In</a> or <a href="../register.php" class="alert-link">Sign Up</a> for a membership to book classes. Alternatively, you can book a single session as a guest below.</p>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>


<?php if ($isLoggedIn && !empty($myUpcomingBookings)): ?>
    <!-- My Upcoming Bookings -->
    <div class="card text-bg-dark mb-4">
        <div class="card-header fw-bold">My Upcoming Bookings</div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-dark table-striped table-hover">
                    <thead>
                        <tr><th>Class</th><th>Date & Time</th><th>Trainer</th><th>Action</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($myUpcomingBookings as $booking): ?>
                                <tr>
                                    <td class="align-middle"><?php echo htmlspecialchars($booking['ClassName']); ?></td>
                                    <td class="align-middle"><?php echo format_date($booking['SessionDate']); ?> at <?php echo format_time($booking['Time']); ?></td>
                                    <td class="align-middle"><?php echo htmlspecialchars($booking['TrainerName']); ?></td>
                                    <td>
                                        <form action="booking.php" method="POST">
                                            <input type="hidden" name="reservationId" value="<?php echo $booking['ReservationID']; ?>">
                                            <button type="submit" name="cancel_booking" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure?');">Cancel</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
<?php endif; ?>

<?php if ($isLoggedIn && !empty($myPastBookingsToRate)): ?>
    <!-- Past Bookings to Rate -->
    <div class="card text-bg-dark mb-4">
        <div class="card-header fw-bold">Past Classes to Rate</div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-dark table-striped table-hover">
                    <thead>
                        <tr><th>Class</th><th>Date & Time</th><th>Trainer</th><th>Action</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($myPastBookingsToRate as $booking): ?>
                            <tr>
                                <td class="align-middle"><?php echo htmlspecialchars($booking['ClassName']); ?></td>
                                <td class="align-middle"><?php echo format_date($booking['SessionDate']); ?> at <?php echo format_time($booking['Time']); ?></td>
                                <td class="align-middle"><?php echo htmlspecialchars($booking['TrainerName']); ?></td>
                                <td>
                                    <button type="button" class="btn btn-warning btn-sm" data-bs-toggle="modal" data-bs-target="#ratingModal" 
                                            data-reservation-id="<?php echo $booking['ReservationID']; ?>" 
                                            data-class-name="<?php echo htmlspecialchars($booking['ClassName']); ?>">
                                        Rate Class
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
<?php endif; ?>

<?php if ($isLoggedIn && !empty($myPastRatedBookings)): ?>
    <!-- Past Rated Bookings -->
    <div class="card text-bg-dark mb-4">
        <div class="card-header fw-bold">My Past Ratings</div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-dark table-striped table-hover">
                    <thead>
                        <tr><th>Class</th><th>Date & Time</th><th>Trainer</th><th>Rating</th><th>Comment</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($myPastRatedBookings as $booking): ?>
                            <tr>
                                <td class="align-middle"><?php echo htmlspecialchars($booking['ClassName']); ?></td>
                                <td class="align-middle"><?php echo format_date($booking['SessionDate']); ?> at <?php echo format_time($booking['Time']); ?></td>
                                <td class="align-middle"><?php echo htmlspecialchars($booking['TrainerName']); ?></td>
                                <td class="align-middle"><?php echo htmlspecialchars(number_format($booking['Rating'], 1)); ?> <i class="fas fa-star text-warning"></i></td>
                                <td class="align-middle"><?php echo htmlspecialchars($booking['RatingComment'] ?? '-'); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
<?php endif; ?>


<!-- Available Classes -->
<div class="card text-bg-dark">
    <div class="card-header fw-bold">Available Classes</div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-dark table-striped table-hover">
                <thead>
                    <tr><th>Class</th><th>Level</th><th>Date & Time</th><th>Trainer</th><th>Availability</th><th>Action</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($availableSessions as $session): 
                        $is_full = $session['CurrentBookings'] >= $session['MaxCapacity'];
                        $percentage = $session['MaxCapacity'] > 0 ? ($session['CurrentBookings'] / $session['MaxCapacity']) * 100 : 0;
                        $level_color = $session['DifficultyLevel'] === 'beginner' ? 'success' : ($session['DifficultyLevel'] === 'intermediate' ? 'warning' : 'danger');
                    ?>
                        <tr>
                            <td class="align-middle"><?php echo htmlspecialchars($session['ClassName']); ?></td>
                            <td class="align-middle"><span class="badge text-bg-<?php echo $level_color; ?> text-capitalize"><?php echo htmlspecialchars($session['DifficultyLevel']); ?></span></td>
                            <td class="align-middle"><?php echo format_date($session['SessionDate']); ?> at <?php echo format_time($session['Time']); ?></td>
                            <td class="align-middle"><?php echo htmlspecialchars($session['TrainerName']); ?></td>
                            <td class="align-middle">
                                <div class="progress" style="height: 20px;" title="<?php echo $session['CurrentBookings'] . ' / ' . $session['MaxCapacity']; ?>">
                                    <div class="progress-bar bg-primary" role="progressbar" style="width: <?php echo $percentage; ?>%;" aria-valuenow="<?php echo $session['CurrentBookings']; ?>" aria-valuemin="0" aria-valuemax="<?php echo $session['MaxCapacity']; ?>">
                                        <?php echo $session['CurrentBookings'] . '/' . $session['MaxCapacity']; ?>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <form action="booking.php" method="POST">
                                    <input type="hidden" name="sessionId" value="<?php echo $session['SessionID']; ?>">
                                    <?php if ($isLoggedIn && $hasActiveMembership): ?>
                                        <button type="submit" name="book_session" class="btn btn-primary btn-sm" <?php echo $is_full ? 'disabled' : ''; ?>>
                                            <?php echo $is_full ? 'Full' : 'Book'; ?>
                                        </button>
                                    <?php elseif ($isLoggedIn && !$hasActiveMembership && $onetimeMembershipCost > 0): ?>
                                        <!-- Logged in but no membership -> offer guest booking for single session -->
                                        <button type="button" class="btn btn-info btn-sm" data-bs-toggle="modal" data-bs-target="#guestBookingModal" 
                                                data-session-id="<?php echo $session['SessionID']; ?>" 
                                                data-class-name="<?php echo htmlspecialchars($session['ClassName']); ?>"
                                                data-guest-name="<?php echo htmlspecialchars($_SESSION['FullName']); ?>"
                                                data-guest-email="<?php echo htmlspecialchars($_SESSION['Email']); ?>"
                                                <?php echo $is_full ? 'disabled' : ''; ?>>
                                            <?php echo $is_full ? 'Full' : 'Book as Guest (RM ' . number_format($onetimeMembershipCost, 2) . ')'; ?>
                                        </button>
                                    <?php elseif (!$isLoggedIn && $onetimeMembershipCost > 0): ?>
                                        <!-- Not logged in -> offer guest booking for single session -->
                                        <button type="button" class="btn btn-info btn-sm" data-bs-toggle="modal" data-bs-target="#guestBookingModal" 
                                                data-session-id="<?php echo $session['SessionID']; ?>" 
                                                data-class-name="<?php echo htmlspecialchars($session['ClassName']); ?>"
                                                <?php echo $is_full ? 'disabled' : ''; ?>>
                                            <?php echo $is_full ? 'Full' : 'Book as Guest (RM ' . number_format($onetimeMembershipCost, 2) . ')'; ?>
                                        </button>
                                    <?php else: ?>
                                        <button type="button" class="btn btn-secondary btn-sm" disabled>Login to Book</button>
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

<!-- Rating Modal -->
<div class="modal fade" id="ratingModal" tabindex="-1" aria-labelledby="ratingModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content text-bg-dark">
      <div class="modal-header">
        <h5 class="modal-title" id="ratingModalLabel">Rate Class: <span id="modalClassName" class="text-primary"></span></h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form action="booking.php" method="POST">
          <div class="modal-body">
            <input type="hidden" name="reservationId" id="modalReservationId">
            <div class="mb-3">
              <label for="rating" class="form-label">Rating (1-5)</label>
              <input type="number" class="form-control" id="rating" name="rating" min="1" max="5" step="0.5" required>
            </div>
            <div class="mb-3">
              <label for="comment" class="form-label">Comment (Optional)</label>
              <textarea class="form-control" id="comment" name="comment" rows="3"></textarea>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            <button type="submit" name="submit_rating" class="btn btn-primary">Submit Rating</button>
          </div>
      </form>
    </div>
  </div>
</div>

<!-- Guest Booking Modal -->
<div class="modal fade" id="guestBookingModal" tabindex="-1" aria-labelledby="guestBookingModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content text-bg-dark">
      <div class="modal-header">
        <h5 class="modal-title" id="guestBookingModalLabel">Book as Guest: <span id="modalGuestClassName" class="text-primary"></span></h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form action="booking.php" method="POST">
          <div class="modal-body">
            <input type="hidden" name="sessionId" id="modalGuestSessionId">
            <input type="hidden" name="isGuestBooking" value="1">
            <p>You are booking a single session as a guest. Cost: <span class="fw-bold text-primary">RM <?php echo number_format($onetimeMembershipCost, 2); ?></span> (Pay on Spot)</p>
            <div class="mb-3">
              <label for="guestFullName" class="form-label">Your Full Name</label>
              <input type="text" class="form-control" id="guestFullName" name="guestFullName" value="<?php echo htmlspecialchars($_SESSION['FullName'] ?? ''); ?>" required>
            </div>
            <div class="mb-3">
              <label for="guestEmail" class="form-label">Your Email</label>
              <input type="email" class="form-control" id="guestEmail" name="guestEmail" value="<?php echo htmlspecialchars($_SESSION['Email'] ?? ''); ?>" required>
            </div>
            <div class="mb-3">
              <label for="guestPhone" class="form-label">Your Phone (Optional)</label>
              <input type="tel" class="form-control" id="guestPhone" name="guestPhone" value="<?php echo htmlspecialchars($_SESSION['Phone'] ?? ''); ?>">
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            <button type="submit" name="book_session" class="btn btn-primary">Confirm Guest Booking</button>
          </div>
      </form>
    </div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const ratingModal = document.getElementById('ratingModal');
    ratingModal.addEventListener('show.bs.modal', function (event) {
        const button = event.relatedTarget; // Button that triggered the modal
        const reservationId = button.getAttribute('data-reservation-id');
        const className = button.getAttribute('data-class-name');
        
        const modalTitle = ratingModal.querySelector('#modalClassName');
        const modalReservationId = ratingModal.querySelector('#modalReservationId');
        
        modalTitle.textContent = className;
        modalReservationId.value = reservationId;
    });

    const guestBookingModal = document.getElementById('guestBookingModal');
    guestBookingModal.addEventListener('show.bs.modal', function (event) {
        const button = event.relatedTarget;
        const sessionId = button.getAttribute('data-session-id');
        const className = button.getAttribute('data-class-name');
        const guestName = button.getAttribute('data-guest-name');
        const guestEmail = button.getAttribute('data-guest-email');
        const guestPhone = button.getAttribute('data-guest-phone');
        
        guestBookingModal.querySelector('#modalGuestClassName').textContent = className;
        guestBookingModal.querySelector('#modalGuestSessionId').value = sessionId;
        if (guestName) {
            guestBookingModal.querySelector('#guestFullName').value = guestName;
        }
        if (guestEmail) {
            guestBookingModal.querySelector('#guestEmail').value = guestEmail;
        }
        if (guestPhone) {
            guestBookingModal.querySelector('#guestPhone').value = guestPhone;
        }
    });
});
</script>

<?php 
$todos_list[10]['status'] = 'in_progress'; // Membership refactor is in progress
include 'includes/client_footer.php'; 
?>