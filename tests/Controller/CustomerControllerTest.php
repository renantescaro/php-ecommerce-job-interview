<?php
namespace Tests\Controller;

use PHPUnit\Framework\TestCase;
use App\Controller\CustomerController;
use App\Repository\CustomerRepository;
use App\Model\Customer;
use App\Model\Address;
use App\Config\Database;
use App\Repository\AddressRepository;
use Exception;

class CustomerControllerTest extends TestCase {
    private $controllerMock;
    private $repositoryMock;

    protected function setUp(): void {
        $this->repositoryMock = $this->createMock(CustomerRepository::class);
        
        $this->controllerMock = $this->getMockBuilder(CustomerController::class)
            // herda de BaseController
            ->onlyMethods(['respond', 'getRequestData'])
            ->disableOriginalConstructor() // impede o construtor original
            ->getMock(); 

        $reflection = new \ReflectionProperty(CustomerController::class, 'repository');
        $reflection->setAccessible(true);
        $reflection->setValue($this->controllerMock, $this->repositoryMock);
    }

    private function getValidInputData(): array {
        return [
            'customer' => [
                'name' => 'John Doe',
                'birth_date' => '1990-01-01',
                'cpf' => '12345678901',
                'rg' => '123',
                'phone' => '11987654321'
            ],
            'addresses' => [
                [
                    'street' => 'Rua Teste', 
                    'number' => '100', 
                    'city' => 'Sao Paulo', 
                    'state' => 'SP', 
                    'zip_code' => '01000-000'
                ]
            ]
        ];
    }

    public function testIndexSuccessReturns200WithData(): void {
        $mockCustomers = [new Customer(1, 'A', '1', '1', '1', '1')];
        $this->repositoryMock->expects($this->once())
            ->method('findAll')
            ->willReturn($mockCustomers);

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
                    $this->assertStringContainsString('Erro interno', $data['error']);
                    return true;
                })
            );
        
        $this->controllerMock->index();
    }
    
    public function testShowSuccessReturns200WithCustomer(): void {
        $customerId = 1;
        $mockCustomer = new Customer($customerId, 'A', '1', '1', '1', '1');
        
        $this->repositoryMock->expects($this->once())
            ->method('findById')
            ->with($customerId)
            ->willReturn($mockCustomer);

        $this->controllerMock->expects($this->once())
            ->method('respond')
            ->with($this->equalTo(200));

        $this->controllerMock->show($customerId);
    }
    
    public function testShowReturns404IfCustomerNotFound(): void {
        $customerId = 999;
        
        $this->repositoryMock->expects($this->once())
            ->method('findById')
            ->with($customerId)
            ->willReturn(null);

        $this->controllerMock->expects($this->once())
            ->method('respond')
            ->with(
                $this->equalTo(404),
                $this->callback(fn($data) => $data['error'] === 'Cliente não encontrado.')
            );

        $this->controllerMock->show($customerId);
    }
    
    public function testStoreSuccessReturns201Created(): void {
        $inputData = $this->getValidInputData();
        $newId = 5;

        $this->controllerMock->expects($this->once())
            ->method('getRequestData')
            ->willReturn($inputData);
        
        $this->repositoryMock->expects($this->once())
            ->method('save')
            ->with(
                $this->isInstanceOf(Customer::class),
                $this->countOf(1) // Verifica se 1 endereço foi passado
            )
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
        $inputData = ['customer' => ['name' => 'A', 'cpf' => '1']]; 
        $this->controllerMock->expects($this->once())
            ->method('getRequestData')
            ->willReturn($inputData);
        
        $this->repositoryMock->expects($this->never())
            ->method('save');

        $this->controllerMock->expects($this->once())
            ->method('respond')
            ->with(
                $this->equalTo(400),
                $this->callback(fn($data) => $data['error'] === 'Dados de cliente ou endereço incompletos.')
            );
        
        $this->controllerMock->store();
    }

    public function testUpdateSuccessReturns200(): void {
        $customerId = 10;
        $inputData = $this->getValidInputData();
        
        $this->controllerMock->expects($this->once())
            ->method('getRequestData')
            ->willReturn($inputData);
             
        $this->repositoryMock->expects($this->once())
            ->method('findById')
            ->with($customerId)
            ->willReturn(new Customer($customerId, 'A', '1', '1', '1', '1'));
        
        $this->repositoryMock->expects($this->once())
            ->method('update')
            ->with($this->isInstanceOf(Customer::class), $this->countOf(1));

        $this->controllerMock->expects($this->once())
            ->method('respond')
            ->with($this->equalTo(200));
        
        $this->controllerMock->update($customerId);
    }
    
    public function testUpdateReturns404IfCustomerNotFound(): void {
        $customerId = 999;
        $inputData = $this->getValidInputData();

        $this->repositoryMock->expects($this->once())
            ->method('findById')
            ->with($customerId)
            ->willReturn(null);
             
        $this->controllerMock->expects($this->once())
            ->method('getRequestData')
            ->willReturn($inputData);

        $this->controllerMock->expects($this->once())
            ->method('respond')
            ->with(
                $this->equalTo(404),
                $this->callback(
                    fn($data) => $data['error'] === 'Cliente a ser atualizado não encontrado.'
                )
            );
        
        $this->controllerMock->update($customerId);
    }
    
    public function testDestroySuccessReturns204NoContent(): void {
        $customerId = 1;
        
        $this->repositoryMock->expects($this->once())
            ->method('delete')
            ->with($customerId)
            ->willReturn(true);

        $this->controllerMock->expects($this->once())
            ->method('respond')
            ->with($this->equalTo(204), $this->equalTo([]));
        
        $this->controllerMock->destroy($customerId);
    }

    public function testDestroyReturns404IfCustomerNotFound(): void {
        $customerId = 999;
        
        $this->repositoryMock->expects($this->once())
            ->method('delete')
            ->with($customerId)
            ->willReturn(false);

        $this->controllerMock->expects($this->once())
            ->method('respond')
            ->with(
                $this->equalTo(404),
                $this->callback(
                fn($data) => $data['error'] === 'Cliente não encontrado ou falha na exclusão.'
            )
            );
        
        $this->controllerMock->destroy($customerId);
    }

    public function testShowReturns500OnError(): void {
        $customerId = 1;
        
        $this->repositoryMock->expects($this->once())
            ->method('findById')
            ->with($customerId)
            ->willThrowException(new Exception("Falha de timeout."));

        $this->controllerMock->expects($this->once())
            ->method('respond')
            ->with(
                $this->equalTo(500),
                $this->callback(function ($data) {
                    $this->assertStringContainsString('Erro interno ao buscar cliente', $data['error']);
                    $this->assertStringContainsString('timeout', $data['details']);
                    return true;
                })
            );
        
        $this->controllerMock->show($customerId);
    }

    public function testStoreReturns500OnError(): void {
        $inputData = $this->getValidInputData();

        $this->controllerMock->expects($this->once())
            ->method('getRequestData')
            ->willReturn($inputData);
        
        $this->repositoryMock->expects($this->once())
            ->method('save')
            ->willThrowException(new Exception("Falha na transação de salvar."));

        $this->controllerMock->expects($this->once())
            ->method('respond')
            ->with(
                $this->equalTo(500),
                $this->callback(function ($data) {
                    $this->assertStringContainsString('Falha ao incluir cliente', $data['error']);
                    $this->assertStringContainsString('Falha na transação', $data['details']);
                    return true;
                })
            );
        
        $this->controllerMock->store();
    }

    public function testUpdateReturns500OnError(): void {
        $customerId = 10;
        $inputData = $this->getValidInputData();
        
        $this->controllerMock->expects($this->once())
            ->method('getRequestData')
            ->willReturn($inputData);
             
        $this->repositoryMock->expects($this->once())
            ->method('findById')
            ->with($customerId)
            ->willReturn(new Customer($customerId, 'A', '1', '1', '1', '1'));
        
        $this->repositoryMock->expects($this->once())
            ->method('update')
            ->willThrowException(new Exception("Erro na atualização do DB."));

        $this->controllerMock->expects($this->once())
            ->method('respond')
            ->with(
                $this->equalTo(500),
                $this->callback(function ($data) {
                    $this->assertStringContainsString('Falha ao atualizar cliente', $data['error']);
                    $this->assertStringContainsString('Erro na atualização do DB', $data['details']);
                    return true;
                })
            );
        
        $this->controllerMock->update($customerId);
    }

    public function testDestroyReturns500OnError(): void {
        $customerId = 1;
        
        $this->repositoryMock->expects($this->once())
            ->method('delete')
            ->with($customerId)
            ->willThrowException(new Exception("Falha no FOREIGN KEY."));

        $this->controllerMock->expects($this->once())
            ->method('respond')
            ->with(
                $this->equalTo(500),
                $this->callback(function ($data) {
                    $this->assertStringContainsString('Erro interno ao excluir cliente', $data['error']);
                    $this->assertStringContainsString('FOREIGN KEY', $data['details']);
                    return true;
                })
            );
        
        $this->controllerMock->destroy($customerId);
    }

    public function testUpdateReturns400IfDataIsMissing(): void {
        $customerId = 1;
        
        $inputData = [
            'customer' => ['name' => ''], // Nome vazio
            'addresses' => [] // Array de endereços vazio
        ]; 
        $this->controllerMock->expects($this->once())
            ->method('getRequestData')
            ->willReturn($inputData);
             
        $this->repositoryMock->expects($this->once())
            ->method('findById')
            ->with($customerId)
            ->willReturn(new Customer($customerId, 'A', '1', '1', '1', '1'));
        
        $this->repositoryMock->expects($this->never())
            ->method('update');

        $this->controllerMock->expects($this->once())
             ->method('respond')
             ->with(
                $this->equalTo(400),
                 $this->callback(
                    fn($data) => $data['error'] === 'Dados de cliente ou endereço incompletos.'
                )
             );
        
        $this->controllerMock->update($customerId);
    }
}
