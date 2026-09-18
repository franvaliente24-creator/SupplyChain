<?php
// sws_connection.php — connection to the Smart Warehousing System
// database. Place this at your SupplyChain root, alongside
// db_connection.php and core_connection.php.
//
// Files that own SWS data (tech_assets.php, asset_assignments.php,
// and qr_generator.php's asset_id branch) should require THIS file
// instead of db_connection.php.

// Load environment variables from .env file
require_once __DIR__ . '/load_env.php';

ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(0);

// Fetch environment variables - NO HARDCODED FALLBACKS
$servername = getenv('DB_SWS_HOST');
$username   = getenv('DB_SWS_USERNAME');
$password   = getenv('DB_SWS_PASSWORD');
$dbname     = getenv('DB_SWS_DATABASE');

// Validate that required environment variables are set
if (!$servername || !$username || !$dbname) {
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode(['message' => 'Database configuration incomplete. Please set DB_SWS_HOST, DB_SWS_USERNAME, DB_SWS_PASSWORD, and DB_SWS_DATABASE environment variables.']);
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
