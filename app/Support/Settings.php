<?php

namespace App\Support;

use App\Models\Setting;

class Settings
{
    /**
     * Definition of the editable business settings.
     *
     * Each entry maps to a fallback key in config/allodakar.php so behaviour is
     * unchanged until an administrator overrides the value in the database.
     *
     * @return array<string, array{config: string, type: string, label: string, help: string, min: float, max: float, step: float}>
     */
    public static function schema(): array
    {
        return [
            'commission.rate' => [
                'config' => 'allodakar.commission.rate',
                'type' => 'float',
                'label' => 'Taux de commission',
                'help' => 'Fraction prélevée par Allo Dakar sur chaque réservation (0.05 = 5 %).',
                'min' => 0,
                'max' => 1,
                'step' => 0.01,
            ],
            'debt.maximum' => [
                'config' => 'allodakar.debt.maximum',
                'type' => 'int',
                'label' => 'Seuil de dette (FCFA)',
                'help' => 'Au-delà, les réservations et publications du transporteur sont bloquées.',
                'min' => 0,
                'max' => 100000000,
                'step' => 1000,
            ],
            'cancellation.deadline_hours' => [
                'config' => 'allodakar.cancellation.deadline_hours',
                'type' => 'int',
                'label' => 'Délai d’annulation (heures)',
                'help' => 'Délai minimum avant le départ pour qu’un passager puisse annuler.',
                'min' => 0,
                'max' => 168,
                'step' => 1,
            ],
            'reminder.lead_hours' => [
                'config' => 'allodakar.reminder.lead_hours',
                'type' => 'int',
                'label' => 'Anticipation des rappels (heures)',
                'help' => 'Fenêtre avant le départ pour notifier les passagers.',
                'min' => 1,
                'max' => 168,
                'step' => 1,
            ],
        ];
    }

    /**
     * Get a business setting, falling back to the application configuration.
     */
    public static function get(string $key): float|int
    {
        $definition = self::schema()[$key] ?? null;

        if ($definition === null) {
            throw new \InvalidArgumentException("Unknown setting [{$key}].");
        }

        $stored = Setting::query()->where('key', $key)->value('value');

        $raw = $stored ?? config($definition['config']);

        return $definition['type'] === 'float' ? (float) $raw : (int) $raw;
    }

    /**
     * Persist a business setting value.
     */
    public static function set(string $key, float|int|string $value): void
    {
        if (! array_key_exists($key, self::schema())) {
            throw new \InvalidArgumentException("Unknown setting [{$key}].");
        }

        Setting::query()->updateOrCreate(['key' => $key], ['value' => (string) $value]);
    }

    /**
     * Get every business setting with its current value and metadata.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function all(): array
    {
        $settings = [];

        foreach (self::schema() as $key => $definition) {
            $settings[] = [
                'key' => $key,
                'label' => $definition['label'],
                'help' => $definition['help'],
                'type' => $definition['type'],
                'value' => self::get($key),
                'min' => $definition['min'],
                'max' => $definition['max'],
                'step' => $definition['step'],
            ];
        }

        return $settings;
    }
}
