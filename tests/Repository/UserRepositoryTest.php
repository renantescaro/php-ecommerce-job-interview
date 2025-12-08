<?php
namespace Tests\Repository;

use Tests\DatabaseTestCase;
use App\Repository\UserRepository;
use App\Model\User;
use Exception;
use PDO;

class UserRepositoryTest extends DatabaseTestCase {
    
    private UserRepository $repository;
    
    protected function setUp(): void {
        parent::setUp();
        $this->repository = new UserRepository($this->db); 
    }

    // Método auxiliar para criar um objeto User de teste
    private function getTestUser(): User {
        return new User(
            null, 
            'Guardião Teste', 
            'guardiao@test.com', 
            'senha_hash_teste'
        );
    }

    public function testSaveUserSuccessReturnsNewId(): void {
        $user = $this->getTestUser();
        
        $userId = $this->repository->save($user);

        $this->assertIsInt($userId);
        $this->assertGreaterThan(0, $userId);

        $foundUser = $this->repository->findById($userId);
        $this->assertNotNull($foundUser);
        $this->assertSame('Guardião Teste', $foundUser->name);
    }

    public function testSaveTransactionRollbackOnError(): void {
        // Causa um erro tentando salvar um usuário sem nome (assumindo NOT NULL na coluna 'name')
        // Ou, mais fácil, forçando uma exceção ou violação de UNIQUE key se já tiver um.
        
        $user1 = $this->getTestUser();
        $this->repository->save($user1);
        
        // Tenta inserir o segundo com o mesmo login
        $user2 = $this->getTestUser();
        
        try {
            $this->repository->save($user2);
            $this->fail("Deveria ter lançado uma exceção de violação de UNIQUE key.");
        } catch (Exception $e) {
            // A exceção é esperada (Falha ao salvar usuário)
            $this->assertStringContainsString('Falha ao salvar usuário', $e->getMessage());
        }

        $allUsers = $this->repository->findAll();
        $this->assertCount(1, $allUsers, "A transação falha não deveria ter inserido o segundo usuário.");
    }

    public function testFindByIdReturnsUserWhenFound(): void {
        $user = $this->getTestUser();
        $userId = $this->repository->save($user);

        $foundUser = $this->repository->findById($userId);
        
        $this->assertNotNull($foundUser);
        $this->assertSame($userId, $foundUser->id);
        $this->assertInstanceOf(User::class, $foundUser);
    }

    public function testFindByIdReturnsNullWhenNotFound(): void {
        $foundUser = $this->repository->findById(99999);
        $this->assertNull($foundUser);
    }

    public function testFindByLoginReturnsUserWhenFound(): void {
        $user = $this->getTestUser();
        $this->repository->save($user);

        $foundUser = $this->repository->findByLogin('guardiao@test.com');
        
        $this->assertNotNull($foundUser);
        $this->assertSame('guardiao@test.com', $foundUser->login);
    }

    public function testFindByLoginReturnsNullWhenNotFound(): void {
        $foundUser = $this->repository->findByLogin('naoexiste@test.com');
        $this->assertNull($foundUser);
    }

    public function testFindAllReturnsAllUsers(): void {
        $user1 = $this->getTestUser();
        $user2 = new User(null, 'Outro Teste', 'outro@test.com', 'hash2');
        
        $this->repository->save($user1);
        $this->repository->save($user2);

        $users = $this->repository->findAll();
        
        $this->assertCount(2, $users);
        $this->assertInstanceOf(User::class, $users[0]);
    }

    public function testFindAllReturnsEmptyArrayWhenNoUsers(): void {
        $users = $this->repository->findAll();
        $this->assertIsArray($users);
        $this->assertEmpty($users);
    }

    public function testUpdateUserSuccess(): void {
        $user = $this->getTestUser();
        $userId = $this->repository->save($user);

        $updatedUser = new User(
            $userId, 
            'Nome Novo', 
            'novo@login.com', 
            'novo_hash'
        );
        
        $result = $this->repository->update($updatedUser, []); 
        $this->assertTrue($result);

        $foundUser = $this->repository->findById($userId);
        $this->assertSame('Nome Novo', $foundUser->name);
        $this->assertSame('novo@login.com', $foundUser->login);
    }
    
    public function testDeleteUserSuccess(): void {
        $user = $this->getTestUser();
        $userId = $this->repository->save($user);

        $result = $this->repository->delete($userId);
        
        $this->assertTrue($result);

        $foundUser = $this->repository->findById($userId);
        $this->assertNull($foundUser);
    }

    public function testDeleteUserReturnsFalseIfNotFound(): void {
        $result = $this->repository->delete(99999);
        $this->assertFalse($result);
    }
}
