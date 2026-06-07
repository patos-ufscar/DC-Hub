<?php
declare(strict_types=1);

namespace App\Core;

use PDO;

/** Limite diário de e-mails de lembrete (recuperação de senha não entra na cota). */
final class ReminderEmailQuota
{
    public const CATEGORY_REMINDER = 'reminder';
    public const CATEGORY_PASSWORD_RESET = 'password_reset';

    public function __construct(private PDO $db) {}

    public function dailyLimit(): int
    {
        $raw = trim($_ENV['REMINDER_EMAIL_DAILY_LIMIT'] ?? '50');
        $limit = (int) $raw;

        return $limit > 0 ? $limit : 50;
    }

    public function sentToday(string $category = self::CATEGORY_REMINDER): int
    {
        $start = AppTimezone::now()->format('Y-m-d') . ' 00:00:00';

        $stmt = $this->db->prepare(
            'SELECT COUNT(*) FROM email_outbound_log
             WHERE category = :cat AND sent_at >= :start'
        );
        $stmt->bindValue(':cat', $category);
        $stmt->bindValue(':start', $start);
        $stmt->execute();

        return (int) $stmt->fetchColumn();
    }

    public function remaining(): int
    {
        return max(0, $this->dailyLimit() - $this->sentToday());
    }

    public function canSendReminder(): bool
    {
        return $this->remaining() > 0;
    }

    public function record(string $category): void
    {
        $stmt = $this->db->prepare(
            'INSERT INTO email_outbound_log (category, sent_at) VALUES (:cat, :sent)'
        );
        $stmt->bindValue(':cat', $category);
        $stmt->bindValue(':sent', date('Y-m-d H:i:s'));
        $stmt->execute();
    }
}
