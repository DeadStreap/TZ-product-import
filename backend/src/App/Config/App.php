<?php

declare(strict_types=1);

namespace App\Config;

use DI\Container;
use DI\ContainerBuilder;
use Slim\Factory\AppFactory;

class App
{
    public static function create(): \Slim\App
    {
        $containerBuilder = new ContainerBuilder();
        $containerBuilder->addDefinitions(Dependencies::register());
        $container = $containerBuilder->build();

        AppFactory::setContainer($container);
        $app = AppFactory::create();

        Middleware::register($app);
        Routes::register($app);

        return $app;
    }
}
