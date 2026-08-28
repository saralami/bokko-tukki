<?php

namespace App\Enums;

enum IncidentCategory: string
{
    case Breakdown = 'breakdown';
    case Accident = 'accident';
    case Delay = 'delay';
    case PassengerIssue = 'passenger_issue';
    case Other = 'other';

    /**
     * Get the human-readable label for the category.
     */
    public function label(): string
    {
        return match ($this) {
            self::Breakdown => 'Panne du véhicule',
            self::Accident => 'Accident',
            self::Delay => 'Retard',
            self::PassengerIssue => 'Problème avec un passager',
            self::Other => 'Autre',
        };
    }

    /**
     * Get every category value as a plain array of strings.
     *
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_map(static fn (self $category): string => $category->value, self::cases());
    }
}
