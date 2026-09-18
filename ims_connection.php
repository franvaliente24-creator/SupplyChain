<?php
// ims_connection.php — Dedicated connection for Inventory Management System (db_ims)
// Load environment variables from .env file
require_once __DIR__ . '/load_env.php';

ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(0);

// Fetch environment variables - NO HARDCODED FALLBACKS
$servername = getenv('DB_IMS_HOST');
$username   = getenv('DB_IMS_USERNAME');
$password   = getenv('DB_IMS_PASSWORD');
$dbname     = getenv('DB_IMS_DATABASE');

// Validate that required environment variables are set
if (!$servername || !$username || !$dbname) {
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode(['message' => 'Database configuration incomplete. Please set DB_IMS_HOST, DB_IMS_USERNAME, DB_IMS_PASSWORD, and DB_IMS_DATABASE environment variables.']);
    exit;
}

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
