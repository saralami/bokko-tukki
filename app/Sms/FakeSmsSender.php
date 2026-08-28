<?php

namespace App\Sms;

use App\Contracts\SmsSender;

class FakeSmsSender implements SmsSender
{
    /**
     * The messages captured during the test run.
     *
     * @var list<array{to: string, message: string}>
     */
    public array $messages = [];

    /**
     * Record the SMS instead of sending it, for assertions in tests.
     */
    public function send(string $to, string $message): void
    {
        $this->messages[] = ['to' => $to, 'message' => $message];
    }
}
