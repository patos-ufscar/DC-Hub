<?php
declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Models\Activity;
use Tests\Support\SqliteTestCase;

final class ActivityReminderTest extends SqliteTestCase
{
    public function testListPendingReminderEntriesIncludesOnlyRsvp(): void
    {
        $tomorrow = (new \DateTimeImmutable('tomorrow', new \DateTimeZone('America/Sao_Paulo')))
            ->format('Y-m-d');

        $this->db->exec(
            "INSERT INTO usuarios (id, email, senha, nome_exibicao, role)
             VALUES (3, 'c@test.dev', 'hash', 'Carol', 'user')"
        );

        $this->insertActivity(1, $tomorrow);
        $this->insertRsvp(1, 1);
        $this->db->exec(
            "INSERT INTO inscricoes (user_id, atividade_id, status)
             VALUES (3, 1, 'presente')"
        );

        $rows = (new Activity($this->db))->listPendingReminderEntries(7);
        $userIds = array_map(static fn(array $r): int => (int) $r['user_id'], $rows);

        self::assertSame([1], $userIds);
    }

    public function testListPendingReminderEntriesExcludesPastActivities(): void
    {
        $yesterday = (new \DateTimeImmutable('yesterday', new \DateTimeZone('America/Sao_Paulo')))
            ->format('Y-m-d');

        $this->insertActivity(1, $yesterday, '08:00:00');
        $this->insertRsvp(1, 1);

        $rows = (new Activity($this->db))->listPendingReminderEntries(7);

        self::assertSame([], $rows);
    }
}
