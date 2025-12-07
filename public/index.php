<?php

declare(strict_types=1);
error_reporting(E_ALL);
ini_set('display_errors', '1');

define('ROOT_PATH', dirname(__DIR__));

require ROOT_PATH . '/vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(ROOT_PATH);
$dotenv->safeLoad();

use App\Config\Database;
use App\Controller\AuthController;
use App\Controller\CustomerController;
use App\Controller\UserController;
use App\Service\CorsMiddleware;

CorsMiddleware::handle();

$uri = $_SERVER['REQUEST_URI'];
$method = $_SERVER['REQUEST_METHOD'];

// Limpar a URI para ignorar parâmetros de query
$uri = strtok($uri, '?');
// Remover barras extras
$uri = trim($uri, '/');

if (strpos($uri, 'api/') === 0) {
    $routeParts = explode('/', substr($uri, 4));
} else {
    $routeParts = explode('/', $uri);
}

$resource = array_shift($routeParts);
$id = null;
$controller = null;
$action = null;

if ($resource === 'auth') {
    $controller = new AuthController();
    $action = array_shift($routeParts) ?? '';

    if ($method === 'POST' && $action === 'login') {
        $controller->login();
    } elseif ($method === 'POST' && $action === 'logout') {
        $controller->logout();
    } else {
        http_response_code(405);
        echo json_encode(['error' => 'Method Not Allowed']);
    }
    return;
}

$controller = getController($resource);

if ($controller === null) {
    http_response_code(404);
    echo json_encode(['error' => 'Not Found']);
}

if (!empty($routeParts)) {
    $id = array_shift($routeParts);
}

if ($method === 'GET' && $id) {
    $controller->show((int)$id);
} elseif ($method === 'GET' && !$id) {
    $controller->index();
} elseif ($method === 'POST') {
    $controller->store();
} elseif ($method === 'PUT' && $id) {
    $controller->update((int)$id);
} elseif ($method === 'DELETE' && $id) {
    $controller->destroy((int)$id);
} else {
    http_response_code(405);
    echo json_encode(['error' => 'Method Not Allowed']);
}


function getController($resource) {
    if ($resource === 'users') {
        $controller = new UserController();
        return $controller;
    }

    if ($resource === 'customers') {
        $controller = new CustomerController();
        return $controller;
    }
    return null;
}
