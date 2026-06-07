<?php
declare(strict_types=1);

namespace App\Core;

use DateTimeImmutable;
use DateTimeZone;

final class AppTimezone
{
    public static function zone(): DateTimeZone
    {
        $name = trim($_ENV['APP_TIMEZONE'] ?? 'America/Sao_Paulo');
        try {
            return new DateTimeZone($name);
        } catch (\Throwable) {
            return new DateTimeZone('America/Sao_Paulo');
        }
    }

    public static function now(): DateTimeImmutable
    {
        return new DateTimeImmutable('now', self::zone());
    }

    public static function today(): string
    {
        return self::now()->format('Y-m-d');
    }
}
