<?php
// Check for debug mode from environment variables
$debugMode = getenv('DEBUG_MODE') === 'true' || getenv('DEBUG_MODE') === '1';

if ($debugMode) {
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', 0);
    ini_set('display_startup_errors', 0);
    error_reporting(0);
}

// Dynamically fetch environment variables with double fallbacks
$servername = getenv('DB_CORE_HOST')     ?: getenv('DB_HOST')     ?: 'mariadb-bzqbcyao.internal';
$username   = getenv('DB_CORE_USERNAME') ?: getenv('DB_USERNAME') ?: 'hf_h22shuncv0';
$password   = getenv('DB_CORE_PASSWORD') ?: getenv('DB_PASSWORD') ?: 'CYqvrsOdkS9mibpEvG4wYmgTNNSE63AS';
$dbname     = getenv('DB_CORE_DATABASE') ?: getenv('DB_DATABASE') ?: 'hf_db_bzqbcyao';

mysqli_report(MYSQLI_REPORT_OFF);

// Attempt database connection
$conn = @new mysqli($servername, $username, $password, $dbname);

// Handle connection failures cleanly
if ($conn->connect_errno) {
    http_response_code(500);
    header('Content-Type: application/json');

    if ($debugMode) {
        echo json_encode([
            'message'     => 'Database connection failed.',
            'debug_error' => $conn->connect_error,
            'debug_errno' => $conn->connect_errno,
            'debug_host'  => $servername,
            'debug_db'    => $dbname
        ]);
    } else {
        echo json_encode(['message' => 'Service temporarily unavailable.']);
    }
    exit;
}

$conn->set_charset('utf8mb4');
?>