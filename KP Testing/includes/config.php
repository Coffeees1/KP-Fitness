<?php
/**
 * KP Fitness - Main Configuration File
 * Combined and Optimized Version
 * 
 * This file contains all core configuration settings, database connection,
 * CSRF protection, helper functions, and session management utilities.
 */

// --- DATABASE CONFIGURATION ---
require_once 'config.db.php';

// --- SITE CONFIGURATION ---
define('SITE_NAME', 'KP Fitness');
define('SITE_URL', 'http://localhost/KP-Fitness'); // Adjust if your path is different

// --- SESSION MANAGEMENT ---
// Start the session if it's not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
    // Generate a CSRF token if one doesn't exist
    if (empty($_SESSION['csrf_token'])) {
        generate_csrf_token();
    }
}

// --- CSRF PROTECTION ---

/**
 * Generates a new CSRF token and stores it in the session.
 */
function generate_csrf_token() {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

/**
 * Validates a CSRF token from a form submission.
 * Dies with a fatal error if the token is invalid.
 * @param string $token The token from the form.
 */
function validate_csrf_token($token) {
    if (!isset($token) || !hash_equals($_SESSION['csrf_token'], $token)) {
        die('CSRF validation failed.');
    }
    // Token is valid - no need to regenerate immediately as it can be reused within session
}

/**
 * Gets the current CSRF token.
 * @return string The CSRF token.
 */
function get_csrf_token() {
    return $_SESSION['csrf_token'];
}

// --- DATABASE CONNECTION (PDO) ---
try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch(PDOException $e){
    die("ERROR: Could not connect to database. " . $e->getMessage());
}

// ============================================================================
// CORE HELPER FUNCTIONS
// ============================================================================

/**
 * Sanitizes user input to prevent XSS attacks.
 * @param string $data The input data to sanitize.
 * @return string The sanitized data.
 */
function sanitize_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

/**
 * Redirects the user to a specified URL.
 * @param string $url The URL to redirect to.
 */
function redirect($url) {
    header("Location: " . $url);
    exit();
}

// ============================================================================
// AUTHENTICATION & AUTHORIZATION
// ============================================================================

/**
 * Checks if a user is logged in.
 * @return bool True if the user is logged in, false otherwise.
 */
function is_logged_in() {
    return isset($_SESSION['UserID']);
}

/**
 * Gets the role of the logged-in user.
 * @return string|null The user's role or null if not logged in.
 */
function get_user_role() {
    return is_logged_in() ? $_SESSION['Role'] : null;
}

/**
 * Checks if the logged-in user is an admin.
 * @return bool True if the user is an admin, false otherwise.
 */
function is_admin() {
    return get_user_role() === 'admin';
}

/**
 * Checks if the logged-in user is a trainer.
 * @return bool True if the user is a trainer, false otherwise.
 */
function is_trainer() {
    return get_user_role() === 'trainer';
}

/**
 * Checks if the logged-in user is a client.
 * @return bool True if the user is a client, false otherwise.
 */
function is_client() {
    return get_user_role() === 'client';
}

/**
 * Requires admin access. Redirects to login if not admin.
 */
function require_admin() {
    if (!is_admin()) {
        redirect(SITE_URL . '/login.php');
    }
}

/**
 * Requires trainer access. Redirects to login if not trainer.
 */
function require_trainer() {
    if (!is_trainer()) {
        redirect(SITE_URL . '/login.php');
    }
}

/**
 * Requires client access. Redirects to login if not client.
 */
function require_client() {
    if (!is_client()) {
        redirect(SITE_URL . '/login.php');
    }
}

// ============================================================================
// NOTIFICATION SYSTEM
// ============================================================================

/**
 * Creates a notification for a user.
 * @param int $userId The ID of the user to notify.
 * @param string $title The title of the notification.
 * @param string $message The notification message.
 * @param string $type The type of notification (info, warning, success, error).
 * @return bool True on success, false on failure.
 */
function create_notification($userId, $title, $message, $type = 'info') {
    global $pdo;
    try {
        $stmt = $pdo->prepare("INSERT INTO notifications (UserID, Title, Message, Type) VALUES (?, ?, ?, ?)");
        $stmt->execute([$userId, $title, $message, $type]);
        return true;
    } catch (PDOException $e) {
        error_log("Failed to create notification: " . $e->getMessage());
        return false;
    }
}

/**
 * Gets the count of unread notifications for a user.
 * @param int $userId The ID of the user.
 * @return int The number of unread notifications.
 */
function get_unread_notifications_count($userId) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE UserID = ? AND IsRead = 0");
        $stmt->execute([$userId]);
        return (int)$stmt->fetchColumn();
    } catch (PDOException $e) {
        error_log("Failed to get unread notifications count: " . $e->getMessage());
        return 0;
    }
}

/**
 * Gets all notifications for a user (limited to most recent 10).
 * @param int $userId The ID of the user.
 * @param int $limit Maximum number of notifications to retrieve.
 * @return array An array of notifications.
 */
function get_notifications($userId, $limit = 10) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("SELECT * FROM notifications WHERE UserID = ? ORDER BY CreatedAt DESC LIMIT ?");
        $stmt->execute([$userId, $limit]);
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log("Failed to get notifications: " . $e->getMessage());
        return [];
    }
}

/**
 * Marks a notification as read.
 * @param int $notificationId The ID of the notification.
 * @param int $userId The ID of the user (for security).
 * @return bool True on success, false on failure.
 */
function mark_notification_read($notificationId, $userId) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("UPDATE notifications SET IsRead = 1 WHERE NotificationID = ? AND UserID = ?");
        $stmt->execute([$notificationId, $userId]);
        return true;
    } catch (PDOException $e) {
        error_log("Failed to mark notification as read: " . $e->getMessage());
        return false;
    }
}

// ============================================================================
// HEALTH & FITNESS CALCULATIONS
// ============================================================================

/**
 * Calculates Body Mass Index (BMI).
 * @param int|null $height Height in cm.
 * @param int|null $weight Weight in kg.
 * @return string The calculated BMI or 'N/A'.
 */
function calculate_bmi($height, $weight) {
    if ($height > 0 && $weight > 0) {
        $heightInMeters = $height / 100;
        $bmi = $weight / ($heightInMeters * $heightInMeters);
        return number_format($bmi, 1);
    }
    return 'N/A';
}

/**
 * Gets the BMI category based on the BMI value.
 * @param float|string $bmi The BMI value.
 * @return string The BMI category.
 */
function get_bmi_category($bmi) {
    if (!is_numeric($bmi)) return 'N/A';
    
    $bmi = (float)$bmi;
    
    if ($bmi < 18.5) {
        return 'Underweight';
    } elseif ($bmi >= 18.5 && $bmi <= 24.9) {
        return 'Normal weight';
    } elseif ($bmi >= 25 && $bmi <= 29.9) {
        return 'Overweight';
    } else {
        return 'Obesity';
    }
}

/**
 * Gets the BMI color class for UI styling.
 * @param float|string $bmi The BMI value.
 * @return string The CSS class name.
 */
function get_bmi_color_class($bmi) {
    if (!is_numeric($bmi)) return 'secondary';
    
    $bmi = (float)$bmi;
    
    if ($bmi < 18.5) {
        return 'info'; // Underweight - blue
    } elseif ($bmi >= 18.5 && $bmi <= 24.9) {
        return 'success'; // Normal - green
    } elseif ($bmi >= 25 && $bmi <= 29.9) {
        return 'warning'; // Overweight - yellow
    } else {
        return 'danger'; // Obesity - red
    }
}

// ============================================================================
// FORMATTING FUNCTIONS
// ============================================================================

/**
 * Formats a date string to d/m/Y format.
 * @param string $date The date string to format.
 * @return string The formatted date.
 */
function format_date($date) {
    if (empty($date)) return 'N/A';
    return date('d/m/Y', strtotime($date));
}

/**
 * Formats a datetime string to d/m/Y H:i format.
 * @param string $datetime The datetime string to format.
 * @return string The formatted datetime.
 */
function format_datetime($datetime) {
    if (empty($datetime)) return 'N/A';
    return date('d/m/Y g:i A', strtotime($datetime));
}

/**
 * Formats a time string to 12-hour format.
 * @param string $time The time string to format.
 * @return string The formatted time.
 */
function format_time($time) {
    if (empty($time)) return 'N/A';
    return date('g:i A', strtotime($time));
}

/**
 * Formats a number as currency (Malaysian Ringgit).
 * @param float $number The number to format.
 * @return string The formatted currency string.
 */
function format_currency($number) {
    return 'RM ' . number_format($number, 2);
}

/**
 * Formats a phone number for display.
 * @param string $phone The phone number to format.
 * @return string The formatted phone number.
 */
function format_phone($phone) {
    if (empty($phone)) return 'N/A';
    // Remove any non-numeric characters
    $phone = preg_replace('/[^0-9]/', '', $phone);
    // Format as XXX-XXXXXXX
    if (strlen($phone) == 10) {
        return substr($phone, 0, 3) . '-' . substr($phone, 3);
    }
    return $phone;
}

// ============================================================================
// SESSION CODE & LIVE SESSION MANAGEMENT
// ============================================================================

/**
 * Checks if a session is currently live (within check-in window).
 * A session is live from 15 minutes before start time until 30 minutes after.
 * @param string $sessionDate The session date (Y-m-d format).
 * @param string $sessionTime The session time (H:i:s format).
 * @return bool True if session is live, false otherwise.
 */
function is_session_live($sessionDate, $sessionTime) {
    try {
        // Combine date and time into a DateTime object
        $sessionDateTime = new DateTime("$sessionDate $sessionTime");
        $now = new DateTime();
        
        // Session is live from 15 minutes before until 30 minutes after
        $liveStart = clone $sessionDateTime;
        $liveStart->modify('-15 minutes');
        
        $liveEnd = clone $sessionDateTime;
        $liveEnd->modify('+30 minutes');
        
        return ($now >= $liveStart && $now <= $liveEnd);
    } catch (Exception $e) {
        error_log("Error checking session live status: " . $e->getMessage());
        return false;
    }
}

/**
 * Generates a random 6-character alphanumeric session code.
 * Avoids confusing characters (0, O, I, 1).
 * @return string The generated session code.
 */
function generate_session_code() {
    $characters = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789'; // Avoid 0, O, I, 1
    $code = '';
    for ($i = 0; $i < 6; $i++) {
        $code .= $characters[random_int(0, strlen($characters) - 1)];
    }
    return $code;
}

/**
 * Gets the session code for a session, generating one if it doesn't exist.
 * Only generates code if the session is currently live.
 * @param int $sessionId The session ID.
 * @param PDO $pdo The database connection.
 * @return string|null The session code or null if session is not live.
 */
function get_or_create_session_code($sessionId, $pdo) {
    try {
        // Get session details
        $stmt = $pdo->prepare("SELECT SessionCode, SessionDate, Time FROM sessions WHERE SessionID = ?");
        $stmt->execute([$sessionId]);
        $session = $stmt->fetch();
        
        if (!$session) {
            return null;
        }
        
        // Check if session is live
        if (!is_session_live($session['SessionDate'], $session['Time'])) {
            return null; // Don't show code if session isn't live
        }
        
        // If code already exists, return it
        if (!empty($session['SessionCode'])) {
            return $session['SessionCode'];
        }
        
        // Generate new code
        $newCode = generate_session_code();
        $updateStmt = $pdo->prepare("UPDATE sessions SET SessionCode = ? WHERE SessionID = ?");
        $updateStmt->execute([$newCode, $sessionId]);
        
        return $newCode;
    } catch (PDOException $e) {
        error_log("Failed to get/create session code: " . $e->getMessage());
        return null;
    }
}

/**
 * Validates a session code for check-in.
 * @param string $code The code to validate.
 * @param PDO $pdo The database connection.
 * @return array|null Session data if valid, null otherwise.
 */
function validate_session_code($code, $pdo) {
    try {
        $stmt = $pdo->prepare("
            SELECT s.*, c.ClassName 
            FROM sessions s
            JOIN activities c ON s.ClassID = c.ClassID
            WHERE s.SessionCode = ? AND s.Status = 'scheduled'
        ");
        $stmt->execute([$code]);
        $session = $stmt->fetch();
        
        if (!$session) {
            return null;
        }
        
        // Check if session is still live
        if (!is_session_live($session['SessionDate'], $session['Time'])) {
            return null;
        }
        
        return $session;
    } catch (PDOException $e) {
        error_log("Failed to validate session code: " . $e->getMessage());
        return null;
    }
}

// ============================================================================
// UTILITY FUNCTIONS
// ============================================================================

/**
 * Gets the user's full profile data.
 * @param int $userId The user ID.
 * @return array|null User data or null if not found.
 */
function get_user_profile($userId) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("
            SELECT u.*, m.PlanName as MembershipPlan, m.Type as MembershipType
            FROM users u
            LEFT JOIN membership m ON u.MembershipID = m.MembershipID
            WHERE u.UserID = ?
        ");
        $stmt->execute([$userId]);
        return $stmt->fetch();
    } catch (PDOException $e) {
        error_log("Failed to get user profile: " . $e->getMessage());
        return null;
    }
}

/**
 * Checks if a user's membership is active.
 * @param int $userId The user ID.
 * @return bool True if membership is active, false otherwise.
 */
function has_active_membership($userId) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("
            SELECT MembershipEndDate 
            FROM users 
            WHERE UserID = ? AND MembershipID IS NOT NULL
        ");
        $stmt->execute([$userId]);
        $result = $stmt->fetch();
        
        if (!$result || !$result['MembershipEndDate']) {
            return false;
        }
        
        $endDate = new DateTime($result['MembershipEndDate']);
        $now = new DateTime();
        
        return $now <= $endDate;
    } catch (Exception $e) {
        error_log("Failed to check membership status: " . $e->getMessage());
        return false;
    }
}

/**
 * Logs an activity to the error log with context.
 * @param string $message The message to log.
 * @param string $level The log level (INFO, WARNING, ERROR).
 */
function log_activity($message, $level = 'INFO') {
    $timestamp = date('Y-m-d H:i:s');
    $userId = $_SESSION['UserID'] ?? 'Guest';
    error_log("[$timestamp] [$level] [User: $userId] $message");
}

// ============================================================================
// ERROR HANDLING
// ============================================================================

/**
 * Displays a user-friendly error message and stops execution.
 * @param string $message The error message to display.
 */
function show_error($message) {
    http_response_code(500);
    echo "<!DOCTYPE html>
<html>
<head>
    <title>Error - " . SITE_NAME . "</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f4f4f4; padding: 50px; text-align: center; }
        .error-box { background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); max-width: 500px; margin: 0 auto; }
        .error-box h1 { color: #e74c3c; margin-bottom: 20px; }
        .error-box p { color: #555; line-height: 1.6; }
        .error-box a { color: #3498db; text-decoration: none; }
    </style>
</head>
<body>
    <div class='error-box'>
        <h1>⚠️ Error</h1>
        <p>" . htmlspecialchars($message) . "</p>
        <p><a href='" . SITE_URL . "'>← Return to Home</a></p>
    </div>
</body>
</html>";
    exit();
}

// ============================================================================
// INITIALIZATION COMPLETE
// ============================================================================

// Set timezone for the application
date_default_timezone_set('Asia/Kuala_Lumpur');

// Log successful configuration load (development only)
if (defined('DEBUG_MODE') && DEBUG_MODE === true) {
    log_activity('Configuration loaded successfully', 'INFO');
}

?>