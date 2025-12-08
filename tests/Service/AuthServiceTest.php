<?php
namespace Tests\Service;

use PHPUnit\Framework\TestCase;
use App\Service\AuthService;
use App\Repository\UserRepository;
use App\Model\User;
use App\Service\PasswordService;

class PasswordServiceStub extends PasswordService {
    public function hash(string $password): string {
        return '$2y$10$Q7iM8O1X/qU7Jp5g0O1W.O.f4gR7pM9hC5v6D8E9F';
    }
}

class AuthServiceTest extends TestCase {
    
    private AuthService $authService;
    private $userRepositoryMock;
    private $passwordServiceMock; // Mock para a dependência
    
    // Hash real para a senha 'senha_correta'
    private const CORRECT_PASSWORD = 'senha_correta';
    private const CORRECT_HASH = '$2y$10$bJk9IqU1YlM3xZ0v2Pq7eO/wE9nS2rM5jG7hT8kC4aR6dY0u'; 

    protected function setUp(): void {
        $this->userRepositoryMock = $this->createMock(UserRepository::class);
        $this->passwordServiceMock = $this->createMock(PasswordService::class);
        
        $this->authService = new AuthService($this->userRepositoryMock);
        
        $reflection = new \ReflectionProperty(AuthService::class, 'passwordService');
        $reflection->setAccessible(true);
        $reflection->setValue($this->authService, $this->passwordServiceMock);
    }
    
    private function getMockUser(): User {
        return new User(1, 'Admin', 'login@test.com', self::CORRECT_HASH);
    }
    
    /**
     * Testa a falha quando a senha está incorreta.
     */
    public function testAuthenticateFailureReturnsNullOnWrongPassword(): void {
        $mockUser = $this->getMockUser();
        
        $this->userRepositoryMock->expects($this->once())
            ->method('findByLogin')
            ->willReturn($mockUser);
        
        $user = $this->authService->authenticate('login@test.com', 'senha_errada');

        $this->assertNull($user);
    }

    /**
     * Testa a falha quando o usuário não é encontrado.
     */
    public function testAuthenticateFailureReturnsNullWhenUserNotFound(): void {
        
        $this->userRepositoryMock->expects($this->once())
            ->method('findByLogin')
            ->willReturn(null);
        
        $user = $this->authService->authenticate('naoexiste@test.com', 'qualquercoisa');

        $this->assertNull($user);
    }
    
    /**
     * Testa a propagação de exceção se o findByLogin falhar.
     */
    public function testAuthenticateThrowsExceptionIfRepositoryFails(): void {
        $exceptionMessage = "Database connection lost.";
        
        $this->userRepositoryMock->expects($this->once())
            ->method('findByLogin')
            ->willThrowException(new \Exception($exceptionMessage));
        
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage($exceptionMessage);

        $this->authService->authenticate('login@test.com', 'senha');
    }
}
