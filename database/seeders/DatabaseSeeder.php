<?php

namespace Database\Seeders;

use App\Enums\TransporterStatus;
use App\Models\Destination;
use App\Models\Driver;
use App\Models\Transporter;
use App\Models\Trip;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(RolesAndPermissionsSeeder::class);
        $this->call(DestinationSeeder::class);

        User::factory()->admin()->create([
            'name' => 'Admin Allo Dakar',
            'email' => 'admin@allodakar.test',
        ]);

        $transporterUser = User::factory()->transporter()->create([
            'name' => 'Transporteur Démo',
            'email' => 'transporteur@allodakar.test',
        ]);

        $transporter = Transporter::factory()->for($transporterUser)->create([
            'company_name' => 'Dakar Transport Express',
            'email' => 'contact@dakar-express.sn',
            'phone' => '+221338000000',
            'address' => 'Gare routière des Baux Maraîchers, Dakar',
            'status' => TransporterStatus::Active,
        ]);

        $drivers = Driver::factory()->count(3)->for($transporter)->create();

        $vehicles = Vehicle::factory()->count(4)->for($transporter)->create();
        $vehicles->each(fn (Vehicle $vehicle, int $index) => $index < $drivers->count()
            ? $vehicle->update(['driver_id' => $drivers[$index]->id])
            : null);

        $destinations = Destination::query()->active()->orderBy('id')->take(3)->get();

        if ($destinations->count() >= 2) {
            $vehicle = $vehicles->first();

            Trip::factory()->for($transporter)->published()->create([
                'vehicle_id' => $vehicle->id,
                'driver_id' => $drivers->first()->id,
                'departure_destination_id' => $destinations[0]->id,
                'arrival_destination_id' => $destinations[1]->id,
                'capacity' => $vehicle->capacity,
                'available_seats' => $vehicle->capacity,
            ]);

            Trip::factory()->for($transporter)->create([
                'departure_destination_id' => $destinations[0]->id,
                'arrival_destination_id' => $destinations[2 % $destinations->count()]->id,
            ]);
        }

        User::factory()->driver()->create([
            'name' => 'Chauffeur Démo',
            'email' => 'chauffeur@allodakar.test',
        ]);

        User::factory()->passenger()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);
    }
}
