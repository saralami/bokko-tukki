<?php

namespace App\Enums;

enum UserRole: string
{
    case Passenger = 'passenger';
    case Driver = 'driver';
    case Transporter = 'transporter';
    case Admin = 'admin';

    /**
     * Get the human-readable label for the role.
     */
    public function label(): string
    {
        return match ($this) {
            self::Passenger => 'Passager',
            self::Driver => 'Chauffeur',
            self::Transporter => 'Transporteur',
            self::Admin => 'Administrateur',
        };
    }

    /**
     * Get every role value as a plain array of strings.
     *
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_map(static fn (self $role): string => $role->value, self::cases());
    }
}
