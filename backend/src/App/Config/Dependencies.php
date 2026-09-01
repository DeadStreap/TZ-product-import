<?php

declare(strict_types=1);

namespace App\Config;

use Doctrine\DBAL\DriverManager;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\ORMSetup;
use App\Repositories\ProductRepository;
use App\Repositories\ProductAttributeRepository;
use App\Repositories\ProductImageRepository;
use App\Services\ImportService;
use App\Services\ImageDownloadService;
use App\Services\AuthService;
use App\Messages\ImportProductsHandler;
use App\Middleware\AuthMiddleware;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Handler\HandlersLocator;
use Symfony\Component\Messenger\Middleware\HandleMessageMiddleware;

class Dependencies
{
    public static function register(): array
    {
        return [
            'settings' => [
                'displayErrorDetails' => ($_ENV['APP_DEBUG'] ?? 'false') === 'true',
                'addContentLengthHeader' => false,
            ],

            \Doctrine\ORM\Configuration::class => \DI\factory(function () {
                return ORMSetup::createAttributeMetadataConfiguration(
                    paths: [dirname(__DIR__, 2) . '/src/App/Entities'],
                    isDevMode: ($_ENV['APP_DEBUG'] ?? 'false') === 'true',
                );
            }),

            \Doctrine\DBAL\Connection::class => \DI\factory(function () {
                $driver = $_ENV['DB_DRIVER'] ?? 'pdo_sqlite';

                if ($driver === 'pdo_sqlite') {
                    $dbPath = $_ENV['DB_PATH'] ?? dirname(__DIR__, 3) . '/data/product_import.db';
                    $dir = dirname($dbPath);
                    if (!is_dir($dir)) {
                        mkdir($dir, 0755, true);
                    }

                    return DriverManager::getConnection([
                        'driver' => 'pdo_sqlite',
                        'path' => $dbPath,
                    ]);
                }

                return DriverManager::getConnection([
                    'dbname' => $_ENV['DB_NAME'] ?? 'product_import',
                    'user' => $_ENV['DB_USER'] ?? 'root',
                    'password' => $_ENV['DB_PASSWORD'] ?? 'secret',
                    'host' => $_ENV['DB_HOST'] ?? '127.0.0.1',
                    'port' => $_ENV['DB_PORT'] ?? '3306',
                    'driver' => 'pdo_mysql',
                    'charset' => 'utf8mb4',
                    'defaultTableOptions' => [
                        'charset' => 'utf8mb4',
                        'collate' => 'utf8mb4_unicode_ci',
                    ],
                ]);
            }),

            EntityManager::class => \DI\factory(function (\DI\Container $c) {
                return EntityManager::create(
                    $c->get(\Doctrine\DBAL\Connection::class),
                    $c->get(\Doctrine\ORM\Configuration::class)
                );
            }),

            ProductRepository::class => \DI\factory(function (\DI\Container $c) {
                return new ProductRepository($c->get(EntityManager::class));
            }),

            ProductAttributeRepository::class => \DI\factory(function (\DI\Container $c) {
                return new ProductAttributeRepository($c->get(EntityManager::class));
            }),

            ProductImageRepository::class => \DI\factory(function (\DI\Container $c) {
                return new ProductImageRepository($c->get(EntityManager::class));
            }),

            AuthService::class => \DI\factory(function () {
                return new AuthService(
                    $_ENV['JWT_SECRET'] ?? 'change-this',
                    (int) ($_ENV['JWT_EXPIRY'] ?? '3600')
                );
            }),

            ImageDownloadService::class => \DI\factory(function () {
                return new ImageDownloadService($_ENV['UPLOAD_DIR'] ?? '/var/www/uploads');
            }),

            ImportService::class => \DI\factory(function (\DI\Container $c) {
                return new ImportService(
                    $c->get(EntityManager::class),
                    $c->get(ProductRepository::class),
                    $c->get(ProductAttributeRepository::class),
                    $c->get(ProductImageRepository::class),
                    $c->get(ImageDownloadService::class)
                );
            }),

            ImportProductsHandler::class => \DI\factory(function (\DI\Container $c) {
                return new ImportProductsHandler(
                    $c->get(ImportService::class)
                );
            }),

            MessageBusInterface::class => \DI\factory(function (\DI\Container $c) {
                $handler = new HandlersLocator([
                    \App\Messages\ImportProductsMessage::class => [
                        $c->get(ImportProductsHandler::class),
                    ],
                ]);

                return new \Symfony\Component\Messenger\MessageBus([
                    new HandleMessageMiddleware($handler),
                ]);
            }),

            AuthMiddleware::class => \DI\factory(function (\DI\Container $c) {
                return new AuthMiddleware($c->get(AuthService::class));
            }),

            \App\Middleware\RateLimitMiddleware::class => \DI\factory(function () {
                return new \App\Middleware\RateLimitMiddleware();
            }),
        ];
    }
}
