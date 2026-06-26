<?php
declare(strict_types=1);

namespace App\Models;

use PDO;

/**
 * Fila de avisos de reagendamento: quando a data/hora de uma atividade muda,
 * cada inscrito (rsvp/presente) recebe uma linha "pendente" que o cron drena
 * respeitando a cota diária de e-mails.
 */
class RescheduleNotification
{
    public function __construct(private PDO $db) {}

    /** Houve mudança de data ou de horário (início/fim)? Ignora diferença de formato. */
    public static function scheduleChanged(array $old, array $new): bool
    {
        return self::normDate((string) ($old['data'] ?? '')) !== self::normDate((string) ($new['data'] ?? ''))
            || self::normTime((string) ($old['hora_inicio'] ?? '')) !== self::normTime((string) ($new['hora_inicio'] ?? ''))
            || self::normTime((string) ($old['hora_fim'] ?? '')) !== self::normTime((string) ($new['hora_fim'] ?? ''));
    }

    /**
     * Enfileira um aviso por inscrito (rsvp/presente). Avisos pendentes anteriores
     * da mesma atividade são descartados para não enviar e-mails duplicados.
     *
     * @param array{data: string, hora_inicio: string, hora_fim: string} $old
     * @param array{data: string, hora_inicio: string, hora_fim: string} $new
     * @return int Quantidade de inscritos enfileirados.
     */
    public function enqueueForActivity(int $atividadeId, array $old, array $new): int
    {
        if ($atividadeId <= 0) {
            throw new \InvalidArgumentException('Atividade inválida para reagendamento.');
        }
        self::assertValidSchedule($old);
        self::assertValidSchedule($new);

        $del = $this->db->prepare(
            "DELETE FROM reagendamentos_pendentes
             WHERE atividade_id = :aid AND status = 'pendente'"
        );
        $del->bindValue(':aid', $atividadeId, PDO::PARAM_INT);
        $del->execute();

        $stmt = $this->db->prepare(
            "INSERT INTO reagendamentos_pendentes
                (user_id, atividade_id, data_antiga, hora_inicio_antiga, hora_fim_antiga,
                 data_nova, hora_inicio_nova, hora_fim_nova)
             SELECT i.user_id, :aid, :do, :hio, :hfo, :dn, :hin, :hfn
             FROM inscricoes i
             WHERE i.atividade_id = :aid2 AND i.status IN ('rsvp', 'presente')"
        );
        $stmt->bindValue(':aid', $atividadeId, PDO::PARAM_INT);
        $stmt->bindValue(':aid2', $atividadeId, PDO::PARAM_INT);
        $stmt->bindValue(':do', self::normDate((string) $old['data']));
        $stmt->bindValue(':hio', self::normTime((string) $old['hora_inicio']));
        $stmt->bindValue(':hfo', self::normTime((string) $old['hora_fim']));
        $stmt->bindValue(':dn', self::normDate((string) $new['data']));
        $stmt->bindValue(':hin', self::normTime((string) $new['hora_inicio']));
        $stmt->bindValue(':hfn', self::normTime((string) $new['hora_fim']));
        $stmt->execute();

        return $stmt->rowCount();
    }

    /**
     * Avisos pendentes prontos para envio, com dados do usuário e da atividade.
     *
     * @return list<array<string, mixed>>
     */
    public function listPending(int $limit): array
    {
        $limit = max(0, $limit);
        if ($limit === 0) {
            return [];
        }

        $stmt = $this->db->prepare(
            "SELECT r.id, r.user_id, r.atividade_id,
                    r.data_antiga, r.hora_inicio_antiga, r.hora_fim_antiga,
                    r.data_nova, r.hora_inicio_nova, r.hora_fim_nova,
                    u.email AS user_email, u.nome_exibicao AS user_nome,
                    a.titulo AS atividade_titulo, l.nome AS local_nome,
                    e.titulo AS evento_titulo
             FROM reagendamentos_pendentes r
             JOIN usuarios u ON u.id = r.user_id
             JOIN atividades a ON a.id = r.atividade_id
             LEFT JOIN locais l ON l.id = a.local_id
             LEFT JOIN eventos e ON e.id = a.evento_id
             WHERE r.status = 'pendente'
             ORDER BY r.created_at, r.id
             LIMIT :limit"
        );
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    public function markSent(int $id): void
    {
        $stmt = $this->db->prepare(
            "UPDATE reagendamentos_pendentes
             SET status = 'enviado', enviado_em = CURRENT_TIMESTAMP
             WHERE id = :id"
        );
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
    }

    public function markFailed(int $id): void
    {
        $stmt = $this->db->prepare(
            "UPDATE reagendamentos_pendentes SET status = 'falhou' WHERE id = :id"
        );
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
    }

    public function countByStatus(string $status): int
    {
        $stmt = $this->db->prepare(
            'SELECT COUNT(*) FROM reagendamentos_pendentes WHERE status = :status'
        );
        $stmt->bindValue(':status', $status);
        $stmt->execute();

        return (int) $stmt->fetchColumn();
    }

    private static function assertValidSchedule(array $schedule): void
    {
        $date = trim((string) ($schedule['data'] ?? ''));
        $dt = \DateTimeImmutable::createFromFormat('!Y-m-d', $date);
        if (!$dt || $dt->format('Y-m-d') !== $date) {
            throw new \InvalidArgumentException('Data inválida para reagendamento.');
        }

        foreach (['hora_inicio', 'hora_fim'] as $field) {
            $time = trim((string) ($schedule[$field] ?? ''));
            if (!preg_match('/^([01]\d|2[0-3]):[0-5]\d(:[0-5]\d)?$/', $time)) {
                throw new \InvalidArgumentException('Horário inválido para reagendamento.');
            }
        }
    }

    private static function normTime(string $time): string
    {
        return substr(trim($time), 0, 5);
    }

    private static function normDate(string $date): string
    {
        return trim($date);
    }
}
