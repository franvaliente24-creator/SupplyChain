<?php
// Load environment variables from .env file
require_once __DIR__ . '/load_env.php';

// Check for debug mode from environment variables
$debugMode = getenv('APP_DEBUG') === 'true' || getenv('APP_DEBUG') === '1';

if ($debugMode) {
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', 0);
    ini_set('display_startup_errors', 0);
    error_reporting(0);
}

// Fetch environment variables - NO HARDCODED FALLBACKS
$servername = getenv('DB_CORE_HOST');
$username   = getenv('DB_CORE_USERNAME');
$password   = getenv('DB_CORE_PASSWORD');
$dbname     = getenv('DB_CORE_DATABASE');

// Validate that required environment variables are set
if (!$servername || !$username || !$dbname) {
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode(['message' => 'Database configuration incomplete. Please set DB_CORE_HOST, DB_CORE_USERNAME, DB_CORE_PASSWORD, and DB_CORE_DATABASE environment variables.']);
    exit;
}

mysqli_report(MYSQLI_REPORT_OFF);

// Attempt database connection
$conn = @new mysqli($servername, $username, $password, $dbname);

// Handle connection failures cleanly - NO DEBUG INFO EXPOSED
if ($conn->connect_errno) {
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode(['message' => 'Service temporarily unavailable.']);
    exit;
}

$conn->set_charset('utf8mb4');
?>
