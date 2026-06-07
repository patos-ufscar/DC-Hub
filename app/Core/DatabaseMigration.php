<?php
declare(strict_types=1);

namespace App\Core;

use PDO;

final class DatabaseMigration
{
    public static function run(PDO $db): void
    {
        if (DatabaseDialect::isSqlite()) {
            self::migrateSqlite($db);
        } else {
            self::migrateMysql($db);
        }
    }

    private static function migrateSqlite(PDO $db): void
    {
        if (self::tableExists($db, 'solicitacoes_role')) {
            self::addColumnIfMissing($db, 'solicitacoes_role', 'grupo_nome_proposto', 'TEXT DEFAULT NULL');
            self::addColumnIfMissing($db, 'solicitacoes_role', 'mensagem', 'TEXT DEFAULT NULL');
            self::ensureNullableGrupoIdSqlite($db);
        }

        self::migratePresence($db);
        self::migrateActivityOptions($db);
        self::migrateActivityStandalone($db);
        self::migrateRedemptionCodeExpiry($db);
        self::migratePasswordResetAndReminders($db);
        self::migrateEmailOutboundLog($db);
    }

    private static function migrateMysql(PDO $db): void
    {
        if (self::tableExists($db, 'solicitacoes_role')) {
            self::addColumnIfMissing($db, 'solicitacoes_role', 'grupo_nome_proposto', 'VARCHAR(100) DEFAULT NULL');
            self::addColumnIfMissing($db, 'solicitacoes_role', 'mensagem', 'TEXT DEFAULT NULL');

            if (self::columnIsNotNull($db, 'solicitacoes_role', 'grupo_id')) {
                $db->exec('ALTER TABLE solicitacoes_role MODIFY grupo_id INT UNSIGNED DEFAULT NULL');
            }
        }

        self::migratePresence($db);
        self::migrateActivityOptions($db);
        self::migrateActivityStandalone($db);
        self::migrateRedemptionCodeExpiry($db);
        self::migratePasswordResetAndReminders($db);
        self::migrateEmailOutboundLog($db);
    }

    private static function migrateEmailOutboundLog(PDO $db): void
    {
        if (DatabaseDialect::isSqlite()) {
            $db->exec(
                'CREATE TABLE IF NOT EXISTS email_outbound_log (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    category TEXT NOT NULL CHECK(category IN (\'reminder\', \'password_reset\')),
                    sent_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
                )'
            );
            $db->exec('CREATE INDEX IF NOT EXISTS idx_email_outbound_sent ON email_outbound_log(sent_at)');
            $db->exec('CREATE INDEX IF NOT EXISTS idx_email_outbound_cat ON email_outbound_log(category, sent_at)');
            return;
        }

        if (!self::tableExists($db, 'email_outbound_log')) {
            $db->exec(
                'CREATE TABLE email_outbound_log (
                    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                    category ENUM(\'reminder\', \'password_reset\') NOT NULL,
                    sent_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    INDEX idx_email_outbound_sent (sent_at),
                    INDEX idx_email_outbound_cat (category, sent_at)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
            );
        }
    }

    private static function migratePasswordResetAndReminders(PDO $db): void
    {
        if (DatabaseDialect::isSqlite()) {
            $db->exec(
                'CREATE TABLE IF NOT EXISTS password_reset_tokens (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    user_id INTEGER NOT NULL,
                    token_hash TEXT NOT NULL UNIQUE,
                    expires_at TEXT NOT NULL,
                    used_at TEXT DEFAULT NULL,
                    created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (user_id) REFERENCES usuarios(id) ON DELETE CASCADE ON UPDATE CASCADE
                )'
            );
            $db->exec('CREATE INDEX IF NOT EXISTS idx_password_reset_user ON password_reset_tokens(user_id)');

            $db->exec(
                'CREATE TABLE IF NOT EXISTS lembretes_enviados (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    user_id INTEGER NOT NULL,
                    atividade_id INTEGER NOT NULL,
                    tipo TEXT NOT NULL CHECK(tipo IN (\'same_day\', \'24h\', \'1h\')),
                    enviado_em TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    UNIQUE(user_id, atividade_id, tipo),
                    FOREIGN KEY (user_id) REFERENCES usuarios(id) ON DELETE CASCADE ON UPDATE CASCADE,
                    FOREIGN KEY (atividade_id) REFERENCES atividades(id) ON DELETE CASCADE ON UPDATE CASCADE
                )'
            );
            return;
        }

        if (!self::tableExists($db, 'password_reset_tokens')) {
            $db->exec(
                'CREATE TABLE password_reset_tokens (
                    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                    user_id INT UNSIGNED NOT NULL,
                    token_hash CHAR(64) NOT NULL,
                    expires_at DATETIME NOT NULL,
                    used_at DATETIME DEFAULT NULL,
                    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    UNIQUE KEY uq_password_reset_hash (token_hash),
                    INDEX idx_password_reset_user (user_id),
                    CONSTRAINT fk_password_reset_user
                        FOREIGN KEY (user_id) REFERENCES usuarios(id)
                        ON DELETE CASCADE ON UPDATE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
            );
        }

        if (!self::tableExists($db, 'lembretes_enviados')) {
            $db->exec(
                'CREATE TABLE lembretes_enviados (
                    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                    user_id INT UNSIGNED NOT NULL,
                    atividade_id INT UNSIGNED NOT NULL,
                    tipo ENUM(\'same_day\',\'24h\',\'1h\') NOT NULL,
                    enviado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    UNIQUE KEY uq_lembrete (user_id, atividade_id, tipo),
                    CONSTRAINT fk_lembrete_user
                        FOREIGN KEY (user_id) REFERENCES usuarios(id)
                        ON DELETE CASCADE ON UPDATE CASCADE,
                    CONSTRAINT fk_lembrete_atividade
                        FOREIGN KEY (atividade_id) REFERENCES atividades(id)
                        ON DELETE CASCADE ON UPDATE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
            );
        }
    }

    private static function migrateRedemptionCodeExpiry(PDO $db): void
    {
        if (!self::tableExists($db, 'atividades')) {
            return;
        }

        self::addColumnIfMissing(
            $db,
            'atividades',
            'codigo_resgate_expira_em',
            DatabaseDialect::isSqlite() ? 'TEXT DEFAULT NULL' : 'DATETIME DEFAULT NULL'
        );
    }

    private static function migrateActivityOptions(PDO $db): void
    {
        if (!self::tableExists($db, 'atividades')) {
            return;
        }

        self::addColumnIfMissing($db, 'atividades', 'descricao', 'TEXT DEFAULT NULL');
        self::addColumnIfMissing(
            $db,
            'atividades',
            'oferece_certificado',
            DatabaseDialect::isSqlite() ? 'INTEGER NOT NULL DEFAULT 1' : 'TINYINT(1) NOT NULL DEFAULT 1'
        );
        self::addColumnIfMissing($db, 'atividades', 'vagas_limite', 'INTEGER DEFAULT NULL');
        self::addColumnIfMissing(
            $db,
            'atividades',
            'exibir_vagas_total',
            DatabaseDialect::isSqlite() ? 'INTEGER NOT NULL DEFAULT 0' : 'TINYINT(1) NOT NULL DEFAULT 0'
        );
        self::addColumnIfMissing(
            $db,
            'atividades',
            'exibir_vagas_ocupadas',
            DatabaseDialect::isSqlite() ? 'INTEGER NOT NULL DEFAULT 0' : 'TINYINT(1) NOT NULL DEFAULT 0'
        );

        if (DatabaseDialect::isSqlite()) {
            $db->exec(
                "UPDATE atividades SET oferece_certificado = 1
                 WHERE oferece_certificado IS NULL
                    OR (oferece_certificado = 0 AND descricao_certificado IS NOT NULL AND descricao_certificado != '')"
            );
        }
    }

    private static function migrateActivityStandalone(PDO $db): void
    {
        if (!self::tableExists($db, 'atividades')) {
            return;
        }

        self::addColumnIfMissing($db, 'atividades', 'grupo_id', 'INTEGER DEFAULT NULL');

        $db->exec(
            'UPDATE atividades SET grupo_id = (
                SELECT grupo_id FROM eventos WHERE eventos.id = atividades.evento_id
             ) WHERE grupo_id IS NULL AND evento_id IS NOT NULL'
        );

        if (DatabaseDialect::isSqlite() && self::columnIsNotNull($db, 'atividades', 'evento_id')) {
            self::rebuildAtividadesNullableEventSqlite($db);
        } elseif (!DatabaseDialect::isSqlite()) {
            if (self::columnIsNotNull($db, 'atividades', 'evento_id')) {
                $db->exec('ALTER TABLE atividades MODIFY evento_id INT UNSIGNED DEFAULT NULL');
            }
        }
    }

    private static function rebuildAtividadesNullableEventSqlite(PDO $db): void
    {
        $db->exec('PRAGMA foreign_keys = OFF');

        $db->exec(
            'CREATE TABLE IF NOT EXISTS atividades_new (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                evento_id INTEGER DEFAULT NULL,
                grupo_id INTEGER NOT NULL,
                titulo TEXT NOT NULL,
                descricao TEXT DEFAULT NULL,
                data TEXT NOT NULL,
                hora_inicio TEXT NOT NULL,
                hora_fim TEXT NOT NULL,
                local_id INTEGER NOT NULL,
                oferece_certificado INTEGER NOT NULL DEFAULT 1,
                descricao_certificado TEXT DEFAULT NULL,
                vagas_limite INTEGER DEFAULT NULL,
                codigo_resgate TEXT DEFAULT NULL,
                created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (evento_id) REFERENCES eventos(id) ON DELETE SET NULL ON UPDATE CASCADE,
                FOREIGN KEY (grupo_id) REFERENCES grupos(id) ON DELETE CASCADE ON UPDATE CASCADE,
                FOREIGN KEY (local_id) REFERENCES locais(id) ON DELETE RESTRICT ON UPDATE CASCADE
            )'
        );

        $db->exec(
            'INSERT INTO atividades_new (
                id, evento_id, grupo_id, titulo, descricao, data, hora_inicio, hora_fim, local_id,
                oferece_certificado, descricao_certificado, vagas_limite, codigo_resgate, created_at
             )
             SELECT
                id, evento_id, COALESCE(grupo_id, (SELECT grupo_id FROM eventos e WHERE e.id = atividades.evento_id)),
                titulo, descricao, data, hora_inicio, hora_fim, local_id,
                COALESCE(oferece_certificado, 1), descricao_certificado, vagas_limite, codigo_resgate, created_at
             FROM atividades'
        );

        $db->exec('DROP TABLE atividades');
        $db->exec('ALTER TABLE atividades_new RENAME TO atividades');
        $db->exec('CREATE INDEX IF NOT EXISTS idx_atividades_data ON atividades(data)');
        $db->exec('PRAGMA foreign_keys = ON');
    }

    private static function migratePresence(PDO $db): void
    {
        if (!self::tableExists($db, 'usuarios')) {
            return;
        }

        self::addColumnIfMissing($db, 'usuarios', 'presenca_uuid', 'TEXT DEFAULT NULL');
        self::addColumnIfMissing($db, 'inscricoes', 'validado_em', 'TEXT DEFAULT NULL');
        self::addColumnIfMissing($db, 'inscricoes', 'validado_por', 'INTEGER DEFAULT NULL');
        self::addColumnIfMissing($db, 'inscricoes', 'metodo_validacao', 'TEXT DEFAULT NULL');

        if (DatabaseDialect::isSqlite()) {
            $db->exec('CREATE UNIQUE INDEX IF NOT EXISTS idx_usuarios_presenca_uuid ON usuarios(presenca_uuid)');
        }

        self::backfillPresencaUuids($db);
    }

    private static function backfillPresencaUuids(PDO $db): void
    {
        $stmt = $db->query(
            "SELECT id FROM usuarios WHERE presenca_uuid IS NULL OR presenca_uuid = ''"
        );
        $ids = $stmt->fetchAll(PDO::FETCH_COLUMN);
        if ($ids === []) {
            return;
        }

        $update = $db->prepare('UPDATE usuarios SET presenca_uuid = :uuid WHERE id = :id');
        foreach ($ids as $id) {
            $update->execute([
                'uuid' => self::generatePresencaUuid(),
                'id'   => (int) $id,
            ]);
        }
    }

    private static function generatePresencaUuid(): string
    {
        $data = random_bytes(16);
        $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80);

        return vsprintf(
            '%s%s-%s-%s-%s-%s%s%s',
            str_split(bin2hex($data), 4)
        );
    }

    private static function ensureNullableGrupoIdSqlite(PDO $db): void
    {
        if (!self::columnIsNotNull($db, 'solicitacoes_role', 'grupo_id')) {
            return;
        }

        $db->exec('PRAGMA foreign_keys = OFF');

        $db->exec(
            'CREATE TABLE IF NOT EXISTS solicitacoes_role_new (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id INTEGER NOT NULL,
                grupo_id INTEGER DEFAULT NULL,
                grupo_nome_proposto TEXT DEFAULT NULL,
                mensagem TEXT DEFAULT NULL,
                status TEXT NOT NULL DEFAULT \'pendente\' CHECK(status IN (\'pendente\', \'aprovado\', \'rejeitado\')),
                created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES usuarios(id) ON DELETE CASCADE ON UPDATE CASCADE,
                FOREIGN KEY (grupo_id) REFERENCES grupos(id) ON DELETE SET NULL ON UPDATE CASCADE
            )'
        );

        $db->exec(
            'INSERT INTO solicitacoes_role_new (id, user_id, grupo_id, grupo_nome_proposto, mensagem, status, created_at)
             SELECT id, user_id, grupo_id, grupo_nome_proposto, mensagem, status, created_at
             FROM solicitacoes_role'
        );

        $db->exec('DROP TABLE solicitacoes_role');
        $db->exec('ALTER TABLE solicitacoes_role_new RENAME TO solicitacoes_role');
        $db->exec('PRAGMA foreign_keys = ON');
    }

    private static function tableExists(PDO $db, string $table): bool
    {
        if (DatabaseDialect::isSqlite()) {
            $stmt = $db->prepare("SELECT name FROM sqlite_master WHERE type = 'table' AND name = :table");
            $stmt->execute(['table' => $table]);

            return (bool) $stmt->fetchColumn();
        }

        $stmt = $db->prepare(
            'SELECT COUNT(*) FROM information_schema.tables
             WHERE table_schema = DATABASE() AND table_name = :table'
        );
        $stmt->execute(['table' => $table]);

        return (int) $stmt->fetchColumn() > 0;
    }

    private static function addColumnIfMissing(PDO $db, string $table, string $column, string $definition): void
    {
        if (self::columnExists($db, $table, $column)) {
            return;
        }

        $db->exec("ALTER TABLE {$table} ADD COLUMN {$column} {$definition}");
    }

    private static function columnExists(PDO $db, string $table, string $column): bool
    {
        if (DatabaseDialect::isSqlite()) {
            $stmt = $db->query("PRAGMA table_info({$table})");
            $columns = $stmt->fetchAll();

            foreach ($columns as $info) {
                if (($info['name'] ?? '') === $column) {
                    return true;
                }
            }

            return false;
        }

        $stmt = $db->prepare(
            'SELECT COUNT(*) FROM information_schema.columns
             WHERE table_schema = DATABASE() AND table_name = :table AND column_name = :column'
        );
        $stmt->execute(['table' => $table, 'column' => $column]);

        return (int) $stmt->fetchColumn() > 0;
    }

    private static function columnIsNotNull(PDO $db, string $table, string $column): bool
    {
        if (DatabaseDialect::isSqlite()) {
            $stmt = $db->query("PRAGMA table_info({$table})");
            $columns = $stmt->fetchAll();

            foreach ($columns as $info) {
                if (($info['name'] ?? '') === $column) {
                    return (int) ($info['notnull'] ?? 0) === 1;
                }
            }

            return false;
        }

        $stmt = $db->prepare(
            'SELECT IS_NULLABLE FROM information_schema.columns
             WHERE table_schema = DATABASE() AND table_name = :table AND column_name = :column'
        );
        $stmt->execute(['table' => $table, 'column' => $column]);
        $nullable = $stmt->fetchColumn();

        return $nullable === 'NO';
    }
}
