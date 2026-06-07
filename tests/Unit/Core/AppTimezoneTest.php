<?php
declare(strict_types=1);

namespace Tests\Unit\Core;

use App\Core\AppTimezone;
use PHPUnit\Framework\TestCase;

final class AppTimezoneTest extends TestCase
{
    public function testTodayUsesConfiguredTimezone(): void
    {
        $_ENV['APP_TIMEZONE'] = 'America/Sao_Paulo';

        $today = AppTimezone::today();
        $now   = AppTimezone::now();

        self::assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2}$/', $today);
        self::assertSame($today, $now->format('Y-m-d'));
    }

    public function testInvalidTimezoneFallsBackToSaoPaulo(): void
    {
        $_ENV['APP_TIMEZONE'] = 'Not/A_Timezone';

        $zone = AppTimezone::zone();

        self::assertSame('America/Sao_Paulo', $zone->getName());
    }
}
