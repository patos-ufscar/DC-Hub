<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Response;
use App\Core\Session;
use App\Models\Registration;
use App\Models\Event;
use PDO;

class CertificateController
{
    private Registration $regModel;
    private Event $eventModel;

    public function __construct(private PDO $db)
    {
        $this->regModel  = new Registration($db);
        $this->eventModel = new Event($db);
    }

    public function checkEligibility(): void
    {
        if (!Session::isLoggedIn()) {
            Response::error('Não autenticado.', 401);
        }

        $userId       = (int) Session::get('user_id');
        $nomeCompleto = Session::get('user_nome_completo');
        $eligible     = $this->regModel->getEligibleCertificates($userId);

        Response::json([
            'success'        => true,
            'nome_completo'  => $nomeCompleto,
            'eligible'       => $eligible,
        ]);
    }

    public function generate(): void
    {
        if (!Session::isLoggedIn()) {
            Response::error('Não autenticado.', 401);
        }

        $userId       = (int) Session::get('user_id');
        $nomeCompleto = Session::get('user_nome_completo');

        if (empty($nomeCompleto)) {
            Response::error('Preencha seu nome completo antes de emitir certificados.', 422);
        }

        $eventoId = (int) ($_GET['evento_id'] ?? 0);
        if ($eventoId <= 0) {
            Response::error('ID de evento inválido.');
        }

        $event = $this->eventModel->findById($eventoId);
        if (!$event) {
            Response::error('Evento não encontrado.', 404);
        }

        $activities = $this->regModel->getCertificateActivities($userId, $eventoId);
        if (empty($activities)) {
            Response::error('Nenhuma atividade com presença confirmada neste evento.');
        }

        $totalMinutos = array_sum(array_column($activities, 'carga_minutos'));
        $totalHoras   = round($totalMinutos / 60, 1);

        // Try DomPDF first, fallback to HTML download
        $dompdfAutoload = dirname(__DIR__, 2) . '/vendor/dompdf/autoload.inc.php';

        $data = [
            'nomeCompleto' => $nomeCompleto,
            'evento'       => $event,
            'activities'   => $activities,
            'totalHoras'   => $totalHoras,
            'dataEmissao'  => date('d/m/Y'),
        ];

        ob_start();
        extract($data);
        include dirname(__DIR__) . '/Templates/certificate-pdf.php';
        $html = ob_get_clean();

        if (is_file($dompdfAutoload)) {
            require_once $dompdfAutoload;
            $dompdf = new \Dompdf\Dompdf(['isRemoteEnabled' => false]);
            $dompdf->loadHtml($html);
            $dompdf->setPaper('A4', 'landscape');
            $dompdf->render();

            $filename = 'certificado_' . preg_replace('/[^a-z0-9]/', '_', strtolower($event['titulo'])) . '.pdf';

            header('Content-Type: application/pdf');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            echo $dompdf->output();
            exit;
        }

        // Fallback: return HTML for browser rendering/print
        header('Content-Type: text/html; charset=utf-8');
        echo $html;
        exit;
    }
}
