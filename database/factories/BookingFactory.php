<?php

namespace Database\Factories;

use App\Enums\BookingStatus;
use App\Enums\PaymentMethod;
use App\Models\Booking;
use App\Models\Trip;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Booking>
 */
class BookingFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $seats = fake()->numberBetween(1, 4);
        $unitPrice = fake()->numberBetween(2000, 15000);

        return [
            'reference' => Booking::generateReference(),
            'passenger_id' => User::factory()->passenger(),
            'trip_id' => Trip::factory()->published(),
            'seats' => $seats,
            'unit_price' => $unitPrice,
            'total_amount' => $unitPrice * $seats,
            'payment_method' => PaymentMethod::Cash,
            'status' => BookingStatus::Pending,
            'idempotency_key' => null,
            'boarded_at' => null,
            'cancelled_at' => null,
        ];
    }

    /**
     * Indicate that the booking is confirmed.
     */
    public function confirmed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => BookingStatus::Confirmed,
        ]);
    }

    /**
     * Indicate that the booking is cancelled.
     */
    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => BookingStatus::Cancelled,
            'cancelled_at' => now(),
        ]);
    }
}
