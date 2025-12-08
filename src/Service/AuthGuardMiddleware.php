<?php
namespace App\Service;

use App\Service\JwtService;
use Exception;

class AuthGuardMiddleware {
    
    /**
     * Verifica o token JWT no cabeçalho Authorization.
     * @return int O ID do usuário (uid) se o token for válido.
     */
    public static function requireLogin(): int {
        $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';

        if (empty($authHeader) || !str_starts_with($authHeader, 'Bearer ')) {
            self::denyAccess();
        }

        // Extrai o token após "Bearer "
        $token = substr($authHeader, 7);

        try {
            $jwtService = new JwtService();
            $decoded = $jwtService->decode($token);
            
            // Retorna o ID do usuário contido no payload
            return $decoded->uid; 
        } catch (Exception $e) {
            // Token inválido, expirado, ou erro de decodificação
            self::denyAccess();
        }
    }
    
    private static function denyAccess(): void {
        http_response_code(401); 
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Acesso negado. Token inválido ou ausente.']);
        exit();
    }
}
