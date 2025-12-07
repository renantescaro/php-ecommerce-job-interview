<?php
namespace App\Repository;

use PDO;
use App\Config\Database;
use App\Model\User;
use App\Repository\AddressRepository;
use Exception;

class UserRepository {
    private PDO $db;

    public function __construct(Database $db) {
        $this->db = $db->getConnection();
    }

    /**
     * Salva um novo usuário usando transação.
     * @param User $user.
     * @return int O ID do novo usuário inserido.
     */
    public function save(User $user): int {
        $this->db->beginTransaction();
        
        try {
            $sql = "INSERT INTO users (name, login, password) 
                    VALUES (:name, :login, :password)";
            
            $stmt = $this->db->prepare($sql);
            
            $stmt->bindParam(':name', $user->name);
            $stmt->bindParam(':login', $user->login);
            $stmt->bindParam(':password', $user->password);

            $stmt->execute();
            $userId = (int)$this->db->lastInsertId();

            $this->db->commit();
            return $userId;

        } catch (Exception $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw new Exception("Falha ao salvar usuário: " . $e->getMessage());
        }
    }

    /**
     * Retorna a lista de todos os usuários, carregando seus endereços.
     * @return User[]
     */
    public function findAll(): array {
        $stmt = $this->db->query("SELECT * FROM users ORDER BY name ASC");
        $usersData = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $users = [];
        foreach ($usersData as $data) {
            $users[] = $this->createUserFromData($data);
        }
        return $users;
    }

    /**
     * Busca um usuário pelo ID.
     * @param int $id O ID do usuário.
     * @return User|null Retorna o objeto User ou null se não encontrado.
     */
    public function findById(int $id): ?User {
        $sql = "SELECT * FROM users WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        $data = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$data) {
            return null;
        }

        return $this->createUserFromData($data);
    }

    /**
     * Atualiza um usuário existente.
     * @param User $user Objeto User com ID preenchido.
     * @return bool Sucesso na operação.
     */
    public function update(User $user, array $addresses): bool {
        $this->db->beginTransaction();
        
        try {
            $sql = "UPDATE users SET 
                        name = :name, 
                        login = :login, 
                        password = :password
                    WHERE id = :id";

            $stmt = $this->db->prepare($sql);
            
            $stmt->bindParam(':id', $user->id, PDO::PARAM_INT);
            $stmt->bindParam(':name', $user->name);
            $stmt->bindParam(':login', $user->login);
            $stmt->bindParam(':password', $user->password);

            $stmt->execute();

            $this->db->commit();
            return true;

        } catch (Exception $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw new Exception("Falha na atualização do usuário: " . $e->getMessage());
        }
    }

    /**
     * Deleta um usuário pelo ID.
     * @param int $id O ID do usuário a ser excluído.
     * @return bool True se o usuário foi excluído, false caso contrário.
     */
    public function delete(int $id): bool {
        $sql = "DELETE FROM users WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->rowCount() > 0;
    }

    /**
     * Método auxiliar para criar um objeto User a partir dos dados do DB.
     * @param array $data Linha de dados do banco de dados.
     * @return User
     */
    private function createUserFromData(array $data): User {
        return new User(
            (int)$data['id'], 
            $data['name'], 
            $data['login'], 
            $data['password'], 
        );
    }
}
