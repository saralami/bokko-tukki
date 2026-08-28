<?php

namespace App\Notifications;

use App\Models\Booking;

class TransporterNewBookingNotification extends BaseNotification
{
    public function __construct(private Booking $booking) {}

    protected function subject(): string
    {
        return 'Nouvelle réservation';
    }

    protected function content(): string
    {
        return "Réservation {$this->booking->reference} de {$this->booking->total_amount} FCFA ({$this->booking->seats} place(s)).";
    }

    /**
     * @return array<string, mixed>
     */
    protected function payload(): array
    {
        return ['booking_id' => $this->booking->id];
    }
}
