<?php

namespace Database\Factories;

use App\Enums\WithdrawalStatus;
use App\Models\Transporter;
use App\Models\Withdrawal;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Withdrawal>
 */
class WithdrawalFactory extends Factory
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
            'amount' => fake()->numberBetween(1000, 50000),
            'status' => WithdrawalStatus::Requested,
            'processed_by' => null,
            'processed_at' => null,
        ];
    }
}
