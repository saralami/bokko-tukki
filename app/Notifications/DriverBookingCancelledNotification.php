<?php

namespace App\Notifications;

use App\Models\Booking;

class DriverBookingCancelledNotification extends BaseNotification
{
    public function __construct(private Booking $booking) {}

    protected function subject(): string
    {
        return 'Réservation annulée';
    }

    protected function content(): string
    {
        return "La réservation {$this->booking->reference} de votre trajet a été annulée.";
    }

    /**
     * @return array<string, mixed>
     */
    protected function payload(): array
    {
        return ['booking_id' => $this->booking->id, 'trip_id' => $this->booking->trip_id];
    }
}
