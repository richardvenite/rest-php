<?php

namespace App\Services;

use App\Interfaces\UserRepositoryInterface;
use App\Jobs\NotifyUserJob;
use App\Models\User;
use GuzzleHttp\Client;
use Throwable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class UserService
{
    private UserRepositoryInterface $userRepository;

    public function __construct(UserRepositoryInterface $userRepository) 
    {
        $this->userRepository = $userRepository;
    }

    public function transfer(string $payer, string $payee, string $value): bool
    {
        try {
            $payerUser = $this->userRepository->getUserByIdentity($payer);
            $payeeUser = $this->userRepository->getUserByIdentity($payee);

            DB::transaction(function () use ($payerUser, $payeeUser, $value) {
                $payerUser->wallet->cash = floatval($payerUser->wallet->cash - $value);
                $payeeUser->wallet->cash = floatval($payeeUser->wallet->cash + $value);
                
                $payerUser->wallet->save();
                $payeeUser->wallet->save();

                if (!$this->authorization()) {
                   throw new \Exception("error on athorization");
                }
            });

            $this->scheduleNotify($payerUser->name, $payeeUser->email, $value);
            
            return true;
        } catch (Throwable $exception) {
            Log::error($exception->getMessage());
            return false;
        } 
    }

    public function hasCashWallet(string $identity, float $value): bool
    {
        try {
            $user = $this->userRepository->getUserByIdentity($identity);
    
            if ($user->wallet->cash == 0 || $user->wallet->cash < $value) {
                return false;
            }

            return true;
        } catch (Throwable $exception) {
            Log::error($exception->getMessage());
            return false;
        }
    }

    public function authorization(): bool
    {
        try {
            $uri = env('AUTHORIZATOR_HOST', '');
            $client = $this->getGuzzleClient();
            $response = $client->get($uri);

            if ($response->getStatusCode() == 200) {
                $responseData = json_decode($response->getBody()->getContents());
                
                if ($responseData->message == 'Autorizado') {
                    return true;
                }
            }

            return false;
        } catch (Throwable $exception) {
            Log::error($exception->getMessage());
            return false;
        }
    }

    public function scheduleNotify(string $payerName, string $payeeEmail, string $value)
    {
        try {
            $payeeMessage = "You received {$value} from {$payerName}";
            $this->dispatchNotifyUserJob(['email' => $payeeEmail, 'message' => $payeeMessage]);
        } catch (Throwable $exception) {
            Log::error($exception->getMessage());
        }
    }

    public function dispatchNotifyUserJob(array $args): void
    {
        NotifyUserJob::dispatch($args)
            ->onQueue('mail')
            ->delay(now()->addMinutes(10));
    }

    public function getGuzzleClient()
    {
        return new Client();
    }
}
