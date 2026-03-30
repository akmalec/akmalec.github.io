<?php

/**
 * Load environment configuration from .env file
 * Supports both manual .env parsing and vlucas/phpdotenv library
 */

// Try to use vlucas/phpdotenv if available (recommended)
if (file_exists(__DIR__ . '/../../vendor/autoload.php')) {
    require_once __DIR__ . '/../../vendor/autoload.php';
    
    if (class_exists('Dotenv\Dotenv')) {
        try {
            $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../../');
            $dotenv->load();
        } catch (Exception $e) {
            error_log("Error loading .env file: " . $e->getMessage());
        }
    }
} else {
    // Fallback: Manual .env parsing if Composer not available
    $envFile = __DIR__ . '/../../.env';
    
    if (file_exists($envFile)) {
        $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        
        foreach ($lines as $line) {
            // Skip comments
            if (strpos(trim($line), '#') === 0) {
                continue;
            }
            
            // Parse KEY=VALUE
            if (strpos($line, '=') !== false) {
                list($key, $value) = explode('=', $line, 2);
                $key = trim($key);
                $value = trim($value);
                
                // Remove quotes if present
                if ((strpos($value, '"') === 0 && strrpos($value, '"') === strlen($value) - 1) ||
                    (strpos($value, "'") === 0 && strrpos($value, "'") === strlen($value) - 1)) {
                    $value = substr($value, 1, -1);
                }
                
                // Set environment variable
                putenv("$key=$value");
            }
        }
    }
}

// Ensure required variables exist (with defaults)
if (!getenv('ENVIRONMENT')) {
    putenv('ENVIRONMENT=production');
}

if (!getenv('SITE_URL')) {
    putenv('SITE_URL=https://akmalec.github.io/pestabukumelaka/');
}
