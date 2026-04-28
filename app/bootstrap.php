<?php
declare(strict_types=1);

// Autoloader
require_once __DIR__ . '/autoload.php';

use App\Core\EnvLoader;
use App\Core\Session;
use App\Core\Csrf;

// Load environment variables
$envPath = dirname(__DIR__) . '/.env';
if (!is_file($envPath)) {
    $envPath = dirname(__DIR__) . '/.env.example';
}
EnvLoader::load($envPath);

// Error handling
$isProduction = ($_ENV['APP_ENV'] ?? 'development') === 'production';
if ($isProduction) {
    ini_set('display_errors', '0');
    ini_set('log_errors', '1');
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', '1');
    error_reporting(E_ALL);
}

// Security headers
header('X-Frame-Options: SAMEORIGIN');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: strict-origin-when-cross-origin');
if ($isProduction) {
    header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
}

// Start secure session
Session::start();

// Initialize CSRF token
Csrf::generateToken();

// Parse JSON request body into $_POST (for XHR requests with Content-Type: application/json)
if (!empty($_SERVER['CONTENT_TYPE']) && str_contains($_SERVER['CONTENT_TYPE'], 'application/json')) {
    $jsonInput = json_decode(file_get_contents('php://input'), true);
    if (is_array($jsonInput)) {
        $_POST = array_merge($_POST, $jsonInput);
    }
}
