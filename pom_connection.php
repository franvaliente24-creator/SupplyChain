<?php
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(0);

$servername = getenv('DB_POM_HOST')     ?: getenv('DB_HOST')     ?: 'mariadb-ssogpouw.internal';
$username   = getenv('DB_POM_USERNAME') ?: getenv('DB_USERNAME') ?: 'hf_epfvnsmgwg';
$password   = getenv('DB_POM_PASSWORD') ?: getenv('DB_PASSWORD') ?: 'nDOYcHMxaXsQwI4sp6hkyrGWWHUwr7fc';
$dbname     = getenv('DB_POM_DATABASE') ?: getenv('DB_DATABASE') ?: 'hf_db_ssogpouw';

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