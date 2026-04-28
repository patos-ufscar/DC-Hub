<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Csrf;
use App\Core\Response;
use App\Core\Session;
use App\Models\Registration;
use App\Models\Activity;
use PDO;

class RegistrationController
{
    private Registration $regModel;
    private Activity $activityModel;

    public function __construct(private PDO $db)
    {
        $this->regModel      = new Registration($db);
        $this->activityModel = new Activity($db);
    }

    public function toggleRsvp(): void
    {
        if (!Session::isLoggedIn()) {
            Response::error('Não autenticado.', 401);
        }
        if (!Csrf::validateRequest()) {
            Response::error('Token CSRF inválido.', 403);
        }

        $atividadeId = (int) ($_POST['atividade_id'] ?? 0);
        if ($atividadeId <= 0) {
            Response::error('ID de atividade inválido.');
        }

        $userId = (int) Session::get('user_id');
        $status = $this->regModel->toggleRsvp($userId, $atividadeId);

        Response::success('RSVP atualizado.', ['status' => $status]);
    }

    public function dashboard(): void
    {
        if (!Session::isLoggedIn()) {
            Response::error('Não autenticado.', 401);
        }

        $userId = (int) Session::get('user_id');
        $rsvps  = $this->regModel->getUserRsvps($userId);

        Response::json(['success' => true, 'rsvps' => $rsvps]);
    }

    public function attendees(): void
    {
        if (!Session::isLoggedIn()) {
            Response::error('Não autenticado.', 401);
        }

        $role = Session::get('user_role');
        if (!in_array($role, ['proj', 'adm'], true)) {
            Response::error('Sem permissão.', 403);
        }

        $atividadeId = (int) ($_GET['atividade_id'] ?? 0);
        if ($atividadeId <= 0) {
            Response::error('ID inválido.');
        }

        // Proj users may only view attendees for their own group's activities
        if ($role === 'proj') {
            $activity = $this->activityModel->findById($atividadeId);
            if (!$activity || (int) $activity['grupo_id'] !== (int) Session::get('user_grupo_id')) {
                Response::error('Sem permissão para esta atividade.', 403);
            }
        }

        $attendees = $this->regModel->getActivityAttendees($atividadeId);

        Response::json(['success' => true, 'attendees' => $attendees]);
    }

    public function validatePresence(): void
    {
        if (!Session::isLoggedIn()) {
            Response::error('Não autenticado.', 401);
        }

        $role = Session::get('user_role');
        if (!in_array($role, ['proj', 'adm'], true)) {
            Response::error('Sem permissão.', 403);
        }

        if (!Csrf::validateRequest()) {
            Response::error('Token CSRF inválido.', 403);
        }

        $atividadeId = (int) ($_POST['atividade_id'] ?? 0);
        $userIds     = $_POST['user_ids'] ?? [];

        if ($atividadeId <= 0 || empty($userIds)) {
            Response::error('Dados inválidos.');
        }

        // Proj users may only validate presence for their own group's activities
        if ($role === 'proj') {
            $activity = $this->activityModel->findById($atividadeId);
            if (!$activity || (int) $activity['grupo_id'] !== (int) Session::get('user_grupo_id')) {
                Response::error('Sem permissão para esta atividade.', 403);
            }
        }

        // Validate that the user IDs are integers
        $userIds = array_map('intval', (array) $userIds);

        $count = $this->regModel->bulkConfirmPresence($atividadeId, $userIds);

        Response::success("Presença confirmada para {$count} participante(s).");
    }

    public function generateCode(): void
    {
        if (!Session::isLoggedIn()) {
            Response::error('Não autenticado.', 401);
        }

        $role = Session::get('user_role');
        if (!in_array($role, ['proj', 'adm'], true)) {
            Response::error('Sem permissão.', 403);
        }

        if (!Csrf::validateRequest()) {
            Response::error('Token CSRF inválido.', 403);
        }

        $atividadeId = (int) ($_POST['atividade_id'] ?? 0);
        if ($atividadeId <= 0) {
            Response::error('ID inválido.');
        }

        // Proj users may only generate codes for their own group's activities
        $activity = $this->activityModel->findById($atividadeId);
        if (!$activity) {
            Response::error('Atividade não encontrada.', 404);
        }
        if ($role === 'proj' && (int) $activity['grupo_id'] !== (int) Session::get('user_grupo_id')) {
            Response::error('Sem permissão para esta atividade.', 403);
        }

        $code = strtoupper(bin2hex(random_bytes(4))); // 8 char hex code
        $this->activityModel->setRedemptionCode($atividadeId, $code);

        Response::success('Código gerado.', ['code' => $code]);
    }

    public function redeemCode(): void
    {
        if (!Session::isLoggedIn()) {
            Response::error('Não autenticado.', 401);
        }

        if (!Csrf::validateRequest()) {
            Response::error('Token CSRF inválido.', 403);
        }

        $code = strtoupper(trim($_POST['code'] ?? ''));
        if ($code === '') {
            Response::error('Código é obrigatório.');
        }

        $userId = (int) Session::get('user_id');
        $result = $this->regModel->confirmByCode($userId, $code);

        if ($result === null) {
            Response::error('Código inválido.');
        }

        Response::success('Presença confirmada com sucesso!');
    }
}
