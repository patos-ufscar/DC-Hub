<?php
declare(strict_types=1);

namespace Tests\Unit\Core;

use App\Core\AppTimezone;
use App\Core\ReminderPlanner;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

final class ReminderPlannerTest extends TestCase
{
    private ReminderPlanner $planner;

    protected function setUp(): void
    {
        parent::setUp();
        $this->planner = new ReminderPlanner(7, 7, 2);
    }

    public function testReturnsEmptyWhenBudgetIsZero(): void
    {
        $digests = $this->planner->buildTodayDigests(
            [$this->entry(1, 10, '2026-06-08')],
            static fn(): bool => false,
            $this->now('2026-06-07 10:00:00'),
            0
        );

        self::assertSame([], $digests);
    }

    public function testSendsUrgentReminderOneDayBeforeActivity(): void
    {
        $digests = $this->planner->buildTodayDigests(
            [$this->entry(1, 10, '2026-06-08', 'a@test.dev', 'Alice')],
            static fn(): bool => false,
            $this->now('2026-06-07 10:00:00'),
            10
        );

        self::assertCount(1, $digests);
        self::assertSame(1, $digests[0]['user_id']);
        self::assertSame('a@test.dev', $digests[0]['email']);
        self::assertCount(1, $digests[0]['items']);
    }

    public function testBundlesMultipleActivitiesForSameUser(): void
    {
        $entries = [
            $this->entry(1, 10, '2026-06-08', 'a@test.dev', 'Alice', 'Aulão A'),
            $this->entry(1, 11, '2026-06-08', 'a@test.dev', 'Alice', 'Aulão B'),
        ];

        $digests = $this->planner->buildTodayDigests(
            $entries,
            static fn(): bool => false,
            $this->now('2026-06-07 10:00:00'),
            10
        );

        self::assertCount(1, $digests);
        self::assertCount(2, $digests[0]['items']);
    }

    public function testRespectsRemainingBudgetAcrossUsers(): void
    {
        $entries = [
            $this->entry(1, 10, '2026-06-08', 'a@test.dev'),
            $this->entry(2, 10, '2026-06-08', 'b@test.dev'),
        ];

        $digests = $this->planner->buildTodayDigests(
            $entries,
            static fn(): bool => false,
            $this->now('2026-06-07 10:00:00'),
            1
        );

        self::assertCount(1, $digests);
    }

    public function testSkipsAlreadyRemindedPairs(): void
    {
        $digests = $this->planner->buildTodayDigests(
            [$this->entry(1, 10, '2026-06-08')],
            static fn(int $uid, int $aid): bool => $uid === 1 && $aid === 10,
            $this->now('2026-06-07 10:00:00'),
            10
        );

        self::assertSame([], $digests);
    }

    public function testSkipsActivitiesOutsideHorizon(): void
    {
        $digests = $this->planner->buildTodayDigests(
            [$this->entry(1, 10, '2026-07-01')],
            static fn(): bool => false,
            $this->now('2026-06-07 10:00:00'),
            10
        );

        self::assertSame([], $digests);
    }

    public function testLateSignupTriggersCloserToEvent(): void
    {
        $entry = $this->entry(1, 10, '2026-06-12', 'a@test.dev', 'Alice');
        $entry['inscricao_em'] = '2026-06-08 12:00:00';

        $tooEarly = $this->planner->buildTodayDigests(
            [$entry],
            static fn(): bool => false,
            $this->now('2026-06-07 10:00:00'),
            10
        );
        self::assertSame([], $tooEarly);

        $onTime = $this->planner->buildTodayDigests(
            [$entry],
            static fn(): bool => false,
            $this->now('2026-06-09 10:00:00'),
            10
        );
        self::assertCount(1, $onTime);
    }

    /** @return array<string, mixed> */
    private function entry(
        int $userId,
        int $activityId,
        string $date,
        string $email = 'a@test.dev',
        string $nome = 'Alice',
        string $titulo = 'Atividade'
    ): array {
        return [
            'user_id'       => $userId,
            'atividade_id'  => $activityId,
            'user_email'    => $email,
            'user_nome'     => $nome,
            'titulo'        => $titulo,
            'data'          => $date,
            'hora_inicio'   => '19:00:00',
            'hora_fim'      => '21:00:00',
            'local_nome'    => 'Lab',
            'evento_titulo' => null,
            'inscricao_em'  => '2026-01-01 10:00:00',
        ];
    }

    private function now(string $when): DateTimeImmutable
    {
        return new DateTimeImmutable($when, AppTimezone::zone());
    }
}
