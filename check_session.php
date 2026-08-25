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

require 'db_connection.php';

$loggedIn = false;
$userId = null;
$email = null;
$username = null;
$role = null;

if (isset($_SESSION['user_id']) && !empty($_SESSION['user_id'])) {
    $userId = (int)$_SESSION['user_id'];
    $email = $_SESSION['email'] ?? null;
    $username = $_SESSION['username'] ?? null;
    
    // Check for session timeout (30 minutes of inactivity)
    $session_timeout = 30 * 60; // 30 minutes in seconds
    if (isset($_SESSION['logged_in_at']) && (time() - $_SESSION['logged_in_at']) > $session_timeout) {
        // Session expired, clear it
        session_unset();
        session_destroy();
        // Proceed to remember me check below
    } else {
        // Update last activity time
        $_SESSION['logged_in_at'] = time();

        $stmt = $conn->prepare('SELECT user_id, username, email, role, is_active FROM users WHERE user_id = ? LIMIT 1');
        $stmt->bind_param('i', $userId);
        if ($stmt->execute()) {
            $result = $stmt->get_result();
            if ($result->num_rows === 1) {
                $row = $result->fetch_assoc();
                if ($row['is_active']) {
                    $loggedIn = true;
                    $email = $row['email'];
                    $username = $row['username'];
                    $role = $row['role'];
                    $_SESSION['username'] = $username;
                }
            }
            $result->free();
        }
        $stmt->close();
    }
}

if (!$loggedIn && !empty($_COOKIE['remember_token']) && !empty($_COOKIE['remember_uid'])) {
    $rememberUid = (int)$_COOKIE['remember_uid'];
    $rememberToken = $_COOKIE['remember_token'];
    $hashedToken = hash('sha256', $rememberToken);

    $rememberTableExists = false;
    $checkTable = $conn->query("SHOW TABLES LIKE 'remember_tokens'");
    if ($checkTable && $checkTable->num_rows === 1) {
        $rememberTableExists = true;
    }
    if ($checkTable) $checkTable->free();

    if ($rememberTableExists) {
        $stmt = $conn->prepare('SELECT user_id, expires_at FROM remember_tokens WHERE user_id = ? AND token = ? LIMIT 1');
        $stmt->bind_param('is', $rememberUid, $hashedToken);
        if ($stmt->execute()) {
            $result = $stmt->get_result();
            if ($result->num_rows === 1) {
                $row = $result->fetch_assoc();
                $expiresAt = strtotime($row['expires_at']);
                if ($expiresAt > time()) {
                    $validUserId = (int)$row['user_id'];
                    $userStmt = $conn->prepare('SELECT user_id, username, email, role, is_active FROM users WHERE user_id = ? LIMIT 1');
                    $userStmt->bind_param('i', $validUserId);
                    if ($userStmt->execute()) {
                        $userResult = $userStmt->get_result();
                        if ($userResult->num_rows === 1) {
                            $userRow = $userResult->fetch_assoc();
                            if ($userRow['is_active']) {
                                session_regenerate_id(true);
                                $_SESSION['user_id'] = $validUserId;
                                $_SESSION['email'] = $userRow['email'];
                                $_SESSION['username'] = $userRow['username'];
                                $_SESSION['logged_in_at'] = time();
                                $loggedIn = true;
                                $userId = $validUserId;
                                $email = $userRow['email'];
                                $username = $userRow['username'];
                                $role = $userRow['role'];
                            }
                        }
                        $userResult->free();
                    }
                    $userStmt->close();
                }
            }
            $result->free();
        }
        $stmt->close();
    } else {
        setcookie('remember_token', '', time() - 3600, '/');
        setcookie('remember_uid', '', time() - 3600, '/');
    }
}

$conn->close();

if ($loggedIn) {
    http_response_code(200);
    echo json_encode([
        'logged_in' => true,
        'user_id' => $userId,
        'email' => $email,
        'username' => $username,
        'role' => $role
    ]);
} else {
    http_response_code(401);
    echo json_encode([
        'logged_in' => false,
        'message' => 'Not authenticated'
    ]);
}
