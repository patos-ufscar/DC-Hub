<?php
declare(strict_types=1);

namespace App\Core;

final class Session
{
    private static bool $started = false;

    public static function start(): void
    {
        if (self::$started || session_status() === PHP_SESSION_ACTIVE) {
            self::$started = true;
            return;
        }

        $isProduction = ($_ENV['APP_ENV'] ?? 'development') === 'production';

        session_set_cookie_params([
            'lifetime' => 0,
            'path'     => '/',
            'domain'   => '',
            'secure'   => $isProduction,
            'httponly'  => true,
            'samesite'=> 'Strict',
        ]);

        session_start();
        self::$started = true;
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        return $_SESSION[$key] ?? $default;
    }

    public static function set(string $key, mixed $value): void
    {
        $_SESSION[$key] = $value;
    }

    public static function remove(string $key): void
    {
        unset($_SESSION[$key]);
    }

    public static function destroy(): void
    {
        $_SESSION = [];

        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params['path'],
                $params['domain'],
                $params['secure'],
                $params['httponly']
            );
        }

        session_destroy();
        self::$started = false;
    }

    public static function regenerate(): void
    {
        session_regenerate_id(true);
    }

    public static function isLoggedIn(): bool
    {
        return isset($_SESSION['user_id']);
    }

    public static function getUser(): ?array
    {
        if (!self::isLoggedIn()) {
            return null;
        }

        return [
            'id'            => $_SESSION['user_id'],
            'email'         => $_SESSION['user_email'] ?? '',
            'nome_exibicao' => $_SESSION['user_nome_exibicao'] ?? '',
            'nome_completo' => $_SESSION['user_nome_completo'] ?? null,
            'role'          => $_SESSION['user_role'] ?? 'user',
            'grupo_id'      => $_SESSION['user_grupo_id'] ?? null,
            'grupo_nome'    => $_SESSION['user_grupo_nome'] ?? null,
        ];
    }

    public static function setUser(array $user): void
    {
        $_SESSION['user_id']             = (int) $user['id'];
        $_SESSION['user_email']          = $user['email'];
        $_SESSION['user_nome_exibicao']  = $user['nome_exibicao'];
        $_SESSION['user_nome_completo']  = $user['nome_completo'] ?? null;
        $_SESSION['user_role']           = $user['role'] ?? 'user';
        $_SESSION['user_grupo_id']       = $user['grupo_id'] ?? null;
        $_SESSION['user_grupo_nome']     = $user['grupo_nome'] ?? null;
    }
}
