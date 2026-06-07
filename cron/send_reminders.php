<?php
declare(strict_types=1);

// Cron: lembretes por e-mail (RF06 + resumo matinal 8h)
//   0 8 * * *   php .../send_reminders.php --type=same_day
//   */30 * * * * php .../send_reminders.php

require_once dirname(__DIR__) . '/app/bootstrap.php';

use App\Core\AppTimezone;
use App\Core\AppUrl;
use App\Core\Database;
use App\Core\Mailer;
use App\Core\ReminderEmailQuota;
use App\Models\Activity;
use App\Models\ReminderLog;

function parseReminderType(array $argv): ?string
{
    foreach ($argv as $arg) {
        if (str_starts_with($arg, '--type=')) {
            $type = substr($arg, 7);
            return in_array($type, ['same_day', 'window'], true) ? $type : null;
        }
    }
    return 'window';
}

function esc(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function sendSameDayDigest(Mailer $mailer, ReminderLog $log, ReminderEmailQuota $quota, array $entries): int
{
    $byUser = [];
    foreach ($entries as $row) {
        $uid = (int) $row['user_id'];
        $aid = (int) $row['atividade_id'];
        if ($log->wasSent($uid, $aid, 'same_day')) {
            continue;
        }
        $byUser[$uid]['meta'] = [
            'email' => $row['user_email'],
            'nome'  => $row['user_nome'],
        ];
        $byUser[$uid]['items'][] = $row;
    }

    $sent = 0;
    foreach ($byUser as $uid => $bundle) {
        if (!$quota->canSendReminder()) {
            break;
        }

        $items = $bundle['items'] ?? [];
        if ($items === []) {
            continue;
        }

        $nome = esc($bundle['meta']['nome'] ?? 'participante');
        $count = count($items);
        $hoje = AppTimezone::now()->format('d/m/Y');
        $listHtml = '';

        foreach ($items as $item) {
            $link = esc(AppUrl::share('atividade=' . (int) $item['atividade_id']));
            $titulo = esc($item['titulo'] ?? '');
            $hora = esc(substr((string) ($item['hora_inicio'] ?? ''), 0, 5));
            $horaFim = esc(substr((string) ($item['hora_fim'] ?? ''), 0, 5));
            $local = esc($item['local_nome'] ?? '');
            $evento = !empty($item['evento_titulo'])
                ? ' · <em>' . esc($item['evento_titulo']) . '</em>'
                : '';
            $listHtml .= "<li><strong>{$titulo}</strong> — {$hora}–{$horaFim} · {$local}{$evento}<br>"
                . "<a href=\"{$link}\">Ver atividade</a></li>";
        }

        $subject = "Seu dia no DC Hub — {$count} atividade(s) hoje";
        $body = <<<HTML
<h2>Bom dia, {$nome}!</h2>
<p>Você tem <strong>{$count}</strong> atividade(s) inscrita(s) para hoje ({$hoje}):</p>
<ul>{$listHtml}</ul>
<p>Nos vemos lá!</p>
<p><small>DC Hub — Departamento de Computação</small></p>
HTML;

        if ($mailer->send($bundle['meta']['email'], $subject, $body)) {
            foreach ($items as $item) {
                $log->markSent($uid, (int) $item['atividade_id'], 'same_day');
            }
            $quota->record(ReminderEmailQuota::CATEGORY_REMINDER);
            $sent++;
        }
    }

    return $sent;
}

function sendWindowReminders(
    Mailer $mailer,
    ReminderLog $log,
    ReminderEmailQuota $quota,
    Activity $activityModel,
    int $hoursAhead,
    string $tipo,
    string $subjectTpl,
    string $bodyIntro
): int {
    $items = $activityModel->getUpcomingWithRsvp($hoursAhead);
    $sent = 0;

    foreach ($items as $item) {
        if (!$quota->canSendReminder()) {
            break;
        }

        $userId = (int) ($item['user_id'] ?? 0);
        $actId = (int) ($item['id'] ?? 0);
        if ($userId <= 0 || $actId <= 0) {
            continue;
        }
        if ($log->wasSent($userId, $actId, $tipo)) {
            continue;
        }

        $nome = esc($item['user_nome'] ?? '');
        $titulo = esc($item['titulo'] ?? '');
        $evento = esc($item['evento_titulo'] ?? 'Atividade avulsa');
        $data = esc(date('d/m/Y', strtotime((string) $item['data'])));
        $hi = esc(substr((string) ($item['hora_inicio'] ?? ''), 0, 5));
        $hf = esc(substr((string) ($item['hora_fim'] ?? ''), 0, 5));
        $local = esc($item['local_nome'] ?? '');
        $grupo = esc($item['grupo_nome'] ?? '');
        $link = esc(AppUrl::share('atividade=' . $actId));

        $subject = sprintf($subjectTpl, $item['titulo'] ?? 'Atividade');
        $body = <<<HTML
<h2>Olá, {$nome}!</h2>
<p>{$bodyIntro}</p>
<p><strong>{$titulo}</strong> ({$evento})</p>
<p><strong>Data:</strong> {$data}<br>
<strong>Horário:</strong> {$hi} – {$hf}<br>
<strong>Local:</strong> {$local}<br>
<strong>Grupo:</strong> {$grupo}</p>
<p><a href="{$link}">Abrir atividade no DC Hub</a></p>
<p><small>DC Hub — Departamento de Computação</small></p>
HTML;

        if ($mailer->send($item['user_email'], $subject, $body)) {
            $log->markSent($userId, $actId, $tipo);
            $quota->record(ReminderEmailQuota::CATEGORY_REMINDER);
            $sent++;
        }
    }

    return $sent;
}

try {
    $runType = parseReminderType($argv ?? []);
    $db = Database::getConnection();
    $activityModel = new Activity($db);
    $mailer = new Mailer();
    $log = new ReminderLog($db);
    $quota = new ReminderEmailQuota($db);
    $sent = 0;
    $limit = $quota->dailyLimit();
    $already = $quota->sentToday();
    $remaining = $quota->remaining();

    if ($remaining <= 0) {
        echo date('Y-m-d H:i:s') . " — Cota diária de lembretes esgotada ({$already}/{$limit}).\n";
        exit(0);
    }

    if ($runType === 'same_day') {
        $today = AppTimezone::today();
        $entries = $activityModel->listTodayRsvpEntries($today);
        $sent = sendSameDayDigest($mailer, $log, $quota, $entries);
        $after = $quota->sentToday();
        echo date('Y-m-d H:i:s') . " — Lembretes same_day ({$today}): {$sent} e-mail(s) (cota {$after}/{$limit})\n";
        exit(0);
    }

    $sent += sendWindowReminders(
        $mailer,
        $log,
        $quota,
        $activityModel,
        24,
        '24h',
        'Lembrete: %s amanhã!',
        'Lembrete: a atividade abaixo acontecerá nas próximas 24 horas.'
    );

    $sent += sendWindowReminders(
        $mailer,
        $log,
        $quota,
        $activityModel,
        1,
        '1h',
        'Começa em breve: %s!',
        'A atividade abaixo começa em menos de 1 hora!'
    );

    $after = $quota->sentToday();
    echo date('Y-m-d H:i:s') . " — Lembretes janela (24h/1h): {$sent} (cota {$after}/{$limit})\n";
} catch (\Throwable $e) {
    error_log('Cron reminder error: ' . $e->getMessage());
    echo 'ERRO: ' . $e->getMessage() . "\n";
    exit(1);
}
