<?php
// ims_connection.php — Dedicated connection for Inventory Management System (db_ims)
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(0);

$servername = getenv('DB_IMS_HOST')     ?: getenv('DB_HOST')     ?: 'mariadb-dgcjft2i.internal';
$username   = getenv('DB_IMS_USERNAME') ?: getenv('DB_USERNAME') ?: 'hf_twmfzp0svf';
$password   = getenv('DB_IMS_PASSWORD') ?: getenv('DB_PASSWORD') ?: 'tBSBFPxQ3rDcDkaig9s7SS8Ba8k3UAEz';
$dbname     = getenv('DB_IMS_DATABASE') ?: getenv('DB_DATABASE') ?: 'hf_db_dgcjft2i';

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