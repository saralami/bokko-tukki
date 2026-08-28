<?php

namespace App\Notifications;

use App\Models\Payment;

class TransporterPaymentReceivedNotification extends BaseNotification
{
    public function __construct(private Payment $payment) {}

    protected function subject(): string
    {
        return 'Paiement reçu';
    }

    protected function content(): string
    {
        return "Paiement de {$this->payment->amount} FCFA reçu (commission {$this->payment->commission_amount} FCFA).";
    }

    /**
     * @return array<string, mixed>
     */
    protected function payload(): array
    {
        return ['payment_id' => $this->payment->id, 'amount' => $this->payment->amount];
    }
}
