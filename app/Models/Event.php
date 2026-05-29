<?php
declare(strict_types=1);

namespace App\Models;

use PDO;

class Event
{
    public function __construct(private PDO $db) {}

    public function create(int $grupoId, string $titulo, ?string $descricao = null): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO eventos (grupo_id, titulo, descricao) VALUES (:gid, :titulo, :descricao)'
        );
        $stmt->bindValue(':gid', $grupoId, PDO::PARAM_INT);
        $stmt->bindValue(':titulo', $titulo);
        $stmt->bindValue(':descricao', $descricao);
        $stmt->execute();

        return (int) $this->db->lastInsertId();
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT e.*, g.nome AS grupo_nome
             FROM eventos e
             JOIN grupos g ON g.id = e.grupo_id
             WHERE e.id = :id'
        );
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();

        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function update(int $id, string $titulo, ?string $descricao): bool
    {
        $stmt = $this->db->prepare(
            'UPDATE eventos SET titulo = :titulo, descricao = :descricao WHERE id = :id'
        );
        $stmt->bindValue(':titulo', $titulo);
        $stmt->bindValue(':descricao', $descricao);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);

        return $stmt->execute();
    }

    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare('DELETE FROM eventos WHERE id = :id');
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        return $stmt->execute();
    }

    public function listByGroup(int $grupoId): array
    {
        $stmt = $this->db->prepare(
            'SELECT e.*, g.nome AS grupo_nome
             FROM eventos e
             JOIN grupos g ON g.id = e.grupo_id
             WHERE e.grupo_id = :gid
             ORDER BY e.created_at DESC'
        );
        $stmt->bindValue(':gid', $grupoId, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    public function listAll(): array
    {
        $stmt = $this->db->query(
            'SELECT e.*, g.nome AS grupo_nome
             FROM eventos e
             JOIN grupos g ON g.id = e.grupo_id
             ORDER BY e.created_at DESC'
        );
        return $stmt->fetchAll();
    }

    /** Eventos com estatísticas para painel de gestão (proj/adm). */
    public function listForManage(?int $grupoId): array
    {
        $sql = 'SELECT e.*, g.nome AS grupo_nome,
                       (SELECT COUNT(*) FROM atividades a WHERE a.evento_id = e.id) AS total_atividades,
                       (SELECT MIN(a.data) FROM atividades a WHERE a.evento_id = e.id AND a.data >= date("now")) AS proxima_data,
                       (SELECT COUNT(*) FROM inscricoes i
                        JOIN atividades a ON a.id = i.atividade_id
                        WHERE a.evento_id = e.id AND i.status IN ("rsvp", "presente")) AS total_inscricoes
                FROM eventos e
                JOIN grupos g ON g.id = e.grupo_id';

        if ($grupoId !== null) {
            $sql .= ' WHERE e.grupo_id = :gid';
        }

        $sql .= ' ORDER BY e.created_at DESC';

        if ($grupoId !== null) {
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':gid', $grupoId, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll();
        }

        return $this->db->query($sql)->fetchAll();
    }

    /** @return int[] IDs de atividades do evento (ordenadas por data). */
    public function listActivityIds(int $eventoId): array
    {
        $stmt = $this->db->prepare(
            'SELECT id FROM atividades WHERE evento_id = :eid ORDER BY data, hora_inicio'
        );
        $stmt->bindValue(':eid', $eventoId, PDO::PARAM_INT);
        $stmt->execute();

        return array_map('intval', array_column($stmt->fetchAll(), 'id'));
    }
}
