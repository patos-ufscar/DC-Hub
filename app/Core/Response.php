<?php
declare(strict_types=1);

namespace App\Core;

final class Response
{
    public static function json(array $data, int $status = 200): never
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        if (!isset($data['ok'])) {
            $data['ok'] = $status < 400;
        }
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }

    public static function success(string $message = 'OK', array $data = []): never
    {
        self::json(array_merge(['ok' => true, 'success' => true, 'message' => $message], $data));
    }

    public static function error(string $message, int $status = 400): never
    {
        self::json(['ok' => false, 'success' => false, 'message' => $message, 'error' => $message], $status);
    }
}
