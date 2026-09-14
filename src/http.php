<?php

declare(strict_types=1);

function config(): array
{
    static $config = null;
    if ($config === null) {
        $config = require __DIR__ . '/config.php';
    }
    return $config;
}

function json_response(mixed $data, int $status = 200): void
{
    http_response_code($status);
    header('Content-Type: application/json');
    echo json_encode($data);
}

function error_response(string $message, int $status): void
{
    json_response(['error' => $message], $status);
}
