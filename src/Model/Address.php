<?php
namespace App\Model;

/**
 * Endereço
 */
class Address {
    public ?int $id;
    public int $customerId;
    public string $street;
    public ?string $number;
    public string $city;
    public string $state;
    public string $zipCode;

    /**
     * Construtor da entidade Address.
     * * @param ?int $id O ID único do endereço (pode ser null se for novo).
     * @param int $customerId O ID do cliente ao qual este endereço pertence.
     * @param string $street Nome da rua.
     * @param ?string $number Número do endereço (pode ser null se não aplicável).
     * @param string $city Cidade.
     * @param string $state Estado (UF).
     * @param string $zipCode CEP.
     */
    public function __construct(
        ?int $id,
        int $customerId,
        string $street,
        ?string $number,
        string $city,
        string $state,
        string $zipCode
    ) {
        $this->id = $id;
        $this->customerId = $customerId;
        $this->street = $street;
        $this->number = $number;
        $this->city = $city;
        $this->state = $state;
        $this->zipCode = $zipCode;
    }
}
