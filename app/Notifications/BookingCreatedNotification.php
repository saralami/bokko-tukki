<?php

namespace App\Notifications;

use App\Models\Booking;

class BookingCreatedNotification extends BaseNotification
{
    public function __construct(private Booking $booking) {}

    protected function subject(): string
    {
        return 'Réservation créée';
    }

    protected function content(): string
    {
        return "Votre réservation {$this->booking->reference} a bien été créée.";
    }

    /**
     * @return array<string, mixed>
     */
    protected function payload(): array
    {
        return ['booking_id' => $this->booking->id, 'reference' => $this->booking->reference];
    }
}
