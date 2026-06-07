<?php
declare(strict_types=1);

namespace App\Core;

use PDO;

final class RateLimiter
{
    public function __construct(private PDO $db) {}

    /**
     * @return true if allowed, false if rate limit exceeded
     */
    public function attempt(string $bucket, int $maxAttempts, int $windowSeconds): bool
    {
        $this->ensureTable();

        $now = time();
        $stmt = $this->db->prepare(
            'SELECT attempts, window_start FROM rate_limits WHERE bucket_key = :bucket'
        );
        $stmt->bindValue(':bucket', $bucket);
        $stmt->execute();
        $row = $stmt->fetch();

        if (!$row) {
            $ins = $this->db->prepare(
                'INSERT INTO rate_limits (bucket_key, attempts, window_start) VALUES (:bucket, 1, :start)'
            );
            $ins->bindValue(':bucket', $bucket);
            $ins->bindValue(':start', $now, PDO::PARAM_INT);
            $ins->execute();

            return true;
        }

        $windowStart = (int) $row['window_start'];
        $attempts = (int) $row['attempts'];

        if ($now - $windowStart >= $windowSeconds) {
            $upd = $this->db->prepare(
                'UPDATE rate_limits SET attempts = 1, window_start = :start WHERE bucket_key = :bucket'
            );
            $upd->bindValue(':start', $now, PDO::PARAM_INT);
            $upd->bindValue(':bucket', $bucket);
            $upd->execute();

            return true;
        }

        if ($attempts >= $maxAttempts) {
            return false;
        }

        $upd = $this->db->prepare(
            'UPDATE rate_limits SET attempts = attempts + 1 WHERE bucket_key = :bucket'
        );
        $upd->bindValue(':bucket', $bucket);
        $upd->execute();

        return true;
    }

    public function clear(string $bucket): void
    {
        $this->ensureTable();
        $stmt = $this->db->prepare('DELETE FROM rate_limits WHERE bucket_key = :bucket');
        $stmt->bindValue(':bucket', $bucket);
        $stmt->execute();
    }

    private function ensureTable(): void
    {
        if (DatabaseDialect::isSqlite()) {
            $this->db->exec(
                'CREATE TABLE IF NOT EXISTS rate_limits (
                    bucket_key TEXT PRIMARY KEY,
                    attempts INTEGER NOT NULL DEFAULT 0,
                    window_start INTEGER NOT NULL
                )'
            );
        } else {
            $this->db->exec(
                'CREATE TABLE IF NOT EXISTS rate_limits (
                    bucket_key VARCHAR(128) PRIMARY KEY,
                    attempts INT UNSIGNED NOT NULL DEFAULT 0,
                    window_start INT UNSIGNED NOT NULL
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4'
            );
        }
    }

    public static function clientIp(): string
    {
        $headers = ['HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR'];
        foreach ($headers as $header) {
            if (empty($_SERVER[$header])) {
                continue;
            }
            $value = (string) $_SERVER[$header];
            if ($header === 'HTTP_X_FORWARDED_FOR') {
                $value = trim(explode(',', $value)[0]);
            }
            if (filter_var($value, FILTER_VALIDATE_IP)) {
                return $value;
            }
        }

        return '0.0.0.0';
    }

    public static function bucket(string $prefix, string ...$parts): string
    {
        return $prefix . ':' . hash('sha256', implode('|', $parts));
    }
}
