<?php
declare(strict_types=1);

namespace App\Core;

final class EnvLoader
{
    public static function load(string $path): void
    {
        if (!is_file($path)) {
            return;
        }

        if (!is_readable($path)) {
            error_log('DC-Hub: .env existe mas não é legível por ' . (PHP_SAPI === 'cli' ? 'CLI' : 'web') . ": {$path}");
            return;
        }

        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if ($lines === false) {
            error_log("DC-Hub: falha ao ler .env: {$path}");
            return;
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
            $value = trim(substr($line, $pos + 1), " \t\n\r\0\x0B");

            if (
                (str_starts_with($value, '"') && str_ends_with($value, '"')) ||
                (str_starts_with($value, "'") && str_ends_with($value, "'"))
            ) {
                $value = substr($value, 1, -1);
            }

            $_ENV[$key] = trim($value);
            putenv($key . '=' . $value);
        }
    }
}
