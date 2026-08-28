<?php

namespace App\Notifications;

use App\Models\Booking;

class BoardingConfirmedNotification extends BaseNotification
{
    public function __construct(private Booking $booking) {}

    protected function subject(): string
    {
        return 'Embarquement confirmé';
    }

    protected function content(): string
    {
        return "Votre embarquement pour la réservation {$this->booking->reference} est confirmé. Bon voyage !";
    }

    /**
     * @return array<string, mixed>
     */
    protected function payload(): array
    {
        return ['booking_id' => $this->booking->id, 'reference' => $this->booking->reference];
    }
}
