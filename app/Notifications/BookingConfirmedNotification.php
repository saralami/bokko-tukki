<?php

namespace App\Notifications;

use App\Models\Booking;

class BookingConfirmedNotification extends BaseNotification
{
    public function __construct(private Booking $booking) {}

    protected function subject(): string
    {
        return 'Réservation confirmée';
    }

    protected function content(): string
    {
        return "Votre réservation {$this->booking->reference} est confirmée.";
    }

    /**
     * @return array<string, mixed>
     */
    protected function payload(): array
    {
        return ['booking_id' => $this->booking->id, 'reference' => $this->booking->reference];
    }
}
