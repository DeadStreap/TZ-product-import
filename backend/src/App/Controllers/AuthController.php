<?php

declare(strict_types=1);

namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\Services\AuthService;

class AuthController
{
    public function __construct(private AuthService $authService)
    {
    }

    public function login(Request $request, Response $response): Response
    {
        $data = json_decode((string) $request->getBody(), true);

        if (empty($data['email']) || empty($data['password'])) {
            return $this->jsonError($response, 'Email and password required', 400);
        }

        $token = $this->authService->login($data['email'], $data['password']);

        if ($token === null) {
            return $this->jsonError($response, 'Invalid credentials', 401);
        }

        $response->getBody()->write(json_encode(['token' => $token]));

        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus(200);
    }

    private function jsonError(Response $response, string $message, int $status): Response
    {
        $response->getBody()->write(json_encode(['error' => $message]));

        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus($status);
    }
}
