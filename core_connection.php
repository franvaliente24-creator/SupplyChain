<?php
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(0);

$servername = getenv('DB_CORE_HOST') ?: 'mariadb-bzqbcyao.internal';
$username   = getenv('DB_CORE_USERNAME') ?: 'hf_h22shuncv0';
$password   = getenv('DB_CORE_PASSWORD') ?: 'CYqvrsOdkS9mibpEvG4wYmgTNNSE63AS';
$dbname     = getenv('DB_CORE_DATABASE') ?: 'hf_db_bzqbcyao';

mysqli_report(MYSQLI_REPORT_OFF);
$conn = @new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_errno) {
    http_response_code(500);
    header('Content-Type: application/json');

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