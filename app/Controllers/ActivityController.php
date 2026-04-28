<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Csrf;
use App\Core\Response;
use App\Core\Session;
use App\Models\Activity;
use App\Models\Event;
use PDO;

class ActivityController
{
    private Activity $activityModel;
    private Event $eventModel;

    public function __construct(private PDO $db)
    {
        $this->activityModel = new Activity($db);
        $this->eventModel    = new Event($db);
    }

    public function create(): void
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

        $eventoId             = (int) ($_POST['evento_id'] ?? 0);
        $titulo               = trim($_POST['titulo'] ?? '');
        $data                 = $_POST['data'] ?? '';
        $horaInicio           = $_POST['hora_inicio'] ?? '';
        $horaFim              = $_POST['hora_fim'] ?? '';
        $localId              = (int) ($_POST['local_id'] ?? 0);
        $descricaoCertificado = trim($_POST['descricao_certificado'] ?? '');

        if ($eventoId <= 0 || $titulo === '' || $data === '' || $horaInicio === '' ||
            $horaFim === '' || $localId <= 0 || $descricaoCertificado === '') {
            Response::error('Preencha todos os campos obrigatórios.');
        }

        // Validate event ownership
        $event = $this->eventModel->findById($eventoId);
        if (!$event) {
            Response::error('Evento não encontrado.', 404);
        }

        if ($role === 'proj' && (int) $event['grupo_id'] !== (int) Session::get('user_grupo_id')) {
            Response::error('Sem permissão para este evento.', 403);
        }

        // Validate time logic
        if ($horaFim <= $horaInicio) {
            Response::error('Horário de fim deve ser posterior ao de início.');
        }

        $id = $this->activityModel->create(
            $eventoId, $titulo, $data, $horaInicio, $horaFim, $localId, $descricaoCertificado
        );

        Response::success('Atividade criada.', ['id' => $id]);
    }

    public function update(): void
    {
        if (!Session::isLoggedIn()) {
            Response::error('Não autenticado.', 401);
        }

        if (!Csrf::validateRequest()) {
            Response::error('Token CSRF inválido.', 403);
        }

        $id                   = (int) ($_POST['id'] ?? 0);
        $titulo               = trim($_POST['titulo'] ?? '');
        $data                 = $_POST['data'] ?? '';
        $horaInicio           = $_POST['hora_inicio'] ?? '';
        $horaFim              = $_POST['hora_fim'] ?? '';
        $localId              = (int) ($_POST['local_id'] ?? 0);
        $descricaoCertificado = trim($_POST['descricao_certificado'] ?? '');

        if ($id <= 0 || $titulo === '' || $data === '' || $horaInicio === '' ||
            $horaFim === '' || $localId <= 0 || $descricaoCertificado === '') {
            Response::error('Preencha todos os campos obrigatórios.');
        }

        $activity = $this->activityModel->findById($id);
        if (!$activity) {
            Response::error('Atividade não encontrada.', 404);
        }

        $this->checkOwnership($activity);

        if ($horaFim <= $horaInicio) {
            Response::error('Horário de fim deve ser posterior ao de início.');
        }

        $this->activityModel->update(
            $id, $titulo, $data, $horaInicio, $horaFim, $localId, $descricaoCertificado
        );

        Response::success('Atividade atualizada.');
    }

    public function delete(): void
    {
        if (!Session::isLoggedIn()) {
            Response::error('Não autenticado.', 401);
        }

        if (!Csrf::validateRequest()) {
            Response::error('Token CSRF inválido.', 403);
        }

        $id = (int) ($_POST['id'] ?? 0);
        if ($id <= 0) {
            Response::error('ID inválido.');
        }

        $activity = $this->activityModel->findById($id);
        if (!$activity) {
            Response::error('Atividade não encontrada.', 404);
        }

        $this->checkOwnership($activity);

        $this->activityModel->delete($id);

        Response::success('Atividade removida.');
    }

    public function detail(): void
    {
        $id = (int) ($_GET['id'] ?? 0);
        if ($id <= 0) {
            Response::error('ID inválido.');
        }

        $activity = $this->activityModel->findById($id);
        if (!$activity) {
            Response::error('Atividade não encontrada.', 404);
        }

        $data = ['success' => true, 'activity' => $activity];

        // If user is logged in, include their RSVP status
        if (Session::isLoggedIn()) {
            $regModel = new \App\Models\Registration($this->db);
            $data['user_status'] = $regModel->getUserStatus(
                (int) Session::get('user_id'),
                $id
            );
        }

        Response::json($data);
    }

    private function checkOwnership(array $activity): void
    {
        $role = Session::get('user_role');
        if ($role === 'adm') {
            return;
        }
        if ($role === 'proj' && (int) $activity['grupo_id'] === (int) Session::get('user_grupo_id')) {
            return;
        }
        Response::error('Sem permissão.', 403);
    }
}
