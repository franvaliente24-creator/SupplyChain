<?php
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(0);

$servername = getenv('DB_POM_HOST') ?: 'localhost';
$username   = getenv('DB_POM_USERNAME') ?: 'root';
$password   = getenv('DB_POM_PASSWORD') ?: '';
$dbname     = getenv('DB_POM_DATABASE') ?: 'db_pom';

mysqli_report(MYSQLI_REPORT_OFF);
$conn = @new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_errno) {
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode(['message' => 'Purchase order database connection offline.']);
    exit;
}
$conn->set_charset('utf8mb4');
?>