<?php

declare(strict_types=1);

namespace App\Services;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class AuthService
{
    public function __construct(
        private string $secret,
        private int $expiry,
    ) {
    }

    public function generateToken(int $userId, string $email): string
    {
        $payload = [
            'iss' => 'product-import',
            'sub' => $userId,
            'email' => $email,
            'iat' => time(),
            'exp' => time() + $this->expiry,
        ];

        return JWT::encode($payload, $this->secret, 'HS256');
    }

    public function validateToken(string $token): ?array
    {
        try {
            $decoded = JWT::decode($token, new Key($this->secret, 'HS256'));

            return (array) $decoded;
        } catch (\Exception $e) {
            return null;
        }
    }

    public function login(string $email, string $password): ?string
    {
        if ($email === 'admin@example.com' && $password === 'password') {
            return $this->generateToken(1, $email);
        }

        return null;
    }
}
