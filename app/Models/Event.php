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
}
