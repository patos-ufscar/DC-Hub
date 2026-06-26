<?php
declare(strict_types=1);

namespace Tests\Unit\Core;

use App\Core\ReminderEmailQuota;
use App\Core\RescheduleNotificationSender;
use App\Models\RescheduleNotification;
use Tests\Support\SqliteTestCase;

final class RescheduleNotificationSenderTest extends SqliteTestCase
{
    public function testDrainSendsAndRecordsQuota(): void
    {
        $_ENV['REMINDER_EMAIL_DAILY_LIMIT'] = '50';
        $model  = $this->seedPending(3);
        $quota  = new ReminderEmailQuota($this->db);
        $sender = new RescheduleNotificationSender($model, $quota);

        $sentTo = [];
        $result = $sender->drain(function (string $to) use (&$sentTo): bool {
            $sentTo[] = $to;
            return true;
        });

        self::assertSame(3, $result['sent']);
        self::assertSame(0, $result['failed']);
        self::assertSame(0, $result['pending_remaining']);
        self::assertCount(3, $sentTo);
        self::assertSame(3, $quota->sentToday(ReminderEmailQuota::CATEGORY_REMINDER));
    }

    public function testDrainStopsAtDailyLimitLeavingRest(): void
    {
        $_ENV['REMINDER_EMAIL_DAILY_LIMIT'] = '2';
        $model  = $this->seedPending(5);
        $quota  = new ReminderEmailQuota($this->db);
        $sender = new RescheduleNotificationSender($model, $quota);

        $result = $sender->drain(static fn(): bool => true);

        self::assertSame(2, $result['sent']);
        self::assertSame(3, $result['pending_remaining']);
        self::assertSame(0, $quota->remaining());
    }

    public function testDrainRespectsQuotaAlreadyConsumedByReminders(): void
    {
        $_ENV['REMINDER_EMAIL_DAILY_LIMIT'] = '3';
        $model = $this->seedPending(5);
        $quota = new ReminderEmailQuota($this->db);
        $quota->record(ReminderEmailQuota::CATEGORY_REMINDER);
        $quota->record(ReminderEmailQuota::CATEGORY_REMINDER);

        $sender = new RescheduleNotificationSender($model, $quota);
        $result = $sender->drain(static fn(): bool => true);

        self::assertSame(1, $result['sent']);
        self::assertSame(4, $result['pending_remaining']);
        self::assertSame(0, $quota->remaining());
    }

    public function testFailedSendMarksFailedAndDoesNotConsumeQuota(): void
    {
        $_ENV['REMINDER_EMAIL_DAILY_LIMIT'] = '50';
        $model  = $this->seedPending(2);
        $quota  = new ReminderEmailQuota($this->db);
        $sender = new RescheduleNotificationSender($model, $quota);

        $result = $sender->drain(static fn(): bool => false);

        self::assertSame(0, $result['sent']);
        self::assertSame(2, $result['failed']);
        self::assertSame(0, $model->countByStatus('pendente'));
        self::assertSame(2, $model->countByStatus('falhou'));
        self::assertSame(0, $quota->sentToday(ReminderEmailQuota::CATEGORY_REMINDER));
    }

    public function testDrainWhenBudgetExhaustedSendsNothing(): void
    {
        $_ENV['REMINDER_EMAIL_DAILY_LIMIT'] = '1';
        $model = $this->seedPending(2);
        $quota = new ReminderEmailQuota($this->db);
        $quota->record(ReminderEmailQuota::CATEGORY_REMINDER);

        $sender = new RescheduleNotificationSender($model, $quota);
        $called = false;
        $result = $sender->drain(function () use (&$called): bool {
            $called = true;
            return true;
        });

        self::assertSame(0, $result['sent']);
        self::assertSame(2, $result['pending_remaining']);
        self::assertFalse($called);
    }

    private function seedPending(int $count): RescheduleNotification
    {
        $this->insertActivity(1, '2026-05-10');

        for ($i = 1; $i <= $count; $i++) {
            $uid = 100 + $i;
            $this->db->exec(
                "INSERT INTO usuarios (id, email, senha, nome_exibicao, role)
                 VALUES ({$uid}, 'u{$uid}@test.dev', 'h', 'User {$uid}', 'user')"
            );
            $this->insertRsvp($uid, 1);
        }

        $model = new RescheduleNotification($this->db);
        $model->enqueueForActivity(
            1,
            ['data' => '2026-05-10', 'hora_inicio' => '19:00:00', 'hora_fim' => '21:00:00'],
            ['data' => '2026-05-12', 'hora_inicio' => '20:00', 'hora_fim' => '22:00']
        );

        return $model;
    }
}
