<?php

namespace App\Sms;

use App\Contracts\SmsSender;
use Illuminate\Support\Facades\Log;

class LogSmsSender implements SmsSender
{
    /**
     * Default driver: log the SMS instead of contacting a real provider.
     */
    public function send(string $to, string $message): void
    {
        Log::info("[SMS] {$to}: {$message}");
    }
}
