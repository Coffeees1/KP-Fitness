<?php
require_once __DIR__ . '/../includes/config.db.php';

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "Exporting custom users...\n";

    // Assuming original seed stopped around 309. Custom users are 310+.
    // We export ALL columns except UserID (let it auto-increment on import)
    $stmt = $pdo->query("SELECT FullName, Email, Password, Role, Phone, Gender, DateOfBirth, Height, Weight, Specialist, WorkingHours, JobType, ProfilePicture, MembershipID, MembershipStartDate, MembershipEndDate, NextMembershipID, AutoRenew, DaysOff FROM users WHERE UserID >= 310");
    
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($users) > 0) {
        $json = json_encode($users, JSON_PRETTY_PRINT);
        file_put_contents(__DIR__ . '/../database/custom_users.json', $json);
        echo "Successfully exported " . count($users) . " custom users to database/custom_users.json.\n";
    } else {
        echo "No custom users found (ID >= 310).\n";
        // Create empty file to avoid errors
        file_put_contents(__DIR__ . '/../database/custom_users.json', '[]');
    }

} catch (PDOException $e) {
    die("Error: " . $e->getMessage());
}

