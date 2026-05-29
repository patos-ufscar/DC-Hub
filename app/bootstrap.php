<?php
declare(strict_types=1);

// Autoloader
require_once __DIR__ . '/autoload.php';

use App\Core\EnvLoader;
use App\Core\Request;
use App\Core\Session;
use App\Core\Csrf;

// Load environment variables
$envPath = dirname(__DIR__) . '/.env';
if (!is_file($envPath)) {
    if (($_ENV['APP_ENV'] ?? getenv('APP_ENV') ?: 'production') === 'production') {
        http_response_code(500);
        header('Content-Type: text/plain; charset=utf-8');
        echo 'Configuração ausente: arquivo .env não encontrado.';
        exit(1);
    }
    $envPath = dirname(__DIR__) . '/.env.example';
}
EnvLoader::load($envPath);

if (!isset($_ENV['APP_ENV']) || $_ENV['APP_ENV'] === '') {
    $_ENV['APP_ENV'] = 'production';
}

// Error handling
$isProduction = $_ENV['APP_ENV'] === 'production';
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
header(
    "Content-Security-Policy: default-src 'self'; " .
    "script-src 'self' https://cdn.jsdelivr.net https://unpkg.com 'unsafe-inline'; " .
    "style-src 'self' https://cdn.jsdelivr.net https://fonts.googleapis.com 'unsafe-inline'; " .
    "font-src 'self' https://cdn.jsdelivr.net https://fonts.gstatic.com; " .
    "img-src 'self' data:; " .
    "connect-src 'self'; " .
    "frame-ancestors 'self'; " .
    "base-uri 'self'; " .
    "form-action 'self'"
);
if ($isProduction) {
    header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
}

// Start secure session
Session::start();

// Initialize CSRF token
Csrf::generateToken();

// Enforce HTTP method for mutating API actions
Request::enforceMethodForAction($_GET['action'] ?? null);

// Parse JSON request body into $_POST (for XHR requests with Content-Type: application/json)
if (!empty($_SERVER['CONTENT_TYPE']) && str_contains($_SERVER['CONTENT_TYPE'], 'application/json')) {
    $jsonInput = json_decode(file_get_contents('php://input'), true);
    if (is_array($jsonInput)) {
        $_POST = array_merge($_POST, $jsonInput);
    }
}
