<?php

use App\Enums\DestinationStatus;
use App\Enums\DriverStatus;
use App\Enums\TripStatus;
use App\Enums\VehicleStatus;
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

    $this->transporter = Transporter::factory()->create();
    $this->actingAs($this->transporter->user);

    $this->vehicle = Vehicle::factory()->for($this->transporter)->create([
        'capacity' => 30,
        'status' => VehicleStatus::Active,
    ]);
    $this->driver = Driver::factory()->for($this->transporter)->create([
        'status' => DriverStatus::Active,
    ]);
    $this->from = Destination::factory()->create();
    $this->to = Destination::factory()->create();

    $this->payload = fn (array $overrides = []): array => array_merge([
        'vehicle_id' => $this->vehicle->id,
        'driver_id' => $this->driver->id,
        'departure_destination_id' => $this->from->id,
        'arrival_destination_id' => $this->to->id,
        'departure_date' => now()->addDays(3)->format('Y-m-d'),
        'departure_time' => '08:30',
        'price_per_seat' => 5000,
    ], $overrides);

    $this->ownedDraft = fn (array $overrides = []): Trip => Trip::factory()->for($this->transporter)->create(array_merge([
        'vehicle_id' => $this->vehicle->id,
        'driver_id' => $this->driver->id,
        'departure_destination_id' => $this->from->id,
        'arrival_destination_id' => $this->to->id,
        'capacity' => 30,
        'available_seats' => 30,
        'price_per_seat' => 5000,
        'status' => TripStatus::Draft,
    ], $overrides));
});

test('a transporter can list only their own trips', function () {
    Trip::factory()->count(2)->for($this->transporter)->create();
    Trip::factory()->count(3)->create();

    $this->get(route('transporter.trips.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('transporter/trips/Index')
            ->has('trips', 2)
        );
});

test('a transporter can view the create form', function () {
    $this->get(route('transporter.trips.create'))->assertOk();
});

test('a transporter can create a draft trip with capacity from the vehicle', function () {
    $this->post(route('transporter.trips.store'), ($this->payload)())
        ->assertRedirect();

    $this->assertDatabaseHas('trips', [
        'transporter_id' => $this->transporter->id,
        'vehicle_id' => $this->vehicle->id,
        'capacity' => 30,
        'available_seats' => 30,
        'status' => 'draft',
    ]);
});

test('a transporter can update a trip', function () {
    $trip = ($this->ownedDraft)();

    $this->patch(route('transporter.trips.update', $trip), ($this->payload)([
        'price_per_seat' => 7500,
    ]))->assertRedirect(route('transporter.trips.show', $trip));

    expect($trip->fresh()->price_per_seat)->toBe(7500);
});

test('trip creation is validated', function () {
    $this->post(route('transporter.trips.store'), [])
        ->assertSessionHasErrors([
            'vehicle_id',
            'departure_destination_id',
            'arrival_destination_id',
            'departure_date',
            'departure_time',
            'price_per_seat',
        ]);
});

test('departure and arrival must differ', function () {
    $this->post(route('transporter.trips.store'), ($this->payload)([
        'arrival_destination_id' => $this->from->id,
    ]))->assertSessionHasErrors(['arrival_destination_id']);
});

test('the departure date cannot be in the past', function () {
    $this->post(route('transporter.trips.store'), ($this->payload)([
        'departure_date' => now()->subDay()->format('Y-m-d'),
    ]))->assertSessionHasErrors(['departure_date']);
});

test('a transporter cannot use a vehicle from another company', function () {
    $otherVehicle = Vehicle::factory()->create();

    $this->post(route('transporter.trips.store'), ($this->payload)([
        'vehicle_id' => $otherVehicle->id,
    ]))->assertSessionHasErrors(['vehicle_id']);
});

test('an inactive destination cannot be used', function () {
    $inactive = Destination::factory()->inactive()->create();

    $this->post(route('transporter.trips.store'), ($this->payload)([
        'arrival_destination_id' => $inactive->id,
    ]))->assertSessionHasErrors(['arrival_destination_id']);
});

test('a valid draft can be published', function () {
    $trip = ($this->ownedDraft)();

    $this->patch(route('transporter.trips.publish', $trip))
        ->assertRedirect(route('transporter.trips.show', $trip));

    expect($trip->fresh())
        ->status->toBe(TripStatus::Published)
        ->available_seats->toBe(30);
});

test('a trip without a driver cannot be published', function () {
    $trip = ($this->ownedDraft)(['driver_id' => null]);

    $this->patch(route('transporter.trips.publish', $trip))
        ->assertSessionHasErrors(['driver_id']);

    expect($trip->fresh()->status)->toBe(TripStatus::Draft);
});

test('a trip with an inactive vehicle cannot be published', function () {
    $this->vehicle->update(['status' => VehicleStatus::Inactive]);
    $trip = ($this->ownedDraft)();

    $this->patch(route('transporter.trips.publish', $trip))
        ->assertSessionHasErrors(['vehicle_id']);

    expect($trip->fresh()->status)->toBe(TripStatus::Draft);
});

test('a trip pointing to a deactivated destination cannot be published', function () {
    $trip = ($this->ownedDraft)();
    $this->to->update(['status' => DestinationStatus::Inactive]);

    $this->patch(route('transporter.trips.publish', $trip))
        ->assertSessionHasErrors(['arrival_destination_id']);
});

test('a trip with a zero price cannot be published', function () {
    $trip = ($this->ownedDraft)(['price_per_seat' => 0]);

    $this->patch(route('transporter.trips.publish', $trip))
        ->assertSessionHasErrors(['price_per_seat']);
});

test('a published trip can be cancelled', function () {
    $trip = ($this->ownedDraft)(['status' => TripStatus::Published]);

    $this->patch(route('transporter.trips.cancel', $trip))
        ->assertRedirect(route('transporter.trips.show', $trip));

    expect($trip->fresh()->status)->toBe(TripStatus::Cancelled);
});

test('available seats can never go negative', function () {
    $trip = ($this->ownedDraft)(['capacity' => 3, 'available_seats' => 3]);

    $trip->reserveSeats(2);
    expect($trip->fresh()->available_seats)->toBe(1);

    expect(fn () => $trip->reserveSeats(5))->toThrow(RuntimeException::class);
    expect($trip->fresh()->available_seats)->toBe(1);
});

test('a transporter cannot view, update, publish or cancel another company trip', function () {
    $otherTrip = Trip::factory()->create();

    $this->get(route('transporter.trips.show', $otherTrip))->assertForbidden();
    $this->get(route('transporter.trips.edit', $otherTrip))->assertForbidden();
    $this->patch(route('transporter.trips.publish', $otherTrip))->assertForbidden();
    $this->patch(route('transporter.trips.cancel', $otherTrip))->assertForbidden();
});

test('a passenger cannot access the trip management', function () {
    $passenger = User::factory()->passenger()->create();

    $this->actingAs($passenger)
        ->get(route('transporter.trips.index'))
        ->assertForbidden();
});

test('guests are redirected to login', function () {
    auth()->logout();

    $this->get(route('transporter.trips.index'))->assertRedirect(route('login'));
});
