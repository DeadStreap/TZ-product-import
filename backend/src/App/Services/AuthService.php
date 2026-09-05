<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\UserRepository;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class AuthService
{
    public function __construct(
        private string $secret,
        private int $expiry,
        private UserRepository $userRepo,
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
        $user = $this->userRepo->findByEmail($email);

        if ($user === null) {
            return null;
        }

        if (!password_verify($password, $user->getPasswordHash())) {
            return null;
        }

        return $this->generateToken($user->getId(), $user->getEmail());
    }
}
