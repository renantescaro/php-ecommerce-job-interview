<?php
namespace Tests\Controller;

use Tests\DatabaseTestCase;
use App\Controller\CustomerController;
use App\Model\Customer;
use App\Model\Address;

class CustomerControllerTest extends DatabaseTestCase {

    // Helper para simular a leitura do input HTTP
    private function mockInput(array $data): void {
        // CustomerController foi modificado para ler este global em ambiente de teste
        $GLOBALS['mock_http_input'] = json_encode($data); 
    }

    /**
     * Testa o endpoint POST /api/customers para inclusão bem-sucedida.
     */
    public function testStoreSuccessReturns201Created(): void {
        $inputData = [
            'customer' => [
                'name' => 'Cliente Teste',
                'birth_date' => '2000-10-10',
                'cpf' => '12345678901',
                'rg' => '123',
                'phone' => '11987654321'
            ],
            'addresses' => [
                ['street' => 'Rua Teste', 'number' => '100', 'city' => 'Sao Paulo', 'state' => 'SP', 'zip_code' => '01000-000']
            ]
        ];
        $this->mockInput($inputData);
        
        $controllerMock = $this->getMockBuilder(CustomerController::class)
            ->onlyMethods(['respond']) 
            ->getMock(); 

        $controllerMock->expects($this->once())
            ->method('respond')
            ->with(
                $this->equalTo(201), // Espera status code 201
                $this->callback(function ($data) { // Verifica os dados da resposta
                    // Verifica se o ID foi retornado e é positivo
                    $this->assertArrayHasKey('id', $data);
                    $this->assertGreaterThan(0, $data['id']);
                    
                    $this->assertTrue(true); 
                    return true;
                })
            );

        $controllerMock->store(); 
    }

    /**
     * Testa o endpoint POST /api/customers com dados incompletos (400 Bad Request).
     */
    public function testStoreReturns400IfDataIsMissing(): void {
        $inputData = [
            'customer' => ['name' => 'Cliente Incompleto', 'cpf' => ''], 
            'addresses' => []
        ];
        $this->mockInput($inputData);
        
        $controllerMock = $this->getMockBuilder(CustomerController::class)
            ->onlyMethods(['respond'])
            ->getMock();

        $controllerMock->expects($this->once())
            ->method('respond')
            ->with(
                $this->equalTo(400), // Espera status code 400
                $this->callback(function ($data) {
                    // Verifica se a mensagem de erro está na resposta
                    $this->assertStringContainsString('incompletos', $data['error']);
                    return true;
                })
            );
        
        $controllerMock->store();
    }

    /**
     * Testa o endpoint GET /api/customers/{id} (Sucesso: 200 OK).
     */
    public function testShowSuccessReturns200AndCustomerData(): void {
        $testCustomer = new Customer(1, 'Ninja Show', '1990-01-01', '11111111111', '123', '987654321');
        
        $controllerMock = $this->getMockBuilder(CustomerController::class)
            ->onlyMethods(['respond']) 
            ->getMock();

        $repository = $controllerMock->repository ?? new \App\Repository\CustomerRepository(new \App\Config\Database(), new \App\Repository\AddressRepository(new \App\Config\Database()));
        $customerId = $repository->save($testCustomer, []);
        
        $controllerMock->expects($this->once())
            ->method('respond')
            ->with(
                $this->equalTo(200), // Espera status code 200
                $this->callback(function ($data) use ($customerId) { 
                    // Verifica se os dados do cliente foram retornados
                    $this->assertArrayHasKey('data', $data);
                    $this->assertSame($customerId, $data['data']->id);
                    $this->assertSame('Ninja Show', $data['data']->name);
                    return true;
                })
            );

        $controllerMock->show($customerId); 
    }

    /**
     * Testa o endpoint GET /api/customers/{id} (Falha: 404 Not Found).
     */
    public function testShowReturns404IfCustomerNotFound(): void {
        $nonExistentId = 9999;
        
        $controllerMock = $this->getMockBuilder(CustomerController::class)
            ->onlyMethods(['respond'])
            ->getMock(); 

        $controllerMock->expects($this->once())
            ->method('respond')
            ->with(
                $this->equalTo(404), // Espera status code 404
                $this->callback(function ($data) {
                    $this->assertStringContainsString('não encontrado', $data['error']);
                    return true;
                })
            );

        $controllerMock->show($nonExistentId);
    }
}
