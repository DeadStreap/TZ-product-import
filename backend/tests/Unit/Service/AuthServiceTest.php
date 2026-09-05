<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Services\AuthService;
use App\Repositories\UserRepository;
use App\Entities\User;
use PHPUnit\Framework\TestCase;

class AuthServiceTest extends TestCase
{
    private AuthService $authService;
    private UserRepository $userRepo;

    protected function setUp(): void
    {
        $this->userRepo = $this->createMock(UserRepository::class);
        $this->authService = new AuthService('test-secret-key', 3600, $this->userRepo);
    }

    public function testGenerateTokenReturnsJwtString(): void
    {
        $token = $this->authService->generateToken(1, 'user@example.com');

        $this->assertIsString($token);
        $this->assertNotEmpty($token);
        $this->assertCount(3, explode('.', $token));
    }

    public function testValidateTokenReturnsPayloadForValidToken(): void
    {
        $token = $this->authService->generateToken(42, 'test@example.com');
        $payload = $this->authService->validateToken($token);

        $this->assertIsArray($payload);
        $this->assertEquals(42, $payload['sub']);
        $this->assertEquals('test@example.com', $payload['email']);
        $this->assertEquals('product-import', $payload['iss']);
        $this->assertArrayHasKey('iat', $payload);
        $this->assertArrayHasKey('exp', $payload);
    }

    public function testValidateTokenReturnsNullForInvalidToken(): void
    {
        $result = $this->authService->validateToken('invalid.token.here');

        $this->assertNull($result);
    }

    public function testValidateTokenReturnsNullForWrongSecret(): void
    {
        $otherService = new AuthService('different-secret', 3600, $this->userRepo);
        $token = $otherService->generateToken(1, 'user@example.com');

        $result = $this->authService->validateToken($token);

        $this->assertNull($result);
    }

    public function testValidateTokenReturnsNullForExpiredToken(): void
    {
        $service = new AuthService('test-secret-key', 0, $this->userRepo);
        $token = $service->generateToken(1, 'user@example.com');

        sleep(1);

        $result = $service->validateToken($token);

        $this->assertNull($result);
    }

    public function testLoginReturnsTokenForValidCredentials(): void
    {
        $user = new User();
        $user->setEmail('admin@example.com');
        $user->setPasswordHash(password_hash('password', PASSWORD_BCRYPT));

        $this->userRepo->expects($this->once())
            ->method('findByEmail')
            ->with('admin@example.com')
            ->willReturn($user);

        $token = $this->authService->login('admin@example.com', 'password');

        $this->assertIsString($token);
        $this->assertNotEmpty($token);

        $payload = $this->authService->validateToken($token);
        $this->assertEquals($user->getId(), $payload['sub']);
        $this->assertEquals('admin@example.com', $payload['email']);
    }

    public function testLoginReturnsNullForWrongPassword(): void
    {
        $user = new User();
        $user->setEmail('admin@example.com');
        $user->setPasswordHash(password_hash('password', PASSWORD_BCRYPT));

        $this->userRepo->method('findByEmail')->willReturn($user);

        $result = $this->authService->login('admin@example.com', 'wrongpassword');

        $this->assertNull($result);
    }

    public function testLoginReturnsNullForWrongEmail(): void
    {
        $this->userRepo->method('findByEmail')->willReturn(null);

        $result = $this->authService->login('wrong@example.com', 'password');

        $this->assertNull($result);
    }

    public function testLoginReturnsNullForEmptyCredentials(): void
    {
        $this->userRepo->method('findByEmail')->willReturn(null);

        $this->assertNull($this->authService->login('', ''));
        $this->assertNull($this->authService->login('admin@example.com', ''));
        $this->assertNull($this->authService->login('', 'password'));
    }
}
