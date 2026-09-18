<?php
// Load centralized session configuration
require_once __DIR__ . '/session_config.php';

header('Content-Type: application/json');

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

clearSecureCookie('remember_token');
clearSecureCookie('remember_uid');

require 'core_connection.php';
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
