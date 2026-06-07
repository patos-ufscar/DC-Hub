<?php
declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Core\ReminderPlanner;
use App\Models\ReminderLog;
use Tests\Support\SqliteTestCase;

final class ReminderLogTest extends SqliteTestCase
{
    public function testWasRemindedDetectsAnyTipo(): void
    {
        $this->insertActivity(1, '2026-12-01');
        $log = new ReminderLog($this->db);

        self::assertFalse($log->wasReminded(1, 1));

        $log->markSent(1, 1, '24h');

        self::assertTrue($log->wasReminded(1, 1));
        self::assertTrue($log->wasSent(1, 1, '24h'));
        self::assertFalse($log->wasSent(1, 1, ReminderPlanner::TIPO));
    }

    public function testMarkSentIsIdempotentPerTipo(): void
    {
        $this->insertActivity(1, '2026-12-01');
        $log = new ReminderLog($this->db);

        $log->markSent(1, 1, ReminderPlanner::TIPO);
        $log->markSent(1, 1, ReminderPlanner::TIPO);

        $count = (int) $this->db->query('SELECT COUNT(*) FROM lembretes_enviados')->fetchColumn();
        self::assertSame(1, $count);
    }
}
