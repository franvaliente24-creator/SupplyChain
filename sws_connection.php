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

$servername = "localhost";
$username   = "root";
$password   = "";
$dbname     = "db_sws";

mysqli_report(MYSQLI_REPORT_OFF);
$conn = @new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_errno) {
    http_response_code(500);
    header('Content-Type: application/json');

    $debug = true; // flip to false before this leaves your machine

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