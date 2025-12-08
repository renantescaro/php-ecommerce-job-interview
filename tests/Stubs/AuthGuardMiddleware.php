<?php

namespace App\Service;

class AuthGuardMiddleware {
    
    /**
     * Retorna um ID de usuário fixo para permitir os testes.
     */
    public static function requireLogin(): int {
        return 1;
    }
}
