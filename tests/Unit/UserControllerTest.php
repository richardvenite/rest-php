<?php

namespace Tests\Unit;

use App\Http\Controllers\Api\UserController;
use App\Jobs\NotifyUserJob;
use Tests\TestCase;
use App\Models\User;
use App\Models\Wallet;
use App\Services\UserService;
use App\Repositories\UserRepository;
use GuzzleHttp\Client;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Log;
use Mockery;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\Stream;
use Illuminate\Http\Request;

class UserControllerTest extends TestCase
{
    use RefreshDatabase;
    
    public function test_transaction_validator_users_exist()
    {
        $arg = [
            'payer' =>'12345678909',
            'payee' => '12345678900',
            'value' => 8.5
        ];

        $request = Mockery::mock(Request::class)->makePartial();
        $request->shouldReceive('all')->andReturn($arg);
        
        $controller = new UserController(new UserRepository());
        $result = $controller->transaction($request);

        $expected = '{"error":{"payer":["The selected payer is invalid."],"payee":["The selected payee is invalid."]}}';
        $this->assertEquals($expected, $result->getContent());
    }

    public function test_transaction_validator_invalid_payer()
    {
        $user = User::factory()->create();

        $arg = [
            'payer' => "123456789090",
            'payee' => $user->identity,
            'value' => 8.5
        ];

        $request = Mockery::mock(Request::class)->makePartial();
        $request->shouldReceive('all')->andReturn($arg);
        
        $controller = new UserController(new UserRepository());
        $result = $controller->transaction($request);

        $expected = '{"error":{"payer":["The payer must not be greater than 11 characters."]}}';
        $this->assertEquals($expected, $result->getContent());
    }

    public function test_transaction_validator_invalid_payee()
    {
        $user = User::factory()->create();

        $arg = [
            'payer' => $user->identity,
            'payee' => '123456789',
            'value' => 8.5
        ];

        $request = Mockery::mock(Request::class)->makePartial();
        $request->shouldReceive('all')->andReturn($arg);
        
        $controller = new UserController(new UserRepository());
        $result = $controller->transaction($request);

        $expected = '{"error":{"payee":["The payee must be at least 11 characters."]}}';
        $this->assertEquals($expected, $result->getContent());
    }

    public function test_transaction_validator_different_users()
    {
        $user = User::factory()->create();

        $arg = [
            'payer' => $user->identity,
            'payee' => $user->identity,
            'value' => 8.5
        ];

        $request = Mockery::mock(Request::class)->makePartial();
        $request->shouldReceive('all')->andReturn($arg);
        
        $controller = new UserController(new UserRepository());
        $result = $controller->transaction($request);

        $expected = '{"error":{"payer":["The payer and payee must be different."]}}';
        $this->assertEquals($expected, $result->getContent());
    }

    public function test_transaction_validator_required()
    {
        $arg = [
            'payer' => '',
            'payee' => '',
            'value' => 0
        ];

        $request = Mockery::mock(Request::class)->makePartial();
        $request->shouldReceive('all')->andReturn($arg);
        
        $controller = new UserController(new UserRepository());
        $result = $controller->transaction($request);

        $expected = '{"error":{"payer":["The payer field is required."],"payee":["The payee field is required."],"value":["The value must be greater than 0."]}}';
        $this->assertEquals($expected, $result->getContent());
    }

    public function test_transaction_payer_dont_have_cash_wallet()
    {
        $users = User::factory()->count(2)->create();

        $arg = [
            'payer' => $users[0]->identity,
            'payee' => $users[1]->identity,
            'value' => 8.5
        ];

        $request = Mockery::mock(Request::class)->makePartial();
        $request->shouldReceive('all')->andReturn($arg);

        $mockService = Mockery::mock(UserService::class)->makePartial();
        $mockService->shouldReceive('hasCashWallet')->andReturn(false);
        
        $repository = new UserRepository();
        $mockController = Mockery::mock(UserController::class, [$repository])->makePartial();
        $mockController->shouldReceive('getService')->andReturn($mockService);
        
        $result = $mockController->transaction($request);

        $expected = '{"error":{"payer":"Payer doesn\'t have enough value in wallet"}}';
        $this->assertEquals($expected, $result->getContent());
    }

    public function test_transaction_transfer_error()
    {
        $users = User::factory()->count(2)->create();

        $arg = [
            'payer' => $users[0]->identity,
            'payee' => $users[1]->identity,
            'value' => 8.5
        ];

        $request = Mockery::mock(Request::class)->makePartial();
        $request->shouldReceive('all')->andReturn($arg);

        $mockService = Mockery::mock(UserService::class)->makePartial();
        $mockService->shouldReceive('hasCashWallet')->andReturn(true);
        $mockService->shouldReceive('transfer')->andReturn(false);
        
        $repository = new UserRepository();
        $mockController = Mockery::mock(UserController::class, [$repository])->makePartial();
        $mockController->shouldReceive('getService')->andReturn($mockService);
        
        $result = $mockController->transaction($request);

        $expected = '{"error":"Unexpected error please try again"}';
        $this->assertEquals($expected, $result->getContent());
    }

    public function test_transaction_success()
    {
        $users = User::factory()->count(2)->create();

        $arg = [
            'value' => 8.5,
            'payer' => $users[0]->identity,
            'payee' => $users[1]->identity
        ];

        $request = Mockery::mock(Request::class)->makePartial();
        $request->shouldReceive('all')->andReturn($arg);

        $mockService = Mockery::mock(UserService::class)->makePartial();
        $mockService->shouldReceive('hasCashWallet')->andReturn(true);
        $mockService->shouldReceive('transfer')->andReturn(true);
        
        $repository = new UserRepository();
        $mockController = Mockery::mock(UserController::class, [$repository])->makePartial();
        $mockController->shouldReceive('getService')->andReturn($mockService);
        
        $result = $mockController->transaction($request);

        $this->assertEquals(json_encode($arg), $result->getContent());
    }
}
