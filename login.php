<?php
// filepath: c:\xampp\htdocs\SupplyChain\login.php
// Load centralized session configuration
require_once __DIR__ . '/session_config.php';

// Load CSRF protection
require_once __DIR__ . '/csrf_config.php';

// Load rate limiter
require_once __DIR__ . '/rate_limiter.php';

// Load input validation
require_once __DIR__ . '/input_validation.php';

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

require 'core_connection.php'; // Include your database connection

// Get the JSON data from the request
$data = json_decode(file_get_contents('php://input'), true);

// Validate CSRF token for POST requests
$csrfToken = $data['csrf_token'] ?? '';
if (!validateCsrfToken($csrfToken)) {
    http_response_code(403);
    echo json_encode(['message' => 'Invalid CSRF token. Please refresh the page and try again.']);
    exit;
}

$email = trim($data['email'] ?? '');
$password = $data['password'] ?? '';
$rememberMe = isset($data['remember_me']) ? (bool)$data['remember_me'] : false;

// Validate input
$emailValidation = validateEmail($email);
if (!$emailValidation['valid']) {
    http_response_code(400);
    echo json_encode(['message' => $emailValidation['error']]);
    exit;
}

$passwordValidation = validateStringLength($password, 1, 128, 'Password');
if (!$passwordValidation['valid']) {
    http_response_code(400);
    echo json_encode(['message' => $passwordValidation['error']]);
    exit;
}

// Check rate limiting before processing login
$rateLimitCheck = isRateLimited($email, 'login');
if ($rateLimitCheck['limited']) {
    http_response_code(429);
    echo json_encode(['message' => $rateLimitCheck['message']]);
    exit;
}

// Validate input
if (!$email || !$password) {
    http_response_code(400);
    echo json_encode(['message' => 'Email and password are required.']);
    exit;
}

// Check if the email exists in the database
$stmt = $conn->prepare('SELECT user_id, username, password_hash, role FROM users WHERE email = ? LIMIT 1');
if (!$stmt) {
    http_response_code(500);
    echo json_encode([
        'message' => 'SQL prepare error',
        'sql_error' => $conn->error
    ]);
    exit;
}
$stmt->bind_param('s', $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $stmt->close();
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
    // Record failed attempt for rate limiting
    recordFailedAttempt($email, 'login');
    
    // Log failed login attempt to activity log (best-effort, never fatal)
    $ip_address = $_SERVER['REMOTE_ADDR'] ?? '';
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
    $failedAction = 'Failed Login';
    $failedDetails = 'Invalid password attempt';
    $log_stmt = $conn->prepare("INSERT INTO activity_log (user_id, username, action, details, ip_address, user_agent) VALUES (?, ?, ?, ?, ?, ?)");
    if ($log_stmt) {
        $log_stmt->bind_param("isssss", $userId, $username, $failedAction, $failedDetails, $ip_address, $user_agent);
        $log_stmt->execute();
        $log_stmt->close();
    }

    // Log to login_history table if it exists
    $login_history_check = $conn->query("SHOW TABLES LIKE 'login_history'");
    if ($login_history_check && $login_history_check->num_rows > 0) {
        $history_stmt = $conn->prepare("INSERT INTO login_history (user_id, username, ip_address, user_agent, login_status, failure_reason) VALUES (?, ?, ?, ?, 'failed', 'Invalid password')");
        if ($history_stmt) {
            $history_stmt->bind_param("isss", $userId, $username, $ip_address, $user_agent);
            $history_stmt->execute();
            $history_stmt->close();
        }
    }
    if ($login_history_check) $login_history_check->free();

    http_response_code(401);
    echo json_encode(['message' => 'Invalid email or password.']);
    exit;
}

// Regenerate session ID to prevent session fixation
session_regenerate_id(true);

// Reset rate limit on successful login
resetRateLimit($email, 'login');

// Store user in session
$_SESSION['user_id'] = $userId;
$_SESSION['logged_in_at'] = time();
$_SESSION['email'] = $email;
$_SESSION['username'] = $username;
$_SESSION['role'] = $row['role'] ?? null;

// Log successful login (best-effort, never fatal)
$ip_address = $_SERVER['REMOTE_ADDR'] ?? '';
$user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
$log_stmt = $conn->prepare("INSERT INTO activity_log (user_id, username, action, details, ip_address, user_agent) VALUES (?, ?, ?, ?, ?, ?)");
if ($log_stmt) {
    $successAction = 'Successful Login';
    $successDetails = 'User logged in successfully';
    $log_stmt->bind_param("isssss", $userId, $username, $successAction, $successDetails, $ip_address, $user_agent);
    $log_stmt->execute();
    $log_stmt->close();
}

// Log to login_history table if it exists
$login_history_check = $conn->query("SHOW TABLES LIKE 'login_history'");
if ($login_history_check && $login_history_check->num_rows > 0) {
    $history_stmt = $conn->prepare("INSERT INTO login_history (user_id, username, ip_address, user_agent, login_status) VALUES (?, ?, ?, ?, 'success')");
    if ($history_stmt) {
        $history_stmt->bind_param("isss", $userId, $username, $ip_address, $user_agent);
        $history_stmt->execute();
        $history_stmt->close();
    }
}
if ($login_history_check) $login_history_check->free();

// Update last_login in users table — only if the column actually exists.
$hasLastLogin = false;
$colCheck = $conn->query("SHOW COLUMNS FROM users LIKE 'last_login'");
if ($colCheck && $colCheck->num_rows > 0) {
    $hasLastLogin = true;
}
if ($colCheck) $colCheck->free();

if ($hasLastLogin) {
    $update_stmt = $conn->prepare("UPDATE users SET last_login = NOW() WHERE user_id = ?");
    if ($update_stmt) {
        $update_stmt->bind_param("i", $userId);
        $update_stmt->execute();
        $update_stmt->close();
    }
}

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
        if ($delStmt) {
            $delStmt->bind_param('i', $userId);
            $delStmt->execute();
            $delStmt->close();
        }

        $hashedToken = hash('sha256', $rememberToken);
        $insStmt = $conn->prepare('INSERT INTO remember_tokens (user_id, token, expires_at) VALUES (?, ?, ?)');
        if ($insStmt) {
            $expiresAt = date('Y-m-d H:i:s', $rememberExpires);
            $insStmt->bind_param('iss', $userId, $hashedToken, $expiresAt);
            $insStmt->execute();
            $insStmt->close();
        }
    }

    setSecureCookie('remember_token', $rememberToken, $rememberExpires);
    setSecureCookie('remember_uid', (string)$userId, $rememberExpires);
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
        clearSecureCookie('remember_token');
        clearSecureCookie('remember_uid');
    }
}

$conn->close();

// Return a success response
echo json_encode([
    'message' => 'Login successful.',
    'redirect' => 'dashboard.html',
    'remember_me' => $rememberMe
]);
