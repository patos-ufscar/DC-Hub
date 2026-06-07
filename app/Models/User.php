<?php
declare(strict_types=1);

namespace App\Models;

use PDO;

class User
{
    public function __construct(private PDO $db) {}

    public static function generatePresencaUuid(): string
    {
        $data = random_bytes(16);
        $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80);

        return vsprintf(
            '%s%s-%s-%s-%s-%s%s%s',
            str_split(bin2hex($data), 4)
        );
    }

    public function create(string $email, string $senha, string $nomeExibicao): int
    {
        $hash = password_hash($senha, PASSWORD_BCRYPT);
        $uuid = self::generatePresencaUuid();

        $stmt = $this->db->prepare(
            'INSERT INTO usuarios (email, senha, nome_exibicao, presenca_uuid)
             VALUES (:email, :senha, :nome, :uuid)'
        );
        $stmt->bindValue(':email', $email);
        $stmt->bindValue(':senha', $hash);
        $stmt->bindValue(':nome', $nomeExibicao);
        $stmt->bindValue(':uuid', $uuid);
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

    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare('DELETE FROM usuarios WHERE id = :id');
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);

        return $stmt->execute();
    }

    public function findByPresencaUuid(string $uuid): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT u.*, g.nome AS grupo_nome
             FROM usuarios u
             LEFT JOIN grupos g ON g.id = u.grupo_id
             WHERE u.presenca_uuid = :uuid'
        );
        $stmt->bindValue(':uuid', $uuid);
        $stmt->execute();

        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function updatePassword(int $id, string $plainPassword): void
    {
        $hash = password_hash($plainPassword, PASSWORD_BCRYPT);
        $stmt = $this->db->prepare('UPDATE usuarios SET senha = :senha WHERE id = :id');
        $stmt->bindValue(':senha', $hash);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
    }

    public function ensurePresencaUuid(int $id): string
    {
        $user = $this->findById($id);
        if (!$user) {
            throw new \RuntimeException('Usuário não encontrado.');
        }

        if (!empty($user['presenca_uuid'])) {
            return $user['presenca_uuid'];
        }

        $uuid = self::generatePresencaUuid();
        $stmt = $this->db->prepare('UPDATE usuarios SET presenca_uuid = :uuid WHERE id = :id');
        $stmt->bindValue(':uuid', $uuid);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();

        return $uuid;
    }
}
