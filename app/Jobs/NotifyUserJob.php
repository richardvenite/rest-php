<?php

namespace App\Jobs;

use App\Mail\Mail;
use Exception;

class NotifyUserJob extends AbstractJob
{
    public function __construct($args)
    {
        parent::__construct($args);
        $this->job = "NotifyUserJob";
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $mail = new Mail();
        $mail->sendNotify($this->args['email'], $this->args['message']);
    }
}
