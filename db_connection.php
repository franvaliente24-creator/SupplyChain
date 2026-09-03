<?php
ini_set('display_errors', 0);          // ← changed: don't leak PHP errors in production
ini_set('display_startup_errors', 0);  // ← changed
error_reporting(E_ALL);

$servername = getenv('DB_HOST') ?: 'localhost';
$username   = getenv('DB_USERNAME') ?: 'root';
$password   = getenv('DB_PASSWORD') ?: '';
$dbname     = getenv('DB_DATABASE') ?: 'supplychain';

mysqli_report(MYSQLI_REPORT_OFF);
$conn = @new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_errno) {
    http_response_code(500);
    header('Content-Type: application/json');

    // Debug mode now driven by env var — OFF unless you explicitly set
    // DEBUG_MODE=true in HostForge's environment variables.
    $debug = getenv('DEBUG_MODE') === 'true';

    if ($debug) {
        echo json_encode([
            'message' => 'Service temporarily unavailable.',
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