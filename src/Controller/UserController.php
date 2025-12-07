<?php
namespace App\Controller;

use App\Config\Database;
use App\Controller\BaseController;
use App\Model\User;
use App\Repository\UserRepository;
use App\Service\PasswordService;
use Exception;

class UserController extends BaseController {
    private UserRepository $repository;

    public function __construct() {
        $db = new Database();
        $this->repository = new UserRepository($db);
        $this->passwordService = new PasswordService();

        // if (!AuthService::isLoggedIn()) {
        //     http_response_code(401); 
        //     echo json_encode(['error' => 'Acesso não autorizado. Faça login.']);
        //     exit();
        // }
    }

    /**
     * GET /api/users
     * Lista todos os usuários.
     */
    public function index(): void {
        try {
            $users = $this->repository->findAll();
            $this->respond(200, ['data' => $users]);
        } catch (Exception $e) {
            $this->respond(500, ['error' => 'Erro interno ao listar usuários.', 'details' => $e->getMessage()]);
        }
    }

    /**
     * GET /api/users/{id}
     * Exibe os detalhes de um usuário específico.
     */
    public function show(int $id): void {
        try {
            $user = $this->repository->findById($id);

            if (!$user) {
                $this->respond(404, ['error' => 'usuários não encontrado.']);
                return;
            }
            $this->respond(200, ['data' => $user]);
        } catch (Exception $e) {
            $this->respond(500, ['error' => 'Erro interno ao buscar usuário.', 'details' => $e->getMessage()]);
        }
    }

    /**
     * POST /api/users
     * Inclui um novo usuário.
     */
    public function store(): void {
        $data = $this->getRequestData();

        if (
            empty($data['user']['name']) ||
            empty($data['user']['login']) ||
            empty($data['user']['password'])
        ) {
            $this->respond(400, ['error' => 'Dados de usuário incompleto.']);
            return;
        }

        try {
            $userData = $data['user'];

            $password = $this->passwordService->hash($userData['password']);

            $user = new User(
                null, // ID nulo para inclusão
                $userData['name'],
                $userData['login'],
                $password,
            );

            $newId = $this->repository->save($user);
            
            $this->respond(201, ['message' => 'Usuário criado com sucesso.', 'id' => $newId]);
            
        } catch (Exception $e) {
            $this->respond(500, ['error' => 'Falha ao incluir usuário.', 'details' => $e->getMessage()]);
        }
    }

    /**
     * PUT /api/users/{id}
     * Edita um usuário existente.
     */
    public function update(int $id): void {
        $data = $this->getRequestData();

        if (!$this->repository->findById($id)) {
             $this->respond(404, ['error' => 'Usuário a ser atualizado não encontrado.']);
             return;
        }
        if (empty($data['user']['name'])) {
            $this->respond(400, ['error' => 'Dados de usuário incompleto.']);
            return;
        }

        try {
            $userData = $data['user'];
            $password = $this->passwordService->hash($userData['password']);
            $user = new User(
                $id,
                $userData['name'],
                $userData['login'],
                $password,
            );

            $this->repository->update($user);
            
            $this->respond(200, ['message' => 'Usuário atualizado com sucesso.']);

        } catch (Exception $e) {
            $this->respond(500, ['error' => 'Falha ao atualizar usuário.', 'details' => $e->getMessage()]);
        }
    }

    /**
     * DELETE /api/users/{id}
     * Exclui um usuário
     */
    public function destroy(int $id): void {
        try {
            $success = $this->repository->delete($id);

            if (!$success) {
                $this->respond(404, ['error' => 'Usuário não encontrado ou falha na exclusão.']);
                return;
            }
            $this->respond(204, []); 
        } catch (Exception $e) {
            $this->respond(500, ['error' => 'Erro interno ao excluir usuário.', 'details' => $e->getMessage()]);
        }
    }
}
