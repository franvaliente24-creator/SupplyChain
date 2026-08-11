<?php
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(0);

header('Content-Type: application/json');
require 'db_connection.php';

$data = json_decode(file_get_contents('php://input'), true);
$email = trim($data['email'] ?? '');

if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(['message' => 'Please enter a valid email address.']);
    exit;
}

$stmt = $conn->prepare('SELECT user_id FROM users WHERE email = ? AND is_active = 1');
$stmt->bind_param('s', $email);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$user) {
    // Don't reveal whether the email exists
    echo json_encode(['message' => 'If that email exists, a reset link has been generated.']);
    exit;
}

$userId = $user['user_id'];
$token = bin2hex(random_bytes(32));
$expiresAt = date('Y-m-d H:i:s', strtotime('+1 hour'));

$del = $conn->prepare('DELETE FROM password_resets WHERE user_id = ?');
$del->bind_param('i', $userId);
$del->execute();
$del->close();

$ins = $conn->prepare('INSERT INTO password_resets (user_id, token, expires_at) VALUES (?, ?, ?)');
$ins->bind_param('iss', $userId, $token, $expiresAt);

if (!$ins->execute()) {
    http_response_code(500);
    echo json_encode(['message' => 'Could not generate reset link. Please try again.']);
    exit;
}
$ins->close();

// TODO: replace this with a real email once you have mail sending set up, e.g.:
// mail($email, 'Password Reset', "Reset your password: https://yoursite.com/Reset_Pass.html?token=$token");

echo json_encode([
    'message' => 'Reset link generated.',
    'reset_url' => 'resetpass.html?token=' . urlencode($token) // remove once real email is wired up
]);

$conn->close();