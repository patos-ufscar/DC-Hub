<?php
declare(strict_types=1);

namespace App\Models;

use PDO;

class Group
{
    public function __construct(private PDO $db) {}

    public function listActive(): array
    {
        $stmt = $this->db->query(
            "SELECT * FROM grupos WHERE status = 'ativo' ORDER BY nome"
        );
        return $stmt->fetchAll();
    }

    public function listAll(): array
    {
        $stmt = $this->db->query('SELECT * FROM grupos ORDER BY nome');
        return $stmt->fetchAll();
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM grupos WHERE id = :id');
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();

        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function create(string $nome, ?string $descricao = null): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO grupos (nome, descricao) VALUES (:nome, :descricao)'
        );
        $stmt->bindValue(':nome', $nome);
        $stmt->bindValue(':descricao', $descricao);
        $stmt->execute();

        return (int) $this->db->lastInsertId();
    }

    public function update(int $id, string $nome, ?string $descricao, string $status): bool
    {
        $stmt = $this->db->prepare(
            'UPDATE grupos SET nome = :nome, descricao = :descricao, status = :status WHERE id = :id'
        );
        $stmt->bindValue(':nome', $nome);
        $stmt->bindValue(':descricao', $descricao);
        $stmt->bindValue(':status', $status);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);

        return $stmt->execute();
    }

    public function countEvents(int $id): int
    {
        $stmt = $this->db->prepare('SELECT COUNT(*) FROM eventos WHERE grupo_id = :id');
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();

        return (int) $stmt->fetchColumn();
    }

    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare('DELETE FROM grupos WHERE id = :id');
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);

        return $stmt->execute();
    }
}
