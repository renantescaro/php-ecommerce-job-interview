<?php
namespace Tests\Controller;

use PHPUnit\Framework\TestCase;
use App\Controller\UserController;
use App\Repository\UserRepository;
use App\Service\PasswordService;
use App\Model\User;
use Exception;

class UserControllerTest extends TestCase {
    
    private $controllerMock;
    private $repositoryMock;
    private $passwordServiceMock;

    protected function setUp(): void {
        $this->repositoryMock = $this->createMock(UserRepository::class);
        $this->passwordServiceMock = $this->createMock(PasswordService::class);
        
        $this->controllerMock = $this->getMockBuilder(UserController::class)
            ->onlyMethods(['respond', 'getRequestData'])
            ->disableOriginalConstructor()
            ->getMock(); 

        $this->injectDependencies();
    }
    
    private function injectDependencies(): void {
        $reflection = new \ReflectionClass(UserController::class);

        $repoProp = $reflection->getProperty('repository');
        $repoProp->setAccessible(true);
        $repoProp->setValue($this->controllerMock, $this->repositoryMock);

        $passProp = $reflection->getProperty('passwordService');
        $passProp->setAccessible(true);
        $passProp->setValue($this->controllerMock, $this->passwordServiceMock);
    }
    
    private function getValidInputData(): array {
        return [
            'user' => [
                'name' => 'Admin Teste',
                'login' => 'admin@test.com',
                'password' => '123456'
            ]
        ];
    }

    public function testIndexSuccessReturns200WithData(): void {
        $mockUsers = [new User(1, 'A', 'a@b.c', 'hash')];
        $this->repositoryMock->expects($this->once())
            ->method('findAll')
            ->willReturn($mockUsers);

        $this->controllerMock->expects($this->once())
            ->method('respond')
            ->with(
                $this->equalTo(200),
                $this->callback(function ($data) {
                    $this->assertArrayHasKey('data', $data);
                    $this->assertCount(1, $data['data']);
                    return true;
                })
            );
        
        $this->controllerMock->index();
    }

    public function testIndexReturns500OnError(): void {
        $this->repositoryMock->expects($this->once())
            ->method('findAll')
            ->willThrowException(new Exception("DB Error"));

        $this->controllerMock->expects($this->once())
            ->method('respond')
            ->with(
                $this->equalTo(500),
                $this->callback(function ($data) {
                    $this->assertStringContainsString('Erro interno ao listar usuários', $data['error']);
                    return true;
                })
            );
        
        $this->controllerMock->index();
    }

    public function testShowSuccessReturns200WithUser(): void {
        $userId = 1;
        $mockUser = new User($userId, 'A', 'a@b.c', 'hash');
        
        $this->repositoryMock->expects($this->once())
            ->method('findById')
            ->with($userId)
            ->willReturn($mockUser);

        $this->controllerMock->expects($this->once())
            ->method('respond')
            ->with($this->equalTo(200));

        $this->controllerMock->show($userId);
    }
    
    public function testShowReturns404IfUserNotFound(): void {
        $userId = 999;
        
        $this->repositoryMock->expects($this->once())
            ->method('findById')
            ->with($userId)
            ->willReturn(null);

        $this->controllerMock->expects($this->once())
            ->method('respond')
            ->with(
                $this->equalTo(404),
                $this->callback(fn($data) => $data['error'] === 'usuários não encontrado.')
            );

        $this->controllerMock->show($userId);
    }

    public function testShowReturns500OnError(): void {
        $userId = 1;
        
        $this->repositoryMock->expects($this->once())
            ->method('findById')
            ->with($userId)
            ->willThrowException(new Exception("DB Timeout."));

        $this->controllerMock->expects($this->once())
            ->method('respond')
            ->with(
                $this->equalTo(500),
                $this->callback(function ($data) {
                    $this->assertStringContainsString('Erro interno ao buscar usuário', $data['error']);
                    return true;
                })
            );
        
        $this->controllerMock->show($userId);
    }

    public function testStoreSuccessReturns201Created(): void {
        $inputData = $this->getValidInputData();
        $newId = 5;
        $hashedPass = 'hashed_pass_value';

        $this->controllerMock->expects($this->once())
             ->method('getRequestData')
             ->willReturn($inputData);
        
        $this->passwordServiceMock->expects($this->once())
             ->method('hash')
             ->with('123456')
             ->willReturn($hashedPass);

        $this->repositoryMock->expects($this->once())
            ->method('save')
            ->with($this->callback(fn($user) => $user->password === $hashedPass))
            ->willReturn($newId);

        $this->controllerMock->expects($this->once())
            ->method('respond')
            ->with(
                $this->equalTo(201),
                $this->callback(fn($data) => $data['id'] === $newId)
            );
        
        $this->controllerMock->store();
    }

    public function testStoreReturns400IfDataIsMissing(): void {
        $inputData = [
            'user' => ['name' => 'A', 'login' => 'a@b.c'] // Faltando 'password'
        ]; 
        $this->controllerMock->expects($this->once())
            ->method('getRequestData')
            ->willReturn($inputData);
        
        $this->repositoryMock->expects($this->never())
            ->method('save');

        $this->controllerMock->expects($this->once())
            ->method('respond')
            ->with(
                $this->equalTo(400),
                $this->callback(fn($data) => $data['error'] === 'Dados de usuário incompleto.')
            );
        
        $this->controllerMock->store();
    }

    public function testStoreReturns500OnError(): void {
        $inputData = $this->getValidInputData();

        $this->controllerMock->expects($this->once())
            ->method('getRequestData')
            ->willReturn($inputData);
        
        $this->passwordServiceMock->expects($this->once())->method('hash')->willReturn('hash');
        
        $this->repositoryMock->expects($this->once())
            ->method('save')
            ->willThrowException(new Exception("Erro de UNIQUE key."));

        $this->controllerMock->expects($this->once())
            ->method('respond')
            ->with(
                $this->equalTo(500),
                $this->callback(function ($data) {
                    $this->assertStringContainsString('Falha ao incluir usuário', $data['error']);
                    return true;
                })
            );
        
        $this->controllerMock->store();
    }
    
    public function testUpdateSuccessReturns200(): void {
        $userId = 10;
        $inputData = $this->getValidInputData();
        
        $this->controllerMock->expects($this->once())
             ->method('getRequestData')
             ->willReturn($inputData);
             
        $this->repositoryMock->expects($this->once())
             ->method('findById')
             ->with($userId)
             ->willReturn(new User($userId, 'A', 'a@b.c', 'hash'));
        
        $this->passwordServiceMock->expects($this->once())->method('hash')->willReturn('new_hash');

        $this->repositoryMock->expects($this->once())
             ->method('update')
             ->with($this->callback(fn($user) => $user->id === $userId));

        $this->controllerMock->expects($this->once())
             ->method('respond')
             ->with($this->equalTo(200));
        
        $this->controllerMock->update($userId);
    }
    
    public function testUpdateReturns404IfUserNotFound(): void {
        $userId = 999;
        $inputData = $this->getValidInputData();

        $this->repositoryMock->expects($this->once())
             ->method('findById')
             ->with($userId)
             ->willReturn(null);
             
        $this->controllerMock->expects($this->once())
             ->method('getRequestData')
             ->willReturn($inputData);
        
        $this->repositoryMock->expects($this->never())->method('update');

        $this->controllerMock->expects($this->once())
             ->method('respond')
             ->with(
                 $this->equalTo(404),
                 $this->callback(fn($data) => $data['error'] === 'Usuário a ser atualizado não encontrado.')
             );
        
        $this->controllerMock->update($userId);
    }
    
    public function testUpdateReturns400IfDataIsMissing(): void {
        $userId = 1;
        
        $inputData = [
            'user' => ['name' => ''] 
        ]; 
        $this->controllerMock->expects($this->once())
             ->method('getRequestData')
             ->willReturn($inputData);
             
        $this->repositoryMock->expects($this->once())
             ->method('findById')
             ->with($userId)
             ->willReturn(new User($userId, 'A', 'a@b.c', 'hash'));
        
        $this->repositoryMock->expects($this->never())->method('update');

        $this->controllerMock->expects($this->once())
             ->method('respond')
             ->with(
                 $this->equalTo(400),
                 $this->callback(fn($data) => $data['error'] === 'Dados de usuário incompleto.')
             );
        
        $this->controllerMock->update($userId);
    }

    public function testUpdateReturns500OnError(): void {
        $userId = 10;
        $inputData = $this->getValidInputData();
        
        $this->controllerMock->expects($this->once())
             ->method('getRequestData')
             ->willReturn($inputData);
             
        $this->repositoryMock->expects($this->once())
             ->method('findById')
             ->willReturn(new User($userId, 'A', 'a@b.c', 'hash'));
        
        $this->passwordServiceMock->expects($this->once())->method('hash')->willReturn('hash');
        
        $this->repositoryMock->expects($this->once())
             ->method('update')
             ->willThrowException(new Exception("DB Lock."));

        $this->controllerMock->expects($this->once())
             ->method('respond')
             ->with(
                 $this->equalTo(500),
                 $this->callback(function ($data) {
                     $this->assertStringContainsString('Falha ao atualizar usuário', $data['error']);
                     return true;
                 })
             );
        
        $this->controllerMock->update($userId);
    }
    
    public function testDestroySuccessReturns204NoContent(): void {
        $userId = 1;
        
        $this->repositoryMock->expects($this->once())
             ->method('delete')
             ->with($userId)
             ->willReturn(true);

        $this->controllerMock->expects($this->once())
             ->method('respond')
             ->with($this->equalTo(204), $this->equalTo([]));
        
        $this->controllerMock->destroy($userId);
    }

    public function testDestroyReturns404IfUserNotFound(): void {
        $userId = 999;
        
        $this->repositoryMock->expects($this->once())
             ->method('delete')
             ->with($userId)
             ->willReturn(false);

        $this->controllerMock->expects($this->once())
             ->method('respond')
             ->with(
                 $this->equalTo(404),
                 $this->callback(fn($data) => $data['error'] === 'Usuário não encontrado ou falha na exclusão.')
             );
        
        $this->controllerMock->destroy($userId);
    }

    public function testDestroyReturns500OnError(): void {
        $userId = 1;
        
        $this->repositoryMock->expects($this->once())
             ->method('delete')
             ->with($userId)
             ->willThrowException(new Exception("DB Connection Lost."));

        $this->controllerMock->expects($this->once())
             ->method('respond')
             ->with(
                 $this->equalTo(500),
                 $this->callback(function ($data) {
                     $this->assertStringContainsString('Erro interno ao excluir usuário', $data['error']);
                     return true;
                 })
             );
        
        $this->controllerMock->destroy($userId);
    }
}
