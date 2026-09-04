<?php
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(0);

$servername = getenv('DB_SVM_HOST') ?: 'localhost';
$username   = getenv('DB_SVM_USERNAME') ?: 'root';
$password   = getenv('DB_SVM_PASSWORD') ?: '';
$dbname     = getenv('DB_SVM_DATABASE') ?: 'db_svm';

mysqli_report(MYSQLI_REPORT_OFF);
$conn = @new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_errno) {
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode(['message' => 'Supplier database connection offline.']);
    exit;
}
$conn->set_charset('utf8mb4');
?>