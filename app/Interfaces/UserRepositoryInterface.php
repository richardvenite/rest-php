<?php

namespace App\Interfaces;

interface UserRepositoryInterface 
{
    public function getUserByIdentity(string $identity);
}