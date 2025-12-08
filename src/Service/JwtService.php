<?php
namespace App\Service;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Exception;

class JwtService {
    
    private const ALGORITHM = 'HS256';
    private static string $secretKey;

    public function __construct() {
        self::$secretKey = $_ENV['JWT_SECRET_KEY'] ?? 'fallback_secret_key_if_env_fails';
        
        if (self::$secretKey === 'fallback_secret_key_if_env_fails') {
            error_log("JWT_SECRET_KEY não definida no .env!");
        }
    }

    /**
     * Gera um JWT contendo o ID do usuário.
     */
    public function encode(int $userId): string {
        $issuedAt = time();
        $expirationTime = $issuedAt + (60 * 60 * 24); // 1 dia
        
        $payload = [
            'iat'  => $issuedAt,          // Issued At: Momento da criação
            'exp'  => $expirationTime,    // Expiration Time: Expira em 1 dia
            'uid'  => $userId             // User ID: Identificador do usuário
        ];
        
        return JWT::encode($payload, self::$secretKey, self::ALGORITHM);
    }

    /**
     * Decodifica e verifica o JWT.
     * @return object O payload do token, incluindo 'uid'.
     * @throws Exception Se o token for inválido, expirado ou não puder ser decodificado.
     */
    public function decode(string $token): object {
        return JWT::decode($token, new Key(self::$secretKey, self::ALGORITHM));
    }
}
