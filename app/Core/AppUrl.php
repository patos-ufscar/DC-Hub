<?php
declare(strict_types=1);

namespace App\Core;

/** URL pública do app (compartilhamento, links absolutos). */
final class AppUrl
{
    private static ?string $dotEnvAppUrl = null;

    public static function base(): string
    {
        $configured = self::configuredUrl();
        $isProduction = self::appEnv() === 'production';

        if ($configured !== '') {
            return $configured;
        }

        if ($isProduction) {
            throw new \RuntimeException(
                'APP_URL deve estar definido em produção (ex.: https://dchub.seudominio.br).'
            );
        }

        return self::detectFromRequest();
    }

    /** URL absoluta com query string (ex.: evento=1, atividade=2). */
    public static function share(string $query): string
    {
        $query = ltrim($query, '?');
        $base = self::base();

        return $query === '' ? $base . '/' : $base . '/?' . $query;
    }

    private static function configuredUrl(): string
    {
        $fromFile = self::appUrlFromDotEnvFile();
        if ($fromFile !== '') {
            return $fromFile;
        }

        $fromEnv = self::env('APP_URL');
        if ($fromEnv !== '') {
            return rtrim($fromEnv, '/');
        }

        return '';
    }

    /** Lê APP_URL direto do .env (fonte de verdade no servidor). */
    private static function appUrlFromDotEnvFile(): string
    {
        if (self::$dotEnvAppUrl !== null) {
            return self::$dotEnvAppUrl;
        }

        self::$dotEnvAppUrl = '';

        $path = self::dotEnvPath();
        if (!is_readable($path)) {
            return self::$dotEnvAppUrl;
        }

        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if ($lines === false) {
            return self::$dotEnvAppUrl;
        }

        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#')) {
                continue;
            }

            $pos = strpos($line, '=');
            if ($pos === false) {
                continue;
            }

            $key = trim(substr($line, 0, $pos));
            $key = preg_replace('/^\xEF\xBB\xBF/', '', $key) ?? $key;
            if ($key !== 'APP_URL') {
                continue;
            }

            $value = self::normalizeEnvValue(trim(substr($line, $pos + 1)));
            if ($value !== '') {
                self::$dotEnvAppUrl = rtrim($value, '/');
            }
            break;
        }

        return self::$dotEnvAppUrl;
    }

    private static function dotEnvPath(): string
    {
        return dirname(__DIR__, 2) . '/.env';
    }

    private static function normalizeEnvValue(string $value): string
    {
        $value = trim($value, " \t\n\r\0\x0B");

        if (
            (str_starts_with($value, '"') && str_ends_with($value, '"')) ||
            (str_starts_with($value, "'") && str_ends_with($value, "'"))
        ) {
            $value = substr($value, 1, -1);
        }

        return trim($value);
    }

    private static function appEnv(): string
    {
        $env = self::env('APP_ENV');
        return $env !== '' ? $env : 'production';
    }

    private static function env(string $key, string $default = ''): string
    {
        $value = $_ENV[$key] ?? getenv($key);
        if ($value === false || $value === null) {
            return $default;
        }

        return self::normalizeEnvValue((string) $value);
    }

    private static function detectFromRequest(): string
    {
        $scheme = 'http';

        if (!empty($_SERVER['HTTP_X_FORWARDED_PROTO'])) {
            $forwarded = strtolower((string) $_SERVER['HTTP_X_FORWARDED_PROTO']);
            $scheme = str_contains($forwarded, 'https') ? 'https' : 'http';
        } elseif (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
            $scheme = 'https';
        }

        $host = $_SERVER['HTTP_X_FORWARDED_HOST']
            ?? $_SERVER['HTTP_HOST']
            ?? 'localhost';

        if (str_contains($host, ',')) {
            $host = trim(explode(',', $host)[0]);
        }

        $scriptDir = dirname($_SERVER['SCRIPT_NAME'] ?? '/');
        $path = rtrim(str_replace('\\', '/', $scriptDir), '/');

        if ($path === '/' || $path === '') {
            $path = '';
        }

        return $scheme . '://' . $host . $path;
    }
}
