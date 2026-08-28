<?php

namespace Database\Factories;

use App\Models\Transporter;
use App\Models\Wallet;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Wallet>
 */
class WalletFactory extends Factory
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
            'available_balance' => 0,
            'outstanding_debt' => 0,
        ];
    }
}
