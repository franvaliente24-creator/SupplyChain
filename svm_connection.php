<?php
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(0);

$servername = getenv('DB_SVM_HOST')     ?: getenv('DB_HOST')     ?: 'mariadb-0ndhr1mv.internal';
$username   = getenv('DB_SVM_USERNAME') ?: getenv('DB_USERNAME') ?: 'hf_bvk8jvwsgq';
$password   = getenv('DB_SVM_PASSWORD') ?: getenv('DB_PASSWORD') ?: 'PZQ1yfgQLlh3GkXGiHFt9md1BHuk9WiO';
$dbname     = getenv('DB_SVM_DATABASE') ?: getenv('DB_DATABASE') ?: 'hf_db_0ndhr1mv';

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