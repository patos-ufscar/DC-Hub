#!/usr/bin/env php
<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/app/autoload.php';

use App\Core\Database;
use App\Core\DatabaseInit;
use App\Core\EnvLoader;
use App\Models\User;

$root = dirname(__DIR__);
$envPath = $root . '/.env';
if (!is_file($envPath)) {
    fwrite(STDERR, "Arquivo .env não encontrado. Copie .env.example para .env antes de inicializar.\n");
    exit(1);
}
EnvLoader::load($envPath);
$_ENV['APP_ENV'] = 'development';

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

$db = Database::getConnection();
$userModel = new User($db);

$adminEmail = 'admin@dchub.local';
if (!$userModel->emailExists($adminEmail)) {
    $plainPassword = bin2hex(random_bytes(8));
    $userModel->create($adminEmail, $plainPassword, 'Admin');
    $admin = $userModel->findByEmail($adminEmail);
    if ($admin) {
        $userModel->updateProfile((int) $admin['id'], 'Administrador do Sistema');
        $userModel->updateRole((int) $admin['id'], 'adm', null);
    }

    echo "Banco SQLite inicializado em: {$path}\n";
    echo "Administrador criado:\n";
    echo "  Email: {$adminEmail}\n";
    echo "  Senha: {$plainPassword}\n";
    echo "(guarde esta senha — não será exibida novamente)\n";
} else {
    echo "Banco SQLite inicializado em: {$path}\n";
    echo "Usuário admin já existe.\n";
}
