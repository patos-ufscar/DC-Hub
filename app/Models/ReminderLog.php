<?php
declare(strict_types=1);

namespace App\Models;

use PDO;

class ReminderLog
{
    public function __construct(private PDO $db) {}

    public function wasSent(int $userId, int $atividadeId, string $tipo): bool
    {
        $stmt = $this->db->prepare(
            'SELECT 1 FROM lembretes_enviados
             WHERE user_id = :uid AND atividade_id = :aid AND tipo = :tipo'
        );
        $stmt->bindValue(':uid', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':aid', $atividadeId, PDO::PARAM_INT);
        $stmt->bindValue(':tipo', $tipo);
        $stmt->execute();

        return (bool) $stmt->fetchColumn();
    }

    public function markSent(int $userId, int $atividadeId, string $tipo): void
    {
        if ($this->wasSent($userId, $atividadeId, $tipo)) {
            return;
        }

        $stmt = $this->db->prepare(
            'INSERT INTO lembretes_enviados (user_id, atividade_id, tipo) VALUES (:uid, :aid, :tipo)'
        );
        $stmt->bindValue(':uid', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':aid', $atividadeId, PDO::PARAM_INT);
        $stmt->bindValue(':tipo', $tipo);
        $stmt->execute();
    }
}
