<?php
declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Models\RescheduleNotification;
use Tests\Support\SqliteTestCase;

final class RescheduleNotificationTest extends SqliteTestCase
{
    public function testScheduleChangedIgnoresTimeFormatDifferences(): void
    {
        $old = ['data' => '2026-05-10', 'hora_inicio' => '19:00:00', 'hora_fim' => '21:00:00'];

        self::assertFalse(RescheduleNotification::scheduleChanged($old, [
            'data' => '2026-05-10', 'hora_inicio' => '19:00', 'hora_fim' => '21:00',
        ]));
    }

    public function testScheduleChangedDetectsDateOrTimeChange(): void
    {
        $old = ['data' => '2026-05-10', 'hora_inicio' => '19:00:00', 'hora_fim' => '21:00:00'];

        self::assertTrue(RescheduleNotification::scheduleChanged($old, [
            'data' => '2026-05-11', 'hora_inicio' => '19:00', 'hora_fim' => '21:00',
        ]));
        self::assertTrue(RescheduleNotification::scheduleChanged($old, [
            'data' => '2026-05-10', 'hora_inicio' => '20:00', 'hora_fim' => '21:00',
        ]));
        self::assertTrue(RescheduleNotification::scheduleChanged($old, [
            'data' => '2026-05-10', 'hora_inicio' => '19:00', 'hora_fim' => '22:00',
        ]));
    }

    public function testEnqueueCreatesOneRowPerSubscriber(): void
    {
        $this->insertActivity(1, '2026-05-10');
        $this->insertRsvp(1, 1);
        $this->insertStatus(2, 1, 'presente');

        $model  = new RescheduleNotification($this->db);
        $queued = $model->enqueueForActivity(1, $this->oldSchedule(), $this->newSchedule());

        self::assertSame(2, $queued);
        self::assertSame(2, $model->countByStatus('pendente'));
    }

    public function testEnqueueIgnoresAbsentAndNonSubscribers(): void
    {
        $this->insertActivity(1, '2026-05-10');
        $this->insertRsvp(1, 1);
        $this->insertStatus(2, 1, 'ausente');

        $model  = new RescheduleNotification($this->db);
        $queued = $model->enqueueForActivity(1, $this->oldSchedule(), $this->newSchedule());

        self::assertSame(1, $queued);
    }

    public function testReEnqueueSupersedesPreviousPending(): void
    {
        $this->insertActivity(1, '2026-05-10');
        $this->insertRsvp(1, 1);

        $model = new RescheduleNotification($this->db);
        $model->enqueueForActivity(1, $this->oldSchedule(), [
            'data' => '2026-05-12', 'hora_inicio' => '20:00', 'hora_fim' => '22:00',
        ]);
        $model->enqueueForActivity(1, $this->oldSchedule(), [
            'data' => '2026-05-13', 'hora_inicio' => '20:00', 'hora_fim' => '22:00',
        ]);

        self::assertSame(1, $model->countByStatus('pendente'));

        $pending = $model->listPending(10);
        self::assertSame('2026-05-13', $pending[0]['data_nova']);
    }

    public function testListPendingExcludesSentAndFailed(): void
    {
        $this->insertActivity(1, '2026-05-10');
        $this->insertRsvp(1, 1);
        $this->insertRsvp(2, 1);

        $model = new RescheduleNotification($this->db);
        $model->enqueueForActivity(1, $this->oldSchedule(), $this->newSchedule());

        $pending = $model->listPending(10);
        self::assertCount(2, $pending);

        $model->markSent((int) $pending[0]['id']);
        $model->markFailed((int) $pending[1]['id']);

        self::assertSame([], $model->listPending(10));
        self::assertSame(0, $model->countByStatus('pendente'));
        self::assertSame(1, $model->countByStatus('enviado'));
        self::assertSame(1, $model->countByStatus('falhou'));
    }

    /** @return array{data: string, hora_inicio: string, hora_fim: string} */
    private function oldSchedule(): array
    {
        return ['data' => '2026-05-10', 'hora_inicio' => '19:00:00', 'hora_fim' => '21:00:00'];
    }

    /** @return array{data: string, hora_inicio: string, hora_fim: string} */
    private function newSchedule(): array
    {
        return ['data' => '2026-05-12', 'hora_inicio' => '20:00', 'hora_fim' => '22:00'];
    }

    private function insertStatus(int $userId, int $activityId, string $status): void
    {
        $stmt = $this->db->prepare(
            'INSERT INTO inscricoes (user_id, atividade_id, status) VALUES (:uid, :aid, :status)'
        );
        $stmt->execute([':uid' => $userId, ':aid' => $activityId, ':status' => $status]);
    }
}
