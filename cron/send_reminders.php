<?php
declare(strict_types=1);

// Cron: lembretes planejados (1 e-mail por inscrição, digest por usuário, cota diária)
//   0 8 * * *   php .../send_reminders.php
//   */30 * * * * php .../send_reminders.php

require_once dirname(__DIR__) . '/app/bootstrap.php';

use App\Core\AppTimezone;
use App\Core\AppUrl;
use App\Core\Database;
use App\Core\Mailer;
use App\Core\ReminderEmailQuota;
use App\Core\ReminderPlanner;
use App\Models\Activity;
use App\Models\ReminderLog;

function esc(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function formatActivityLine(array $item): string
{
    $link = esc(AppUrl::share('atividade=' . (int) $item['atividade_id']));
    $titulo = esc($item['titulo'] ?? '');
    $data = esc(date('d/m/Y', strtotime((string) ($item['data']))));
    $hora = esc(substr((string) ($item['hora_inicio'] ?? ''), 0, 5));
    $horaFim = esc(substr((string) ($item['hora_fim'] ?? ''), 0, 5));
    $local = esc($item['local_nome'] ?? '');
    $evento = !empty($item['evento_titulo'])
        ? ' · <em>' . esc($item['evento_titulo']) . '</em>'
        : '';

    return "<li><strong>{$titulo}</strong> — {$data}, {$hora}–{$horaFim} · {$local}{$evento}<br>"
        . "<a href=\"{$link}\">Ver atividade</a></li>";
}

/**
 * @param list<array{user_id: int, email: string, nome: string, items: list<array>}> $digests
 */
function sendPlannedDigests(
    Mailer $mailer,
    ReminderLog $log,
    ReminderEmailQuota $quota,
    array $digests
): int {
    $sent = 0;

    foreach ($digests as $digest) {
        if (!$quota->canSendReminder()) {
            break;
        }

        $uid   = (int) $digest['user_id'];
        $items = $digest['items'] ?? [];
        if ($items === []) {
            continue;
        }

        $nome  = esc($digest['nome'] ?? 'participante');
        $count = count($items);
        $listHtml = '';
        foreach ($items as $item) {
            $listHtml .= formatActivityLine($item);
        }

        $firstDate = date('d/m/Y', strtotime((string) ($items[0]['data'] ?? AppTimezone::today())));
        $subject = $count === 1
            ? 'Lembrete: ' . ($items[0]['titulo'] ?? 'atividade no DC Hub')
            : "Lembrete: {$count} atividades no DC Hub";

        $intro = $count === 1
            ? '<p>Você está inscrito na atividade abaixo:</p>'
            : "<p>Você tem <strong>{$count}</strong> atividades inscritas em breve"
                . ($firstDate !== '' ? " (a partir de {$firstDate})" : '')
                . ':</p>';

        $body = <<<HTML
<h2>Olá, {$nome}!</h2>
{$intro}
<ul>{$listHtml}</ul>
<p>Nos vemos lá!</p>
<p><small>DC Hub — uma iniciativa PATOS.dev</small></p>
HTML;

        if ($mailer->send($digest['email'], $subject, $body)) {
            foreach ($items as $item) {
                $log->markSent($uid, (int) $item['atividade_id'], ReminderPlanner::TIPO);
            }
            $quota->record(ReminderEmailQuota::CATEGORY_REMINDER);
            $sent++;
        } else {
            error_log("Lembrete não enviado: user={$uid} email={$digest['email']}");
        }
    }

    return $sent;
}

try {
    $db = Database::getConnection();
    $planner = ReminderPlanner::fromEnv();
    $activityModel = new Activity($db);
    $mailer = new Mailer();
    $log = new ReminderLog($db);
    $quota = new ReminderEmailQuota($db);

    $limit     = $quota->dailyLimit();
    $already   = $quota->sentToday();
    $remaining = $quota->remaining();

    if ($remaining <= 0) {
        echo date('Y-m-d H:i:s') . " — Cota diária de lembretes esgotada ({$already}/{$limit}).\n";
        exit(0);
    }

    $horizon = max(1, (int) ($_ENV['REMINDER_PLANNING_HORIZON_DAYS'] ?? 7));
    $entries = $activityModel->listPendingReminderEntries($horizon);
    $digests = $planner->buildTodayDigests(
        $entries,
        static fn(int $uid, int $aid): bool => $log->wasReminded($uid, $aid),
        AppTimezone::now(),
        $remaining
    );

    $sent  = sendPlannedDigests($mailer, $log, $quota, $digests);
    $after = $quota->sentToday();
    $pendingUsers = count($digests);

    echo date('Y-m-d H:i:s')
        . " — Lembretes planejados: {$sent}/{$pendingUsers} e-mail(s) (cota {$after}/{$limit})\n";
} catch (\Throwable $e) {
    error_log('Cron reminder error: ' . $e->getMessage());
    echo 'ERRO: ' . $e->getMessage() . "\n";
    exit(1);
}
