<?php

use App\Enums\DriverStatus;
use App\Models\Driver;
use App\Models\Transporter;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function () {
    app(PermissionRegistrar::class)->forgetCachedPermissions();
    $this->transporter = Transporter::factory()->create();
    $this->actingAs($this->transporter->user);
});

test('a transporter can list only their own drivers', function () {
    Driver::factory()->count(2)->for($this->transporter)->create();
    Driver::factory()->count(4)->create(); // other companies

    $this->get(route('transporter.drivers.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('transporter/drivers/Index')
            ->has('drivers', 2)
        );
});

test('a transporter can view the create form', function () {
    $this->get(route('transporter.drivers.create'))->assertOk();
});

test('a transporter can create a driver', function () {
    $this->post(route('transporter.drivers.store'), [
        'first_name' => 'Modou',
        'last_name' => 'Fall',
        'phone' => '+221771112233',
        'license_number' => 'SN-2024-0099',
    ])->assertRedirect(route('transporter.drivers.index'));

    $this->assertDatabaseHas('drivers', [
        'first_name' => 'Modou',
        'last_name' => 'Fall',
        'transporter_id' => $this->transporter->id,
        'status' => 'active',
    ]);
});

test('a transporter can update their driver', function () {
    $driver = Driver::factory()->for($this->transporter)->create();

    $this->patch(route('transporter.drivers.update', $driver), [
        'first_name' => 'Awa',
        'last_name' => 'Sow',
        'status' => DriverStatus::Inactive->value,
    ])->assertRedirect(route('transporter.drivers.index'));

    expect($driver->fresh())
        ->first_name->toBe('Awa')
        ->last_name->toBe('Sow')
        ->status->toBe(DriverStatus::Inactive);
});

test('a transporter can delete their driver', function () {
    $driver = Driver::factory()->for($this->transporter)->create();

    $this->delete(route('transporter.drivers.destroy', $driver))
        ->assertRedirect(route('transporter.drivers.index'));

    $this->assertDatabaseMissing('drivers', ['id' => $driver->id]);
});

test('a transporter can suspend a driver', function () {
    $driver = Driver::factory()->for($this->transporter)->create();

    $this->patch(route('transporter.drivers.status', $driver), ['status' => 'suspended']);

    expect($driver->fresh()->status)->toBe(DriverStatus::Suspended);
});

test('driver creation is validated', function () {
    $this->post(route('transporter.drivers.store'), [])
        ->assertSessionHasErrors(['first_name', 'last_name']);
});

test('an invalid driver status is rejected', function () {
    $driver = Driver::factory()->for($this->transporter)->create();

    $this->patch(route('transporter.drivers.status', $driver), ['status' => 'sleeping'])
        ->assertSessionHasErrors(['status']);
});

test('a transporter cannot view, update or delete another company driver', function () {
    $otherDriver = Driver::factory()->create();

    $this->get(route('transporter.drivers.edit', $otherDriver))->assertForbidden();

    $this->patch(route('transporter.drivers.update', $otherDriver), [
        'first_name' => 'Hack',
        'last_name' => 'Attempt',
    ])->assertForbidden();

    $this->patch(route('transporter.drivers.status', $otherDriver), ['status' => 'inactive'])
        ->assertForbidden();

    $this->delete(route('transporter.drivers.destroy', $otherDriver))->assertForbidden();
});

test('a passenger cannot access the driver management', function () {
    $passenger = User::factory()->passenger()->create();

    $this->actingAs($passenger)
        ->get(route('transporter.drivers.index'))
        ->assertForbidden();
});
