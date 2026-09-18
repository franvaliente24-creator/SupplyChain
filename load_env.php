<?php
/**
 * Simple .env file loader for PHP
 * This file loads environment variables from a .env file in the project root
 * Place this file at the top of your PHP scripts or include it in your configuration
 */

function loadEnv($path = null) {
    if ($path === null) {
        $path = __DIR__ . DIRECTORY_SEPARATOR . '.env';
    }

    if (!file_exists($path)) {
        return false;
    }

    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        // Skip comments
        if (strpos(trim($line), '#') === 0) {
            continue;
        }

        // Parse key=value pairs
        if (strpos($line, '=') !== false) {
            list($key, $value) = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);

            // Remove quotes if present
            if (preg_match('/^(["\'])(.*)\1$/', $value, $matches)) {
                $value = $matches[2];
            }

            // Set environment variable if not already set
            if (!getenv($key)) {
                putenv("$key=$value");
                $_ENV[$key] = $value;
            }
        }
    }

    return true;
}

// Load .env file if it exists
loadEnv();
?>
