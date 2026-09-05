<?php
// sws_connection.php — connection to the Smart Warehousing System
// database. Place this at your SupplyChain root, alongside
// db_connection.php and core_connection.php.
//
// Files that own SWS data (tech_assets.php, asset_assignments.php,
// and qr_generator.php's asset_id branch) should require THIS file
// instead of db_connection.php.

ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(0);

$servername = getenv('DB_SWS_HOST')     ?: getenv('DB_HOST')     ?: 'mariadb-n9o7nsa7.internal';
$username   = getenv('DB_SWS_USERNAME') ?: getenv('DB_USERNAME') ?: 'hf_ejcnux6zyo';
$password   = getenv('DB_SWS_PASSWORD') ?: getenv('DB_PASSWORD') ?: 'bg0uMqT7KfIBEcyKRgfg1qimuT6cy9Bv';
$dbname     = getenv('DB_SWS_DATABASE') ?: getenv('DB_DATABASE') ?: 'hf_db_n9o7nsa7';

mysqli_report(MYSQLI_REPORT_OFF);
$conn = @new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_errno) {
    http_response_code(500);
    header('Content-Type: application/json');

    $debug = getenv('DEBUG_MODE') === 'true';

    if ($debug) {
        echo json_encode([
            'message' => 'Service temporarily unavailable.',
            'debug_error' => $conn->connect_error,
            'debug_errno' => $conn->connect_errno,
            'debug_host'  => $servername,
            'debug_db'    => $dbname
        ]);
    } else {
        echo json_encode(['message' => 'Service temporarily unavailable.']);
    }
    exit;
}

$conn->set_charset('utf8mb4');
?>