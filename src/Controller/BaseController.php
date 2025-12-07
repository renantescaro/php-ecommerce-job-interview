<?php
namespace App\Controller;

abstract class BaseController {

    /**
     * Define o cabeçalho Content-Type para JSON e trata a resposta.
     * Este é o ponto onde o Mock Parcial é aplicado nos testes.
     */
    protected function respond(int $statusCode, array $data): void {
        header('Content-Type: application/json');
        http_response_code($statusCode);
        echo json_encode($data);
    }

    /**
     * Lida com a leitura dos dados de entrada HTTP (php://input).
     * Lê o mock global em ambiente de teste (PHPUNIT_RUNNING).
     */
    protected function getRequestData(): array {
        // Ambiente de teste
        if (defined('PHPUNIT_RUNNING') && isset($GLOBALS['mock_http_input'])) { 
            return json_decode($GLOBALS['mock_http_input'], true) ?? [];
        }

        // Produção
        return json_decode(file_get_contents('php://input'), true) ?? [];
    }
}
