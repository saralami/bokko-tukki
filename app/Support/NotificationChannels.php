<?php

namespace App\Support;

class NotificationChannels
{
    /**
     * Resolve the delivery channels available for the given notifiable.
     *
     * In-app (database) is always used; e-mail and SMS are added when the
     * notifiable exposes the corresponding contact detail. WhatsApp and other
     * channels can be appended here as they are integrated.
     *
     * @return array<int, string>
     */
    public static function for(object $notifiable): array
    {
        $channels = ['database'];

        if (! empty(data_get($notifiable, 'email'))) {
            $channels[] = 'mail';
        }

        if (! empty(data_get($notifiable, 'phone'))) {
            $channels[] = 'sms';
        }

        return $channels;
    }
}
