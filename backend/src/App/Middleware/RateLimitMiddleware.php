<?php

declare(strict_types=1);

namespace App\Middleware;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class RateLimitMiddleware implements MiddlewareInterface
{
    /** @var array<string, list<int>> */
    private array $cache = [];

    private int $window = 60;
    private int $maxRequests = 60;

    public function process(Request $request, RequestHandlerInterface $handler): Response
    {
        $ip = $request->getServerParams()['REMOTE_ADDR'] ?? 'unknown';
        $path = (string) $request->getUri()->getPath();

        $limit = str_contains($path, '/import') ? 10 : $this->maxRequests;

        $key = $ip . ':' . $path;
        $now = time();

        if (!isset($this->cache[$key])) {
            $this->cache[$key] = [];
        }

        $this->cache[$key] = array_filter(
            $this->cache[$key],
            fn (int $timestamp): bool => $now - $timestamp < $this->window
        );

        if (count($this->cache[$key]) >= $limit) {
            $response = new \Slim\Psr7\Response();
            $response->getBody()->write(json_encode(['error' => 'Rate limit exceeded']));

            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withHeader('Retry-After', (string) $this->window)
                ->withStatus(429);
        }

        $this->cache[$key][] = $now;

        return $handler->handle($request);
    }
}
