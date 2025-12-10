<?php
define('PAGE_TITLE', 'Client Dashboard');
require_once '../includes/config.php';
require_client();

$userId = $_SESSION['UserID'];
try {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE UserID = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch();
    
    // --- Replace "N/A" with a more user-friendly placeholder ---
    $height = $user['Height'] ?? '-';
    $weight = $user['Weight'] ?? '-';
    $bmi = ($height !== '-' && $weight !== '-') ? calculate_bmi($user['Height'], $user['Weight']) : '-';
    $bmiCategory = ($bmi !== '-') ? get_bmi_category($bmi) : 'Not Calculated';
    
    $stmt = $pdo->prepare("SELECT s.SessionDate, s.Time, c.ClassName FROM reservations r JOIN sessions s ON r.SessionID = s.SessionID JOIN classes c ON s.ClassID = c.ClassID WHERE r.UserID = ? AND r.Status = 'booked' AND s.SessionDate >= CURDATE() ORDER BY s.SessionDate, s.Time LIMIT 5");
    $stmt->execute([$userId]);
    $upcomingBookings = $stmt->fetchAll();

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM workout_plans WHERE UserID = ?");
    $stmt->execute([$userId]);
    $workoutPlanCount = $stmt->fetchColumn();

    $stmt = $pdo->prepare("SELECT m.Type FROM users u LEFT JOIN membership m ON u.MembershipID = m.MembershipID WHERE u.UserID = ?");
    $stmt->execute([$userId]);
    $membership = $stmt->fetch();

} catch (PDOException $e) {
    $feedback = ['type' => 'danger', 'message' => 'Could not fetch dashboard data: ' . $e->getMessage()];
    $user = []; $upcomingBookings = []; $workoutPlanCount = 0; $membership = null;
    $height = $weight = $bmi = $bmiCategory = '-';
}

                            <li class="list-group-item bg-transparent d-flex justify-content-between">
                                <span><?php echo htmlspecialchars($booking['ClassName']); ?></span>
                                <span class="text-body-secondary"><?php echo format_date($booking['SessionDate']); ?> at <?php echo format_time($booking['Time']); ?></span>
                            </li>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </div>