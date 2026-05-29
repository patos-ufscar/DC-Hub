<?php
declare(strict_types=1);

namespace App\Core;

use PDO;
use PDOException;

final class Database
{
    private static ?PDO $connection = null;

    public static function driver(): string
    {
        return DatabaseDialect::driver();
    }

    public static function getConnection(): PDO
    {
        if (self::$connection === null) {
            $driver = self::driver();

            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ];

            try {
                if ($driver === 'sqlite') {
                    $path = self::sqlitePath();
                    DatabaseInit::initializeSqliteIfNeeded($path);

                    self::$connection = new PDO('sqlite:' . $path, null, null, $options);
                    self::$connection->exec('PRAGMA foreign_keys = ON');
                    DatabaseMigration::run(self::$connection);
                } else {
                    $host = $_ENV['DB_HOST'] ?? '127.0.0.1';
                    $port = $_ENV['DB_PORT'] ?? '3306';
                    $name = $_ENV['DB_NAME'] ?? 'dc_hub';
                    $user = $_ENV['DB_USER'] ?? 'root';
                    $pass = $_ENV['DB_PASS'] ?? '';

                    $dsn = "mysql:host={$host};port={$port};dbname={$name};charset=utf8mb4";
                    $options[PDO::MYSQL_ATTR_INIT_COMMAND] = 'SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci';

                    self::$connection = new PDO($dsn, $user, $pass, $options);
                    DatabaseMigration::run(self::$connection);
                }
            } catch (PDOException $e) {
                error_log('Database connection failed: ' . $e->getMessage());
                throw new \RuntimeException('Falha na conexão com o banco de dados.');
            }
        }

        return self::$connection;
    }

    private static function sqlitePath(): string
    {
        $path = $_ENV['DB_PATH'] ?? 'database/dc_hub.sqlite';

        if (!str_starts_with($path, '/')) {
            $path = dirname(__DIR__, 2) . '/' . ltrim($path, '/');
        }

        return $path;
    }
}
