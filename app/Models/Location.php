<?php
declare(strict_types=1);

namespace App\Models;

use PDO;

class Location
{
    public function __construct(private PDO $db) {}

    public function listActive(): array
    {
        $stmt = $this->db->query(
            "SELECT * FROM locais WHERE status = 'ativo' ORDER BY nome"
        );
        return $stmt->fetchAll();
    }

    public function listAll(): array
    {
        $stmt = $this->db->query('SELECT * FROM locais ORDER BY nome');
        return $stmt->fetchAll();
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM locais WHERE id = :id');
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();

        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function create(string $nome): int
    {
        $stmt = $this->db->prepare('INSERT INTO locais (nome) VALUES (:nome)');
        $stmt->bindValue(':nome', $nome);
        $stmt->execute();

        return (int) $this->db->lastInsertId();
    }

    public function update(int $id, string $nome, string $status): bool
    {
        $stmt = $this->db->prepare(
            'UPDATE locais SET nome = :nome, status = :status WHERE id = :id'
        );
        $stmt->bindValue(':nome', $nome);
        $stmt->bindValue(':status', $status);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);

        return $stmt->execute();
    }
}
