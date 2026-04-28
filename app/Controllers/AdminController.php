<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Csrf;
use App\Core\Response;
use App\Core\Session;
use App\Models\User;
use App\Models\Group;
use PDO;

class AdminController
{
    private User $userModel;
    private Group $groupModel;

    public function __construct(private PDO $db)
    {
        $this->userModel  = new User($db);
        $this->groupModel = new Group($db);
    }

    public function listUsers(): void
    {
        $this->requireAdmin();
        $users = $this->userModel->listAll();
        Response::json(['success' => true, 'users' => $users]);
    }

    public function listRoleRequests(): void
    {
        $this->requireAdmin();

        $stmt = $this->db->query(
            "SELECT sr.*, u.email, u.nome_exibicao, g.nome AS grupo_nome
             FROM solicitacoes_role sr
             JOIN usuarios u ON u.id = sr.user_id
             JOIN grupos g ON g.id = sr.grupo_id
             WHERE sr.status = 'pendente'
             ORDER BY sr.created_at DESC"
        );

        Response::json(['success' => true, 'requests' => $stmt->fetchAll()]);
    }

    public function approveRole(): void
    {
        $this->requireAdmin();

        if (!Csrf::validateRequest()) {
            Response::error('Token CSRF inválido.', 403);
        }

        $requestId = (int) ($_POST['request_id'] ?? 0);
        if ($requestId <= 0) {
            Response::error('ID inválido.');
        }

        $stmt = $this->db->prepare(
            'SELECT * FROM solicitacoes_role WHERE id = :id'
        );
        $stmt->bindValue(':id', $requestId, PDO::PARAM_INT);
        $stmt->execute();
        $request = $stmt->fetch();

        if (!$request) {
            Response::error('Solicitação não encontrada.', 404);
        }

        // Update request status
        $update = $this->db->prepare(
            "UPDATE solicitacoes_role SET status = 'aprovado' WHERE id = :id"
        );
        $update->bindValue(':id', $requestId, PDO::PARAM_INT);
        $update->execute();

        // Update user role
        $this->userModel->updateRole(
            (int) $request['user_id'],
            'proj',
            (int) $request['grupo_id']
        );

        Response::success('Solicitação aprovada.');
    }

    public function rejectRole(): void
    {
        $this->requireAdmin();

        if (!Csrf::validateRequest()) {
            Response::error('Token CSRF inválido.', 403);
        }

        $requestId = (int) ($_POST['request_id'] ?? 0);
        if ($requestId <= 0) {
            Response::error('ID inválido.');
        }

        $stmt = $this->db->prepare(
            "UPDATE solicitacoes_role SET status = 'rejeitado' WHERE id = :id"
        );
        $stmt->bindValue(':id', $requestId, PDO::PARAM_INT);
        $stmt->execute();

        Response::success('Solicitação rejeitada.');
    }

    public function requestRole(): void
    {
        if (!Session::isLoggedIn()) {
            Response::error('Não autenticado.', 401);
        }

        if (!Csrf::validateRequest()) {
            Response::error('Token CSRF inválido.', 403);
        }

        $grupoId = (int) ($_POST['grupo_id'] ?? 0);
        if ($grupoId <= 0) {
            Response::error('Selecione um grupo.');
        }

        $userId = (int) Session::get('user_id');

        // Check for existing pending request
        $check = $this->db->prepare(
            "SELECT id FROM solicitacoes_role WHERE user_id = :uid AND grupo_id = :gid AND status = 'pendente'"
        );
        $check->bindValue(':uid', $userId, PDO::PARAM_INT);
        $check->bindValue(':gid', $grupoId, PDO::PARAM_INT);
        $check->execute();

        if ($check->fetch()) {
            Response::error('Já existe uma solicitação pendente para este grupo.');
        }

        $stmt = $this->db->prepare(
            'INSERT INTO solicitacoes_role (user_id, grupo_id) VALUES (:uid, :gid)'
        );
        $stmt->bindValue(':uid', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':gid', $grupoId, PDO::PARAM_INT);
        $stmt->execute();

        Response::success('Solicitação enviada. Aguarde a aprovação do administrador.');
    }

    public function listGroups(): void
    {
        $groups = $this->groupModel->listAll();
        Response::json(['success' => true, 'groups' => $groups]);
    }

    public function listActiveGroups(): void
    {
        $groups = $this->groupModel->listActive();
        Response::json(['success' => true, 'groups' => $groups]);
    }

    public function createGroup(): void
    {
        $this->requireAdmin();

        if (!Csrf::validateRequest()) {
            Response::error('Token CSRF inválido.', 403);
        }

        $nome      = trim($_POST['nome'] ?? '');
        $descricao = trim($_POST['descricao'] ?? '');

        if ($nome === '') {
            Response::error('Nome do grupo é obrigatório.');
        }

        $id = $this->groupModel->create($nome, $descricao ?: null);
        Response::success('Grupo criado.', ['id' => $id]);
    }

    public function updateGroup(): void
    {
        $this->requireAdmin();

        if (!Csrf::validateRequest()) {
            Response::error('Token CSRF inválido.', 403);
        }

        $id        = (int) ($_POST['id'] ?? 0);
        $nome      = trim($_POST['nome'] ?? '');
        $descricao = trim($_POST['descricao'] ?? '');
        $status    = $_POST['status'] ?? 'ativo';

        if ($id <= 0 || $nome === '') {
            Response::error('Dados inválidos.');
        }

        $this->groupModel->update($id, $nome, $descricao ?: null, $status);
        Response::success('Grupo atualizado.');
    }

    private function requireAdmin(): void
    {
        if (!Session::isLoggedIn()) {
            Response::error('Não autenticado.', 401);
        }
        if (Session::get('user_role') !== 'adm') {
            Response::error('Acesso restrito a administradores.', 403);
        }
    }
}
