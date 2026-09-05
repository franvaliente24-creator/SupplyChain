<?php
// psm_connection.php — Dedicated connection for Procurement & Sourcing Management (db_psm)
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(0);

$servername = getenv('DB_PSM_HOST')     ?: getenv('DB_HOST')     ?: 'mariadb-d5ybzwp1.internal';
$username   = getenv('DB_PSM_USERNAME') ?: getenv('DB_USERNAME') ?: 'hf_m30otlrz6e';
$password   = getenv('DB_PSM_PASSWORD') ?: getenv('DB_PASSWORD') ?: 'Yn4KVfLxI7IVkKrYqPdVPsjZHXLMsNNy';
$dbname     = getenv('DB_PSM_DATABASE') ?: getenv('DB_DATABASE') ?: 'hf_db_d5ybzwp1';

mysqli_report(MYSQLI_REPORT_OFF);
$conn = @new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_errno) {
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode([
        'message' => 'Procurement & Sourcing database service unavailable.',
        'error'   => $conn->connect_error
    ]);
    exit;
}

$conn->set_charset('utf8mb4');
?>