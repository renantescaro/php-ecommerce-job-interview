<?php
namespace App\Controller;

use App\Service\AuthService;
use App\Controller\BaseController;
use App\Config\Database;
use App\Repository\UserRepository;
use Exception;

class AuthController extends BaseController {
    public function __construct() {
        $db = new Database();
        $userRepository = new UserRepository($db);
        $this->authService = new AuthService($userRepository);
        
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
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
                $_SESSION['user_id'] = $user->id;
                $_SESSION['login'] = $user->login;
                $_SESSION['is_logged_in'] = true;

                $this->respond(200, 
                    ['message' => 'Login realizado com sucesso.',
                        'user' => ['login' => $user->login
                    ]]
                );
                return;
            }

            $this->respond(401, ['error' => 'Credenciais inválidas.']);

        } catch (Exception $e) {
            $this->respond(500, ['error' => 'Erro interno ao fazer login.', 'details' => $e->getMessage()]);
        }
    }

    /**
     * POST /api/auth/logout
     * Encerra a sessão do usuário
     */
    public function logout(): void {
        $_SESSION = [];

        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }

        session_destroy();

        $this->respond(200, ['message' => 'Logout realizado com sucesso.']);
    }
}
