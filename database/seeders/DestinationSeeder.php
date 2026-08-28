<?php

namespace Database\Seeders;

use App\Enums\DestinationStatus;
use App\Models\Destination;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DestinationSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the interurban destinations of Senegal.
     *
     * Add a new row here to make a destination available across the platform.
     */
    public function run(): void
    {
        $destinations = [
            ['city' => 'Dakar', 'region' => 'Dakar', 'latitude' => 14.6928, 'longitude' => -17.4467],
            ['city' => 'Thiès', 'region' => 'Thiès', 'latitude' => 14.7910, 'longitude' => -16.9256],
            ['city' => 'Mbour', 'region' => 'Thiès', 'latitude' => 14.4198, 'longitude' => -16.9635],
            ['city' => 'Saint-Louis', 'region' => 'Saint-Louis', 'latitude' => 16.0179, 'longitude' => -16.4896],
            ['city' => 'Touba', 'region' => 'Diourbel', 'latitude' => 14.8500, 'longitude' => -15.8833],
            ['city' => 'Diourbel', 'region' => 'Diourbel', 'latitude' => 14.6551, 'longitude' => -16.2314],
            ['city' => 'Kaolack', 'region' => 'Kaolack', 'latitude' => 14.1652, 'longitude' => -16.0726],
            ['city' => 'Ziguinchor', 'region' => 'Ziguinchor', 'latitude' => 12.5833, 'longitude' => -16.2719],
            ['city' => 'Tambacounda', 'region' => 'Tambacounda', 'latitude' => 13.7708, 'longitude' => -13.6672],
            ['city' => 'Louga', 'region' => 'Louga', 'latitude' => 15.6100, 'longitude' => -16.2286],
            ['city' => 'Kolda', 'region' => 'Kolda', 'latitude' => 12.8833, 'longitude' => -14.9500],
            ['city' => 'Fatick', 'region' => 'Fatick', 'latitude' => 14.3390, 'longitude' => -16.4110],
        ];

        foreach ($destinations as $destination) {
            Destination::updateOrCreate(
                ['city' => $destination['city'], 'region' => $destination['region']],
                [...$destination, 'status' => DestinationStatus::Active],
            );
        }
    }
}
