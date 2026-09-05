<?php

declare(strict_types=1);

namespace App\Tests\Unit\Middleware;

use App\Middleware\AuthMiddleware;
use App\Repositories\UserRepository;
use App\Services\AuthService;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Psr7\Response;

class AuthMiddlewareTest extends TestCase
{
    private AuthService $authService;
    private AuthMiddleware $middleware;

    protected function setUp(): void
    {
        $userRepo = $this->createMock(UserRepository::class);
        $this->authService = new AuthService('test-secret', 3600, $userRepo);
        $this->middleware = new AuthMiddleware($this->authService);
    }

    public function testReturns401WhenNoAuthorizationHeader(): void
    {
        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('getHeaderLine')->with('Authorization')->willReturn('');

        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->expects($this->never())->method('handle');

        $response = $this->middleware->process($request, $handler);

        $this->assertEquals(401, $response->getStatusCode());
        $body = (string) $response->getBody();
        $data = json_decode($body, true);
        $this->assertEquals('Unauthorized', $data['error']);
    }

    public function testReturns401WhenHeaderNotBearer(): void
    {
        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('getHeaderLine')->with('Authorization')->willReturn('Basic abc123');

        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->expects($this->never())->method('handle');

        $response = $this->middleware->process($request, $handler);

        $this->assertEquals(401, $response->getStatusCode());
    }

    public function testReturns401WhenTokenInvalid(): void
    {
        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('getHeaderLine')->with('Authorization')->willReturn('Bearer invalid-token');

        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->expects($this->never())->method('handle');

        $response = $this->middleware->process($request, $handler);

        $this->assertEquals(401, $response->getStatusCode());
    }

    public function testPassesRequestToHandlerWithValidToken(): void
    {
        $token = $this->authService->generateToken(1, 'user@example.com');

        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('getHeaderLine')->with('Authorization')->willReturn('Bearer ' . $token);
        $request->method('withAttribute')->willReturnSelf();

        $expectedResponse = new Response();
        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->expects($this->once())
            ->method('handle')
            ->willReturn($expectedResponse);

        $response = $this->middleware->process($request, $handler);

        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testSetsUserAttributeOnRequest(): void
    {
        $token = $this->authService->generateToken(1, 'user@example.com');

        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('getHeaderLine')->with('Authorization')->willReturn('Bearer ' . $token);
        $request->expects($this->once())
            ->method('withAttribute')
            ->with('user', $this->callback(function (array $payload) {
                return $payload['sub'] === 1 && $payload['email'] === 'user@example.com';
            }))
            ->willReturnSelf();

        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->method('handle')->willReturn(new Response());

        $this->middleware->process($request, $handler);
    }

    public function testReturnsJsonContentType(): void
    {
        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('getHeaderLine')->with('Authorization')->willReturn('');

        $handler = $this->createMock(RequestHandlerInterface::class);

        $response = $this->middleware->process($request, $handler);

        $this->assertStringContainsString('application/json', $response->getHeaderLine('Content-Type'));
    }
}
