<?php
// Mock Session for testing
session_start();
$_SESSION['UserID'] = 2; // Assuming ID 2 is John Doe (Trainer) from seed
$_SESSION['Role'] = 'trainer';
$_SESSION['FullName'] = 'John Doe';

// Mock POST data
$_POST['message'] = 'Hello';

// Include the handler (modify require_trainer to not redirect for CLI/test if needed, or ensuring session matches works)
// We need to bypass require_trainer redirect if we run via CLI which has no browser session persistence usually,
// but here we manually set $_SESSION superglobal which config.php reads.

// Capture output
ob_start();
require 'trainer_chatbot_handler.php';
$output = ob_get_clean();

echo "--- RAW OUTPUT ---\n";
echo $output;
echo "\n------------------\n";

// Check if valid JSON
$json = json_decode($output, true);
if ($json === null) {
    echo "JSON DECODE ERROR: " . json_last_error_msg() . "\n";
} else {
    echo "JSON IS VALID.\n";
    print_r($json);
}

