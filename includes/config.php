<?php
// Database configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'kp_f');
define('DB_USER', 'root');
define('DB_PASS', '');

// Site configuration
define('SITE_NAME', 'KP Fitness');
define('SITE_URL', 'http://localhost/kp-fitness');
define('UPLOAD_PATH', 'uploads/');

// Session configuration
session_start();

// Error reporting (for development)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Database connection
try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// Helper functions
function sanitize_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

function redirect($url) {
    header("Location: $url");
    exit();
}

function is_logged_in() {
    return isset($_SESSION['UserID']);
}

function is_admin() {
    return isset($_SESSION['Role']) && $_SESSION['Role'] === 'admin';
}

function is_trainer() {
    return isset($_SESSION['Role']) && $_SESSION['Role'] === 'trainer';
}

function is_client() {
    return isset($_SESSION['Role']) && $_SESSION['Role'] === 'client';
}

function get_user_role() {
    return $_SESSION['Role'] ?? null;
}

function require_login() {
    if (!is_logged_in()) {
        redirect('login.php');
    }
}

function require_admin() {
    if (!is_admin()) {
        $_SESSION['error'] = 'Access denied. Admin privileges required.';
        redirect('dashboard.php');
    }
}

function require_trainer() {
    if (!is_trainer()) {
        $_SESSION['error'] = 'Access denied. Trainer privileges required.';
        redirect('dashboard.php');
    }
}

function require_client() {
    if (!is_client()) {
        $_SESSION['error'] = 'Access denied. Client privileges required.';
        redirect('dashboard.php');
    }
}

// Calculate BMI
function calculate_bmi($height, $weight) {
    if ($height > 0 && $weight > 0) {
        $height_m = $height / 100;
        return round($weight / ($height_m * $height_m), 1);
    }
    return 0;
}

// Get BMI category
function get_bmi_category($bmi) {
    if ($bmi < 18.5) return 'Underweight';
    if ($bmi < 25) return 'Normal weight';
    if ($bmi < 30) return 'Overweight';
    return 'Obese';
}

// Format currency
function format_currency($amount) {
    return 'RM ' . number_format($amount, 2);
}

// Format date
function format_date($date) {
    return date('d M Y', strtotime($date));
}

// Format time
function format_time($time) {
    return date('h:i A', strtotime($time));
}

// Create notification
function create_notification($userId, $title, $message, $type = 'info') {
    global $pdo;
    
    $stmt = $pdo->prepare("INSERT INTO notifications (UserID, Title, Message, Type) VALUES (?, ?, ?, ?)");
    return $stmt->execute([$userId, $title, $message, $type]);
}

// Get unread notifications count
function get_unread_notifications_count($userId) {
    global $pdo;
    
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE UserID = ? AND IsRead = FALSE");
    $stmt->execute([$userId]);
    return $stmt->fetchColumn();
}

// Mark notification as read
function mark_notification_read($notificationId, $userId) {
    global $pdo;
    
    $stmt = $pdo->prepare("UPDATE notifications SET IsRead = TRUE WHERE NotificationID = ? AND UserID = ?");
    return $stmt->execute([$notificationId, $userId]);
}
?>