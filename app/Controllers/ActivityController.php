<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\AppUrl;
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

        $input = $this->parseActivityInput($role);
        $this->assertGrupoAccess($input['grupo_id'], $role);

        $id = $this->activityModel->create($input);

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

        $id = (int) ($_POST['id'] ?? 0);
        if ($id <= 0) {
            Response::error('ID inválido.');
        }

        $activity = $this->activityModel->findById($id);
        if (!$activity) {
            Response::error('Atividade não encontrada.', 404);
        }

        $role = Session::get('user_role');
        $this->checkOwnership($activity, $role);

        $input = $this->parseActivityInput($role);
        $this->assertGrupoAccess($input['grupo_id'], $role);

        $occupied = $this->activityModel->countOccupiedSpots($id);
        if ($input['vagas_limite'] !== null && $input['vagas_limite'] < $occupied) {
            Response::error(
                "O limite de vagas não pode ser menor que o número de inscritos ({$occupied})."
            );
        }

        $this->activityModel->update($id, $input);

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

        $this->checkOwnership($activity, Session::get('user_role'));

        $this->activityModel->delete($id);

        Response::success('Atividade removida.');
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

        $upcoming = ($_GET['period'] ?? 'upcoming') !== 'past';
        $grupoId = null;

        if ($role === 'proj') {
            $grupoId = (int) Session::get('user_grupo_id');
            if ($grupoId <= 0) {
                Response::json(['success' => true, 'activities' => []]);
            }
        } elseif (!empty($_GET['grupo_id'])) {
            $grupoId = (int) $_GET['grupo_id'];
        }

        $activities = $this->activityModel->listForManage($grupoId, $upcoming);

        Response::json(['success' => true, 'activities' => $activities]);
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

        $activity['share_url'] = AppUrl::share('atividade=' . $id);
        $data = ['success' => true, 'activity' => $activity];

        if (!empty($activity['evento_id'])) {
            $event = $this->eventModel->findById((int) $activity['evento_id']);
            if ($event) {
                $data['event'] = [
                    'id'        => (int) $event['id'],
                    'titulo'    => $event['titulo'],
                    'descricao' => $event['descricao'],
                    'grupo_nome'=> $event['grupo_nome'],
                ];
            }
        }

        if (Session::isLoggedIn()) {
            $regModel = new \App\Models\Registration($this->db);
            $userId = (int) Session::get('user_id');
            $data['user_status'] = $regModel->getUserStatus($userId, $id);
            $data['user_inscrito'] = $data['user_status'] !== null;
            if ($activity['vagas_limite'] !== null) {
                $data['vagas_disponiveis'] = max(
                    0,
                    (int) $activity['vagas_limite'] - (int) $activity['vagas_ocupadas']
                );
            }
        }

        Response::json($data);
    }

    /** @return array<string, mixed> */
    private function parseActivityInput(string $role): array
    {
        $associadaEvento = in_array($_POST['associada_evento'] ?? '0', ['1', 'true', 'on'], true);
        $eventoIdRaw = trim((string) ($_POST['evento_id'] ?? ''));
        $eventoId = ($associadaEvento && $eventoIdRaw !== '') ? (int) $eventoIdRaw : null;
        $grupoIdRaw = trim((string) ($_POST['grupo_id'] ?? ''));
        $titulo   = trim($_POST['titulo'] ?? '');
        $descricao = trim($_POST['descricao'] ?? '');
        $data     = $_POST['data'] ?? '';
        $horaInicio = $_POST['hora_inicio'] ?? '';
        $horaFim    = $_POST['hora_fim'] ?? '';
        $localId    = (int) ($_POST['local_id'] ?? 0);
        $ofereceCert = in_array($_POST['oferece_certificado'] ?? '0', ['1', 'true', 'on'], true);
        $descricaoCert = trim($_POST['descricao_certificado'] ?? '');
        $vagasRaw = trim((string) ($_POST['vagas_limite'] ?? ''));
        $vagasLimite = $vagasRaw === '' ? null : (int) $vagasRaw;

        if ($titulo === '' || $data === '' || $horaInicio === '' || $horaFim === '' || $localId <= 0) {
            Response::error('Preencha título, data, horários e local.');
        }
        if ($horaFim <= $horaInicio) {
            Response::error('Horário de fim deve ser posterior ao de início.');
        }
        if ($vagasLimite !== null && $vagasLimite < 1) {
            Response::error('Informe um número válido de vagas ou deixe em branco para ilimitado.');
        }
        if ($ofereceCert && $descricaoCert === '') {
            Response::error('Informe a descrição para o certificado ou desmarque a opção de certificado.');
        }

        $grupoId = null;

        if ($associadaEvento) {
            if ($eventoId === null || $eventoId <= 0) {
                Response::error('Selecione o evento ou desmarque a associação.');
            }
            $event = $this->eventModel->findById($eventoId);
            if (!$event) {
                Response::error('Evento não encontrado.', 404);
            }
            $grupoId = (int) $event['grupo_id'];
        } else {
            $eventoId = null;
            if ($role === 'proj') {
                $grupoId = (int) Session::get('user_grupo_id');
                if ($grupoId <= 0) {
                    Response::error('Seu perfil de projeto não possui grupo vinculado.');
                }
            } else {
                $grupoId = (int) $grupoIdRaw;
                if ($grupoId <= 0) {
                    Response::error('Selecione o grupo organizador.');
                }
            }
        }

        if ($role === 'proj') {
            $userGrupoId = (int) Session::get('user_grupo_id');
            if ($userGrupoId <= 0) {
                Response::error('Seu perfil de projeto não possui grupo vinculado.');
            }
            if ($grupoId !== $userGrupoId) {
                Response::error('Você só pode criar atividades para o seu grupo.', 403);
            }
        }

        return [
            'evento_id'             => $eventoId,
            'grupo_id'              => $grupoId,
            'titulo'                => $titulo,
            'descricao'             => $descricao !== '' ? $descricao : null,
            'data'                  => $data,
            'hora_inicio'           => $horaInicio,
            'hora_fim'              => $horaFim,
            'local_id'              => $localId,
            'oferece_certificado'   => $ofereceCert,
            'descricao_certificado' => $ofereceCert ? $descricaoCert : null,
            'vagas_limite'          => $vagasLimite,
        ];
    }

    private function assertGrupoAccess(int $grupoId, string $role): void
    {
        if ($role === 'adm') {
            return;
        }
        if ($role === 'proj' && $grupoId === (int) Session::get('user_grupo_id')) {
            return;
        }
        Response::error('Sem permissão para este grupo.', 403);
    }

    private function checkOwnership(array $activity, string $role): void
    {
        if ($role === 'adm') {
            return;
        }
        if ($role === 'proj' && (int) $activity['grupo_id'] === (int) Session::get('user_grupo_id')) {
            return;
        }
        Response::error('Sem permissão.', 403);
    }
}
