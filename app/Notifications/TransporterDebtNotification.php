<?php

namespace App\Notifications;

use App\Models\Payment;

class TransporterDebtNotification extends BaseNotification
{
    public function __construct(private Payment $payment) {}

    protected function subject(): string
    {
        return 'Dette de commission';
    }

    protected function content(): string
    {
        return "Paiement cash : la commission de {$this->payment->commission_amount} FCFA s'ajoute à votre dette.";
    }

    /**
     * @return array<string, mixed>
     */
    protected function payload(): array
    {
        return ['payment_id' => $this->payment->id, 'commission' => $this->payment->commission_amount];
    }
}
