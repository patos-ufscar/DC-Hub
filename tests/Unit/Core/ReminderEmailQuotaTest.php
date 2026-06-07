<?php
declare(strict_types=1);

namespace Tests\Unit\Core;

use App\Core\ReminderEmailQuota;
use Tests\Support\SqliteTestCase;

final class ReminderEmailQuotaTest extends SqliteTestCase
{
    public function testDailyLimitFromEnv(): void
    {
        $_ENV['REMINDER_EMAIL_DAILY_LIMIT'] = '30';
        $quota = new ReminderEmailQuota($this->db);

        self::assertSame(30, $quota->dailyLimit());
    }

    public function testRemainingDecreasesAfterRecord(): void
    {
        $_ENV['REMINDER_EMAIL_DAILY_LIMIT'] = '3';
        $quota = new ReminderEmailQuota($this->db);

        self::assertSame(3, $quota->remaining());

        $quota->record(ReminderEmailQuota::CATEGORY_REMINDER);
        $quota->record(ReminderEmailQuota::CATEGORY_REMINDER);

        self::assertSame(1, $quota->remaining());
        self::assertTrue($quota->canSendReminder());
    }

    public function testPasswordResetDoesNotConsumeReminderQuota(): void
    {
        $_ENV['REMINDER_EMAIL_DAILY_LIMIT'] = '2';
        $quota = new ReminderEmailQuota($this->db);

        $quota->record(ReminderEmailQuota::CATEGORY_PASSWORD_RESET);
        $quota->record(ReminderEmailQuota::CATEGORY_PASSWORD_RESET);

        self::assertSame(2, $quota->remaining());
        self::assertSame(0, $quota->sentToday(ReminderEmailQuota::CATEGORY_REMINDER));
    }

    public function testCanSendReminderIsFalseWhenExhausted(): void
    {
        $_ENV['REMINDER_EMAIL_DAILY_LIMIT'] = '1';
        $quota = new ReminderEmailQuota($this->db);
        $quota->record(ReminderEmailQuota::CATEGORY_REMINDER);

        self::assertFalse($quota->canSendReminder());
    }
}
