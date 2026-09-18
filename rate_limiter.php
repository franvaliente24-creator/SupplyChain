<?php
/**
 * Rate Limiter for Login and Sensitive Operations
 * Prevents brute force attacks by limiting request frequency
 */

// Load environment variables from .env file
require_once __DIR__ . '/load_env.php';

// Get rate limiting settings from environment variables
$rateLimitEnabled = getenv('RATE_LIMIT_ENABLED') === 'true';
$rateLimitMaxAttempts = (int)(getenv('RATE_LIMIT_MAX_ATTEMPTS') ?: '5');
$rateLimitWindow = (int)(getenv('RATE_LIMIT_WINDOW') ?: '900'); // 15 minutes default
$rateLimitLockoutDuration = (int)(getenv('RATE_LIMIT_LOCKOUT_DURATION') ?: '1800'); // 30 minutes default

/**
 * Check if rate limiting is enabled
 */
function isRateLimitEnabled() {
    global $rateLimitEnabled;
    return $rateLimitEnabled;
}

/**
 * Get client IP address
 */
function getClientIp() {
    $ip = '';
    
    // Check for forwarded IP (behind proxy)
    if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ips = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
        $ip = trim($ips[0]);
    } elseif (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        $ip = $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['REMOTE_ADDR'])) {
        $ip = $_SERVER['REMOTE_ADDR'];
    }
    
    // Validate IP
    if (filter_var($ip, FILTER_VALIDATE_IP)) {
        return $ip;
    }
    
    return $_SERVER['REMOTE_ADDR'] ?? 'unknown';
}

/**
 * Generate rate limit key
 */
function getRateLimitKey($identifier, $action = 'login') {
    $ip = getClientIp();
    return "rate_limit_{$action}_{$identifier}_{$ip}";
}

/**
 * Check if request is rate limited
 */
function isRateLimited($identifier, $action = 'login') {
    if (!isRateLimitEnabled()) {
        return ['limited' => false, 'remaining' => PHP_INT_MAX];
    }
    
    global $rateLimitMaxAttempts, $rateLimitWindow, $rateLimitLockoutDuration;
    
    if (!isset($_SESSION)) {
        session_start();
    }
    
    $key = getRateLimitKey($identifier, $action);
    $now = time();
    
    // Initialize rate limit data if not exists
    if (!isset($_SESSION[$key])) {
        $_SESSION[$key] = [
            'attempts' => 0,
            'first_attempt' => $now,
            'lockout_until' => 0
        ];
    }
    
    $data = $_SESSION[$key];
    
    // Check if currently locked out
    if ($data['lockout_until'] > $now) {
        $remainingLockout = $data['lockout_until'] - $now;
        return [
            'limited' => true,
            'remaining' => 0,
            'lockout_remaining' => $remainingLockout,
            'message' => "Too many attempts. Please try again in " . ceil($remainingLockout / 60) . " minutes."
        ];
    }
    
    // Reset attempts if window has expired
    if ($now - $data['first_attempt'] > $rateLimitWindow) {
        $_SESSION[$key] = [
            'attempts' => 0,
            'first_attempt' => $now,
            'lockout_until' => 0
        ];
        $data = $_SESSION[$key];
    }
    
    // Check if limit exceeded
    if ($data['attempts'] >= $rateLimitMaxAttempts) {
        // Set lockout
        $_SESSION[$key]['lockout_until'] = $now + $rateLimitLockoutDuration;
        $remainingLockout = $rateLimitLockoutDuration;
        
        return [
            'limited' => true,
            'remaining' => 0,
            'lockout_remaining' => $remainingLockout,
            'message' => "Too many attempts. Please try again in " . ceil($remainingLockout / 60) . " minutes."
        ];
    }
    
    $remaining = $rateLimitMaxAttempts - $data['attempts'];
    
    return [
        'limited' => false,
        'remaining' => $remaining,
        'attempts' => $data['attempts']
    ];
}

/**
 * Record a failed attempt
 */
function recordFailedAttempt($identifier, $action = 'login') {
    if (!isRateLimitEnabled()) {
        return;
    }
    
    if (!isset($_SESSION)) {
        session_start();
    }
    
    $key = getRateLimitKey($identifier, $action);
    $now = time();
    
    if (!isset($_SESSION[$key])) {
        $_SESSION[$key] = [
            'attempts' => 0,
            'first_attempt' => $now,
            'lockout_until' => 0
        ];
    }
    
    $_SESSION[$key]['attempts']++;
}

/**
 * Reset rate limit for successful attempt
 */
function resetRateLimit($identifier, $action = 'login') {
    if (!isset($_SESSION)) {
        session_start();
    }
    
    $key = getRateLimitKey($identifier, $action);
    unset($_SESSION[$key]);
}

/**
 * Get rate limit status for display
 */
function getRateLimitStatus($identifier, $action = 'login') {
    if (!isRateLimitEnabled()) {
        return null;
    }
    
    if (!isset($_SESSION)) {
        session_start();
    }
    
    $key = getRateLimitKey($identifier, $action);
    
    if (!isset($_SESSION[$key])) {
        return null;
    }
    
    $data = $_SESSION[$key];
    $now = time();
    
    return [
        'attempts' => $data['attempts'],
        'max_attempts' => $rateLimitMaxAttempts,
        'window_remaining' => max(0, $rateLimitWindow - ($now - $data['first_attempt'])),
        'is_locked' => $data['lockout_until'] > $now,
        'lockout_remaining' => max(0, $data['lockout_until'] - $now)
    ];
}
?>