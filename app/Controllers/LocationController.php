<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Csrf;
use App\Core\Response;
use App\Core\Session;
use App\Models\Location;
use PDO;

class LocationController
{
    private Location $locationModel;

    public function __construct(private PDO $db)
    {
        $this->locationModel = new Location($db);
    }

    public function list(): void
    {
        $locations = $this->locationModel->listActive();
        Response::json(['success' => true, 'locations' => $locations]);
    }

    public function listAll(): void
    {
        if (!Session::isLoggedIn() || !in_array(Session::get('user_role'), ['proj', 'adm'], true)) {
            Response::error('Sem permissão.', 403);
        }

        $locations = $this->locationModel->listAll();
        Response::json(['success' => true, 'locations' => $locations]);
    }

    public function create(): void
    {
        if (!Session::isLoggedIn()) {
            Response::error('Não autenticado.', 401);
        }
        if (!in_array(Session::get('user_role'), ['proj', 'adm'], true)) {
            Response::error('Sem permissão.', 403);
        }
        if (!Csrf::validateRequest()) {
            Response::error('Token CSRF inválido.', 403);
        }

        $nome = trim($_POST['nome'] ?? '');
        if ($nome === '') {
            Response::error('Nome do local é obrigatório.');
        }

        $id = $this->locationModel->create($nome);
        Response::success('Local criado.', ['id' => $id]);
    }

    public function update(): void
    {
        if (!Session::isLoggedIn()) {
            Response::error('Não autenticado.', 401);
        }
        if (!in_array(Session::get('user_role'), ['proj', 'adm'], true)) {
            Response::error('Sem permissão.', 403);
        }
        if (!Csrf::validateRequest()) {
            Response::error('Token CSRF inválido.', 403);
        }

        $id     = (int) ($_POST['id'] ?? 0);
        $nome   = trim($_POST['nome'] ?? '');
        $status = $_POST['status'] ?? 'ativo';

        if ($id <= 0 || $nome === '') {
            Response::error('Dados inválidos.');
        }

        $this->locationModel->update($id, $nome, $status);
        Response::success('Local atualizado.');
    }
}
