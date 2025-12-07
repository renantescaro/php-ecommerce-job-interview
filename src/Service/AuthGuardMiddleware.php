<?php
namespace App\Service;

use App\Service\AuthService;

/**
 * Serviço/Guardião para verificar a autenticação antes de executar Controllers.
 */
class AuthGuardMiddleware {
    
    /**
     * Verifica se o usuário está logado e, se não, encerra a requisição.
     */
    public static function requireLogin(): void {
        if (!AuthService::isLoggedIn()) { 
            http_response_code(401); 
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Acesso negado. Requer autenticação.']);
            exit(); 
        }
    }
}
