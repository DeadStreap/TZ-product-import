<?php

declare(strict_types=1);

namespace App\Tests\Unit\Middleware;

use App\Middleware\RateLimitMiddleware;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Psr7\Response;
use Slim\Psr7\Uri;

class RateLimitMiddlewareTest extends TestCase
{
    private string $cacheDir;

    protected function setUp(): void
    {
        $this->cacheDir = sys_get_temp_dir() . '/rate_limit_test_' . uniqid();
        mkdir($this->cacheDir, 0755, true);
    }

    protected function tearDown(): void
    {
        $files = glob($this->cacheDir . '/*/*');
        if ($files) {
            array_map('unlink', $files);
        }
        @rmdir($this->cacheDir);
    }

    public function testAllowsRequestUnderLimit(): void
    {
        $middleware = new RateLimitMiddleware($this->cacheDir);
        $request = $this->createRequest('/api/products');
        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->expects($this->once())
            ->method('handle')
            ->willReturn(new Response());

        $response = $middleware->process($request, $handler);

        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testReturns429WhenLimitExceeded(): void
    {
        $middleware = new RateLimitMiddleware($this->cacheDir);
        $request = $this->createRequest('/api/products');
        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->method('handle')->willReturn(new Response());

        for ($i = 0; $i < 60; $i++) {
            $middleware->process($request, $handler);
        }

        $response = $middleware->process($request, $handler);

        $this->assertEquals(429, $response->getStatusCode());
        $body = json_decode((string) $response->getBody(), true);
        $this->assertEquals('Rate limit exceeded', $body['error']);
        $this->assertNotEmpty($response->getHeaderLine('Retry-After'));
    }

    public function testImportPathHasLowerLimit(): void
    {
        $middleware = new RateLimitMiddleware($this->cacheDir);
        $request = $this->createRequest('/api/import');
        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->method('handle')->willReturn(new Response());

        for ($i = 0; $i < 10; $i++) {
            $middleware->process($request, $handler);
        }

        $response = $middleware->process($request, $handler);

        $this->assertEquals(429, $response->getStatusCode());
    }

    public function testDifferentPathsHaveSeparateLimits(): void
    {
        $middleware = new RateLimitMiddleware($this->cacheDir);
        $productsRequest = $this->createRequest('/api/products');
        $importRequest = $this->createRequest('/api/import');
        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->method('handle')->willReturn(new Response());

        for ($i = 0; $i < 10; $i++) {
            $middleware->process($importRequest, $handler);
        }

        $response = $middleware->process($importRequest, $handler);
        $this->assertEquals(429, $response->getStatusCode());

        $response = $middleware->process($productsRequest, $handler);
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testDifferentIpsHaveSeparateLimits(): void
    {
        $middleware = new RateLimitMiddleware($this->cacheDir);
        $request1 = $this->createRequest('/api/products', '192.168.1.1');
        $request2 = $this->createRequest('/api/products', '192.168.1.2');
        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->method('handle')->willReturn(new Response());

        for ($i = 0; $i < 60; $i++) {
            $middleware->process($request1, $handler);
        }

        $response1 = $middleware->process($request1, $handler);
        $this->assertEquals(429, $response1->getStatusCode());

        $response2 = $middleware->process($request2, $handler);
        $this->assertEquals(200, $response2->getStatusCode());
    }

    private function createRequest(string $path, string $ip = '127.0.0.1'): ServerRequestInterface
    {
        $request = $this->createMock(ServerRequestInterface::class);
        $uri = $this->createMock(Uri::class);
        $uri->method('getPath')->willReturn($path);

        $request->method('getUri')->willReturn($uri);
        $request->method('getServerParams')->willReturn(['REMOTE_ADDR' => $ip]);
        $request->method('withAttribute')->willReturnSelf();

        return $request;
    }
}
