<?php
namespace App\Controller;

use App\Service\AuthService;
use App\Controller\BaseController;
use App\Config\Database;
use App\Repository\UserRepository;
use App\Service\JwtService;
use Exception;

class AuthController extends BaseController {

    public function __construct() {
        $db = new Database();
        $userRepository = new UserRepository($db);
        $this->authService = new AuthService($userRepository);
        $this->jwtService = new JwtService();
    }
    
    /**
     * GET /api/auth/login
     * Faz o login do usuário.
     */
    public function login(): void {
        $data = $this->getRequestData();

        $login = $data['login'] ?? '';
        $password = $data['password'] ?? '';

        if (empty($login) || empty($password)) {
            $this->respond(400, ['error' => 'Usuário e senha são obrigatórios.']);
            return;
        }

        try {        
            $user = $this->authService->authenticate($login, $password);

            if ($user) {
                $token = $this->jwtService->encode($user->id);

                $this->respond(200, 
                    ['message' => 'Login realizado com sucesso.',
                        'user' => [
                            'token' => $token,
                            'id' => $user->id,
                            'name' => $user->name,
                            'login' => $user->login,
                        ]
                    ]
                );
                return;
            }

            $this->respond(401, ['error' => 'Credenciais inválidas.']);

        } catch (Exception $e) {
            $this->respond(500, ['error' => 'Erro interno ao fazer login.', 'details' => $e->getMessage()]);
        }
    }
}
