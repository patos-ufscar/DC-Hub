<?php
declare(strict_types=1);

namespace App\Core;

/** URL pública do app (compartilhamento, links absolutos). */
final class AppUrl
{
    /** @var list<string> */
    private const URL_ENV_KEYS = ['APP_URL', 'SITE_URL', 'PUBLIC_URL', 'BASE_URL'];

    public static function base(): string
    {
        $configured = self::configuredUrl();
        $isProduction = self::env('APP_ENV', 'production') === 'production';

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
        foreach (self::URL_ENV_KEYS as $key) {
            $value = self::env($key);
            if ($value !== '') {
                return rtrim($value, '/');
            }
        }

        return '';
    }

    private static function env(string $key, string $default = ''): string
    {
        $value = $_ENV[$key] ?? getenv($key);
        if ($value === false || $value === null) {
            return $default;
        }

        return trim((string) $value);
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

        // X-Forwarded-Host pode ser lista "host1, host2"
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
