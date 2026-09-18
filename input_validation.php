<?php
/**
 * Input Validation Library
 * Provides centralized input validation functions for security
 */

/**
 * Validate email address
 */
function validateEmail($email) {
    $email = trim($email);
    if (empty($email)) {
        return ['valid' => false, 'error' => 'Email is required'];
    }
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return ['valid' => false, 'error' => 'Invalid email format'];
    }
    
    if (strlen($email) > 255) {
        return ['valid' => false, 'error' => 'Email is too long'];
    }
    
    return ['valid' => true, 'value' => $email];
}

/**
 * Validate password strength
 */
function validatePassword($password) {
    if (empty($password)) {
        return ['valid' => false, 'error' => 'Password is required'];
    }
    
    if (strlen($password) < 8) {
        return ['valid' => false, 'error' => 'Password must be at least 8 characters'];
    }
    
    // Check for at least one uppercase letter
    if (!preg_match('/[A-Z]/', $password)) {
        return ['valid' => false, 'error' => 'Password must contain at least one uppercase letter'];
    }
    
    // Check for at least one lowercase letter
    if (!preg_match('/[a-z]/', $password)) {
        return ['valid' => false, 'error' => 'Password must contain at least one lowercase letter'];
    }
    
    // Check for at least one number
    if (!preg_match('/[0-9]/', $password)) {
        return ['valid' => false, 'error' => 'Password must contain at least one number'];
    }
    
    if (strlen($password) > 128) {
        return ['valid' => false, 'error' => 'Password is too long'];
    }
    
    return ['valid' => true, 'value' => $password];
}

/**
 * Validate string length
 */
function validateStringLength($string, $min = 0, $max = 255, $fieldName = 'Field') {
    $string = trim($string);
    $length = strlen($string);
    
    if ($length < $min) {
        return ['valid' => false, 'error' => "$fieldName must be at least $min characters"];
    }
    
    if ($length > $max) {
        return ['valid' => false, 'error' => "$fieldName must not exceed $max characters"];
    }
    
    return ['valid' => true, 'value' => $string];
}

/**
 * Validate integer
 */
function validateInteger($value, $min = null, $max = null, $fieldName = 'Field') {
    if (!is_numeric($value)) {
        return ['valid' => false, 'error' => "$fieldName must be a number"];
    }
    
    $value = (int)$value;
    
    if ($min !== null && $value < $min) {
        return ['valid' => false, 'error' => "$fieldName must be at least $min"];
    }
    
    if ($max !== null && $value > $max) {
        return ['valid' => false, 'error' => "$fieldName must not exceed $max"];
    }
    
    return ['valid' => true, 'value' => $value];
}

/**
 * Validate float/decimal
 */
function validateFloat($value, $min = null, $max = null, $fieldName = 'Field') {
    if (!is_numeric($value)) {
        return ['valid' => false, 'error' => "$fieldName must be a number"];
    }
    
    $value = (float)$value;
    
    if ($min !== null && $value < $min) {
        return ['valid' => false, 'error' => "$fieldName must be at least $min"];
    }
    
    if ($max !== null && $value > $max) {
        return ['valid' => false, 'error' => "$fieldName must not exceed $max"];
    }
    
    return ['valid' => true, 'value' => $value];
}

/**
 * Validate date
 */
function validateDate($date, $format = 'Y-m-d', $fieldName = 'Date') {
    $date = trim($date);
    if (empty($date)) {
        return ['valid' => false, 'error' => "$fieldName is required"];
    }
    
    $d = DateTime::createFromFormat($format, $date);
    if (!$d || $d->format($format) !== $date) {
        return ['valid' => false, 'error' => "Invalid $fieldName format"];
    }
    
    return ['valid' => true, 'value' => $date];
}

/**
 * Sanitize string input
 */
function sanitizeString($string) {
    return htmlspecialchars(trim($string), ENT_QUOTES, 'UTF-8');
}

/**
 * Validate enum value
 */
function validateEnum($value, $allowedValues, $fieldName = 'Field') {
    if (!in_array($value, $allowedValues, true)) {
        return ['valid' => false, 'error' => "Invalid $fieldName value"];
    }
    
    return ['valid' => true, 'value' => $value];
}

/**
 * Validate URL
 */
function validateUrl($url, $fieldName = 'URL') {
    $url = trim($url);
    if (empty($url)) {
        return ['valid' => false, 'error' => "$fieldName is required"];
    }
    
    if (!filter_var($url, FILTER_VALIDATE_URL)) {
        return ['valid' => false, 'error' => "Invalid $fieldName format"];
    }
    
    return ['valid' => true, 'value' => $url];
}

/**
 * Validate phone number (basic format)
 */
function validatePhone($phone, $fieldName = 'Phone number') {
    $phone = trim($phone);
    if (empty($phone)) {
        return ['valid' => false, 'error' => "$fieldName is required"];
    }
    
    // Basic phone validation - allows digits, spaces, dashes, parentheses, plus
    if (!preg_match('/^[\d\s\-\(\)\+]+$/', $phone)) {
        return ['valid' => false, 'error' => "Invalid $fieldName format"];
    }
    
    return ['valid' => true, 'value' => $phone];
}

/**
 * Validate username
 */
function validateUsername($username) {
    $username = trim($username);
    if (empty($username)) {
        return ['valid' => false, 'error' => 'Username is required'];
    }
    
    if (strlen($username) < 3) {
        return ['valid' => false, 'error' => 'Username must be at least 3 characters'];
    }
    
    if (strlen($username) > 50) {
        return ['valid' => false, 'error' => 'Username is too long'];
    }
    
    // Allow alphanumeric, underscores, and hyphens
    if (!preg_match('/^[a-zA-Z0-9_-]+$/', $username)) {
        return ['valid' => false, 'error' => 'Username can only contain letters, numbers, underscores, and hyphens'];
    }
    
    return ['valid' => true, 'value' => $username];
}
?>
