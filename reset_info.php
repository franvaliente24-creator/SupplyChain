<?php
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(0);

header('Content-Type: application/json');
require 'db_connection.php'; // Include your database connection

// Handle POST request to generate reset token
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $email = $data['email'] ?? '';

    if (!$email) {
        http_response_code(400);
        echo json_encode(['message' => 'Email address is required.']);
        exit;
    }

    // Check if the email exists in the database (use correct user_id column)
    $stmt = $conn->prepare('SELECT user_id, email FROM users WHERE email = ?');
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        echo json_encode(['message' => 'If that email exists, a reset link has been generated.']);
        exit;
    }

    $row = $result->fetch_assoc();
    $userId = $row['user_id'];
    $userEmail = $row['email'];

    // Generate a unique token
    $token = bin2hex(random_bytes(32));
    $expiresAt = date('Y-m-d H:i:s', strtotime('+1 hour'));

    // Delete any existing tokens for this user first
    $delStmt = $conn->prepare('DELETE FROM password_resets WHERE user_id = ?');
    $delStmt->bind_param('i', $userId);
    $delStmt->execute();
    $delStmt->close();

    // Store the token in the database with an expiration time
    $insStmt = $conn->prepare('INSERT INTO password_resets (user_id, token, expires_at) VALUES (?, ?, ?)');
    $insStmt->bind_param('iss', $userId, $token, $expiresAt);

    if ($insStmt->execute()) {
        // Provide a way to access the reset URL since mail() is not configured on localhost
        $resetLink = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http')
            . '://' . $_SERVER['HTTP_HOST']
            . rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\')
            . '/resetpass.html?token=' . urlencode($token);
        
        // Try to send email, but always return success with the link for local development
        $mailSent = @mail($userEmail, 'Password Reset Request', "Click the link to reset your password: $resetLink");

        echo json_encode([
            'message' => 'If that email exists, a reset link has been generated.',
            'reset_url' => $resetLink,
            'mail_sent' => $mailSent
        ]);
    } else {
        http_response_code(500);
        echo json_encode(['message' => 'Failed to generate reset link. Please try again.']);
    }
    $insStmt->close();
    $stmt->close();
    $conn->close();
    exit;
}

// Handle GET request to validate token
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $token = $_GET['token'] ?? '';

    if (!$token) {
        http_response_code(400);
        echo json_encode(['message' => 'Token is required.']);
        exit;
    }

    // Check if the token exists and is valid (use correct user_id column)
    $stmt = $conn->prepare('SELECT email FROM users u JOIN password_resets pr ON u.user_id = pr.user_id WHERE pr.token = ? AND pr.expires_at > NOW()');
    $stmt->bind_param('s', $token);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        http_response_code(404);
        echo json_encode(['message' => 'Invalid or expired token.']);
        exit;
    }

    $row = $result->fetch_assoc();
    echo json_encode(['email' => $row['email']]);
    $stmt->close();
    $conn->close();
}
?>