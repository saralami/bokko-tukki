<?php

namespace App\Notifications;

use App\Models\Booking;

class PaymentConfirmedNotification extends BaseNotification
{
    public function __construct(private Booking $booking) {}

    protected function subject(): string
    {
        return 'Paiement confirmé';
    }

    protected function content(): string
    {
        return "Le paiement de {$this->booking->total_amount} FCFA pour la réservation {$this->booking->reference} est confirmé.";
    }

    /**
     * @return array<string, mixed>
     */
    protected function payload(): array
    {
        return ['booking_id' => $this->booking->id, 'amount' => $this->booking->total_amount];
    }
}
