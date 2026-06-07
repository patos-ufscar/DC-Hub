<?php
declare(strict_types=1);

namespace App\Models;

use PDO;

class PasswordReset
{
    private const TTL_MINUTES = 60;

    public function __construct(private PDO $db) {}

    public static function hashToken(string $rawToken): string
    {
        return hash('sha256', $rawToken);
    }

    /** @return array{raw: string, expires_at: string} */
    public function createForUser(int $userId): array
    {
        $this->invalidateForUser($userId);

        $raw = bin2hex(random_bytes(32));
        $hash = self::hashToken($raw);
        $expiresAt = date('Y-m-d H:i:s', time() + self::TTL_MINUTES * 60);

        $stmt = $this->db->prepare(
            'INSERT INTO password_reset_tokens (user_id, token_hash, expires_at)
             VALUES (:uid, :hash, :exp)'
        );
        $stmt->bindValue(':uid', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':hash', $hash);
        $stmt->bindValue(':exp', $expiresAt);
        $stmt->execute();

        return ['raw' => $raw, 'expires_at' => $expiresAt];
    }

    public function findValidByRawToken(string $rawToken): ?array
    {
        $hash = self::hashToken($rawToken);
        $stmt = $this->db->prepare(
            'SELECT * FROM password_reset_tokens
             WHERE token_hash = :hash AND used_at IS NULL AND expires_at > :now
             LIMIT 1'
        );
        $stmt->bindValue(':hash', $hash);
        $stmt->bindValue(':now', date('Y-m-d H:i:s'));
        $stmt->execute();

        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function markUsed(int $id): void
    {
        $usedAt = date('Y-m-d H:i:s');
        $stmt = $this->db->prepare(
            'UPDATE password_reset_tokens SET used_at = :used WHERE id = :id'
        );
        $stmt->bindValue(':used', $usedAt);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
    }

    public function invalidateForUser(int $userId): void
    {
        $stmt = $this->db->prepare(
            'UPDATE password_reset_tokens SET used_at = :used
             WHERE user_id = :uid AND used_at IS NULL'
        );
        $stmt->bindValue(':used', date('Y-m-d H:i:s'));
        $stmt->bindValue(':uid', $userId, PDO::PARAM_INT);
        $stmt->execute();
    }
}
