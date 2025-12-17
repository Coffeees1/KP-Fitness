<?php
// scripts/check_system.php

echo "Starting system check...\n";

// Adjust path to config
$configPath = __DIR__ . '/../includes/config.php';
if (!file_exists($configPath)) {
    die("CRITICAL ERROR: includes/config.php not found at $configPath\n");
}

echo "Found config.php...\n";

// We need to suppress the redirect logic in config.php if we want to include it without issues in CLI
// But config.php doesn't redirect on include, only the helper functions do when called.
// However, config.php starts a session. CLI might complain or not work with sessions the same way, but usually it's fine (just no persistence).
require_once $configPath;

echo "Database connection established successfully.\n";

// Check Tables
$tables = [
    'users',
    'sessions',
    'reservations',
    'membership',
    'activities',
    'notifications'
];

$missingTables = [];

foreach ($tables as $table) {
    try {
        $stmt = $pdo->query("SELECT 1 FROM $table LIMIT 1");
        echo "Table '$table' exists.\n";
    } catch (PDOException $e) {
        $missingTables[] = $table;
        echo "ERROR: Table '$table' MISSING or invalid.\n";
    }
}

if (!empty($missingTables)) {
    echo "\nCRITICAL: The following tables are missing: " . implode(', ', $missingTables) . "\n";
    echo "You likely need to import database/schema.sql.\n";
} else {
    echo "\nDatabase structure looks correct (checked key tables).\n";
}

// Check Critical Files
$criticalFiles = [
    '../index.php',
    '../login.php',
    '../dashboard.php',
    '../admin/dashboard.php',
    '../client/dashboard.php',
    '../trainer/dashboard.php'
];

echo "\nChecking critical files...\n";
foreach ($criticalFiles as $file) {
    if (file_exists(__DIR__ . '/' . $file)) {
        echo "OK: $file\n";
    } else {
        echo "MISSING: $file\n";
    }
}

echo "\nSystem check complete.\n";
?>
