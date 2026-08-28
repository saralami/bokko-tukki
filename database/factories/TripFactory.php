<?php

namespace Database\Factories;

use App\Enums\TripStatus;
use App\Models\Destination;
use App\Models\Transporter;
use App\Models\Trip;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Trip>
 */
class TripFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $capacity = fake()->numberBetween(15, 55);

        return [
            'transporter_id' => Transporter::factory(),
            'vehicle_id' => null,
            'driver_id' => null,
            'departure_destination_id' => Destination::factory(),
            'arrival_destination_id' => Destination::factory(),
            'departure_date' => fake()->dateTimeBetween('+1 day', '+2 months')->format('Y-m-d'),
            'departure_time' => fake()->time('H:i'),
            'price_per_seat' => fake()->numberBetween(2000, 15000),
            'capacity' => $capacity,
            'available_seats' => $capacity,
            'status' => TripStatus::Draft,
        ];
    }

    /**
     * Indicate that the trip is published.
     */
    public function published(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => TripStatus::Published,
        ]);
    }

    /**
     * Indicate that the trip is cancelled.
     */
    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => TripStatus::Cancelled,
        ]);
    }
}
