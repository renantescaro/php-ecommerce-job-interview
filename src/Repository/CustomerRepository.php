<?php
namespace App\Repository;

use PDO;
use App\Config\Database;
use App\Model\Customer;
use App\Model\Address;
use App\Repository\AddressRepository;
use Exception;

class CustomerRepository {
    private PDO $db;
    private AddressRepository $addressRepository;

    public function __construct(Database $db, AddressRepository $addressRepository) {
        $this->db = $db->getConnection();
        $this->addressRepository = $addressRepository;
    }

    /**
     * Salva um novo cliente e seus endereços relacionados usando transação.
     * @param Customer $customer O objeto Customer sem ID.
     * @param Address[] $addresses Array de objetos Address.
     * @return int O ID do novo cliente inserido.
     */
    public function save(Customer $customer, array $addresses): int {
        $this->db->beginTransaction();
        
        try {
            $sql = "INSERT INTO customers (name, birth_date, cpf, rg, phone) 
                    VALUES (:name, :birthDate, :cpf, :rg, :phone)";
            
            $stmt = $this->db->prepare($sql);
            
            $stmt->bindParam(':name', $customer->name);
            $stmt->bindParam(':birthDate', $customer->birthDate);
            $stmt->bindParam(':cpf', $customer->cpf);
            $stmt->bindParam(':rg', $customer->rg);
            $stmt->bindParam(':phone', $customer->phone);

            $stmt->execute();
            $customerId = (int)$this->db->lastInsertId();

            foreach ($addresses as $address) {
                $address->customerId = $customerId;
                $this->addressRepository->save($address);
            }

            $this->db->commit();
            return $customerId;

        } catch (Exception $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw new Exception("Falha ao salvar cliente e endereços: " . $e->getMessage());
        }
    }

    /**
     * Retorna a lista de todos os clientes, carregando seus endereços.
     * @return Customer[]
     */
    public function findAll(): array {
        $stmt = $this->db->query("SELECT * FROM customers ORDER BY name ASC");
        $customersData = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $customers = [];
        foreach ($customersData as $data) {
            $customer = $this->createCustomerFromData($data);
            
            // Carrega os endereços relacionados
            $customer->addresses = $this->addressRepository->findByCustomerId($customer->id); 
            $customers[] = $customer;
        }
        return $customers;
    }

    /**
     * Busca um cliente pelo ID.
     * @param int $id O ID do cliente.
     * @return Customer|null Retorna o objeto Customer ou null se não encontrado.
     */
    public function findById(int $id): ?Customer {
        $sql = "SELECT * FROM customers WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        $data = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$data) {
            return null;
        }

        $customer = $this->createCustomerFromData($data);
        // Carrega os endereços
        $customer->addresses = $this->addressRepository->findByCustomerId($customer->id); 
        return $customer;
    }

    /**
     * Atualiza um Cliente existente e gerencia seus Endereços em transação.
     * Remove todos os endereços antigos e insere os novos fornecidos.
     * @param Customer $customer Objeto Customer com ID preenchido.
     * @param Address[] $addresses Array de objetos Address a serem salvos.
     * @return bool Sucesso na operação.
     */
    public function update(Customer $customer, array $addresses): bool {
        $this->db->beginTransaction();
        
        try {
            $sql = "UPDATE customers SET 
                        name = :name, 
                        birth_date = :birthDate, 
                        cpf = :cpf, 
                        rg = :rg, 
                        phone = :phone 
                    WHERE id = :id";

            $stmt = $this->db->prepare($sql);
            
            $stmt->bindParam(':id', $customer->id, PDO::PARAM_INT);
            $stmt->bindParam(':name', $customer->name);
            $stmt->bindParam(':birthDate', $customer->birthDate);
            $stmt->bindParam(':cpf', $customer->cpf);
            $stmt->bindParam(':rg', $customer->rg);
            $stmt->bindParam(':phone', $customer->phone);

            $stmt->execute();

            $this->addressRepository->deleteByCustomerId($customer->id);

            foreach ($addresses as $address) {
                $address->customerId = $customer->id; 
                $this->addressRepository->save($address);
            }

            $this->db->commit();
            return true;

        } catch (Exception $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw new Exception("Falha na atualização do cliente e endereços: " . $e->getMessage());
        }
    }

    /**
     * Deleta um cliente pelo ID.
     * @param int $id O ID do cliente a ser excluído.
     * @return bool True se o cliente foi excluído, false caso contrário.
     */
    public function delete(int $id): bool {
        $sql = "DELETE FROM customers WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->rowCount() > 0;
    }

    /**
     * Método auxiliar para criar um objeto Customer a partir dos dados do DB.
     * @param array $data Linha de dados do banco de dados.
     * @return Customer
     */
    private function createCustomerFromData(array $data): Customer {
        return new Customer(
            (int)$data['id'], 
            $data['name'], 
            $data['birth_date'], 
            $data['cpf'], 
            $data['rg'], 
            $data['phone']
        );
    }
}
