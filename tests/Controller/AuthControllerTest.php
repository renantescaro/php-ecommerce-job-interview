<?php
namespace Tests\Controller;

use PHPUnit\Framework\TestCase;
use App\Controller\AuthController;
use App\Service\AuthService;
use App\Service\JwtService;
use App\Model\User;
use Exception;

class AuthControllerTest extends TestCase {
    
    private $controllerMock;
    private $authServiceMock;
    private $jwtServiceMock;

    protected function setUp(): void {
        $this->authServiceMock = $this->createMock(AuthService::class);
        $this->jwtServiceMock = $this->createMock(JwtService::class);
        
        $this->controllerMock = $this->getMockBuilder(AuthController::class)
            ->onlyMethods(['respond', 'getRequestData'])
            ->disableOriginalConstructor()
            ->getMock(); 

        $this->injectDependencies();
    }
    
    private function injectDependencies(): void {
        $reflection = new \ReflectionClass(AuthController::class);

        // Injeta AuthService Mock
        $authServiceProp = $reflection->getProperty('authService');
        $authServiceProp->setAccessible(true);
        $authServiceProp->setValue($this->controllerMock, $this->authServiceMock);

        // Injeta JwtService Mock
        $jwtServiceProp = $reflection->getProperty('jwtService');
        $jwtServiceProp->setAccessible(true);
        $jwtServiceProp->setValue($this->controllerMock, $this->jwtServiceMock);
    }
    
    private function getValidLoginData(): array {
        return [
            'login' => 'teste@login.com',
            'password' => 'senha_secreta'
        ];
    }


    public function testLoginReturns400IfDataIsMissing(): void {
        $inputData = ['login' => 'teste@login.com']; // Faltando 'password'

        $this->controllerMock->expects($this->once())
            ->method('getRequestData')
            ->willReturn($inputData);
        
        $this->controllerMock->expects($this->once())
            ->method('respond')
            ->with(
                $this->equalTo(400),
                $this->callback(fn($data) => $data['error'] === 'Usuário e senha são obrigatórios.')
            );

        $this->authServiceMock->expects($this->never())->method('authenticate');
        
        $this->controllerMock->login();
    }

    public function testLoginReturns401IfCredentialsAreInvalid(): void {
        $inputData = $this->getValidLoginData();

        $this->controllerMock->expects($this->once())
            ->method('getRequestData')
            ->willReturn($inputData);

        $this->authServiceMock->expects($this->once())
            ->method('authenticate')
            ->willReturn(null);

        $this->jwtServiceMock->expects($this->never())->method('encode');
        
        $this->controllerMock->expects($this->once())
            ->method('respond')
            ->with(
                $this->equalTo(401),
                $this->callback(fn($data) => $data['error'] === 'Credenciais inválidas.')
            );
        
        $this->controllerMock->login();
    }
    
    public function testLoginReturns500OnError(): void {
        $inputData = $this->getValidLoginData();
        $exceptionMessage = 'Erro na comunicação com o DB.';

        $this->controllerMock->expects($this->once())
            ->method('getRequestData')
            ->willReturn($inputData);

        $this->authServiceMock->expects($this->once())
            ->method('authenticate')
            ->willThrowException(new Exception($exceptionMessage));

        $this->controllerMock->expects($this->once())
            ->method('respond')
            ->with(
                $this->equalTo(500),
                $this->callback(function ($data) use ($exceptionMessage) {
                    $this->assertStringContainsString('Erro interno ao fazer login.', $data['error']);
                    $this->assertSame($exceptionMessage, $data['details']);
                    return true;
                })
            );
        
        $this->controllerMock->login();
    }
}
