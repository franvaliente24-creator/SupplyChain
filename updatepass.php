<?php
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(0);

header('Content-Type: application/json');
require 'core_connection.php';

$data = json_decode(file_get_contents('php://input'), true);
$token = $data['token'] ?? '';
$newPassword = $data['password'] ?? '';

if (!$token || !$newPassword) {
    http_response_code(400);
    echo json_encode(['message' => 'Token and password are required.']);
    exit;
}

if (strlen($newPassword) < 8) {
    http_response_code(400);
    echo json_encode(['message' => 'Password must be at least 8 characters long.']);
    exit;
}

$stmt = $conn->prepare('SELECT user_id FROM password_resets WHERE token = ? AND expires_at > NOW() LIMIT 1');
$stmt->bind_param('s', $token);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    http_response_code(404);
    echo json_encode(['message' => 'Invalid or expired token.']);
    exit;
}

$row = $result->fetch_assoc();
$userId = (int)$row['user_id'];
$stmt->close();

$hashedPassword = password_hash($newPassword, PASSWORD_BCRYPT);
$updStmt = $conn->prepare('UPDATE users SET password_hash = ? WHERE user_id = ? LIMIT 1');
$updStmt->bind_param('si', $hashedPassword, $userId);

if ($updStmt->execute()) {
    $updStmt->close();

    $delStmt = $conn->prepare('DELETE FROM password_resets WHERE token = ?');
    $delStmt->bind_param('s', $token);
    $delStmt->execute();
    $delStmt->close();

    $delStmt2 = $conn->prepare('DELETE FROM remember_tokens WHERE user_id = ?');
    $tableCheck = $conn->query("SHOW TABLES LIKE 'remember_tokens'");
    if ($tableCheck && $tableCheck->num_rows === 1 && $delStmt2) {
        $delStmt2->execute();
    }
    if ($tableCheck) $tableCheck->free();
    if ($delStmt2) $delStmt2->close();

    setcookie('remember_token', '', time() - 3600, '/');
    setcookie('remember_uid', '', time() - 3600, '/');
    setcookie('remember_token', '', time() - 3600, '/');
    setcookie('remember_uid', '', time() - 3600, '/');

    $conn->close();
    echo json_encode(['message' => 'Password updated successfully.']);
} else {
    $updStmt->close();
    $conn->close();
    http_response_code(500);
    echo json_encode(['message' => 'Failed to update password.']);
}
?>