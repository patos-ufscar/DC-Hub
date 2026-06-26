<?php
declare(strict_types=1);

// Cron: avisos de reagendamento (data/hora de uma atividade foi alterada).
// Compartilha a cota diária de e-mails com os lembretes (categoria 'reminder'),
// então NÃO estoura o limite de 50/dia. Agende um pouco antes do
// send_reminders.php para priorizar os avisos de reagendamento.
//   */15 * * * * php .../send_reschedule_notifications.php

require_once dirname(__DIR__) . '/app/bootstrap.php';

use App\Core\Database;
use App\Core\Mailer;
use App\Core\ReminderEmailQuota;
use App\Core\RescheduleNotificationSender;
use App\Models\RescheduleNotification;

try {
    $db     = Database::getConnection();
    $quota  = new ReminderEmailQuota($db);
    $mailer = new Mailer();
    $sender = new RescheduleNotificationSender(new RescheduleNotification($db), $quota);

    $limit = $quota->dailyLimit();

    if ($quota->remaining() <= 0) {
        echo date('Y-m-d H:i:s')
            . " — Cota diária de e-mails esgotada ({$quota->sentToday()}/{$limit}).\n";
        exit(0);
    }

    $result = $sender->drain(
        static fn(string $to, string $subject, string $body): bool => $mailer->send($to, $subject, $body)
    );

    echo date('Y-m-d H:i:s')
        . " — Avisos de reagendamento: {$result['sent']} enviado(s), "
        . "{$result['failed']} falha(s), {$result['pending_remaining']} pendente(s) "
        . "(cota {$quota->sentToday()}/{$limit})\n";
} catch (\Throwable $e) {
    error_log('Cron reschedule error: ' . $e->getMessage());
    echo 'ERRO: ' . $e->getMessage() . "\n";
    exit(1);
}
