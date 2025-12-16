<?php
require_once '../includes/config.php';
require_once '../includes/config.db.php';

echo "<h1>Debug Info</h1>";
echo "User ID in Session: " . ($_SESSION['UserID'] ?? 'Not Set') . "<br>";
echo "Role: " . ($_SESSION['AccountType'] ?? 'Not Set') . "<br>";

$trainerId = $_SESSION['UserID'] ?? 0;

echo "<h2>Sessions in DB for Trainer $trainerId</h2>";

try {
    $stmt = $pdo->prepare("SELECT * FROM sessions WHERE TrainerID = ?");
    $stmt->execute([$trainerId]);
    $sessions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($sessions)) {
        echo "No sessions found directly in 'sessions' table for TrainerID $trainerId.<br>";
    } else {
        echo "<pre>";
        print_r($sessions);
        echo "</pre>";
    }

    echo "<h2>All Sessions</h2>";
    $stmt = $pdo->query("SELECT * FROM sessions LIMIT 5");
    $all = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "<pre>";
    print_r($all);
    echo "</pre>";

} catch (PDOException $e) {
    echo "SQL Error: " . $e->getMessage();
}
?>
