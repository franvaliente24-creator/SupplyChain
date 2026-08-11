<?php
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(0);

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "supplychain"; // Correct database name

mysqli_report(MYSQLI_REPORT_OFF);
$conn = @new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_errno) {
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode(['message' => 'Service temporarily unavailable.']);
    exit;
}

$conn->set_charset('utf8mb4');
?>