<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$servername = "localhost";
$username   = "root";
$password   = "";
$dbname     = "supplychain"; // Correct database name

mysqli_report(MYSQLI_REPORT_OFF);
$conn = @new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_errno) {
    http_response_code(500);
    header('Content-Type: application/json');

    // TEMPORARY DEBUG MODE
    // This shows the *real* MySQL error instead of a generic message so we can
    // see exactly why the connection is failing. Remove/disable this before
    // sharing the site with anyone else — it can leak server details.
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