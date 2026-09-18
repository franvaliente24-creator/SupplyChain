<?php
/**
 * CSRF Protection Configuration and Functions
 * Include this file in any PHP script that needs CSRF protection
 */

// Load environment variables from .env file
require_once __DIR__ . '/load_env.php';

// Get CSRF settings from environment variables
$csrfTokenLength = (int)(getenv('CSRF_TOKEN_LENGTH') ?: '32');
$csrfTokenExpiry = (int)(getenv('CSRF_TOKEN_EXPIRY') ?: '3600');

/**
 * Generate a new CSRF token
 */
function generateCsrfToken() {
    global $csrfTokenLength, $csrfTokenExpiry;
    
    if (!isset($_SESSION)) {
        session_start();
    }
    
    $token = bin2hex(random_bytes($csrfTokenLength / 2));
    $_SESSION['csrf_token'] = $token;
    $_SESSION['csrf_token_expiry'] = time() + $csrfTokenExpiry;
    
    return $token;
}

/**
 * Validate CSRF token from POST request
 */
function validateCsrfToken($token) {
    if (!isset($_SESSION)) {
        session_start();
    }
    
    // Check if token exists in session
    if (!isset($_SESSION['csrf_token']) || !isset($_SESSION['csrf_token_expiry'])) {
        return false;
    }
    
    // Check if token has expired
    if (time() > $_SESSION['csrf_token_expiry']) {
        unset($_SESSION['csrf_token']);
        unset($_SESSION['csrf_token_expiry']);
        return false;
    }
    
    // Validate token
    return hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Get current CSRF token (generate if doesn't exist)
 */
function getCsrfToken() {
    if (!isset($_SESSION)) {
        session_start();
    }
    
    // Generate new token if doesn't exist or expired
    if (!isset($_SESSION['csrf_token']) || 
        !isset($_SESSION['csrf_token_expiry']) || 
        time() > $_SESSION['csrf_token_expiry']) {
        return generateCsrfToken();
    }
    
    return $_SESSION['csrf_token'];
}

/**
 * Generate HTML for CSRF token input field
 */
function csrfTokenField() {
    $token = getCsrfToken();
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token) . '">';
}

/**
 * Validate CSRF token from current POST request and exit if invalid
 */
function requireCsrfProtection() {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $token = $_POST['csrf_token'] ?? $_GET['csrf_token'] ?? '';
        
        if (!validateCsrfToken($token)) {
            http_response_code(403);
            header('Content-Type: application/json');
            echo json_encode(['message' => 'Invalid CSRF token. Please refresh the page and try again.']);
            exit;
        }
    }
}

/**
 * Clear CSRF token (useful after successful form submission)
 */
function clearCsrfToken() {
    if (!isset($_SESSION)) {
        session_start();
    }
    
    unset($_SESSION['csrf_token']);
    unset($_SESSION['csrf_token_expiry']);
}
?>