<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Response;
use App\Models\Activity;
use PDO;

class ExportController
{
    private Activity $activityModel;

    public function __construct(private PDO $db)
    {
        $this->activityModel = new Activity($db);
    }

    public function googleCalendar(): void
    {
        $id = (int) ($_GET['atividade_id'] ?? 0);
        if ($id <= 0) {
            Response::error('ID inválido.');
        }

        $activity = $this->activityModel->findById($id);
        if (!$activity) {
            Response::error('Atividade não encontrada.', 404);
        }

        $startDt = str_replace(['-', ':'], '', $activity['data'] . 'T' . $activity['hora_inicio']) . '00';
        $endDt   = str_replace(['-', ':'], '', $activity['data'] . 'T' . $activity['hora_fim']) . '00';

        $params = http_build_query([
            'action'   => 'TEMPLATE',
            'text'     => $activity['titulo'] . ' - ' . $activity['evento_titulo'],
            'dates'    => $startDt . '/' . $endDt,
            'details'  => $activity['descricao_certificado'],
            'location' => $activity['local_nome'],
        ]);

        $url = 'https://calendar.google.com/calendar/render?' . $params;

        Response::json(['success' => true, 'url' => $url]);
    }

    public function ics(): void
    {
        $id = (int) ($_GET['atividade_id'] ?? 0);
        if ($id <= 0) {
            Response::error('ID inválido.');
        }

        $activity = $this->activityModel->findById($id);
        if (!$activity) {
            Response::error('Atividade não encontrada.', 404);
        }

        $dtStart = str_replace('-', '', $activity['data']) . 'T' . str_replace(':', '', $activity['hora_inicio']) . '00';
        $dtEnd   = str_replace('-', '', $activity['data']) . 'T' . str_replace(':', '', $activity['hora_fim']) . '00';
        $uid     = 'dchub-' . $activity['id'] . '@dchub.local';

        $ics = "BEGIN:VCALENDAR\r\n";
        $ics .= "VERSION:2.0\r\n";
        $ics .= "PRODID:-//DC Hub//DC Hub//PT\r\n";
        $ics .= "BEGIN:VEVENT\r\n";
        $ics .= "UID:{$uid}\r\n";
        $ics .= "DTSTART:{$dtStart}\r\n";
        $ics .= "DTEND:{$dtEnd}\r\n";
        $ics .= "SUMMARY:" . $this->escapeIcs($activity['titulo'] . ' - ' . $activity['evento_titulo']) . "\r\n";
        $ics .= "DESCRIPTION:" . $this->escapeIcs($activity['descricao_certificado']) . "\r\n";
        $ics .= "LOCATION:" . $this->escapeIcs($activity['local_nome']) . "\r\n";
        $ics .= "END:VEVENT\r\n";
        $ics .= "END:VCALENDAR\r\n";

        $filename = 'atividade_' . $activity['id'] . '.ics';

        header('Content-Type: text/calendar; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        echo $ics;
        exit;
    }

    private function escapeIcs(string $text): string
    {
        $text = str_replace(["\r\n", "\r"], "\n", $text);
        $text = str_replace(['\\', ';', ',', "\n"], ['\\\\', '\\;', '\\,', '\\n'], $text);

        return $text;
    }
}
