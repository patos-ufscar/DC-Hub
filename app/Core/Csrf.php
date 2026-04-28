<?php
declare(strict_types=1);

namespace App\Core;

final class Csrf
{
    public static function generateToken(): string
    {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }

        return $_SESSION['csrf_token'];
    }

    public static function getTokenField(): string
    {
        $token = htmlspecialchars(self::generateToken(), ENT_QUOTES, 'UTF-8');
        return '<input type="hidden" name="csrf_token" value="' . $token . '">';
    }

    public static function validateToken(?string $token): bool
    {
        if ($token === null || empty($_SESSION['csrf_token'])) {
            return false;
        }

        return hash_equals($_SESSION['csrf_token'], $token);
    }

    public static function validateRequest(): bool
    {
        $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;
        return self::validateToken($token);
    }
}
