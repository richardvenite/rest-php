<?php

namespace App\Mail;

use App\Jobs\NotifyUserJob;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;

class Mail 
{
    protected $notifyHost;

    public function __construct() 
    {
        $this->notifyHost = env('NOTIFY_HOST', '');
    }

    public function sendNotify(string $email, string $message) 
    {
        try {
            $client = new Client();
            $response = $client->post($this->notifyHost, [
                'email' => $email,
                'message' => $message
            ]);

            if ($response->getStatusCode() == 200) {
                $responseData = json_decode($response->getBody()->getContents());

                if ($responseData->message == 'Success') {
                    return true;
                }
            }

            return false;
        } catch (\Throwable $exception) {
            Log::error($exception->getMessage());
            NotifyUserJob::dispatch(['email' => $email, 'message' => $message])
                ->onQueue('mail')
                ->delay(now()->addMinutes(10));

            return false;
        }
    }
}