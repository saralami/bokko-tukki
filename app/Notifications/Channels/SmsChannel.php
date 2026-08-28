<?php

namespace App\Notifications\Channels;

use App\Contracts\SmsSender;
use Illuminate\Notifications\Notification;

class SmsChannel
{
    public function __construct(private SmsSender $sender) {}

    /**
     * Send the given notification through the SMS abstraction.
     */
    public function send(object $notifiable, Notification $notification): void
    {
        if (! method_exists($notification, 'toSms')) {
            return;
        }

        $to = method_exists($notifiable, 'routeNotificationFor')
            ? $notifiable->routeNotificationFor('sms', $notification)
            : null;

        if (empty($to)) {
            return;
        }

        $this->sender->send((string) $to, (string) $notification->toSms($notifiable));
    }
}
