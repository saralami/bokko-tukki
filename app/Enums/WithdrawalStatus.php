<?php

namespace App\Enums;

enum WithdrawalStatus: string
{
    case Requested = 'requested';
    case Approved = 'approved';
    case Paid = 'paid';
    case Rejected = 'rejected';

    /**
     * Get the human-readable label for the status.
     */
    public function label(): string
    {
        return match ($this) {
            self::Requested => 'Demandé',
            self::Approved => 'Approuvé',
            self::Paid => 'Payé',
            self::Rejected => 'Rejeté',
        };
    }
}
