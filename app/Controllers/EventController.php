<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\AppUrl;
use App\Core\Csrf;
use App\Core\Response;
use App\Core\Session;
use App\Models\Event;
use PDO;

class EventController
{
    private Event $eventModel;

    public function __construct(private PDO $db)
    {
        $this->eventModel = new Event($db);
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

        $titulo    = trim($_POST['titulo'] ?? '');
        $descricao = trim($_POST['descricao'] ?? '');
        $grupoId   = (int) ($_POST['grupo_id'] ?? 0);

        if ($titulo === '' || $grupoId <= 0) {
            Response::error('Título e grupo são obrigatórios.');
        }

        // Proj users can only create events for their own group
        if ($role === 'proj' && $grupoId !== (int) Session::get('user_grupo_id')) {
            Response::error('Você só pode criar eventos para o seu grupo.', 403);
        }

        $id = $this->eventModel->create($grupoId, $titulo, $descricao ?: null);

        Response::success('Evento criado.', ['id' => $id]);
    }

    public function update(): void
    {
        if (!Session::isLoggedIn()) {
            Response::error('Não autenticado.', 401);
        }

        if (!Csrf::validateRequest()) {
            Response::error('Token CSRF inválido.', 403);
        }

        $id        = (int) ($_POST['id'] ?? 0);
        $titulo    = trim($_POST['titulo'] ?? '');
        $descricao = trim($_POST['descricao'] ?? '');

        if ($id <= 0 || $titulo === '') {
            Response::error('Dados inválidos.');
        }

        $event = $this->eventModel->findById($id);
        if (!$event) {
            Response::error('Evento não encontrado.', 404);
        }

        $this->checkOwnership($event);

        $this->eventModel->update($id, $titulo, $descricao ?: null);

        Response::success('Evento atualizado.');
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

        $event = $this->eventModel->findById($id);
        if (!$event) {
            Response::error('Evento não encontrado.', 404);
        }

        $this->checkOwnership($event);

        $this->eventModel->delete($id);

        Response::success('Evento removido.');
    }

    public function listManage(): void
    {
        if (!Session::isLoggedIn()) {
            Response::error('Não autenticado.', 401);
        }

        $role = Session::get('user_role');
        if (!in_array($role, ['proj', 'adm'], true)) {
            Response::error('Sem permissão.', 403);
        }

        $grupoId = null;
        if ($role === 'proj') {
            $grupoId = (int) Session::get('user_grupo_id');
            if ($grupoId <= 0) {
                Response::json(['success' => true, 'events' => []]);
            }
        }

        $events = $this->eventModel->listForManage($grupoId);

        Response::json(['success' => true, 'events' => $events]);
    }

    public function list(): void
    {
        $grupoId = !empty($_GET['grupo_id']) ? (int) $_GET['grupo_id'] : null;

        if ($grupoId) {
            $events = $this->eventModel->listByGroup($grupoId);
        } else {
            $events = $this->eventModel->listAll();
        }

        Response::json(['success' => true, 'events' => $events]);
    }

    public function detail(): void
    {
        $id = (int) ($_GET['id'] ?? 0);
        if ($id <= 0) {
            Response::error('ID inválido.');
        }

        $event = $this->eventModel->findById($id);
        if (!$event) {
            Response::error('Evento não encontrado.', 404);
        }

        $activityModel = new \App\Models\Activity($this->db);
        $activities = $activityModel->listByEvent($id);

        if (Session::isLoggedIn()) {
            $regModel = new \App\Models\Registration($this->db);
            $userId = (int) Session::get('user_id');
            foreach ($activities as &$activity) {
                $status = $regModel->getUserStatus($userId, (int) $activity['id']);
                $activity['usuario_inscrito'] = $status !== null;
                $activity['usuario_status'] = $status;
                $activity['vagas_disponiveis'] = $activity['vagas_limite'] === null
                    ? null
                    : max(0, (int) $activity['vagas_limite'] - (int) $activity['vagas_ocupadas']);
            }
            unset($activity);
        }

        $event['atividades'] = $activities;
        $event['share_url'] = AppUrl::share('evento=' . $id);

        Response::json([
            'success' => true,
            'event'   => $event,
        ]);
    }

    private function checkOwnership(array $event): void
    {
        $role = Session::get('user_role');
        if ($role === 'adm') {
            return;
        }
        if ($role === 'proj' && (int) $event['grupo_id'] === (int) Session::get('user_grupo_id')) {
            return;
        }
        Response::error('Sem permissão para este evento.', 403);
    }
}
