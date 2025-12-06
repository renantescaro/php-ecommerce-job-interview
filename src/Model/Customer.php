<?php
namespace App\Model;

/**
 * Cliente
 */
class Customer {
    public ?int $id;
    public string $name;
    public string $birthDate;
    public string $cpf;
    public ?string $rg;
    public ?string $phone;
    public array $addresses = []; 

    /**
     * Construtor da entidade Customer.
     * * @param ?int $id ID do cliente.
     * @param string $name Nome do cliente.
     * @param string $birthDate Data de nascimento.
     * @param string $cpf CPF.
     * @param ?string $rg RG (opcional).
     * @param ?string $phone Telefone (opcional).
     */
    public function __construct(
        ?int $id,
        string $name,
        string $birthDate,
        string $cpf,
        ?string $rg,
        ?string $phone
    ) {
        $this->id = $id;
        $this->name = $name;
        $this->birthDate = $birthDate;
        $this->cpf = $cpf;
        $this->rg = $rg;
        $this->phone = $phone;
    }
}
