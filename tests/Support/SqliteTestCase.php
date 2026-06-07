<?php
declare(strict_types=1);

namespace Tests\Support;

use PDO;
use PHPUnit\Framework\TestCase;

abstract class SqliteTestCase extends TestCase
{
    protected PDO $db;

    protected function setUp(): void
    {
        parent::setUp();

        $this->db = new PDO('sqlite::memory:', null, null, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
        $this->db->exec('PRAGMA foreign_keys = ON');

        $schemaPath = dirname(__DIR__, 2) . '/database/schema.sqlite.sql';
        $sql        = (string) file_get_contents($schemaPath);
        $sql        = preg_replace('/PRAGMA\s+foreign_keys\s*=\s*OFF\s*;/i', '', $sql) ?? $sql;
        $this->db->exec($sql);

        $this->seedMinimal();
    }

    protected function seedMinimal(): void
    {
        $this->db->exec("INSERT INTO grupos (id, nome) VALUES (1, 'PATOS')");
        $this->db->exec("INSERT INTO locais (id, nome) VALUES (1, 'Lab')");
        $this->db->exec(
            "INSERT INTO usuarios (id, email, senha, nome_exibicao, role)
             VALUES (1, 'a@test.dev', 'hash', 'Alice', 'user'),
                    (2, 'b@test.dev', 'hash', 'Bob', 'user')"
        );
    }

    protected function insertActivity(
        int $id,
        string $date,
        string $start = '19:00:00',
        string $title = 'Atividade teste'
    ): void {
        $stmt = $this->db->prepare(
            'INSERT INTO atividades (id, grupo_id, titulo, data, hora_inicio, hora_fim, local_id)
             VALUES (:id, 1, :titulo, :data, :hi, :hf, 1)'
        );
        $stmt->execute([
            ':id'     => $id,
            ':titulo' => $title,
            ':data'   => $date,
            ':hi'     => $start,
            ':hf'     => '21:00:00',
        ]);
    }

    protected function insertRsvp(int $userId, int $activityId, string $createdAt = '2026-01-01 10:00:00'): void
    {
        $stmt = $this->db->prepare(
            "INSERT INTO inscricoes (user_id, atividade_id, status, created_at)
             VALUES (:uid, :aid, 'rsvp', :created)"
        );
        $stmt->execute([
            ':uid'     => $userId,
            ':aid'     => $activityId,
            ':created' => $createdAt,
        ]);
    }
}
