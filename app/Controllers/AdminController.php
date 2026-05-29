<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Csrf;
use App\Core\Response;
use App\Core\Session;
use App\Models\User;
use App\Models\Group;
use App\Models\Location;
use PDO;

class AdminController
{
    private User $userModel;
    private Group $groupModel;
    private Location $locationModel;

    public function __construct(private PDO $db)
    {
        $this->userModel     = new User($db);
        $this->groupModel    = new Group($db);
        $this->locationModel = new Location($db);
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
             LEFT JOIN grupos g ON g.id = sr.grupo_id
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

        if (empty($request['grupo_id'])) {
            Response::error(
                'Este pedido propõe um grupo ainda não cadastrado. Cadastre o grupo antes de aprovar.'
            );
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

        $grupoIdRaw = trim((string) ($_POST['grupo_id'] ?? ''));
        $grupoNomeProposto = trim($_POST['grupo_nome_proposto'] ?? '');
        $mensagem = trim($_POST['mensagem'] ?? '');
        $userId = (int) Session::get('user_id');

        $isNewGroup = $grupoIdRaw === '' || $grupoIdRaw === 'new';
        $grupoId = null;

        if ($isNewGroup) {
            if ($grupoNomeProposto === '') {
                Response::error('Informe o nome do grupo de extensão.');
            }
        } else {
            $grupoId = (int) $grupoIdRaw;
            if ($grupoId <= 0) {
                Response::error('Selecione um grupo válido.');
            }
        }

        if ($isNewGroup) {
            $check = $this->db->prepare(
                "SELECT id FROM solicitacoes_role
                 WHERE user_id = :uid AND grupo_id IS NULL AND grupo_nome_proposto = :nome AND status = 'pendente'"
            );
            $check->bindValue(':uid', $userId, PDO::PARAM_INT);
            $check->bindValue(':nome', $grupoNomeProposto);
            $check->execute();
        } else {
            $check = $this->db->prepare(
                "SELECT id FROM solicitacoes_role
                 WHERE user_id = :uid AND grupo_id = :gid AND status = 'pendente'"
            );
            $check->bindValue(':uid', $userId, PDO::PARAM_INT);
            $check->bindValue(':gid', $grupoId, PDO::PARAM_INT);
            $check->execute();
        }

        if ($check->fetch()) {
            Response::error('Já existe uma solicitação pendente para este grupo.');
        }

        $stmt = $this->db->prepare(
            'INSERT INTO solicitacoes_role (user_id, grupo_id, grupo_nome_proposto, mensagem)
             VALUES (:uid, :gid, :nome, :msg)'
        );
        $stmt->bindValue(':uid', $userId, PDO::PARAM_INT);
        if ($grupoId === null) {
            $stmt->bindValue(':gid', null, PDO::PARAM_NULL);
        } else {
            $stmt->bindValue(':gid', $grupoId, PDO::PARAM_INT);
        }
        $stmt->bindValue(':nome', $isNewGroup ? $grupoNomeProposto : null);
        $stmt->bindValue(':msg', $mensagem !== '' ? $mensagem : null);
        $stmt->execute();

        Response::success('Solicitação enviada. Aguarde a aprovação do administrador.');
    }

    public function listGroups(): void
    {
        $this->requireAdmin();
        $groups = $this->groupModel->listAll();
        Response::json(['success' => true, 'groups' => $groups]);
    }

    public function updateUser(): void
    {
        $this->requireAdmin();

        if (!Csrf::validateRequest()) {
            Response::error('Token CSRF inválido.', 403);
        }

        $userId  = (int) ($_POST['user_id'] ?? 0);
        $role    = $_POST['role'] ?? '';
        $grupoId = isset($_POST['grupo_id']) && $_POST['grupo_id'] !== ''
            ? (int) $_POST['grupo_id']
            : null;

        if ($userId <= 0 || !in_array($role, ['user', 'proj', 'adm'], true)) {
            Response::error('Dados inválidos.');
        }

        if ($role === 'proj' && ($grupoId === null || $grupoId <= 0)) {
            Response::error('Selecione um grupo para usuários de projeto.');
        }

        if ($role !== 'proj') {
            $grupoId = null;
        }

        $currentUserId = (int) Session::get('user_id');
        if ($userId === $currentUserId && $role !== 'adm') {
            Response::error('Você não pode remover seu próprio perfil de administrador.');
        }

        if (!$this->userModel->findById($userId)) {
            Response::error('Usuário não encontrado.', 404);
        }

        $this->userModel->updateRole($userId, $role, $grupoId);
        Response::success('Usuário atualizado.');
    }

    public function deleteUser(): void
    {
        $this->requireAdmin();

        if (!Csrf::validateRequest()) {
            Response::error('Token CSRF inválido.', 403);
        }

        $userId = (int) ($_POST['user_id'] ?? 0);
        if ($userId <= 0) {
            Response::error('ID inválido.');
        }

        if ($userId === (int) Session::get('user_id')) {
            Response::error('Você não pode excluir sua própria conta.');
        }

        if (!$this->userModel->findById($userId)) {
            Response::error('Usuário não encontrado.', 404);
        }

        $this->userModel->delete($userId);
        Response::success('Usuário excluído.');
    }

    public function deleteGroup(): void
    {
        $this->requireAdmin();

        if (!Csrf::validateRequest()) {
            Response::error('Token CSRF inválido.', 403);
        }

        $id = (int) ($_POST['id'] ?? 0);
        if ($id <= 0) {
            Response::error('ID inválido.');
        }

        if ($this->groupModel->countEvents($id) > 0) {
            Response::error('Não é possível excluir: o grupo possui eventos cadastrados. Inative-o em vez disso.');
        }

        $this->groupModel->delete($id);
        Response::success('Grupo excluído.');
    }

    public function deleteLocation(): void
    {
        $this->requireAdmin();

        if (!Csrf::validateRequest()) {
            Response::error('Token CSRF inválido.', 403);
        }

        $id = (int) ($_POST['id'] ?? 0);
        if ($id <= 0) {
            Response::error('ID inválido.');
        }

        if ($this->locationModel->countActivities($id) > 0) {
            Response::error('Não é possível excluir: o local está em uso por atividades. Inative-o em vez disso.');
        }

        $this->locationModel->delete($id);
        Response::success('Local excluído.');
    }

    public function listActiveGroups(): void
    {
        $role = Session::get('user_role');
        $grupoId = (int) Session::get('user_grupo_id');

        if ($role === 'proj' && $grupoId > 0) {
            $group = $this->groupModel->findById($grupoId);
            $groups = ($group && ($group['status'] ?? '') === 'ativo') ? [$group] : [];
        } else {
            $groups = $this->groupModel->listActive();
        }

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
