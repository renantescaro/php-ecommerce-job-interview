<?php
namespace App\Service;

use App\Repository\UserRepository;
use App\Model\User;

/**
 * Serviço responsável pela lógica de autenticação.
 */
class AuthService {
    private UserRepository $userRepository;

    public function __construct(UserRepository $userRepository) {
        $this->userRepository = $userRepository;
        $this->passwordService = new PasswordService();
        
        // Garante que a sessão esteja iniciada antes de qualquer uso
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    /**
     * Tenta logar o usuário com login e senha.
     * @param string $login
     * @param string $password
     * @return User|null Retorna o objeto User se autenticação for bem-sucedida.
     */
    public function authenticate(string $login, string $password): ?User {
        $user = $this->userRepository->findByLogin($login);

        if (!$user) {
            return null; 
        }

        if (password_verify($password, $user->password)) {
            return $user; 
        }

        return null; 
    }

    /**
     * Verifica se o usuário está logado.
     * Deve ser chamada pelo AuthGuard Middleware.
     * @return bool
     */
    public static function isLoggedIn(): bool {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        return isset($_SESSION['is_logged_in']) && $_SESSION['is_logged_in'] === true;
    }
}
