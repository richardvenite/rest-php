<?php

namespace Tests\Unit;

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

class UserServiceTest extends TestCase
{
    use RefreshDatabase;
    
    protected function getService()
    {
        $repository = new UserRepository();
        return new UserService($repository);
    }

    public function test_has_cash_wallet_return_true()
    {
        $wallet = Wallet::factory(['cash' => 10])->create();
        $user = User::find($wallet->user_id);
        $this->assertTrue($this->getService()->hasCashWallet($user->identity, 5));
    }

    public function test_has_cash_wallet_return_false_with_cash_zero()
    {
        $wallet = Wallet::factory()->create();
        $user = User::find($wallet->user_id);
        $this->assertFalse($this->getService()->hasCashWallet($user->identity, 5));
    }

    public function test_has_cash_wallet_exception()
    {
        $mockUserRepository = Mockery::mock(UserRepository::class . '[getUserByIdentity]', function ($mock) {
            $mock->shouldReceive('getUserByIdentity')->andThrow(new \Exception('Teste'));
        });

        Log::shouldReceive('error')
            ->once()
            ->with('Teste');

        $service = new UserService($mockUserRepository);

        $this->assertFalse($service->hasCashWallet('12345678909', 5));
    }
    
    public function test_schedule_notify()
    {
        Bus::fake();

        $arg = ['email' => 'email', 'message' => 'You received 10 from name'];
        
        $service = $this->getService();
        
        $service->scheduleNotify("name", "email", 10);

        Bus::assertDispatched(function (NotifyUserJob $job) use ($arg) {
            return $job->args === $arg;
        });
    }

    public function test_schedule_notify_exception()
    {
        $mockService = Mockery::mock(UserService::class)->makePartial();
        $mockService->shouldReceive('dispatchNotifyUserJob')->andThrow(new \Exception('Teste'));

        Log::shouldReceive('error')
            ->once()
            ->with('Teste');

        $mockService->scheduleNotify("name", "email", 10);
    }

    public function test_authorization()
    {
        $body = Mockery::mock(Stream::class)->makePartial();
        $body->shouldReceive('getContents')->andReturn('{"message": "Autorizado"}');
        $response = Mockery::mock(Response::class . '[getBody]', function ($mock) use ($body) {
            $mock->shouldReceive('getBody')->andReturn($body);
        });

        $client = Mockery::mock(Client::class)->makePartial();
        $client->shouldReceive('get')->andReturn($response);

        $mockService = Mockery::mock(UserService::class)->makePartial();
        $mockService->shouldReceive('getGuzzleClient')->andReturn($client);

        $result = $mockService->authorization();
        $this->assertTrue($result);
    }

    public function test_authorization_status_code_different_200()
    {
        $response = Mockery::mock(Response::class . '[getStatusCode]', function ($mock) {
            $mock->shouldReceive('getStatusCode')->andReturn(500);
        });

        $client = Mockery::mock(Client::class)->makePartial();
        $client->shouldReceive('get')->andReturn($response);

        $mockService = Mockery::mock(UserService::class)->makePartial();
        $mockService->shouldReceive('getGuzzleClient')->andReturn($client);

        Log::shouldReceive('error')
            ->never();

        $result = $mockService->authorization();
        $this->assertFalse($result);
    }

    public function test_authorization_exception()
    {
        $mockService = Mockery::mock(UserService::class)->makePartial();
        $mockService->shouldReceive('getGuzzleClient')->andThrow(new \Exception('Teste'));

        Log::shouldReceive('error')
            ->once()
            ->with('Teste');

        $result = $mockService->authorization();
        $this->assertFalse($result);
    }

    public function test_transfer_exception()
    {
        $mockUserRepository = Mockery::mock(UserRepository::class . '[getUserByIdentity]', function ($mock) {
            $mock->shouldReceive('getUserByIdentity')->andThrow(new \Exception('Teste'));
        });

        Log::shouldReceive('error')
            ->once()
            ->with('Teste');

        $service = new UserService($mockUserRepository);

        $this->assertFalse($service->transfer('12345678909', '12345678999', 5));
    }

    public function test_transfer_success()
    {
        $repository = new UserRepository();

        $mockService = Mockery::mock(UserService::class, [$repository])->makePartial();
        $mockService->shouldReceive('authorization')->andReturn(true);
        $mockService->shouldReceive('scheduleNotify')->andReturn(true);

        $wallets = Wallet::factory(['cash' => 10])->count(2)->create();

        $payer = User::find($wallets[0]->user_id);
        $payee = User::find($wallets[1]->user_id);

        $this->assertTrue($mockService->transfer($payer->identity, $payee->identity, 5));

        $payer = User::find($wallets[0]->user_id);
        $payee = User::find($wallets[1]->user_id);

        $newValuePayerWallet = $payer->wallet->cash;
        $newValuePayeeWallet = $payee->wallet->cash;

        $this->assertEquals(5.00, $newValuePayerWallet);
        $this->assertEquals(15.00, $newValuePayeeWallet);
    }

    public function test_transfer_rollback_transaction()
    {
        $repository = new UserRepository();

        $mockService = Mockery::mock(UserService::class, [$repository])->makePartial();
        $mockService->shouldReceive('authorization')->andThrow(new \Exception('Teste'));

        $wallets = Wallet::factory(['cash' => 10])->count(2)->create();

        $payer = User::find($wallets[0]->user_id);
        $payee = User::find($wallets[1]->user_id);

        Log::shouldReceive('error')
            ->once()
            ->with('Teste');

        $this->assertFalse($mockService->transfer($payer->identity, $payee->identity, 5));

        $payer = User::find($wallets[0]->user_id);
        $payee = User::find($wallets[1]->user_id);

        $newValuePayerWallet = $payer->wallet->cash;
        $newValuePayeeWallet = $payee->wallet->cash;

        $this->assertEquals(10.00, $newValuePayerWallet);
        $this->assertEquals(10.00, $newValuePayeeWallet);
    }
}
