<?php

declare(strict_types=1);

namespace App\Config;

use Slim\App;
use App\Controllers\AuthController;
use App\Controllers\ImportController;
use App\Controllers\ProductController;
use App\Controllers\HealthController;
use App\Middleware\RateLimitMiddleware;

class Routes
{
    public static function register(App $app): void
    {
        $app->get('/api/health', HealthController::class . ':check');

        $app->get('/docs', function ($request, $response) {
            $response->getBody()->write('<!DOCTYPE html><html><head><title>API Docs</title><link rel="stylesheet" href="https://unpkg.com/swagger-ui-dist@5/swagger-ui.css"><style>body{margin:0;padding:0}</style></head><body><div id="swagger-ui"></div><script src="https://unpkg.com/swagger-ui-dist@5/swagger-ui-bundle.js"></script><script src="https://unpkg.com/swagger-ui-dist@5/swagger-ui-standalone-preset.js"></script><script>SwaggerUIBundle({url:"/openapi.yaml",dom_id:"#swagger-ui",presets:[SwaggerUIBundle.presets.apis,SwaggerUIStandalonePreset],layout:"StandaloneLayout"})</script></body></html>');
            return $response->withHeader('Content-Type', 'text/html');
        });

        $app->get('/openapi.yaml', function ($request, $response) {
            $yaml = file_get_contents(__DIR__ . '/../../../docs/openapi.yaml');
            $response->getBody()->write($yaml);
            return $response->withHeader('Content-Type', 'text/yaml');
        });

        $app->post('/api/auth/login', AuthController::class . ':login');

        $app->group('/api', function ($group) {
            $group->post('/import', ImportController::class . ':import')
                ->add(RateLimitMiddleware::class);
            $group->get('/import/{id:[0-9]+}/status', ImportController::class . ':status');

            $group->get('/products', ProductController::class . ':index');
            $group->get('/products/{id:[0-9]+}', ProductController::class . ':show');
        })->add(\App\Middleware\AuthMiddleware::class);
    }
}
