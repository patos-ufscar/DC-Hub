<?php
declare(strict_types=1);

namespace App\Core;

use DateTimeImmutable;

/**
 * Escolhe quais inscrições (user × atividade) devem ser lembradas hoje.
 * Cada par recebe no máximo um e-mail (tipo scheduled); várias atividades do mesmo
 * usuário no mesmo dia entram num único digest.
 */
final class ReminderPlanner
{
    public const TIPO = 'scheduled';

    public function __construct(
        private int $horizonDays = 7,
        private int $maxLeadDays = 7,
        private int $lateSignupDays = 2,
    ) {}

    public static function fromEnv(): self
    {
        $horizon = max(1, (int) ($_ENV['REMINDER_PLANNING_HORIZON_DAYS'] ?? 7));
        $lead    = max(1, (int) ($_ENV['REMINDER_MAX_LEAD_DAYS'] ?? 7));

        return new self($horizon, $lead, 2);
    }

    /**
     * @param list<array<string, mixed>> $entries linhas de Activity::listPendingReminderEntries
     * @param callable(int, int): bool   $alreadyReminded (userId, atividadeId)
     * @return list<array{user_id: int, email: string, nome: string, items: list<array>}>
     */
    public function buildTodayDigests(
        array $entries,
        callable $alreadyReminded,
        DateTimeImmutable $now,
        int $remainingBudget
    ): array {
        if ($remainingBudget <= 0 || $entries === []) {
            return [];
        }

        $today = $now->format('Y-m-d');
        $due   = [];

        foreach ($entries as $row) {
            $userId = (int) ($row['user_id'] ?? 0);
            $actId  = (int) ($row['atividade_id'] ?? 0);
            if ($userId <= 0 || $actId <= 0) {
                continue;
            }
            if ($alreadyReminded($userId, $actId)) {
                continue;
            }

            $actStart = $this->activityStart($row);
            if ($actStart <= $now) {
                continue;
            }

            if (!$this->isWithinHorizon($actStart, $now)) {
                continue;
            }

            if (!$this->shouldSendToday($row, $actStart, $now, $today)) {
                continue;
            }

            $due[] = [
                'row'        => $row,
                'user_id'    => $userId,
                'act_id'     => $actId,
                'act_start'  => $actStart,
                'urgency'    => $actStart->getTimestamp(),
            ];
        }

        if ($due === []) {
            return [];
        }

        usort($due, static fn(array $a, array $b): int => $a['urgency'] <=> $b['urgency']);

        $byUser = [];
        foreach ($due as $item) {
            $uid = $item['user_id'];
            $row = $item['row'];
            $byUser[$uid]['meta'] = [
                'email' => (string) ($row['user_email'] ?? ''),
                'nome'  => (string) ($row['user_nome'] ?? ''),
            ];
            $byUser[$uid]['urgency'] = min($byUser[$uid]['urgency'] ?? PHP_INT_MAX, $item['urgency']);
            $byUser[$uid]['items'][] = $row;
        }

        uasort(
            $byUser,
            static fn(array $a, array $b): int => ($a['urgency'] ?? PHP_INT_MAX) <=> ($b['urgency'] ?? PHP_INT_MAX)
        );

        $digests = [];
        foreach ($byUser as $uid => $bundle) {
            if (count($digests) >= $remainingBudget) {
                break;
            }
            $items = $bundle['items'] ?? [];
            if ($items === [] || ($bundle['meta']['email'] ?? '') === '') {
                continue;
            }
            usort(
                $items,
                static fn(array $a, array $b): int =>
                    ($a['data'] ?? '') <=> ($b['data'] ?? '')
                    ?: ($a['hora_inicio'] ?? '') <=> ($b['hora_inicio'] ?? '')
            );
            $digests[] = [
                'user_id' => (int) $uid,
                'email'   => $bundle['meta']['email'],
                'nome'    => $bundle['meta']['nome'],
                'items'   => $items,
            ];
        }

        return $digests;
    }

    private function activityStart(array $row): DateTimeImmutable
    {
        $zone = AppTimezone::zone();
        $raw  = ($row['data'] ?? '') . ' ' . ($row['hora_inicio'] ?? '00:00:00');

        return new DateTimeImmutable($raw, $zone);
    }

    private function isWithinHorizon(DateTimeImmutable $actStart, DateTimeImmutable $now): bool
    {
        $limit = $now->modify('+' . $this->horizonDays . ' days');

        return $actStart <= $limit;
    }

    private function shouldSendToday(
        array $row,
        DateTimeImmutable $actStart,
        DateTimeImmutable $now,
        string $today
    ): bool {
        $actDate   = $actStart->format('Y-m-d');
        $todayDt   = new DateTimeImmutable($today, AppTimezone::zone());
        $daysUntil = (int) $todayDt->diff(new DateTimeImmutable($actDate, AppTimezone::zone()))->days;
        $isPast    = $actDate < $today;

        if ($isPast) {
            return false;
        }

        $earliest = $this->earliestSendDate($actDate, $today);
        if ($today < $earliest) {
            return false;
        }

        if ($daysUntil <= 1) {
            return true;
        }

        if ($this->isLateSignup($row, $now) && $daysUntil <= $this->lateSignupDays + 1) {
            return true;
        }

        $preferred = $this->preferredSpreadDate($actDate, $daysUntil, $earliest);
        if ($today >= $preferred) {
            return true;
        }

        return false;
    }

    private function earliestSendDate(string $actDate, string $today): string
    {
        $zone     = AppTimezone::zone();
        $earliest = (new DateTimeImmutable($actDate, $zone))
            ->modify('-' . $this->maxLeadDays . ' days')
            ->format('Y-m-d');

        return $earliest > $today ? $earliest : $today;
    }

    private function preferredSpreadDate(string $actDate, int $daysUntil, string $earliest): string
    {
        $zone = AppTimezone::zone();
        $lead = min($this->maxLeadDays, max(2, (int) ceil($daysUntil * 0.45)));
        $pref = (new DateTimeImmutable($actDate, $zone))
            ->modify('-' . $lead . ' days')
            ->format('Y-m-d');

        return $pref < $earliest ? $earliest : $pref;
    }

    private function isLateSignup(array $row, DateTimeImmutable $now): bool
    {
        $created = trim((string) ($row['inscricao_em'] ?? ''));
        if ($created === '') {
            return false;
        }

        try {
            $at = new DateTimeImmutable($created, AppTimezone::zone());
        } catch (\Throwable) {
            return false;
        }

        $cutoff = $now->modify('-' . $this->lateSignupDays . ' days');

        return $at >= $cutoff;
    }
}
