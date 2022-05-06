<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\User;
use App\Repositories\UserRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;

class UserRepositoryTest extends TestCase
{    
    use RefreshDatabase;
    
    public function test_get_user_by_identity_return_user()
    {
        $repository = new UserRepository();
        $expected = User::factory(['identity' => '12345678999'])->create();
        $user = $repository->getUserByIdentity('12345678999');
        $this->assertInstanceOf(User::class, $user);
        $this->assertEquals($expected->identity, $user->identity);
    }

    public function test_get_user_by_identity_return_didnt_find_user()
    {
        $repository = new UserRepository();
        $user = $repository->getUserByIdentity('12345678999');
        $this->assertNull($user);
    }
}
