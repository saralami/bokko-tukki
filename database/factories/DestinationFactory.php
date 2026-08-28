<?php

namespace Database\Factories;

use App\Enums\DestinationStatus;
use App\Models\Destination;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Destination>
 */
class DestinationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'city' => fake()->unique()->city(),
            'region' => fake()->randomElement([
                'Dakar', 'Thiès', 'Saint-Louis', 'Diourbel', 'Kaolack',
                'Ziguinchor', 'Louga', 'Fatick', 'Kolda', 'Tambacounda',
            ]),
            'latitude' => fake()->latitude(12, 17),
            'longitude' => fake()->longitude(-18, -11),
            'status' => DestinationStatus::Active,
        ];
    }

    /**
     * Indicate that the destination is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => DestinationStatus::Inactive,
        ]);
    }
}
