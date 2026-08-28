<?php

namespace App\Notifications;

use App\Support\NotificationChannels;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

abstract class BaseNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Get the delivery channels for the notification.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return NotificationChannels::for($notifiable);
    }

    /**
     * The notification subject / title.
     */
    abstract protected function subject(): string;

    /**
     * The notification message body, shared across every channel.
     */
    abstract protected function content(): string;

    /**
     * Extra structured data stored with the in-app notification.
     *
     * @return array<string, mixed>
     */
    protected function payload(): array
    {
        return [];
    }

    /**
     * Get the in-app (database) representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return ['title' => $this->subject(), 'body' => $this->content(), ...$this->payload()];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject($this->subject())
            ->line($this->content());
    }

    /**
     * Get the SMS representation of the notification.
     */
    public function toSms(object $notifiable): string
    {
        return $this->content();
    }
}
