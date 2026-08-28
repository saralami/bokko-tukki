<?php

namespace App\Enums;

enum IncidentStatus: string
{
    case Open = 'open';
    case Resolved = 'resolved';

    /**
     * Get the human-readable label for the status.
     */
    public function label(): string
    {
        return match ($this) {
            self::Open => 'En cours',
            self::Resolved => 'Résolu',
        };
    }
}
