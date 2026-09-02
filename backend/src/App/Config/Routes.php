<?php

declare(strict_types=1);

namespace App\Config;

use Slim\App;
use App\Controllers\AuthController;
use App\Controllers\ImportController;
use App\Controllers\ProductController;
use App\Controllers\HealthController;

class Routes
{
    public static function register(App $app): void
    {
        $app->get('/api/health', HealthController::class . ':check');

        $app->post('/api/auth/login', AuthController::class . ':login');

        $app->group('/api', function ($group) {
            $group->post('/import', ImportController::class . ':import');
            $group->get('/import/{id:[0-9]+}/status', ImportController::class . ':status');

            $group->get('/products', ProductController::class . ':index');
            $group->get('/products/{id:[0-9]+}', ProductController::class . ':show');
        })->add(\App\Middleware\AuthMiddleware::class);
    }
}
