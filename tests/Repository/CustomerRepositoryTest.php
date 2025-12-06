<?php
namespace Tests\Repository;

use App\Config\Database;
use Tests\DatabaseTestCase;
use App\Repository\AddressRepository;
use App\Repository\CustomerRepository;
use App\Model\Customer;
use App\Model\Address;
use Exception;

class CustomerRepositoryTest extends DatabaseTestCase {
    
    private CustomerRepository $repository;

    protected function setUp(): void {
        parent::setUp(); 
        $addressRepository = new AddressRepository($this->db);
        $this->repository = new CustomerRepository($this->db, $addressRepository);
    }

    protected function tearDown(): void {
        parent::tearDown();
    }

    /**
     * Teste o método de inclusão (save)
     */
    public function testCustomerCanBeSavedWithAddresses(): void {
        $customer = new Customer(null, 'Teste', '2000-01-01', '12345678901', '112233', '11999999999');
        $addresses = [
            new Address(null, 0, 'Rua Teste', '123', 'Cidade Teste', 'SP', '01000-000')
        ];
        
        $newId = $this->repository->save($customer, $addresses);

        $this->assertIsInt($newId);
        $this->assertGreaterThan(0, $newId);

        $savedCustomer = $this->repository->findById($newId);
        $this->assertNotNull($savedCustomer);
        $this->assertSame('Teste', $savedCustomer->name);

        $this->assertCount(1, $savedCustomer->addresses);
        $this->assertSame('Rua Teste', $savedCustomer->addresses[0]->street);
    }

    /**
     * Teste o método de busca por id (findById)
     */
    public function testFindByIdReturnsNullForNonExistingCustomer(): void {
        $customer = $this->repository->findById(999); 
        $this->assertNull($customer);
    }

    /**
     * Teste para verificar se findAll() retorna todos os clientes com seus endereços.
     */
    public function testFindAllReturnsArrayOfCustomers(): void {
        $customer1 = new Customer(null, 'Alice', '1990-01-01', '11122233344', '111', '988887777');
        $addresses1 = [
            new Address(null, 0, 'Rua A', '1', 'Cidade X', 'SP', '00000-000')
        ];
        $this->repository->save($customer1, $addresses1);

        $customer2 = new Customer(null, 'Bob', '1995-05-05', '55566677788', '222', '999990000');
        $addresses2 = [
            new Address(null, 0, 'Rua B', '10', 'Cidade Y', 'RJ', '11111-111'),
            new Address(null, 0, 'Av. C', '20', 'Cidade Y', 'RJ', '22222-222')
        ];
        $this->repository->save($customer2, $addresses2);

        $customers = $this->repository->findAll();

        $this->assertCount(2, $customers);

        $this->assertInstanceOf(Customer::class, $customers[0]);
        $this->assertInstanceOf(Customer::class, $customers[1]);

        $alice = array_filter($customers, fn($c) => $c->name === 'Alice');
        $alice = array_values($alice)[0]; // Pega o primeiro elemento filtrado

        $this->assertSame('11122233344', $alice->cpf);

        $this->assertCount(1, $alice->addresses);
        $this->assertSame('Rua A', $alice->addresses[0]->street);

        $bob = array_filter($customers, fn($c) => $c->name === 'Bob');
        $bob = array_values($bob)[0]; 

        $this->assertCount(2, $bob->addresses);
        $this->assertSame('Av. C', $bob->addresses[1]->street);
    }

    /**
     * Teste para verificar se o cliente é atualizado corretamente,
     * e se os endereços antigos são substituídos pelos novos.
     */
    public function testCustomerCanBeUpdatedWithNewAddresses(): void {
        $originalCustomer = new Customer(null, 'Cliente Antigo', '1980-01-01', '99988877766', '1234', '11900000000');
        $originalAddresses = [
            new Address(null, 0, 'Rua Inicial', '100', 'Cidade Velha', 'SP', '01000-000'),
            new Address(null, 0, 'Av. Antiga', '200', 'Cidade Velha', 'SP', '01000-000')
        ];
        $customerId = $this->repository->save($originalCustomer, $originalAddresses);
        
        $initialCustomer = $this->repository->findById($customerId);
        $this->assertCount(2, $initialCustomer->addresses, "Deve haver 2 endereços antes do update.");
        
        $updatedCustomer = new Customer(
            $customerId, // O ID deve ser mantido
            'Cliente Novo', 
            '1985-05-05', 
            '12345678900', // CPF alterado
            '9876', 
            '21977777777'
        );

        $newAddresses = [
            new Address(null, 0, 'Rua da Fire', '333', 'Ninja City', 'RJ', '99999-999')
        ];

        $success = $this->repository->update($updatedCustomer, $newAddresses);
        
        $this->assertTrue($success, "A operação de update deve retornar true.");
        
        $retrievedCustomer = $this->repository->findById($customerId);
        
        $this->assertSame('Cliente Novo', $retrievedCustomer->name, "O nome do cliente deve ser atualizado.");
        $this->assertSame('12345678900', $retrievedCustomer->cpf, "O CPF do cliente deve ser atualizado.");

        $this->assertCount(1, $retrievedCustomer->addresses, "Deve haver apenas 1 endereço após o update.");
        
        $this->assertSame('Rua da Fire', $retrievedCustomer->addresses[0]->street, "O novo endereço deve ser o da Fire.");
    }

    /**
     * Teste para verificar se o cliente e seus endereços relacionados são excluídos.
     */
    public function testCustomerAndAddressesAreDeleted(): void {
        $customerToDelete = new Customer(null, 'Cliente Excluir', '1999-09-09', '11111111111', '555', '11900000000');
        $addressesToDelete = [
            new Address(null, 0, 'Rua da Exclusão', '999', 'Cidade Delete', 'SP', '00000-000')
        ];
        $customerId = $this->repository->save($customerToDelete, $addressesToDelete);

        $initialCustomer = $this->repository->findById($customerId);
        $this->assertNotNull($initialCustomer, "O cliente deve existir antes da operação de delete.");

        $success = $this->repository->delete($customerId);

        $this->assertTrue($success, "A operação de delete deve retornar true.");

        $deletedCustomer = $this->repository->findById($customerId);
        $this->assertNull($deletedCustomer, "O cliente não deve ser encontrado após o delete.");

        $failure = $this->repository->delete(99999);
        $this->assertFalse($failure, "Deletar um ID inexistente deve retornar false.");
    }
}
