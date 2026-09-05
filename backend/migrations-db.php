<?php

declare(strict_types=1);

$driver = $_ENV['DB_DRIVER'] ?? 'pdo_mysql';

if ($driver === 'pdo_sqlite') {
    return [
        'driver' => 'pdo_sqlite',
        'path' => $_ENV['DB_PATH'] ?? dirname(__DIR__) . '/data/product_import.db',
    ];
}

return [
    'driver' => $driver,
    'dbname' => $_ENV['DB_NAME'] ?? 'product_import',
    'user' => $_ENV['DB_USER'] ?? 'root',
    'password' => $_ENV['DB_PASSWORD'] ?? 'secret',
    'host' => $_ENV['DB_HOST'] ?? '127.0.0.1',
    'port' => $_ENV['DB_PORT'] ?? '3306',
    'charset' => 'utf8mb4',
];
