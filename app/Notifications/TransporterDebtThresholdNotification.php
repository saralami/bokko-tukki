<?php

namespace App\Notifications;

class TransporterDebtThresholdNotification extends BaseNotification
{
    public function __construct(private int $debt, private int $maximum) {}

    protected function subject(): string
    {
        return 'Seuil de dette dépassé';
    }

    protected function content(): string
    {
        return "Votre dette ({$this->debt} FCFA) dépasse le seuil autorisé ({$this->maximum} FCFA). Réservations et publications suspendues jusqu'à régularisation.";
    }

    /**
     * @return array<string, mixed>
     */
    protected function payload(): array
    {
        return ['debt' => $this->debt, 'maximum' => $this->maximum];
    }
}
