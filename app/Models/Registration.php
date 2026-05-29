<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\DatabaseDialect;
use PDO;

class Registration
{
    public function __construct(private PDO $db) {}

    public function toggleRsvp(int $userId, int $atividadeId): string
    {
        // Check if registration exists
        $stmt = $this->db->prepare(
            'SELECT id, status FROM inscricoes WHERE user_id = :uid AND atividade_id = :aid'
        );
        $stmt->bindValue(':uid', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':aid', $atividadeId, PDO::PARAM_INT);
        $stmt->execute();
        $existing = $stmt->fetch();

        if ($existing) {
            // If already has confirmed presence, can't toggle
            if ($existing['status'] === 'presente') {
                return 'presente';
            }
            // Remove RSVP
            $del = $this->db->prepare('DELETE FROM inscricoes WHERE id = :id');
            $del->bindValue(':id', (int) $existing['id'], PDO::PARAM_INT);
            $del->execute();
            return 'removed';
        }

        if (!$this->hasAvailableSpot($atividadeId)) {
            return 'full';
        }

        // Create RSVP
        $ins = $this->db->prepare(
            "INSERT INTO inscricoes (user_id, atividade_id, status) VALUES (:uid, :aid, 'rsvp')"
        );
        $ins->bindValue(':uid', $userId, PDO::PARAM_INT);
        $ins->bindValue(':aid', $atividadeId, PDO::PARAM_INT);
        $ins->execute();

        return 'rsvp';
    }

    public function hasAvailableSpot(int $atividadeId): bool
    {
        $stmt = $this->db->prepare('SELECT vagas_limite FROM atividades WHERE id = :id');
        $stmt->bindValue(':id', $atividadeId, PDO::PARAM_INT);
        $stmt->execute();
        $row = $stmt->fetch();

        if (!$row || $row['vagas_limite'] === null) {
            return true;
        }

        $limit = (int) $row['vagas_limite'];
        $occupied = $this->countOccupiedSpots($atividadeId);

        return $occupied < $limit;
    }

    public function countOccupiedSpots(int $atividadeId): int
    {
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) FROM inscricoes
             WHERE atividade_id = :aid AND status IN ('rsvp', 'presente')"
        );
        $stmt->bindValue(':aid', $atividadeId, PDO::PARAM_INT);
        $stmt->execute();

        return (int) $stmt->fetchColumn();
    }

    public function getUserRsvps(int $userId): array
    {
        $duration = DatabaseDialect::durationMinutesExpr();
        $stmt = $this->db->prepare(
            "SELECT i.*, a.titulo AS atividade_titulo, a.data, a.hora_inicio, a.hora_fim,
                    e.titulo AS evento_titulo, e.id AS evento_id,
                    g.nome AS grupo_nome, l.nome AS local_nome,
                    {$duration} AS carga_minutos
             FROM inscricoes i
             JOIN atividades a ON a.id = i.atividade_id
             LEFT JOIN eventos e ON e.id = a.evento_id
             JOIN grupos g ON g.id = a.grupo_id
             JOIN locais l ON l.id = a.local_id
             WHERE i.user_id = :uid
             ORDER BY a.data DESC, a.hora_inicio"
        );
        $stmt->bindValue(':uid', $userId, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    public function getActivityAttendees(int $atividadeId): array
    {
        $stmt = $this->db->prepare(
            'SELECT i.user_id, i.status, i.validado_em, i.metodo_validacao,
                    u.nome_exibicao, u.email,
                    v.nome_exibicao AS validado_por_nome
             FROM inscricoes i
             JOIN usuarios u ON u.id = i.user_id
             LEFT JOIN usuarios v ON v.id = i.validado_por
             WHERE i.atividade_id = :aid
             ORDER BY u.nome_exibicao'
        );
        $stmt->bindValue(':aid', $atividadeId, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    public function getCheckinList(int $atividadeId): array
    {
        $stmt = $this->db->prepare(
            'SELECT u.id AS user_id, u.nome_exibicao, u.email,
                    i.status, i.validado_em, i.metodo_validacao,
                    v.nome_exibicao AS validado_por_nome
             FROM inscricoes i
             JOIN usuarios u ON u.id = i.user_id
             LEFT JOIN usuarios v ON v.id = i.validado_por
             WHERE i.atividade_id = :aid
             ORDER BY
                CASE i.status WHEN \'presente\' THEN 0 WHEN \'rsvp\' THEN 1 ELSE 2 END,
                u.nome_exibicao'
        );
        $stmt->bindValue(':aid', $atividadeId, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    /**
     * @return array{already: bool, user_id: int, nome_exibicao: string, status: string}
     */
    public function markPresent(int $userId, int $atividadeId, int $validatedBy, string $method): array
    {
        $userStmt = $this->db->prepare(
            'SELECT id, nome_exibicao FROM usuarios WHERE id = :id'
        );
        $userStmt->bindValue(':id', $userId, PDO::PARAM_INT);
        $userStmt->execute();
        $user = $userStmt->fetch();
        if (!$user) {
            throw new \InvalidArgumentException('Usuário não encontrado.');
        }

        $check = $this->db->prepare(
            'SELECT id, status FROM inscricoes WHERE user_id = :uid AND atividade_id = :aid'
        );
        $check->bindValue(':uid', $userId, PDO::PARAM_INT);
        $check->bindValue(':aid', $atividadeId, PDO::PARAM_INT);
        $check->execute();
        $existing = $check->fetch();

        if ($existing && $existing['status'] === 'presente') {
            return [
                'already'       => true,
                'user_id'       => $userId,
                'nome_exibicao' => $user['nome_exibicao'],
                'status'        => 'presente',
            ];
        }

        if ($existing) {
            $stmt = $this->db->prepare(
                "UPDATE inscricoes
                 SET status = 'presente', validado_em = CURRENT_TIMESTAMP,
                     validado_por = :validador, metodo_validacao = :metodo
                 WHERE id = :id"
            );
            $stmt->bindValue(':validador', $validatedBy, PDO::PARAM_INT);
            $stmt->bindValue(':metodo', $method);
            $stmt->bindValue(':id', (int) $existing['id'], PDO::PARAM_INT);
            $stmt->execute();
        } else {
            $stmt = $this->db->prepare(
                "INSERT INTO inscricoes (user_id, atividade_id, status, validado_em, validado_por, metodo_validacao)
                 VALUES (:uid, :aid, 'presente', CURRENT_TIMESTAMP, :validador, :metodo)"
            );
            $stmt->bindValue(':uid', $userId, PDO::PARAM_INT);
            $stmt->bindValue(':aid', $atividadeId, PDO::PARAM_INT);
            $stmt->bindValue(':validador', $validatedBy, PDO::PARAM_INT);
            $stmt->bindValue(':metodo', $method);
            $stmt->execute();
        }

        return [
            'already'       => false,
            'user_id'       => $userId,
            'nome_exibicao' => $user['nome_exibicao'],
            'status'        => 'presente',
        ];
    }

    public function confirmByCode(int $userId, string $code): ?array
    {
        // Find activity by code
        $stmt = $this->db->prepare(
            'SELECT id FROM atividades WHERE codigo_resgate = :code'
        );
        $stmt->bindValue(':code', $code);
        $stmt->execute();
        $activity = $stmt->fetch();

        if (!$activity) {
            return null;
        }

        $this->markPresent($userId, (int) $activity['id'], $userId, 'codigo');

        return $activity;
    }

    public function getUserStatus(int $userId, int $atividadeId): ?string
    {
        $stmt = $this->db->prepare(
            'SELECT status FROM inscricoes WHERE user_id = :uid AND atividade_id = :aid'
        );
        $stmt->bindValue(':uid', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':aid', $atividadeId, PDO::PARAM_INT);
        $stmt->execute();

        $row = $stmt->fetch();
        return $row ? $row['status'] : null;
    }

    public function getEligibleCertificates(int $userId): array
    {
        $duration = DatabaseDialect::durationMinutesExpr();
        $stmt = $this->db->prepare(
            "SELECT e.id AS evento_id, e.titulo AS evento_titulo, g.nome AS grupo_nome,
                    COUNT(a.id) AS total_atividades,
                    SUM({$duration}) AS total_minutos
             FROM inscricoes i
             JOIN atividades a ON a.id = i.atividade_id
             JOIN eventos e ON e.id = a.evento_id
             JOIN grupos g ON g.id = e.grupo_id
             WHERE i.user_id = :uid AND i.status = 'presente'
               AND a.oferece_certificado = 1
             GROUP BY e.id, e.titulo, g.nome
             ORDER BY e.titulo"
        );
        $stmt->bindValue(':uid', $userId, PDO::PARAM_INT);
        $stmt->execute();
        $eventCerts = $stmt->fetchAll();

        $stmt2 = $this->db->prepare(
            "SELECT a.id AS atividade_id, a.titulo AS evento_titulo, g.nome AS grupo_nome,
                    1 AS total_atividades,
                    {$duration} AS total_minutos
             FROM inscricoes i
             JOIN atividades a ON a.id = i.atividade_id
             JOIN grupos g ON g.id = a.grupo_id
             WHERE i.user_id = :uid AND i.status = 'presente'
               AND a.oferece_certificado = 1
               AND a.evento_id IS NULL
             ORDER BY a.titulo"
        );
        $stmt2->bindValue(':uid', $userId, PDO::PARAM_INT);
        $stmt2->execute();
        $standalone = $stmt2->fetchAll();

        foreach ($eventCerts as &$row) {
            $row['tipo'] = 'evento';
            $row['atividade_id'] = null;
        }
        unset($row);

        foreach ($standalone as &$row) {
            $row['tipo'] = 'atividade';
            $row['evento_id'] = null;
        }
        unset($row);

        return array_merge($eventCerts, $standalone);
    }

    public function getCertificateActivities(int $userId, int $eventoId): array
    {
        $duration = DatabaseDialect::durationMinutesExpr();
        $stmt = $this->db->prepare(
            "SELECT a.titulo, a.data, a.hora_inicio, a.hora_fim, a.descricao_certificado,
                    l.nome AS local_nome,
                    {$duration} AS carga_minutos
             FROM inscricoes i
             JOIN atividades a ON a.id = i.atividade_id
             JOIN locais l ON l.id = a.local_id
             WHERE i.user_id = :uid AND a.evento_id = :eid AND i.status = 'presente'
               AND a.oferece_certificado = 1
             ORDER BY a.data, a.hora_inicio"
        );
        $stmt->bindValue(':uid', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':eid', $eventoId, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    public function getCertificateStandaloneActivity(int $userId, int $atividadeId): ?array
    {
        $duration = DatabaseDialect::durationMinutesExpr();
        $stmt = $this->db->prepare(
            "SELECT a.titulo, a.data, a.hora_inicio, a.hora_fim, a.descricao_certificado,
                    l.nome AS local_nome,
                    {$duration} AS carga_minutos
             FROM inscricoes i
             JOIN atividades a ON a.id = i.atividade_id
             JOIN locais l ON l.id = a.local_id
             WHERE i.user_id = :uid AND a.id = :aid AND a.evento_id IS NULL
               AND i.status = 'presente' AND a.oferece_certificado = 1"
        );
        $stmt->bindValue(':uid', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':aid', $atividadeId, PDO::PARAM_INT);
        $stmt->execute();

        $rows = $stmt->fetchAll();
        return $rows !== [] ? $rows : null;
    }

    public function bulkConfirmPresence(int $atividadeId, array $userIds, int $validatedBy): int
    {
        $count = 0;

        foreach ($userIds as $uid) {
            $result = $this->markPresent((int) $uid, $atividadeId, $validatedBy, 'manual');
            if (!$result['already']) {
                $count++;
            }
        }

        return $count;
    }
}
