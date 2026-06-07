<?php
declare(strict_types=1);

require dirname(__DIR__) . '/vendor/autoload.php';
require dirname(__DIR__) . '/app/autoload.php';

use App\Core\EnvLoader;

$root = dirname(__DIR__);

EnvLoader::load($root . '/.env.example');

$_ENV['APP_ENV'] = 'testing';
$_ENV['APP_TIMEZONE'] = 'America/Sao_Paulo';
$_ENV['DB_DRIVER'] = 'sqlite';
$_ENV['DB_PATH'] = ':memory:';
$_ENV['REMINDER_EMAIL_DAILY_LIMIT'] = '50';
$_ENV['REMINDER_PLANNING_HORIZON_DAYS'] = '7';
$_ENV['REMINDER_MAX_LEAD_DAYS'] = '7';

putenv('APP_ENV=testing');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$_SESSION = [];
