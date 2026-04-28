<?php
declare(strict_types=1);

namespace App\Models;

use PDO;

class User
{
    public function __construct(private PDO $db) {}

    public function create(string $email, string $senha, string $nomeExibicao): int
    {
        $hash = password_hash($senha, PASSWORD_BCRYPT);

        $stmt = $this->db->prepare(
            'INSERT INTO usuarios (email, senha, nome_exibicao) VALUES (:email, :senha, :nome)'
        );
        $stmt->bindValue(':email', $email);
        $stmt->bindValue(':senha', $hash);
        $stmt->bindValue(':nome', $nomeExibicao);
        $stmt->execute();

        return (int) $this->db->lastInsertId();
    }

    public function findByEmail(string $email): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT u.*, g.nome AS grupo_nome
             FROM usuarios u
             LEFT JOIN grupos g ON g.id = u.grupo_id
             WHERE u.email = :email'
        );
        $stmt->bindValue(':email', $email);
        $stmt->execute();

        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT u.*, g.nome AS grupo_nome
             FROM usuarios u
             LEFT JOIN grupos g ON g.id = u.grupo_id
             WHERE u.id = :id'
        );
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();

        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function updateProfile(int $id, string $nomeCompleto, ?string $nomeExibicao = null): bool
    {
        if ($nomeExibicao !== null) {
            $stmt = $this->db->prepare(
                'UPDATE usuarios SET nome_completo = :nc, nome_exibicao = :ne WHERE id = :id'
            );
            $stmt->bindValue(':ne', $nomeExibicao);
        } else {
            $stmt = $this->db->prepare(
                'UPDATE usuarios SET nome_completo = :nc WHERE id = :id'
            );
        }
        $stmt->bindValue(':nc', $nomeCompleto);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);

        return $stmt->execute();
    }

    public function updateRole(int $id, string $role, ?int $grupoId = null): bool
    {
        $stmt = $this->db->prepare(
            'UPDATE usuarios SET role = :role, grupo_id = :gid WHERE id = :id'
        );
        $stmt->bindValue(':role', $role);
        $stmt->bindValue(':gid', $grupoId, $grupoId === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);

        return $stmt->execute();
    }

    public function listAll(): array
    {
        $stmt = $this->db->query(
            'SELECT u.id, u.email, u.nome_exibicao, u.role, u.grupo_id, g.nome AS grupo_nome
             FROM usuarios u
             LEFT JOIN grupos g ON g.id = u.grupo_id
             ORDER BY u.nome_exibicao'
        );
        return $stmt->fetchAll();
    }

    public function emailExists(string $email): bool
    {
        $stmt = $this->db->prepare('SELECT 1 FROM usuarios WHERE email = :email');
        $stmt->bindValue(':email', $email);
        $stmt->execute();

        return (bool) $stmt->fetch();
    }
}
