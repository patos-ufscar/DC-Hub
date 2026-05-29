<?php
declare(strict_types=1);

namespace App\Core;

use PDO;
use RuntimeException;

final class DatabaseInit
{
    public static function initializeSqliteIfNeeded(string $path): void
    {
        if (is_file($path) && filesize($path) > 0) {
            return;
        }

        $root = dirname(__DIR__, 2);
        $schemaFile = $root . '/database/schema.sqlite.sql';
        $seedsFile = $root . '/database/seeds.sqlite.sql';

        if (!is_file($schemaFile)) {
            throw new RuntimeException('Arquivo de schema SQLite não encontrado: database/schema.sqlite.sql');
        }

        $dir = dirname($path);
        if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
            throw new RuntimeException('Não foi possível criar o diretório do banco SQLite.');
        }

        $pdo = new PDO('sqlite:' . $path, null, null, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        ]);

        self::runSqlFile($pdo, $schemaFile);

        $env = strtolower($_ENV['APP_ENV'] ?? getenv('APP_ENV') ?: 'production');
        if ($env === 'development' && is_file($seedsFile)) {
            self::runSqlFile($pdo, $seedsFile);
        }
    }

    private static function runSqlFile(PDO $pdo, string $file): void
    {
        $sql = file_get_contents($file);
        if ($sql === false) {
            throw new RuntimeException('Não foi possível ler o arquivo SQL: ' . $file);
        }

        $pdo->exec($sql);
    }
}
