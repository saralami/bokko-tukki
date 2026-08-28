<?php

namespace Database\Factories;

use App\Models\Transporter;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Transporter>
 */
class TransporterFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory()->transporter(),
            'company_name' => fake()->company(),
            'phone' => fake()->numerify('+2217#######'),
            'status' => 'active',
        ];
    }
}
