<?php

declare(strict_types=1);

namespace App\Middleware;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use App\Services\AuthService;

class AuthMiddleware implements MiddlewareInterface
{
    public function __construct(private AuthService $authService)
    {
    }

    public function process(Request $request, RequestHandlerInterface $handler): Response
    {
        $authHeader = $request->getHeaderLine('Authorization');

        if ($authHeader === '' || !str_starts_with($authHeader, 'Bearer ')) {
            return $this->unauthorized();
        }

        $token = substr($authHeader, 7);
        $payload = $this->authService->validateToken($token);

        if ($payload === null) {
            return $this->unauthorized();
        }

        $request = $request->withAttribute('user', $payload);

        return $handler->handle($request);
    }

    private function unauthorized(): Response
    {
        $response = new \Slim\Psr7\Response();
        $response->getBody()->write(json_encode(['error' => 'Unauthorized']));

        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus(401);
    }
}
