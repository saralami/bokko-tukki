<?php

use App\Enums\VehicleStatus;
use App\Models\Driver;
use App\Models\Transporter;
use App\Models\User;
use App\Models\Vehicle;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function () {
    app(PermissionRegistrar::class)->forgetCachedPermissions();
    $this->transporter = Transporter::factory()->create();
    $this->actingAs($this->transporter->user);
});

test('a transporter can list only their own vehicles', function () {
    Vehicle::factory()->count(2)->for($this->transporter)->create();
    Vehicle::factory()->count(3)->create(); // another transporter's fleet

    $this->get(route('transporter.vehicles.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('transporter/vehicles/Index')
            ->has('vehicles', 2)
        );
});

test('a transporter can view the create form', function () {
    $this->get(route('transporter.vehicles.create'))->assertOk();
});

test('a transporter can create a vehicle for their own company', function () {
    $this->post(route('transporter.vehicles.store'), [
        'registration' => 'DK-1234-AB',
        'brand' => 'Toyota',
        'model' => 'Hiace',
        'capacity' => 18,
    ])->assertRedirect(route('transporter.vehicles.index'));

    $this->assertDatabaseHas('vehicles', [
        'registration' => 'DK-1234-AB',
        'transporter_id' => $this->transporter->id,
        'status' => 'active',
    ]);
});

test('a transporter can update their vehicle', function () {
    $vehicle = Vehicle::factory()->for($this->transporter)->create();

    $this->patch(route('transporter.vehicles.update', $vehicle), [
        'registration' => 'DK-9999-ZZ',
        'brand' => 'Mercedes',
        'model' => 'Sprinter',
        'capacity' => 22,
        'status' => VehicleStatus::Maintenance->value,
    ])->assertRedirect(route('transporter.vehicles.index'));

    expect($vehicle->fresh())
        ->registration->toBe('DK-9999-ZZ')
        ->status->toBe(VehicleStatus::Maintenance);
});

test('a transporter can soft delete their vehicle', function () {
    $vehicle = Vehicle::factory()->for($this->transporter)->create();

    $this->delete(route('transporter.vehicles.destroy', $vehicle))
        ->assertRedirect(route('transporter.vehicles.index'));

    $this->assertSoftDeleted($vehicle);
});

test('a transporter can toggle a vehicle status', function () {
    $vehicle = Vehicle::factory()->for($this->transporter)->create(['status' => VehicleStatus::Active]);

    $this->patch(route('transporter.vehicles.status', $vehicle), ['status' => 'inactive']);

    expect($vehicle->fresh()->status)->toBe(VehicleStatus::Inactive);
});

test('a transporter can assign one of their drivers to a vehicle', function () {
    $driver = Driver::factory()->for($this->transporter)->create();

    $this->post(route('transporter.vehicles.store'), [
        'registration' => 'DK-4321-CD',
        'brand' => 'Renault',
        'model' => 'Master',
        'capacity' => 15,
        'driver_id' => $driver->id,
    ])->assertRedirect(route('transporter.vehicles.index'));

    $this->assertDatabaseHas('vehicles', [
        'registration' => 'DK-4321-CD',
        'driver_id' => $driver->id,
    ]);
});

test('vehicle creation is validated', function () {
    $this->post(route('transporter.vehicles.store'), [])
        ->assertSessionHasErrors(['registration', 'brand', 'model', 'capacity']);
});

test('vehicle registration must be unique', function () {
    Vehicle::factory()->for($this->transporter)->create(['registration' => 'DK-0001-AA']);

    $this->post(route('transporter.vehicles.store'), [
        'registration' => 'DK-0001-AA',
        'brand' => 'Toyota',
        'model' => 'Coaster',
        'capacity' => 30,
    ])->assertSessionHasErrors(['registration']);
});

test('vehicle capacity must be at least one', function () {
    $this->post(route('transporter.vehicles.store'), [
        'registration' => 'DK-0002-AA',
        'brand' => 'Toyota',
        'model' => 'Coaster',
        'capacity' => 0,
    ])->assertSessionHasErrors(['capacity']);
});

test('an invalid vehicle status is rejected', function () {
    $vehicle = Vehicle::factory()->for($this->transporter)->create();

    $this->patch(route('transporter.vehicles.status', $vehicle), ['status' => 'flying'])
        ->assertSessionHasErrors(['status']);
});

test('a transporter cannot assign a driver from another company', function () {
    $otherDriver = Driver::factory()->create();

    $this->post(route('transporter.vehicles.store'), [
        'registration' => 'DK-7777-XX',
        'brand' => 'Iveco',
        'model' => 'Daily',
        'capacity' => 19,
        'driver_id' => $otherDriver->id,
    ])->assertSessionHasErrors(['driver_id']);
});

test('a transporter cannot view, update or delete another company vehicle', function () {
    $otherVehicle = Vehicle::factory()->create();

    $this->get(route('transporter.vehicles.edit', $otherVehicle))->assertForbidden();

    $this->patch(route('transporter.vehicles.update', $otherVehicle), [
        'registration' => 'DK-0000-HACK',
        'brand' => 'X',
        'model' => 'Y',
        'capacity' => 10,
    ])->assertForbidden();

    $this->patch(route('transporter.vehicles.status', $otherVehicle), ['status' => 'inactive'])
        ->assertForbidden();

    $this->delete(route('transporter.vehicles.destroy', $otherVehicle))->assertForbidden();
});

test('a passenger cannot access the vehicle management', function () {
    $passenger = User::factory()->passenger()->create();

    $this->actingAs($passenger)
        ->get(route('transporter.vehicles.index'))
        ->assertForbidden();
});

test('guests are redirected to login', function () {
    auth()->logout();

    $this->get(route('transporter.vehicles.index'))->assertRedirect(route('login'));
});
