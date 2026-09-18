<?php
// Load centralized session configuration
require_once __DIR__ . '/session_config.php';

// Load CSRF protection
require_once __DIR__ . '/csrf_config.php';

header('Content-Type: application/json');

// Generate and return CSRF token
$token = getCsrfToken();
echo json_encode(['csrf_token' => $token]);
?>