<?php

declare(strict_types=1);

require __DIR__ . '/vendor/autoload.php';

use Dotenv\Dotenv;
use Doctrine\DBAL\DriverManager;

$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->safeLoad();

$dbPath = $_ENV['DB_PATH'] ?? 'data/product_import.db';
$dir = dirname($dbPath);
if (!is_dir($dir)) {
    mkdir($dir, 0755, true);
}

$conn = DriverManager::getConnection([
    'driver' => 'pdo_sqlite',
    'path' => $dbPath,
]);

echo "Creating tables...\n";

$conn->executeStatement('
    CREATE TABLE IF NOT EXISTS products (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        external_code VARCHAR(255) NOT NULL UNIQUE,
        name VARCHAR(500) NOT NULL,
        description TEXT,
        price NUMERIC(10, 2) NOT NULL,
        purchase_price NUMERIC(10, 2),
        discount REAL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
    )
');

$conn->executeStatement('
    CREATE TABLE IF NOT EXISTS product_attributes (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        product_id INTEGER NOT NULL,
        attr_key VARCHAR(255) NOT NULL,
        attr_value TEXT,
        FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
    )
');

$conn->executeStatement('
    CREATE TABLE IF NOT EXISTS product_images (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        product_id INTEGER NOT NULL,
        url VARCHAR(1000) NOT NULL,
        path VARCHAR(500),
        FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
    )
');

$conn->executeStatement('
    CREATE TABLE IF NOT EXISTS import_tasks (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        status VARCHAR(20) NOT NULL DEFAULT \'pending\',
        result TEXT,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
    )
');

echo "Tables created successfully!\n";
echo "Database: " . realpath($dbPath) . "\n";
