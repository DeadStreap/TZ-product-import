<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use App\Config\App;

$dotenv = Dotenv\Dotenv::createImmutable(dirname(__DIR__));
$dotenv->safeLoad();

$app = App::create();
$app->run();
