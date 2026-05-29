<?php
declare(strict_types=1);

namespace App\Core;

/** URL pública do app (compartilhamento, links absolutos). */
final class AppUrl
{
    public static function base(): string
    {
        $configured = trim($_ENV['APP_URL'] ?? '');
        $isProduction = ($_ENV['APP_ENV'] ?? 'production') === 'production';

        if ($configured !== '') {
            return rtrim($configured, '/');
        }

        if ($isProduction) {
            throw new \RuntimeException(
                'APP_URL deve estar definido em produção (ex.: https://dchub.seudominio.br).'
            );
        }

        return self::detectFromRequest();
    }

    private static function detectFromRequest(): string
    {
        $scheme = 'http';

        if (!empty($_SERVER['HTTP_X_FORWARDED_PROTO'])) {
            $scheme = strtolower((string) $_SERVER['HTTP_X_FORWARDED_PROTO']) === 'https' ? 'https' : 'http';
        } elseif (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
            $scheme = 'https';
        }

        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $scriptDir = dirname($_SERVER['SCRIPT_NAME'] ?? '/');
        $path = rtrim(str_replace('\\', '/', $scriptDir), '/');

        if ($path === '/' || $path === '') {
            $path = '';
        }

        return $scheme . '://' . $host . $path;
    }
}
