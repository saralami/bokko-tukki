<?php

namespace App\Notifications;

use App\Models\Booking;

class DriverNewBookingNotification extends BaseNotification
{
    public function __construct(private Booking $booking) {}

    protected function subject(): string
    {
        return 'Nouvelle réservation';
    }

    protected function content(): string
    {
        return "Nouvelle réservation {$this->booking->reference} ({$this->booking->seats} place(s)) sur votre trajet.";
    }

    /**
     * @return array<string, mixed>
     */
    protected function payload(): array
    {
        return ['booking_id' => $this->booking->id, 'trip_id' => $this->booking->trip_id];
    }
}
