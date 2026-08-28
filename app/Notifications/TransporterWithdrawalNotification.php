<?php

namespace App\Notifications;

use App\Models\Withdrawal;

class TransporterWithdrawalNotification extends BaseNotification
{
    public function __construct(private Withdrawal $withdrawal) {}

    protected function subject(): string
    {
        return 'Retrait';
    }

    protected function content(): string
    {
        return "Votre retrait de {$this->withdrawal->amount} FCFA est : {$this->withdrawal->status->label()}.";
    }

    /**
     * @return array<string, mixed>
     */
    protected function payload(): array
    {
        return ['withdrawal_id' => $this->withdrawal->id, 'status' => $this->withdrawal->status->value];
    }
}
