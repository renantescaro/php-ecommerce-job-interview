<?php
namespace App\Service;

use App\Repository\UserRepository;
use App\Model\User;

/**
 * Serviço responsável pela lógica de autenticação.
 */
class AuthService {
    private UserRepository $userRepository;
    private PasswordService $passwordService;

    public function __construct(UserRepository $userRepository) {
        $this->userRepository = $userRepository;
        $this->passwordService = new PasswordService();
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
}
