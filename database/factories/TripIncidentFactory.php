<?php

namespace Database\Factories;

use App\Enums\IncidentCategory;
use App\Enums\IncidentStatus;
use App\Models\Driver;
use App\Models\Trip;
use App\Models\TripIncident;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TripIncident>
 */
class TripIncidentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'trip_id' => Trip::factory(),
            'driver_id' => Driver::factory(),
            'category' => fake()->randomElement(IncidentCategory::cases()),
            'message' => fake()->sentence(),
            'status' => IncidentStatus::Open,
        ];
    }

    /**
     * Indicate that the incident has been resolved.
     */
    public function resolved(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => IncidentStatus::Resolved,
            'resolved_at' => now(),
        ]);
    }
}
