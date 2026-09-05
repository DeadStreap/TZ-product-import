<?php

declare(strict_types=1);

namespace App\Config;

use Slim\App;

class Middleware
{
    public static function register(App $app): void
    {
        $app->addBodyParsingMiddleware();

        $corsOrigin = $_ENV['CORS_ORIGIN'] ?? '*';
        $app->add(function ($request, $handler) use ($corsOrigin) {
            $response = $handler->handle($request);
            return $response
                ->withHeader('Access-Control-Allow-Origin', $corsOrigin)
                ->withHeader('Access-Control-Allow-Headers', 'X-Requested-With, Content-Type, Accept, Origin, Authorization')
                ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, PATCH, OPTIONS');
        });

        // ponytail: rate limit disabled for demo

        $app->addErrorMiddleware(
            ($_ENV['APP_DEBUG'] ?? 'false') === 'true',
            true,
            true
        );
    }
}
