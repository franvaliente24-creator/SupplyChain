<?php
// ims_connection.php — Dedicated connection for Inventory Management System (db_ims)
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(0);

$servername = getenv('DB_IMS_HOST') ?: 'localhost';
$username   = getenv('DB_IMS_USERNAME') ?: 'root';
$password   = getenv('DB_IMS_PASSWORD') ?: '';
$dbname     = getenv('DB_IMS_DATABASE') ?: 'db_ims';

mysqli_report(MYSQLI_REPORT_OFF);
$conn = @new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_errno) {
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode([
        'message' => 'Inventory Management database service unavailable.',
        'error'   => $conn->connect_error
    ]);
    exit;
}

$conn->set_charset('utf8mb4');
?>