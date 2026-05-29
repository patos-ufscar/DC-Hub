<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Csrf;
use App\Core\RateLimiter;
use App\Core\Response;
use App\Core\Session;
use App\Models\Registration;
use App\Models\Activity;
use App\Models\User;
use PDO;

class RegistrationController
{
    private Registration $regModel;
    private Activity $activityModel;
    private RateLimiter $rateLimiter;

    public function __construct(private PDO $db)
    {
        $this->regModel      = new Registration($db);
        $this->activityModel = new Activity($db);
        $this->rateLimiter   = new RateLimiter($db);
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

        if ($status === 'full') {
            Response::error('Vagas esgotadas para esta atividade.');
        }

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

        $userIds = array_map('intval', (array) $userIds);

        foreach ($userIds as $uid) {
            if ($this->regModel->getUserStatus($uid, $atividadeId) !== 'rsvp') {
                Response::error('Só é possível confirmar presença de participantes inscritos (RSVP).');
            }
        }

        $count = $this->regModel->bulkConfirmPresence(
            $atividadeId,
            $userIds,
            (int) Session::get('user_id')
        );

        Response::success("Presença confirmada para {$count} participante(s).", ['confirmed' => $count]);
    }

    public function checkinList(): void
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

        $this->assertActivityAccess($atividadeId, $role);

        $users = $this->regModel->getCheckinList($atividadeId);

        Response::json(['success' => true, 'users' => $users]);
    }

    public function scanPresence(): void
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
        $uuid        = trim($_POST['presenca_uuid'] ?? '');

        if ($atividadeId <= 0 || $uuid === '') {
            Response::error('Dados inválidos.');
        }

        $this->assertActivityAccess($atividadeId, $role);

        $userModel = new User($this->db);
        $user = $userModel->findByPresencaUuid($uuid);
        if (!$user) {
            Response::error('QR Code não reconhecido.');
        }

        $result = $this->regModel->markPresent(
            (int) $user['id'],
            $atividadeId,
            (int) Session::get('user_id'),
            'qr'
        );

        $message = $result['already']
            ? "{$result['nome_exibicao']} já tinha presença confirmada."
            : "Presença confirmada: {$result['nome_exibicao']}.";

        Response::success($message, [
            'user_id'       => $result['user_id'],
            'nome_exibicao' => $result['nome_exibicao'],
            'already'       => $result['already'],
        ]);
    }

    public function myQr(): void
    {
        if (!Session::isLoggedIn()) {
            Response::error('Não autenticado.', 401);
        }

        $userModel = new User($this->db);
        $uuid = $userModel->ensurePresencaUuid((int) Session::get('user_id'));

        Response::json([
            'success'       => true,
            'presenca_uuid'  => $uuid,
            'nome_exibicao' => Session::get('user_nome_exibicao'),
        ]);
    }

    private function assertActivityAccess(int $atividadeId, string $role): void
    {
        if ($role !== 'proj') {
            return;
        }

        $activity = $this->activityModel->findById($atividadeId);
        if (!$activity || (int) $activity['grupo_id'] !== (int) Session::get('user_grupo_id')) {
            Response::error('Sem permissão para esta atividade.', 403);
        }
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
        $bucket = RateLimiter::bucket('redeem', RateLimiter::clientIp(), (string) $userId);
        if (!$this->rateLimiter->attempt($bucket, 10, 600)) {
            Response::error('Muitas tentativas. Aguarde alguns minutos.', 429);
        }

        $result = $this->regModel->confirmByCode($userId, $code);

        if ($result === null) {
            Response::error('Código inválido ou expirado.');
        }

        $this->rateLimiter->clear($bucket);

        Response::success('Presença confirmada com sucesso!');
    }
}
