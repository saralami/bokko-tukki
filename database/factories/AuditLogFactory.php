<?php

namespace Database\Factories;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AuditLog>
 */
class AuditLogFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory()->admin(),
            'action' => fake()->randomElement(['user.suspended', 'transporter.status_changed', 'payment.refunded']),
            'description' => fake()->sentence(),
            'meta' => [],
            'ip_address' => fake()->ipv4(),
        ];
    }
}
