<?php

namespace Database\Factories;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Booking;
use App\Models\Payment;
use App\Models\Transporter;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Payment>
 */
class PaymentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $amount = fake()->numberBetween(2000, 20000);

        return [
            'booking_id' => Booking::factory(),
            'transporter_id' => Transporter::factory(),
            'method' => PaymentMethod::MobileMoney,
            'amount' => $amount,
            'commission_amount' => (int) round($amount * 0.05),
            'status' => PaymentStatus::Completed,
            'provider' => 'wave',
            'provider_reference' => 'MOMO-'.fake()->unique()->numerify('########'),
            'idempotency_key' => null,
            'processed_at' => now(),
        ];
    }
}
