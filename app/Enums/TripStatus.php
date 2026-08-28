<?php

namespace App\Enums;

enum TripStatus: string
{
    case Draft = 'draft';
    case Published = 'published';
    case Boarding = 'boarding';
    case Departed = 'departed';
    case Completed = 'completed';
    case Cancelled = 'cancelled';

    /**
     * Get the human-readable label for the status.
     */
    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Brouillon',
            self::Published => 'Publié',
            self::Boarding => 'Embarquement',
            self::Departed => 'Parti',
            self::Completed => 'Terminé',
            self::Cancelled => 'Annulé',
        };
    }

    /**
     * Get every status value as a plain array of strings.
     *
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_map(static fn (self $status): string => $status->value, self::cases());
    }
}
