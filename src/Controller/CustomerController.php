<?php
namespace App\Controller;

use App\Config\Database;
use App\Model\Customer;
use App\Model\Address;
use App\Repository\CustomerRepository;
use App\Repository\AddressRepository; 
use Exception;

class CustomerController {
    private CustomerRepository $repository;

    public function __construct() {
        $db = new Database();
        $addressRepository = new AddressRepository($db);
        $this->repository = new CustomerRepository($db, $addressRepository); 

        // if (!AuthService::isLoggedIn()) {
        //     http_response_code(401); 
        //     echo json_encode(['error' => 'Acesso não autorizado. Faça login.']);
        //     exit();
        // }
    }

    /**
     * Define o cabeçalho Content-Type para JSON e trata a resposta.
     * @param int $statusCode Código HTTP de resposta.
     * @param array $data Dados a serem serializados para JSON.
     */
    private function respond(int $statusCode, array $data): void {
        header('Content-Type: application/json');
        http_response_code($statusCode);
        echo json_encode($data);
    }
    
    /**
     * GET /api/customers
     * Lista todos os clientes.
     */
    public function index(): void {
        try {
            $customers = $this->repository->findAll();
            $this->respond(200, ['data' => $customers]);
        } catch (Exception $e) {
            $this->respond(500, ['error' => 'Erro interno ao listar clientes.', 'details' => $e->getMessage()]);
        }
    }

    /**
     * GET /api/customers/{id}
     * Exibe os detalhes de um cliente específico.
     */
    public function show(int $id): void {
        try {
            $customer = $this->repository->findById($id);

            if (!$customer) {
                $this->respond(404, ['error' => 'Cliente não encontrado.']);
                return;
            }
            $this->respond(200, ['data' => $customer]);
        } catch (Exception $e) {
            $this->respond(500, ['error' => 'Erro interno ao buscar cliente.', 'details' => $e->getMessage()]);
        }
    }

    /**
     * POST /api/customers
     * Inclui um novo cliente e seus endereços.
     */
    public function store(): void {
        $data = json_decode(file_get_contents('php://input'), true);

        if (empty($data['customer']['name']) || empty($data['customer']['cpf']) || empty($data['addresses'])) {
            $this->respond(400, ['error' => 'Dados de cliente ou endereço incompletos.']);
            return;
        }

        try {
            $customerData = $data['customer'];
            $customer = new Customer(
                null, // ID nulo para inclusão
                $customerData['name'],
                $customerData['birth_date'],
                $customerData['cpf'],
                $customerData['rg'] ?? null,
                $customerData['phone'] ?? null
            );

            $addresses = [];
            foreach ($data['addresses'] as $addrData) {
                $addresses[] = new Address(
                    null, 0, // ID e customerId placeholder
                    $addrData['street'],
                    $addrData['number'] ?? null,
                    $addrData['city'],
                    $addrData['state'],
                    $addrData['zip_code']
                );
            }

            $newId = $this->repository->save($customer, $addresses);
            
            $this->respond(201, ['message' => 'Cliente criado com sucesso.', 'id' => $newId]);
            
        } catch (Exception $e) {
            $this->respond(500, ['error' => 'Falha ao incluir cliente.', 'details' => $e->getMessage()]);
        }
    }

    /**
     * PUT /api/customers/{id}
     * Edita um cliente existente e seus endereços.
     */
    public function update(int $id): void {
        $data = json_decode(file_get_contents('php://input'), true);

        if (!$this->repository->findById($id)) {
             $this->respond(404, ['error' => 'Cliente a ser atualizado não encontrado.']);
             return;
        }
        if (empty($data['customer']['name']) || empty($data['addresses'])) {
            $this->respond(400, ['error' => 'Dados de cliente ou endereço incompletos.']);
            return;
        }

        try {
            $customerData = $data['customer'];
            $customer = new Customer(
                $id,
                $customerData['name'],
                $customerData['birth_date'],
                $customerData['cpf'],
                $customerData['rg'] ?? null,
                $customerData['phone'] ?? null
            );

            $addresses = [];
            foreach ($data['addresses'] as $addrData) {
                $addresses[] = new Address(
                    null, $id,
                    $addrData['street'],
                    $addrData['number'] ?? null,
                    $addrData['city'],
                    $addrData['state'],
                    $addrData['zip_code']
                );
            }

            $this->repository->update($customer, $addresses);
            
            $this->respond(200, ['message' => 'Cliente e endereços atualizados com sucesso.']);

        } catch (Exception $e) {
            $this->respond(500, ['error' => 'Falha ao atualizar cliente.', 'details' => $e->getMessage()]);
        }
    }

    /**
     * DELETE /api/customers/{id}
     * Exclui um cliente e seus endereços relacionados
     */
    public function destroy(int $id): void {
        try {
            $success = $this->repository->delete($id);

            if (!$success) {
                $this->respond(404, ['error' => 'Cliente não encontrado ou falha na exclusão.']);
                return;
            }
            $this->respond(204, []); 
        } catch (Exception $e) {
            $this->respond(500, ['error' => 'Erro interno ao excluir cliente.', 'details' => $e->getMessage()]);
        }
    }
}
