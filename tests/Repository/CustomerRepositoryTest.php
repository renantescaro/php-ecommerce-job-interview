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
}
