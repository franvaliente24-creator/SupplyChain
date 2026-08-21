<?php
// filepath: c:\xampp\htdocs\SupplyChain\login.php
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

require 'db_connection.php'; // Include your database connection

// Get the JSON data from the request
$data = json_decode(file_get_contents('php://input'), true);
$email = trim($data['email'] ?? '');
$password = $data['password'] ?? '';
$rememberMe = isset($data['remember_me']) ? (bool)$data['remember_me'] : false;

// Validate input
if (!$email || !$password) {
    http_response_code(400);
    echo json_encode(['message' => 'Email and password are required.']);
    exit;
}

// Check if the email exists in the database
$stmt = $conn->prepare('SELECT user_id, username, password_hash FROM users WHERE email = ? LIMIT 1');
$stmt->bind_param('s', $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    http_response_code(401);
    echo json_encode(['message' => 'Invalid email or password.']);
    exit;
}

$row = $result->fetch_assoc();
$userId = (int)$row['user_id'];
$username = $row['username'] ?? null;
$hashedPassword = $row['password_hash'];
$stmt->close();

// Verify the password
if (!password_verify($password, $hashedPassword)) {
    // Log failed login attempt to activity log
    $ip_address = $_SERVER['REMOTE_ADDR'] ?? '';
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
    $log_stmt = $conn->prepare("INSERT INTO activity_log (user_id, username, action, details, ip_address, user_agent) VALUES (?, ?, ?, ?, ?, ?)");
    $log_stmt->bind_param("issss", $userId, $username, 'Failed Login', 'Invalid password attempt', $ip_address, $user_agent);
    $log_stmt->execute();
    $log_stmt->close();
    
    // Log to login_history table if it exists
    $login_history_check = $conn->query("SHOW TABLES LIKE 'login_history'");
    if ($login_history_check && $login_history_check->num_rows > 0) {
        $history_stmt = $conn->prepare("INSERT INTO login_history (user_id, username, ip_address, user_agent, login_status, failure_reason) VALUES (?, ?, ?, ?, 'failed', 'Invalid password')");
        $history_stmt->bind_param("isss", $userId, $username, $ip_address, $user_agent);
        $history_stmt->execute();
        $history_stmt->close();
    }
    $login_history_check->free();
    
    http_response_code(401);
    echo json_encode(['message' => 'Invalid email or password.']);
    exit;
}

// Regenerate session ID to prevent session fixation
session_regenerate_id(true);

// Store user in session
$_SESSION['user_id'] = $userId;
$_SESSION['logged_in_at'] = time();
$_SESSION['email'] = $email;
$_SESSION['username'] = $username;

// Log successful login
$ip_address = $_SERVER['REMOTE_ADDR'] ?? '';
$user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
$log_stmt = $conn->prepare("INSERT INTO activity_log (user_id, username, action, details, ip_address, user_agent) VALUES (?, ?, ?, ?, ?, ?)");
$log_stmt->bind_param("issss", $userId, $username, 'Successful Login', 'User logged in successfully', $ip_address, $user_agent);
$log_stmt->execute();
$log_stmt->close();

// Log to login_history table if it exists
$login_history_check = $conn->query("SHOW TABLES LIKE 'login_history'");
if ($login_history_check && $login_history_check->num_rows > 0) {
    $history_stmt = $conn->prepare("INSERT INTO login_history (user_id, username, ip_address, user_agent, login_status) VALUES (?, ?, ?, ?, 'success')");
    $history_stmt->bind_param("isss", $userId, $username, $ip_address, $user_agent);
    $history_stmt->execute();
    $history_stmt->close();
}
$login_history_check->free();

// Update last_login in users table
$update_stmt = $conn->prepare("UPDATE users SET last_login = NOW() WHERE user_id = ?");
$update_stmt->bind_param("i", $userId);
$update_stmt->execute();
$update_stmt->close();

// Handle Remember Me - set a persistent cookie valid for 30 days
$rememberTableExists = false;
$tableCheck = $conn->query("SHOW TABLES LIKE 'remember_tokens'");
if ($tableCheck && $tableCheck->num_rows === 1) {
    $rememberTableExists = true;
}
if ($tableCheck) $tableCheck->free();

if ($rememberMe) {
    $rememberToken = bin2hex(random_bytes(32));
    $rememberExpires = time() + (30 * 24 * 60 * 60);

    if ($rememberTableExists) {
        $delStmt = $conn->prepare('DELETE FROM remember_tokens WHERE user_id = ?');
        $delStmt->bind_param('i', $userId);
        $delStmt->execute();
        $delStmt->close();

        $hashedToken = hash('sha256', $rememberToken);
        $insStmt = $conn->prepare('INSERT INTO remember_tokens (user_id, token, expires_at) VALUES (?, ?, ?)');
        $expiresAt = date('Y-m-d H:i:s', $rememberExpires);
        $insStmt->bind_param('iss', $userId, $hashedToken, $expiresAt);
        $insStmt->execute();
        $insStmt->close();
    }

    setcookie(
        'remember_token',
        $rememberToken,
        [
            'expires' => $rememberExpires,
            'path' => '/',
            'domain' => '',
            'secure' => false,
            'httponly' => true,
            'samesite' => 'Lax'
        ]
    );
    setcookie(
        'remember_uid',
        (string)$userId,
        [
            'expires' => $rememberExpires,
            'path' => '/',
            'domain' => '',
            'secure' => false,
            'httponly' => true,
            'samesite' => 'Lax'
        ]
    );
} else {
    if ($rememberTableExists) {
        $delStmt = $conn->prepare('DELETE FROM remember_tokens WHERE user_id = ?');
        if ($delStmt) {
            $delStmt->bind_param('i', $userId);
            $delStmt->execute();
            $delStmt->close();
        }
    }

    if (isset($_COOKIE['remember_token'])) {
        setcookie('remember_token', '', time() - 3600, '/');
        setcookie('remember_uid', '', time() - 3600, '/');
    }
}

$conn->close();

// Return a success response
echo json_encode([
    'message' => 'Login successful.',
    'redirect' => 'dashboard.html',
    'remember_me' => $rememberMe
]);
?>