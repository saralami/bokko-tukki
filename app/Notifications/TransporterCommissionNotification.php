<?php

namespace App\Notifications;

use App\Models\Payment;

class TransporterCommissionNotification extends BaseNotification
{
    public function __construct(private Payment $payment) {}

    protected function subject(): string
    {
        return 'Commission Allo Dakar';
    }

    protected function content(): string
    {
        return "Une commission de {$this->payment->commission_amount} FCFA a été appliquée.";
    }

    /**
     * @return array<string, mixed>
     */
    protected function payload(): array
    {
        return ['payment_id' => $this->payment->id, 'commission' => $this->payment->commission_amount];
    }
}
