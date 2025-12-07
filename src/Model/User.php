<?php
namespace App\Model;

/**
 * Usuário
 */
class User {
    public ?int $id;
    public string $name;
    public string $login;
    public string $password;

    /**
     * Construtor da entidade User.
     * * @param ?int $id ID do usuário.
     * @param string $name Nome do usuário.
     * @param string $login email usado para login.
     * @param string $password senha do usuário.
     */
    public function __construct(
        ?int $id,
        string $name,
        string $login,
        string $password,
    ) {
        $this->id = $id;
        $this->name = $name;
        $this->login = $login;
        $this->password = $password;
    }
}
