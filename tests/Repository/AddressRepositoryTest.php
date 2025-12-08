<?php
namespace Tests\Repository;

use App\Config\Database;
use Tests\DatabaseTestCase;
use App\Repository\AddressRepository;
use App\Model\Address;
use PDO;
use InvalidArgumentException;
use Exception;

class AddressRepositoryTest extends DatabaseTestCase {

    private int $customerId;
    private AddressRepository $repository;

    protected function setUp(): void {
        parent::setUp(); 
        $this->repository = new AddressRepository($this->db);
    
        $this->customerId = $this->insertTestCustomer();
    }

    protected function tearDown(): void {
        parent::tearDown();
    }

    private function insertTestCustomer(): int {
        $pdo = $this->db->getConnection();
        $stmt = $pdo->prepare(
            "INSERT INTO customers (name, birth_date, cpf, rg, phone)
            VALUES ('Update Test', '1990-01-01', '11111111111', '123', '11999999999')");
        $stmt->execute();
        return (int)$pdo->lastInsertId();
    }
    
    private function insertTestAddress(): Address {
        $address = new Address(
            null, 
            $this->customerId, 
            'Rua Original', 
            '100', 
            'São Paulo', 
            'SP', 
            '01000-000'
        );
        $addressId = $this->repository->save($address);
        $address->id = $addressId;
        return $address;
    }

    /**
     * Testa a atualização bem-sucedida de um endereço existente.
     */
    public function testUpdateSuccess(): void {
        $originalAddress = $this->insertTestAddress();
        
        $originalAddress->street = 'Rua Atualizada';
        $originalAddress->zipCode = '99999-999';
        
        $result = $this->repository->update($originalAddress);
        
        $this->assertTrue($result, "O update deve retornar true após a alteração.");

        $pdo = $this->db->getConnection();
        $stmt = $pdo->prepare("SELECT * FROM addresses WHERE id = :id");
        $stmt->bindParam(':id', $originalAddress->id, PDO::PARAM_INT);
        $stmt->execute();
        $updatedData = $stmt->fetch(PDO::FETCH_ASSOC);

        $this->assertSame('Rua Atualizada', $updatedData['street']);
        $this->assertSame('99999-999', $updatedData['zip_code']);
    }

    /**
     * Testa a tentativa de update com ID nulo (deve lançar exceção).
     */
    public function testUpdateThrowsExceptionIfIdIsNull(): void {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("O ID do Endereço é obrigatório para a operação de update.");

        $addressWithoutId = new Address(
            null, 
            $this->customerId, 
            'Rua Sem ID', 
            '1', 
            'Cidade', 
            'UF', 
            '00000-000'
        );

        $this->repository->update($addressWithoutId);
    }
    
    /**
     * Testa se o update retorna false ao tentar atualizar um ID inexistente.
     */
    public function testUpdateReturnsFalseIfAddressNotFound(): void {
        $nonExistentId = 99999;
        $address = new Address(
            $nonExistentId, 
            $this->customerId, 
            'Rua Inexistente', 
            '1', 
            'Cidade', 
            'UF', 
            '00000-000'
        );

        $result = $this->repository->update($address);

        $this->assertFalse($result, "O update deve retornar false se nenhum registro foi afetado.");
    }
}
