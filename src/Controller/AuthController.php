<?php
namespace App\Controller;

use App\Config\Database;
use Exception;

class AuthController {
    public function __construct() {
        $db = new Database();
    }

    protected function getRequestData(): array {
        // Ambiente de teste
        if (defined('PHPUNIT_RUNNING') && isset($GLOBALS['mock_http_input'])) { 
            return json_decode($GLOBALS['mock_http_input'], true) ?? [];
        }

        // Produção
        return json_decode(file_get_contents('php://input'), true) ?? [];
    }

    /**
     * Define o cabeçalho Content-Type para JSON e trata a resposta.
     * @param int $statusCode Código HTTP de resposta.
     * @param array $data Dados a serem serializados para JSON.
     */
    protected function respond(int $statusCode, array $data): void {
        header('Content-Type: application/json');
        http_response_code($statusCode);
        echo json_encode($data);
    }
    
    /**
     * GET /api/auth/login
     * Faz o login do usuário.
     */
    public function login(): void {
        try {
            $this->respond(200, ['data' => ['token' => 'aaa', 'userData' => 'vish']]);
        } catch (Exception $e) {
            $this->respond(500, ['error' => 'Erro interno ao fazer login.', 'details' => $e->getMessage()]);
        }
    }

    public function logout(): void {
        $this->respond(200, ['data' => []]);
    }
}
