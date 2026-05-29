<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\DatabaseDialect;
use PDO;

class Activity
{
    private const SELECT_BASE = '
        a.*, l.nome AS local_nome, e.titulo AS evento_titulo,
        g.nome AS grupo_nome';

    public function __construct(private PDO $db) {}

    public function create(array $data): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO atividades (
                evento_id, grupo_id, titulo, descricao, data, hora_inicio, hora_fim, local_id,
                oferece_certificado, descricao_certificado, vagas_limite
             ) VALUES (
                :eid, :gid, :titulo, :descricao, :data, :hi, :hf, :lid,
                :cert, :dc, :vagas
             )'
        );
        $this->bindActivityData($stmt, $data, true);
        $stmt->execute();

        return (int) $this->db->lastInsertId();
    }

    public function findById(int $id): ?array
    {
        $duration = DatabaseDialect::durationMinutesExpr();
        $stmt = $this->db->prepare(
            "SELECT " . self::SELECT_BASE . ",
                    {$duration} AS carga_minutos,
                    (SELECT COUNT(*) FROM inscricoes i
                     WHERE i.atividade_id = a.id AND i.status IN ('rsvp', 'presente')) AS vagas_ocupadas
             FROM atividades a
             JOIN locais l ON l.id = a.local_id
             JOIN grupos g ON g.id = a.grupo_id
             LEFT JOIN eventos e ON e.id = a.evento_id
             WHERE a.id = :id"
        );
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();

        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function update(int $id, array $data): bool
    {
        $stmt = $this->db->prepare(
            'UPDATE atividades SET
                evento_id = :eid,
                grupo_id = :gid,
                titulo = :titulo,
                descricao = :descricao,
                data = :data,
                hora_inicio = :hi,
                hora_fim = :hf,
                local_id = :lid,
                oferece_certificado = :cert,
                descricao_certificado = :dc,
                vagas_limite = :vagas
             WHERE id = :id'
        );
        $this->bindActivityData($stmt, $data, true);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);

        return $stmt->execute();
    }

    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare('DELETE FROM atividades WHERE id = :id');
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        return $stmt->execute();
    }

    public function countOccupiedSpots(int $id): int
    {
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) FROM inscricoes
             WHERE atividade_id = :aid AND status IN ('rsvp', 'presente')"
        );
        $stmt->bindValue(':aid', $id, PDO::PARAM_INT);
        $stmt->execute();

        return (int) $stmt->fetchColumn();
    }

    public function listByEvent(int $eventoId): array
    {
        $duration = DatabaseDialect::durationMinutesExpr();
        $stmt = $this->db->prepare(
            "SELECT a.*, l.nome AS local_nome,
                    {$duration} AS carga_minutos,
                    (SELECT COUNT(*) FROM inscricoes i
                     WHERE i.atividade_id = a.id AND i.status IN ('rsvp', 'presente')) AS vagas_ocupadas
             FROM atividades a
             JOIN locais l ON l.id = a.local_id
             WHERE a.evento_id = :eid
             ORDER BY a.data, a.hora_inicio"
        );
        $stmt->bindValue(':eid', $eventoId, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    public function listForManage(?int $grupoId, bool $upcoming = true): array
    {
        $duration = DatabaseDialect::durationMinutesExpr();
        $today = date('Y-m-d');
        $dateCmp = $upcoming ? 'a.data >= :today' : 'a.data < :today';

        $sql = "SELECT a.id, a.titulo, a.data, a.hora_inicio, a.hora_fim, a.evento_id,
                       a.grupo_id, g.nome AS grupo_nome, l.nome AS local_nome,
                       e.titulo AS evento_titulo, a.vagas_limite,
                       {$duration} AS carga_minutos,
                       (SELECT COUNT(*) FROM inscricoes i
                        WHERE i.atividade_id = a.id AND i.status IN ('rsvp', 'presente')) AS inscritos,
                       (SELECT COUNT(*) FROM inscricoes i
                        WHERE i.atividade_id = a.id AND i.status = 'presente') AS presentes
                FROM atividades a
                JOIN locais l ON l.id = a.local_id
                JOIN grupos g ON g.id = a.grupo_id
                LEFT JOIN eventos e ON e.id = a.evento_id
                WHERE {$dateCmp}";

        if ($grupoId !== null) {
            $sql .= ' AND a.grupo_id = :gid';
        }

        $sql .= ' ORDER BY a.data ' . ($upcoming ? 'ASC' : 'DESC') . ', a.hora_inicio';

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':today', $today);

        if ($grupoId !== null) {
            $stmt->bindValue(':gid', $grupoId, PDO::PARAM_INT);
        }

        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function listByDateRange(string $startDate, string $endDate, ?int $grupoId = null): array
    {
        $duration = DatabaseDialect::durationMinutesExpr();
        $sql = "SELECT " . self::SELECT_BASE . ",
                       {$duration} AS carga_minutos,
                       (SELECT COUNT(*) FROM inscricoes i
                        WHERE i.atividade_id = a.id AND i.status IN ('rsvp', 'presente')) AS vagas_ocupadas
                FROM atividades a
                JOIN locais l ON l.id = a.local_id
                JOIN grupos g ON g.id = a.grupo_id
                LEFT JOIN eventos e ON e.id = a.evento_id
                WHERE a.data BETWEEN :start AND :end";

        if ($grupoId !== null) {
            $sql .= ' AND a.grupo_id = :gid';
        }

        $sql .= ' ORDER BY a.data, a.hora_inicio';

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':start', $startDate);
        $stmt->bindValue(':end', $endDate);

        if ($grupoId !== null) {
            $stmt->bindValue(':gid', $grupoId, PDO::PARAM_INT);
        }

        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function setRedemptionCode(int $id, string $code): bool
    {
        $stmt = $this->db->prepare(
            'UPDATE atividades SET codigo_resgate = :code WHERE id = :id'
        );
        $stmt->bindValue(':code', $code);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);

        return $stmt->execute();
    }

    public function findByRedemptionCode(string $code): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT * FROM atividades WHERE codigo_resgate = :code'
        );
        $stmt->bindValue(':code', $code);
        $stmt->execute();

        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function getUpcomingWithRsvp(int $hoursAhead): array
    {
        $upcoming = DatabaseDialect::upcomingActivitiesWhere();
        $stmt = $this->db->prepare(
            "SELECT a.*, e.titulo AS evento_titulo, g.nome AS grupo_nome, l.nome AS local_nome,
                    u.email AS user_email, u.nome_exibicao AS user_nome
             FROM atividades a
             JOIN grupos g ON g.id = a.grupo_id
             LEFT JOIN eventos e ON e.id = a.evento_id
             JOIN locais l ON l.id = a.local_id
             JOIN inscricoes i ON i.atividade_id = a.id AND i.status = 'rsvp'
             JOIN usuarios u ON u.id = i.user_id
             WHERE {$upcoming}
             ORDER BY a.data, a.hora_inicio"
        );
        $stmt->bindValue(':hours', $hoursAhead, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    private function bindActivityData(\PDOStatement $stmt, array $data, bool $includeRelations = false): void
    {
        if ($includeRelations) {
            if ($data['evento_id'] === null) {
                $stmt->bindValue(':eid', null, PDO::PARAM_NULL);
            } else {
                $stmt->bindValue(':eid', (int) $data['evento_id'], PDO::PARAM_INT);
            }
            $stmt->bindValue(':gid', (int) $data['grupo_id'], PDO::PARAM_INT);
        }

        $stmt->bindValue(':titulo', $data['titulo']);
        $stmt->bindValue(':descricao', $data['descricao'] ?: null);
        $stmt->bindValue(':data', $data['data']);
        $stmt->bindValue(':hi', $data['hora_inicio']);
        $stmt->bindValue(':hf', $data['hora_fim']);
        $stmt->bindValue(':lid', (int) $data['local_id'], PDO::PARAM_INT);
        $stmt->bindValue(':cert', $data['oferece_certificado'] ? 1 : 0, PDO::PARAM_INT);
        $stmt->bindValue(':dc', $data['descricao_certificado'] ?: null);
        if ($data['vagas_limite'] === null) {
            $stmt->bindValue(':vagas', null, PDO::PARAM_NULL);
        } else {
            $stmt->bindValue(':vagas', (int) $data['vagas_limite'], PDO::PARAM_INT);
        }
    }
}
