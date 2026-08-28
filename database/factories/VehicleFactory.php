<?php

namespace Database\Factories;

use App\Enums\VehicleStatus;
use App\Models\Transporter;
use App\Models\Vehicle;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Vehicle>
 */
class VehicleFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'transporter_id' => Transporter::factory(),
            'driver_id' => null,
            'registration' => strtoupper(fake()->unique()->bothify('DK-####-??')),
            'brand' => fake()->randomElement(['Toyota', 'Mercedes', 'Renault', 'Hyundai', 'Iveco']),
            'model' => fake()->randomElement(['Hiace', 'Sprinter', 'Master', 'County', 'Coaster']),
            'capacity' => fake()->numberBetween(7, 70),
            'status' => VehicleStatus::Active,
        ];
    }

    /**
     * Indicate that the vehicle is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => VehicleStatus::Inactive,
        ]);
    }
}
