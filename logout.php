<?php
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(0);

header('Content-Type: application/json');

if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'domain' => '',
        'secure' => false,
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
    session_start();
}

$_SESSION = [];

if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params['path'],
        $params['domain'],
        $params['secure'],
        $params['httponly']
    );
}

session_destroy();

setcookie('remember_token', '', time() - 3600, '/SupplyChain');
setcookie('remember_uid', '', time() - 3600, '/SupplyChain');
setcookie('remember_token', '', time() - 3600, '/');
setcookie('remember_uid', '', time() - 3600, '/');

require 'db_connection.php';
if (!empty($_COOKIE['remember_uid'])) {
    $rememberUid = (int)$_COOKIE['remember_uid'];
    $tableCheck = $conn->query("SHOW TABLES LIKE 'remember_tokens'");
    if ($tableCheck && $tableCheck->num_rows === 1) {
        $delStmt = $conn->prepare('DELETE FROM remember_tokens WHERE user_id = ?');
        if ($delStmt) {
            $delStmt->bind_param('i', $rememberUid);
            $delStmt->execute();
            $delStmt->close();
        }
    }
    if ($tableCheck) $tableCheck->free();
}
$conn->close();

http_response_code(200);
echo json_encode([
    'message' => 'Logged out successfully.',
    'redirect' => 'index.html'
]);
