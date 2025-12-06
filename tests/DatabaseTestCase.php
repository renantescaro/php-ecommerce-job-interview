<?php
namespace Tests;

use PHPUnit\Framework\TestCase;
use Dotenv\Dotenv;
use App\Config\Database;
use PDO;
use Exception;

/**
 * Classe Base para todos os testes que requerem uma conexão com o banco de dados.
 * É responsável por carregar o .env e limpar as tabelas antes de cada teste.
 */
abstract class DatabaseTestCase extends TestCase {
    
    protected PDO $connection; 
    protected Database $db;
    
    // As tabelas devem ser na ordem de exclusão
    private array $testTables = ['addresses', 'customers', 'users']; 

    protected function setUp(): void {
        parent::setUp();
        
        // carrega o env
        $rootPath = dirname(__DIR__);
        $dotenv = Dotenv::createImmutable($rootPath);
        $dotenv->safeLoad();

        // sobrescreve as variaveis de ambiente
        $_ENV['DB_HOST'] = $_ENV['TEST_DB_HOST'];
        $_ENV['DB_NAME'] = $_ENV['TEST_DB_NAME'];
        $_ENV['DB_USER'] = $_ENV['TEST_DB_USER'];
        $_ENV['DB_PASS'] = $_ENV['TEST_DB_PASS'];

        $this->db = new Database();
        
        try {
            $this->connection = $this->db->getConnection();
        } catch (\Exception $e) {
            $this->fail("Falha ao conectar ao banco de dados de teste. Verifique o .env: " . $e->getMessage());
        }

        $this->cleanDatabase(); 
    }
    
    /**
     * Garante que as tabelas existam e as limpa (TRUNCATE) antes de cada teste.
     */
    protected function cleanDatabase(): void {
        // Desliga temporariamente a verificação de Foreign Keys
        $this->connection->exec('SET FOREIGN_KEY_CHECKS = 0');

        foreach ($this->testTables as $table) {
            try {
                // Tenta executar um TRUNCATE
                $this->connection->exec("TRUNCATE TABLE {$table}");

            } catch (\PDOException $e) {
                // Se a tabela não existe (SQLSTATE 42S02)
                if (strpos($e->getMessage(), '42S02') !== false || strpos($e->getMessage(), 'doesn\'t exist') !== false) {
                    
                    $dbName = $_ENV['DB_NAME'] ?? 'TEST_DB';
                    throw new Exception(
                        "A tabela '{$table}' não existe no banco de dados '{$dbName}'.");
                }
                
                $this->connection->exec('SET FOREIGN_KEY_CHECKS = 1');
                throw $e; 
            }
        }
        
        // Liga novamente a verificação de Foreign Keys
        $this->connection->exec('SET FOREIGN_KEY_CHECKS = 1');
    }
}
