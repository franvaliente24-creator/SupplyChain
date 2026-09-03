<?php
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(0);

header('Content-Type: application/json');
require 'core_connection.php';

$data = json_decode(file_get_contents('php://input'), true);
$username = trim($data['username'] ?? '');
$email = trim($data['email'] ?? '');
$role = $data['role'] ?? 'Staff';
$password = $data['password'] ?? '';
$confirmPassword = $data['confirm_password'] ?? '';

$validRoles = ['Administrator', 'Supply Chain Manager', 'Staff'];

if (!$username || !$email || !$password || !$confirmPassword) {
    http_response_code(400);
    echo json_encode(['message' => 'All fields are required.']);
    exit;
}
if ($password !== $confirmPassword) {
    http_response_code(400);
    echo json_encode(['message' => 'Passwords do not match.']);
    exit;
}
if (strlen($username) < 2 || strlen($username) > 50) {
    http_response_code(400);
    echo json_encode(['message' => 'Username must be between 2 and 50 characters.']);
    exit;
}
if (!preg_match('/^[A-Za-z0-9_.-]+$/', $username)) {
    http_response_code(400);
    echo json_encode(['message' => 'Username may only contain letters, numbers, dots, underscores, and hyphens.']);
    exit;
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(['message' => 'Please enter a valid email address.']);
    exit;
}
if (strlen($password) < 8) {
    http_response_code(400);
    echo json_encode(['message' => 'Password must be at least 8 characters long.']);
    exit;
}
if (!in_array($role, $validRoles, true)) {
    $role = 'Staff';
}

// Check for existing username or email
$stmt = $conn->prepare('SELECT user_id FROM users WHERE username = ? OR email = ? LIMIT 1');
$stmt->bind_param('ss', $username, $email);
$stmt->execute();
$exists = $stmt->get_result();
if ($exists && $exists->num_rows > 0) {
    $row = $exists->fetch_assoc();
    $stmt->close();
    http_response_code(409);
    echo json_encode(['message' => 'An account with that username or email already exists. Please sign in.']);
    exit;
}
$stmt->close();

$passwordHash = password_hash($password, PASSWORD_BCRYPT);

$ins = $conn->prepare('INSERT INTO users (username, email, password_hash, role) VALUES (?, ?, ?, ?)');
$ins->bind_param('ssss', $username, $email, $passwordHash, $role);

if ($ins->execute()) {
    $ins->close();
    $conn->close();
    echo json_encode(['message' => 'Account created successfully.']);
} else {
    $err = $ins->error;
    $ins->close();
    $conn->close();
    error_log('Register insert failed: ' . $err);
    http_response_code(500);
    echo json_encode(['message' => 'Failed to create account: ' . $err]);
}
