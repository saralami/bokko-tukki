<?php

namespace App\Contracts;

interface SmsSender
{
    /**
     * Send an SMS message to the given phone number.
     *
     * Implementations plug a concrete provider (Orange, Twilio, WhatsApp, ...)
     * without the business code ever depending on it.
     */
    public function send(string $to, string $message): void;
}
