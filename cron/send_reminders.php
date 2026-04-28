<?php
declare(strict_types=1);

// Cron Job: Envio de lembretes automáticos (RF06)
// Executar via cron:
//   0,30 * * * * php /caminho/para/DC-Hub/cron/send_reminders.php

require_once dirname(__DIR__) . '/app/bootstrap.php';

use App\Core\Database;
use App\Core\Mailer;
use App\Models\Activity;

try {
    $db            = Database::getConnection();
    $activityModel = new Activity($db);
    $mailer        = new Mailer();

    // Send reminders for activities starting in 24 hours
    $upcoming24h = $activityModel->getUpcomingWithRsvp(24);
    $sent = 0;

    foreach ($upcoming24h as $item) {
        $subject = "Lembrete: {$item['titulo']} amanhã!";
        $body = sprintf(
            '<h2>Olá, %s!</h2>
            <p>Lembrete: a atividade <strong>%s</strong> do evento <strong>%s</strong> acontecerá amanhã.</p>
            <p><strong>Data:</strong> %s<br>
            <strong>Horário:</strong> %s - %s<br>
            <strong>Local:</strong> %s<br>
            <strong>Grupo:</strong> %s</p>
            <p>Nos vemos lá! 🎓</p>
            <p><small>DC Hub — Departamento de Computação</small></p>',
            htmlspecialchars($item['user_nome'], ENT_QUOTES, 'UTF-8'),
            htmlspecialchars($item['titulo'], ENT_QUOTES, 'UTF-8'),
            htmlspecialchars($item['evento_titulo'], ENT_QUOTES, 'UTF-8'),
            date('d/m/Y', strtotime($item['data'])),
            substr($item['hora_inicio'], 0, 5),
            substr($item['hora_fim'], 0, 5),
            htmlspecialchars($item['local_nome'], ENT_QUOTES, 'UTF-8'),
            htmlspecialchars($item['grupo_nome'], ENT_QUOTES, 'UTF-8')
        );

        if ($mailer->send($item['user_email'], $subject, $body)) {
            $sent++;
        }
    }

    // Send reminders for activities starting in 1 hour
    $upcoming1h = $activityModel->getUpcomingWithRsvp(1);

    foreach ($upcoming1h as $item) {
        $subject = "Começa em breve: {$item['titulo']}!";
        $body = sprintf(
            '<h2>Olá, %s!</h2>
            <p>A atividade <strong>%s</strong> começa em menos de 1 hora!</p>
            <p><strong>Horário:</strong> %s<br>
            <strong>Local:</strong> %s</p>
            <p><small>DC Hub — Departamento de Computação</small></p>',
            htmlspecialchars($item['user_nome'], ENT_QUOTES, 'UTF-8'),
            htmlspecialchars($item['titulo'], ENT_QUOTES, 'UTF-8'),
            substr($item['hora_inicio'], 0, 5),
            htmlspecialchars($item['local_nome'], ENT_QUOTES, 'UTF-8')
        );

        if ($mailer->send($item['user_email'], $subject, $body)) {
            $sent++;
        }
    }

    echo date('Y-m-d H:i:s') . " — Lembretes enviados: {$sent}\n";
} catch (\Throwable $e) {
    error_log('Cron reminder error: ' . $e->getMessage());
    echo "ERRO: " . $e->getMessage() . "\n";
    exit(1);
}
