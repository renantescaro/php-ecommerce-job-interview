<?php
namespace App\Repository;

use PDO;
use App\Config\Database;
use App\Model\Address;

class AddressRepository {
    private PDO $db;

    public function __construct(Database $db) {
        $this->db = $db->getConnection();
    }

    /**
     * Salva um novo endereço no banco de dados.
     * @param Address $address Objeto Address a ser salvo.
     * @return int O ID do novo endereço inserido.
     */
    public function save(Address $address): int {
        $sql = "INSERT INTO addresses (customer_id, street, number, city, state, zip_code) 
                VALUES (:customer_id, :street, :number, :city, :state, :zip_code)";
        
        $stmt = $this->db->prepare($sql);
        
        $stmt->bindParam(':customer_id', $address->customerId, PDO::PARAM_INT);
        $stmt->bindParam(':street', $address->street);
        $stmt->bindParam(':number', $address->number);
        $stmt->bindParam(':city', $address->city);
        $stmt->bindParam(':state', $address->state);
        $stmt->bindParam(':zip_code', $address->zipCode);

        $stmt->execute();

        return (int)$this->db->lastInsertId();
    }

    /**
     * Encontra todos os endereços associados a um Cliente.
     * @param int $customerId O ID do cliente.
     * @return Address[] Array de objetos Address.
     */
    public function findByCustomerId(int $customerId): array {
        $sql = "SELECT * FROM addresses WHERE customer_id = :customer_id";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':customer_id', $customerId, PDO::PARAM_INT);
        $stmt->execute();
        
        $addressesData = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $addresses = [];

        foreach ($addressesData as $data) {
            $addresses[] = $this->createAddressFromData($data);
        }
        return $addresses;
    }

    /**
     * Atualiza um endereço existente no banco de dados.
     * @param Address $address Objeto Address com o ID preenchido.
     * @return bool Sucesso na operação (true se uma linha foi afetada).
     */
    public function update(Address $address): bool {
        if ($address->id === null) {
            throw new \InvalidArgumentException("O ID do Endereço é obrigatório para a operação de update.");
        }

        $sql = "UPDATE addresses SET 
                    customer_id = :customer_id, 
                    street = :street, 
                    number = :number, 
                    city = :city, 
                    state = :state, 
                    zip_code = :zip_code
                WHERE id = :id";
        
        $stmt = $this->db->prepare($sql);

        $stmt->bindParam(':id', $address->id, PDO::PARAM_INT);
        $stmt->bindParam(':customer_id', $address->customerId, PDO::PARAM_INT);
        $stmt->bindParam(':street', $address->street);
        $stmt->bindParam(':number', $address->number);
        $stmt->bindParam(':city', $address->city);
        $stmt->bindParam(':state', $address->state);
        $stmt->bindParam(':zip_code', $address->zipCode);

        $stmt->execute();

        return $stmt->rowCount() > 0;
    }

    /**
     * Deleta todos os endereços associados a um Cliente.
     * @param int $customerId O ID do cliente.
     * @return bool
     */
    public function deleteByCustomerId(int $customerId): bool {
        $sql = "DELETE FROM addresses WHERE customer_id = :customer_id";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':customer_id', $customerId, PDO::PARAM_INT);
        
        return $stmt->execute();
    }

    /**
     * Método auxiliar para criar um objeto Address a partir dos dados do DB.
     * @param array $data Linha de dados do banco de dados.
     * @return Address
     */
    private function createAddressFromData(array $data): Address {
        $address = new Address(
            (int)$data['id'],
            (int)$data['customer_id'],
            $data['street'],
            $data['number'],
            $data['city'],
            $data['state'],
            $data['zip_code']
        );
        return $address;
    }
}
