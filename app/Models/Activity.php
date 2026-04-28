<?php
declare(strict_types=1);

namespace App\Models;

use PDO;

class Activity
{
    public function __construct(private PDO $db) {}

    public function create(
        int $eventoId,
        string $titulo,
        string $data,
        string $horaInicio,
        string $horaFim,
        int $localId,
        string $descricaoCertificado
    ): int {
        $stmt = $this->db->prepare(
            'INSERT INTO atividades (evento_id, titulo, data, hora_inicio, hora_fim, local_id, descricao_certificado)
             VALUES (:eid, :titulo, :data, :hi, :hf, :lid, :dc)'
        );
        $stmt->bindValue(':eid', $eventoId, PDO::PARAM_INT);
        $stmt->bindValue(':titulo', $titulo);
        $stmt->bindValue(':data', $data);
        $stmt->bindValue(':hi', $horaInicio);
        $stmt->bindValue(':hf', $horaFim);
        $stmt->bindValue(':lid', $localId, PDO::PARAM_INT);
        $stmt->bindValue(':dc', $descricaoCertificado);
        $stmt->execute();

        return (int) $this->db->lastInsertId();
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT a.*, l.nome AS local_nome, e.titulo AS evento_titulo, e.grupo_id, g.nome AS grupo_nome,
                    TIMESTAMPDIFF(MINUTE, CONCAT(a.data, " ", a.hora_inicio), CONCAT(a.data, " ", a.hora_fim)) AS carga_minutos
             FROM atividades a
             JOIN locais l ON l.id = a.local_id
             JOIN eventos e ON e.id = a.evento_id
             JOIN grupos g ON g.id = e.grupo_id
             WHERE a.id = :id'
        );
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();

        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function update(
        int $id,
        string $titulo,
        string $data,
        string $horaInicio,
        string $horaFim,
        int $localId,
        string $descricaoCertificado
    ): bool {
        $stmt = $this->db->prepare(
            'UPDATE atividades SET titulo = :titulo, data = :data, hora_inicio = :hi,
             hora_fim = :hf, local_id = :lid, descricao_certificado = :dc WHERE id = :id'
        );
        $stmt->bindValue(':titulo', $titulo);
        $stmt->bindValue(':data', $data);
        $stmt->bindValue(':hi', $horaInicio);
        $stmt->bindValue(':hf', $horaFim);
        $stmt->bindValue(':lid', $localId, PDO::PARAM_INT);
        $stmt->bindValue(':dc', $descricaoCertificado);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);

        return $stmt->execute();
    }

    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare('DELETE FROM atividades WHERE id = :id');
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        return $stmt->execute();
    }

    public function listByEvent(int $eventoId): array
    {
        $stmt = $this->db->prepare(
            'SELECT a.*, l.nome AS local_nome,
                    TIMESTAMPDIFF(MINUTE, CONCAT(a.data, " ", a.hora_inicio), CONCAT(a.data, " ", a.hora_fim)) AS carga_minutos
             FROM atividades a
             JOIN locais l ON l.id = a.local_id
             WHERE a.evento_id = :eid
             ORDER BY a.data, a.hora_inicio'
        );
        $stmt->bindValue(':eid', $eventoId, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    public function listByDateRange(string $startDate, string $endDate, ?int $grupoId = null): array
    {
        $sql = 'SELECT a.*, l.nome AS local_nome, e.titulo AS evento_titulo,
                       e.grupo_id, g.nome AS grupo_nome,
                       TIMESTAMPDIFF(MINUTE, CONCAT(a.data, " ", a.hora_inicio), CONCAT(a.data, " ", a.hora_fim)) AS carga_minutos
                FROM atividades a
                JOIN locais l ON l.id = a.local_id
                JOIN eventos e ON e.id = a.evento_id
                JOIN grupos g ON g.id = e.grupo_id
                WHERE a.data BETWEEN :start AND :end';

        if ($grupoId !== null) {
            $sql .= ' AND e.grupo_id = :gid';
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
        $stmt = $this->db->prepare(
            "SELECT a.*, e.titulo AS evento_titulo, g.nome AS grupo_nome, l.nome AS local_nome,
                    u.email AS user_email, u.nome_exibicao AS user_nome
             FROM atividades a
             JOIN eventos e ON e.id = a.evento_id
             JOIN grupos g ON g.id = e.grupo_id
             JOIN locais l ON l.id = a.local_id
             JOIN inscricoes i ON i.atividade_id = a.id AND i.status = 'rsvp'
             JOIN usuarios u ON u.id = i.user_id
             WHERE CONCAT(a.data, ' ', a.hora_inicio)
                   BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL :hours HOUR)
             ORDER BY a.data, a.hora_inicio"
        );
        $stmt->bindValue(':hours', $hoursAhead, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }
}
