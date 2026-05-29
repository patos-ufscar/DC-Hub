<?php
declare(strict_types=1);

namespace App\Core;

final class DatabaseDialect
{
    public static function driver(): string
    {
        return strtolower($_ENV['DB_DRIVER'] ?? 'sqlite');
    }

    public static function isSqlite(): bool
    {
        return self::driver() === 'sqlite';
    }

    public static function durationMinutesExpr(
        string $date = 'a.data',
        string $start = 'a.hora_inicio',
        string $end = 'a.hora_fim'
    ): string {
        if (self::isSqlite()) {
            return "CAST((julianday({$date} || ' ' || {$end}) - julianday({$date} || ' ' || {$start})) * 24 * 60 AS INTEGER)";
        }

        return "TIMESTAMPDIFF(MINUTE, CONCAT({$date}, ' ', {$start}), CONCAT({$date}, ' ', {$end}))";
    }

    public static function activityStartExpr(
        string $date = 'a.data',
        string $time = 'a.hora_inicio'
    ): string {
        if (self::isSqlite()) {
            return "({$date} || ' ' || {$time})";
        }

        return "CONCAT({$date}, ' ', {$time})";
    }

    public static function upcomingActivitiesWhere(string $hoursPlaceholder = ':hours'): string
    {
        $start = self::activityStartExpr();

        if (self::isSqlite()) {
            return "{$start} BETWEEN datetime('now', 'localtime') AND datetime('now', 'localtime', '+' || {$hoursPlaceholder} || ' hours')";
        }

        return "{$start} BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL {$hoursPlaceholder} HOUR)";
    }
}
