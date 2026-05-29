#!/usr/bin/env php
<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/app/bootstrap.php';

use App\Core\DatabaseInit;
use App\Core\EnvLoader;

$root = dirname(__DIR__);
$envPath = $root . '/.env';
if (!is_file($envPath)) {
    $envPath = $root . '/.env.example';
}
EnvLoader::load($envPath);

$driver = strtolower($_ENV['DB_DRIVER'] ?? 'sqlite');
if ($driver !== 'sqlite') {
    fwrite(STDERR, "Este script inicializa apenas bancos SQLite (DB_DRIVER=sqlite).\n");
    exit(1);
}

$path = $_ENV['DB_PATH'] ?? 'database/dc_hub.sqlite';
if (!str_starts_with($path, '/')) {
    $path = $root . '/' . ltrim($path, '/');
}

if (is_file($path)) {
    unlink($path);
}

DatabaseInit::initializeSqliteIfNeeded($path);

echo "Banco SQLite inicializado em: {$path}\n";
echo "Usuário admin: admin@dchub.local / admin123\n";
