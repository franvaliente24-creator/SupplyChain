<?php
/**
 * Centralized session configuration for security
 * Include this file at the beginning of any PHP script that needs session management
 */

// Load environment variables from .env file
require_once __DIR__ . '/load_env.php';

// Configure PHP error reporting based on environment
$debugMode = getenv('APP_DEBUG') === 'true' || getenv('APP_DEBUG') === '1';
if ($debugMode) {
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', 0);
    ini_set('display_startup_errors', 0);
    error_reporting(0);
}

// Get security settings from environment variables
$sessionSecure = getenv('SESSION_SECURE') === 'true';
$sessionHttpOnly = getenv('SESSION_HTTPONLY') !== 'false'; // Default to true
$sessionSameSite = getenv('SESSION_SAMESITE') ?: 'Strict';
$sessionTimeout = (int)(getenv('SESSION_TIMEOUT') ?: '1800');

// Configure session cookie parameters for security
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => $sessionTimeout,
        'path' => '/',
        'domain' => '',
        'secure' => $sessionSecure,
        'httponly' => $sessionHttpOnly,
        'samesite' => $sessionSameSite
    ]);
    session_start();
}

// Function to check session timeout
function checkSessionTimeout() {
    $sessionTimeout = (int)(getenv('SESSION_TIMEOUT') ?: '1800');
    if (isset($_SESSION['logged_in_at']) && (time() - $_SESSION['logged_in_at']) > $sessionTimeout) {
        // Session expired
        session_unset();
        session_destroy();
        return false;
    }
    // Update last activity time
    $_SESSION['logged_in_at'] = time();
    return true;
}

// Function to set secure cookies
function setSecureCookie($name, $value, $expires, $path = '/', $domain = '') {
    $sessionSecure = getenv('SESSION_SECURE') === 'true';
    $sessionHttpOnly = getenv('SESSION_HTTPONLY') !== 'false';
    $sessionSameSite = getenv('SESSION_SAMESITE') ?: 'Strict';

    setcookie(
        $name,
        $value,
        [
            'expires' => $expires,
            'path' => $path,
            'domain' => $domain,
            'secure' => $sessionSecure,
            'httponly' => $sessionHttpOnly,
            'samesite' => $sessionSameSite
        ]
    );
}

// Function to clear secure cookies
function clearSecureCookie($name, $path = '/', $domain = '') {
    $sessionSecure = getenv('SESSION_SECURE') === 'true';
    $sessionHttpOnly = getenv('SESSION_HTTPONLY') !== 'false';

    setcookie(
        $name,
        '',
        [
            'expires' => time() - 3600,
            'path' => $path,
            'domain' => $domain,
            'secure' => $sessionSecure,
            'httponly' => $sessionHttpOnly
        ]
    );
}
?>