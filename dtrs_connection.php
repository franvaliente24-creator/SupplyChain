<?php
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(0);

$servername = getenv('DB_DTRS_HOST')     ?: getenv('DB_HOST')     ?: 'mariadb-hwwfapjm.internal';
$username   = getenv('DB_DTRS_USERNAME') ?: getenv('DB_USERNAME') ?: 'hf_vsobeo5cgb';
$password   = getenv('DB_DTRS_PASSWORD') ?: getenv('DB_PASSWORD') ?: 'LMZwl7pOx8RtPpkah8dFz9OvqAuH5m43';
$dbname     = getenv('DB_DTRS_DATABASE') ?: getenv('DB_DATABASE') ?: 'hf_db_hwwfapjm';

mysqli_report(MYSQLI_REPORT_OFF);
$conn = @new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_errno) {
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode(['message' => 'Logistics tracking database connection offline.']);
    exit;
}
$conn->set_charset('utf8mb4');
?>