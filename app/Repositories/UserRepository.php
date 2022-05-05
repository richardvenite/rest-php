<?php
namespace App\Repositories;

use App\Interfaces\UserRepositoryInterface;
use App\Models\User;

class UserRepository implements UserRepositoryInterface
{
    public function getUserByIdentity(string $identity)
    {
        return User::where('identity', '=', $identity)->firstOrFail();
    }

}
