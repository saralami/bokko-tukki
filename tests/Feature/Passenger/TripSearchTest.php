<?php

use App\Enums\DriverStatus;
use App\Enums\TransporterStatus;
use App\Enums\TripStatus;
use App\Models\Destination;
use App\Models\Driver;
use App\Models\Transporter;
use App\Models\Trip;
use App\Models\User;
use App\Models\Vehicle;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function () {
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    $this->passenger = User::factory()->passenger()->create();
    $this->actingAs($this->passenger);

    $this->dakar = Destination::factory()->create([
        'city' => 'Dakar', 'region' => 'Dakar', 'latitude' => 14.6928, 'longitude' => -17.4467,
    ]);
    $this->thies = Destination::factory()->create([
        'city' => 'Thiès', 'region' => 'Thiès', 'latitude' => 14.7910, 'longitude' => -16.9256,
    ]);
    $this->saintLouis = Destination::factory()->create([
        'city' => 'Saint-Louis', 'region' => 'Saint-Louis', 'latitude' => 16.0179, 'longitude' => -16.4896,
    ]);

    $this->published = fn (array $overrides = []): Trip => Trip::factory()->published()->create(array_merge([
        'departure_destination_id' => $this->dakar->id,
        'arrival_destination_id' => $this->thies->id,
        'departure_date' => now()->addDays(5)->format('Y-m-d'),
        'departure_time' => '08:30',
        'capacity' => 10,
        'available_seats' => 10,
        'price_per_seat' => 5000,
    ], $overrides));
});

test('a passenger can open the search page', function () {
    $this->get(route('passenger.search'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('passenger/Search'));
});

test('only published, available and future trips are returned', function () {
    ($this->published)(); // valid, available, future
    ($this->published)(['status' => TripStatus::Draft]);
    ($this->published)(['status' => TripStatus::Cancelled]);
    ($this->published)(['departure_date' => now()->subDay()->format('Y-m-d')]); // past
    ($this->published)(['available_seats' => 0]); // full

    $this->get(route('passenger.search'))
        ->assertInertia(fn (Assert $page) => $page->has('results', 1));
});

test('results can be filtered by arrival destination', function () {
    ($this->published)(['arrival_destination_id' => $this->thies->id]);
    ($this->published)(['arrival_destination_id' => $this->saintLouis->id]);

    $this->get(route('passenger.search', ['arrival_destination_id' => $this->saintLouis->id]))
        ->assertInertia(fn (Assert $page) => $page
            ->has('results', 1)
            ->where('results.0.arrival.city', 'Saint-Louis')
        );
});

test('results can be filtered by departure destination', function () {
    ($this->published)(['departure_destination_id' => $this->dakar->id]);
    ($this->published)(['departure_destination_id' => $this->saintLouis->id]);

    $this->get(route('passenger.search', ['departure_destination_id' => $this->dakar->id]))
        ->assertInertia(fn (Assert $page) => $page
            ->has('results', 1)
            ->where('results.0.departure.city', 'Dakar')
        );
});

test('results can be filtered by date', function () {
    ($this->published)(['departure_date' => now()->addDays(3)->format('Y-m-d')]);
    ($this->published)(['departure_date' => now()->addDays(10)->format('Y-m-d')]);

    $this->get(route('passenger.search', ['date' => now()->addDays(10)->format('Y-m-d')]))
        ->assertInertia(fn (Assert $page) => $page->has('results', 1));
});

test('results respect the requested number of seats', function () {
    ($this->published)(['available_seats' => 2]);

    $this->get(route('passenger.search', ['seats' => 3]))
        ->assertInertia(fn (Assert $page) => $page->has('results', 0));

    $this->get(route('passenger.search', ['seats' => 2]))
        ->assertInertia(fn (Assert $page) => $page->has('results', 1));
});

test('an empty search returns no results gracefully', function () {
    $this->get(route('passenger.search', ['arrival_destination_id' => $this->saintLouis->id]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->has('results', 0));
});

test('coordinates annotate distance and sort results by proximity', function () {
    ($this->published)(['departure_destination_id' => $this->saintLouis->id]);
    ($this->published)(['departure_destination_id' => $this->dakar->id]);

    $this->get(route('passenger.search', [
        'latitude' => 14.6928,
        'longitude' => -17.4467,
    ]))->assertInertia(fn (Assert $page) => $page
        ->has('results', 2)
        ->where('results.0.departure.city', 'Dakar')
        ->where('results.1.departure.city', 'Saint-Louis')
        ->whereNot('results.0.distance_km', null)
    );
});

test('the search radius excludes trips that are too far', function () {
    ($this->published)(['departure_destination_id' => $this->saintLouis->id]);
    ($this->published)(['departure_destination_id' => $this->dakar->id]);

    $this->get(route('passenger.search', [
        'latitude' => 14.6928,
        'longitude' => -17.4467,
        'radius' => 50,
    ]))->assertInertia(fn (Assert $page) => $page
        ->has('results', 1)
        ->where('results.0.departure.city', 'Dakar')
    );
});

test('invalid search inputs are rejected', function () {
    $this->get(route('passenger.search', ['seats' => 0]))->assertSessionHasErrors(['seats']);
    $this->get(route('passenger.search', ['date' => 'not-a-date']))->assertSessionHasErrors(['date']);
    $this->get(route('passenger.search', ['latitude' => 999, 'longitude' => 0]))->assertSessionHasErrors(['latitude']);
    $this->get(route('passenger.search', ['longitude' => 10]))->assertSessionHasErrors(['latitude']);
    $this->get(route('passenger.search', ['sort' => 'bogus']))->assertSessionHasErrors(['sort']);
});

test('search results never expose private data', function () {
    $transporter = Transporter::factory()->create([
        'company_name' => 'Dakar Express',
        'email' => 'secret@dakar-express.sn',
        'phone' => '+221777777777',
        'address' => 'Adresse privée',
        'status' => TransporterStatus::Active,
    ]);
    $vehicle = Vehicle::factory()->for($transporter)->create();
    $driver = Driver::factory()->for($transporter)->create([
        'phone' => '+221700000000',
        'license_number' => 'SECRET-LICENSE',
        'status' => DriverStatus::Active,
    ]);

    ($this->published)([
        'transporter_id' => $transporter->id,
        'vehicle_id' => $vehicle->id,
        'driver_id' => $driver->id,
    ]);

    $this->get(route('passenger.search'))
        ->assertInertia(fn (Assert $page) => $page
            ->has('results.0', fn (Assert $result) => $result
                ->where('transporter.company_name', 'Dakar Express')
                ->missing('transporter.phone')
                ->missing('transporter.email')
                ->missing('transporter.address')
                ->missing('driver')
                ->has('vehicle', fn (Assert $v) => $v->hasAll(['brand', 'model'])->missing('registration'))
                ->etc()
            )
        );
});

test('a passenger can view a published trip detail', function () {
    $trip = ($this->published)();

    $this->get(route('passenger.trips.show', $trip))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('passenger/TripDetails')
            ->missing('trip.driver')
        );
});

test('a passenger cannot view a non published trip detail', function () {
    $draft = Trip::factory()->create([
        'departure_destination_id' => $this->dakar->id,
        'arrival_destination_id' => $this->thies->id,
    ]);

    $this->get(route('passenger.trips.show', $draft))->assertNotFound();
});

test('a transporter cannot access the passenger search', function () {
    $transporter = Transporter::factory()->create();

    $this->actingAs($transporter->user)
        ->get(route('passenger.search'))
        ->assertForbidden();
});

test('guests are redirected to login', function () {
    auth()->logout();

    $this->get(route('passenger.search'))->assertRedirect(route('login'));
});
