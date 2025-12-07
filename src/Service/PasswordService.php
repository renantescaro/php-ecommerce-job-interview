<?php

namespace App\Service;

use App\Repository\UserRepository;
use App\Model\User;

class PasswordService {
    public function hash(string $password): string {
        return password_hash($password, PASSWORD_BCRYPT); 
    }
}
