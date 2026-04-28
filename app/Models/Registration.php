<?php
declare(strict_types=1);

namespace App\Models;

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

        // Create RSVP
        $ins = $this->db->prepare(
            "INSERT INTO inscricoes (user_id, atividade_id, status) VALUES (:uid, :aid, 'rsvp')"
        );
        $ins->bindValue(':uid', $userId, PDO::PARAM_INT);
        $ins->bindValue(':aid', $atividadeId, PDO::PARAM_INT);
        $ins->execute();

        return 'rsvp';
    }

    public function getUserRsvps(int $userId): array
    {
        $stmt = $this->db->prepare(
            'SELECT i.*, a.titulo AS atividade_titulo, a.data, a.hora_inicio, a.hora_fim,
                    e.titulo AS evento_titulo, e.id AS evento_id,
                    g.nome AS grupo_nome, l.nome AS local_nome,
                    TIMESTAMPDIFF(MINUTE, CONCAT(a.data, " ", a.hora_inicio), CONCAT(a.data, " ", a.hora_fim)) AS carga_minutos
             FROM inscricoes i
             JOIN atividades a ON a.id = i.atividade_id
             JOIN eventos e ON e.id = a.evento_id
             JOIN grupos g ON g.id = e.grupo_id
             JOIN locais l ON l.id = a.local_id
             WHERE i.user_id = :uid
             ORDER BY a.data DESC, a.hora_inicio'
        );
        $stmt->bindValue(':uid', $userId, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    public function getActivityAttendees(int $atividadeId): array
    {
        $stmt = $this->db->prepare(
            'SELECT i.*, u.nome_exibicao, u.email
             FROM inscricoes i
             JOIN usuarios u ON u.id = i.user_id
             WHERE i.atividade_id = :aid
             ORDER BY u.nome_exibicao'
        );
        $stmt->bindValue(':aid', $atividadeId, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    public function confirmPresence(int $userId, int $atividadeId): bool
    {
        $stmt = $this->db->prepare(
            "UPDATE inscricoes SET status = 'presente' WHERE user_id = :uid AND atividade_id = :aid"
        );
        $stmt->bindValue(':uid', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':aid', $atividadeId, PDO::PARAM_INT);

        return $stmt->execute();
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

        // Check if user has RSVP
        $check = $this->db->prepare(
            'SELECT id FROM inscricoes WHERE user_id = :uid AND atividade_id = :aid'
        );
        $check->bindValue(':uid', $userId, PDO::PARAM_INT);
        $check->bindValue(':aid', (int) $activity['id'], PDO::PARAM_INT);
        $check->execute();

        if (!$check->fetch()) {
            // Auto-register and confirm
            $ins = $this->db->prepare(
                "INSERT INTO inscricoes (user_id, atividade_id, status) VALUES (:uid, :aid, 'presente')"
            );
            $ins->bindValue(':uid', $userId, PDO::PARAM_INT);
            $ins->bindValue(':aid', (int) $activity['id'], PDO::PARAM_INT);
            $ins->execute();
        } else {
            $this->confirmPresence($userId, (int) $activity['id']);
        }

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
        $stmt = $this->db->prepare(
            "SELECT e.id AS evento_id, e.titulo AS evento_titulo, g.nome AS grupo_nome,
                    COUNT(a.id) AS total_atividades,
                    SUM(TIMESTAMPDIFF(MINUTE, CONCAT(a.data, ' ', a.hora_inicio), CONCAT(a.data, ' ', a.hora_fim))) AS total_minutos
             FROM inscricoes i
             JOIN atividades a ON a.id = i.atividade_id
             JOIN eventos e ON e.id = a.evento_id
             JOIN grupos g ON g.id = e.grupo_id
             WHERE i.user_id = :uid AND i.status = 'presente'
             GROUP BY e.id, e.titulo, g.nome
             ORDER BY e.titulo"
        );
        $stmt->bindValue(':uid', $userId, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    public function getCertificateActivities(int $userId, int $eventoId): array
    {
        $stmt = $this->db->prepare(
            "SELECT a.titulo, a.data, a.hora_inicio, a.hora_fim, a.descricao_certificado,
                    l.nome AS local_nome,
                    TIMESTAMPDIFF(MINUTE, CONCAT(a.data, ' ', a.hora_inicio), CONCAT(a.data, ' ', a.hora_fim)) AS carga_minutos
             FROM inscricoes i
             JOIN atividades a ON a.id = i.atividade_id
             JOIN locais l ON l.id = a.local_id
             WHERE i.user_id = :uid AND a.evento_id = :eid AND i.status = 'presente'
             ORDER BY a.data, a.hora_inicio"
        );
        $stmt->bindValue(':uid', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':eid', $eventoId, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    public function bulkConfirmPresence(int $atividadeId, array $userIds): int
    {
        $count = 0;
        $stmt = $this->db->prepare(
            "UPDATE inscricoes SET status = 'presente' WHERE user_id = :uid AND atividade_id = :aid"
        );

        foreach ($userIds as $uid) {
            $stmt->bindValue(':uid', (int) $uid, PDO::PARAM_INT);
            $stmt->bindValue(':aid', $atividadeId, PDO::PARAM_INT);
            $stmt->execute();
            $count += $stmt->rowCount();
        }

        return $count;
    }
}
