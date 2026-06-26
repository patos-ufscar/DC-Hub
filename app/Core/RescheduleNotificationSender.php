<?php
declare(strict_types=1);

namespace App\Core;

use App\Models\RescheduleNotification;

/**
 * Drena a fila de avisos de reagendamento respeitando a cota diária de e-mails.
 * Cada envio bem-sucedido conta na mesma categoria 'reminder' do log de saída,
 * de modo que reagendamentos e lembretes compartilham o limite de 50/dia.
 */
final class RescheduleNotificationSender
{
    public function __construct(
        private RescheduleNotification $model,
        private ReminderEmailQuota $quota,
    ) {}

    /**
     * @param callable(string $to, string $subject, string $htmlBody): bool $send
     * @return array{sent: int, failed: int, pending_remaining: int}
     */
    public function drain(callable $send): array
    {
        $sent = 0;
        $failed = 0;

        $budget = $this->quota->remaining();
        if ($budget <= 0) {
            return [
                'sent'              => 0,
                'failed'            => 0,
                'pending_remaining' => $this->model->countByStatus('pendente'),
            ];
        }

        foreach ($this->model->listPending($budget) as $row) {
            if (!$this->quota->canSendReminder()) {
                break;
            }

            $id    = (int) ($row['id'] ?? 0);
            $email = trim((string) ($row['user_email'] ?? ''));
            if ($id <= 0 || $email === '') {
                continue;
            }

            [$subject, $body] = $this->buildEmail($row);

            if ($send($email, $subject, $body)) {
                $this->model->markSent($id);
                $this->quota->record(ReminderEmailQuota::CATEGORY_REMINDER);
                $sent++;
            } else {
                $this->model->markFailed($id);
                $failed++;
            }
        }

        return [
            'sent'              => $sent,
            'failed'            => $failed,
            'pending_remaining' => $this->model->countByStatus('pendente'),
        ];
    }

    /** @return array{0: string, 1: string} [subject, htmlBody] */
    private function buildEmail(array $row): array
    {
        $nome   = self::esc((string) ($row['user_nome'] ?? 'participante'));
        $titulo = self::esc((string) ($row['atividade_titulo'] ?? 'atividade'));
        $local  = self::esc((string) ($row['local_nome'] ?? ''));
        $evento = !empty($row['evento_titulo'])
            ? ' · <em>' . self::esc((string) $row['evento_titulo']) . '</em>'
            : '';

        $oldWhen = self::esc(self::formatWhen(
            (string) ($row['data_antiga'] ?? ''),
            (string) ($row['hora_inicio_antiga'] ?? ''),
            (string) ($row['hora_fim_antiga'] ?? '')
        ));
        $newWhen = self::esc(self::formatWhen(
            (string) ($row['data_nova'] ?? ''),
            (string) ($row['hora_inicio_nova'] ?? ''),
            (string) ($row['hora_fim_nova'] ?? '')
        ));

        $link = self::esc(AppUrl::share('atividade=' . (int) ($row['atividade_id'] ?? 0)));
        $localLine = $local !== '' ? "<p>Local: <strong>{$local}</strong></p>" : '';

        $subject = 'Atividade remarcada: ' . (string) ($row['atividade_titulo'] ?? 'DC Hub');

        $body = <<<HTML
<h2>Olá, {$nome}!</h2>
<p>A data/horário de uma atividade em que você está inscrito foi alterada:</p>
<p><strong>{$titulo}</strong>{$evento}</p>
<p>De: <s>{$oldWhen}</s><br>
Para: <strong>{$newWhen}</strong></p>
{$localLine}
<p><a href="{$link}">Ver atividade</a></p>
<p><small>DC Hub — uma iniciativa PATOS.dev</small></p>
HTML;

        return [$subject, $body];
    }

    private static function formatWhen(string $data, string $horaInicio, string $horaFim): string
    {
        $ts      = strtotime($data);
        $dataFmt = $ts !== false ? date('d/m/Y', $ts) : $data;
        $hi      = substr($horaInicio, 0, 5);
        $hf      = substr($horaFim, 0, 5);

        $horas = $hi;
        if ($hf !== '') {
            $horas = $horas !== '' ? $horas . '–' . $hf : $hf;
        }

        return trim($dataFmt . ($horas !== '' ? ', ' . $horas : ''));
    }

    private static function esc(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }
}
