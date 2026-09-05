<?php

declare(strict_types=1);

namespace App\Middleware;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\Cache\CacheItem;

class RateLimitMiddleware implements MiddlewareInterface
{
    private FilesystemAdapter $cache;
    private int $window = 60;
    private int $maxRequests = 120;

    public function __construct(?string $cacheDir = null)
    {
        $this->cache = new FilesystemAdapter('rate_limit', $this->window * 2, $cacheDir ?? sys_get_temp_dir());
    }

    public function process(Request $request, RequestHandlerInterface $handler): Response
    {
        $ip = $request->getServerParams()['REMOTE_ADDR'] ?? 'unknown';
        $path = (string) $request->getUri()->getPath();

        $limit = ($request->getMethod() === 'POST' && str_contains($path, '/import')) ? 10 : $this->maxRequests;

        $key = md5($ip . ':' . $path);
        /** @var CacheItem $item */
        $item = $this->cache->getItem($key);

        if (!$item->isHit()) {
            $item->set(1);
            $item->expiresAfter($this->window);
            $this->cache->save($item);

            return $handler->handle($request);
        }

        $count = $item->get();

        if ($count >= $limit) {
            $response = new \Slim\Psr7\Response();
            $response->getBody()->write(json_encode(['error' => 'Rate limit exceeded']));

            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withHeader('Retry-After', (string) $this->window)
                ->withStatus(429);
        }

        $item->set($count + 1);
        $this->cache->save($item);

        return $handler->handle($request);
    }
}
