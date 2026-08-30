<?php
// core_connection.php — connection to the Identity/Audit service database.
// Place this at your SupplyChain root, alongside db_connection.php.
// Files that own core data (login.php, register.php, logout.php,
// check_session.php, forgotpass.php, reset_info.php, updatepass.php,
// activity_log.php, login_history.php) should require THIS file
// instead of db_connection.php.

ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(0);

$servername = "localhost";
$username   = "root";
$password   = "";
$dbname     = "db_core";

mysqli_report(MYSQLI_REPORT_OFF);
$conn = @new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_errno) {
    http_response_code(500);
    header('Content-Type: application/json');

    // Same temporary debug pattern as db_connection.php — flip to false
    // before this leaves your machine.
    $debug = true;

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