<?php

use App\Models\Destination;
use App\Models\Transporter;
use App\Models\Trip;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function () {
    app(PermissionRegistrar::class)->forgetCachedPermissions();
    $this->admin = User::factory()->admin()->create();
    $this->actingAs($this->admin);
});

test('an admin can list destinations', function () {
    Destination::factory()->count(3)->create();

    $this->get(route('admin.destinations.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/destinations/Index')
            ->has('destinations', 3)
        );
});

test('an admin can view the create form', function () {
    $this->get(route('admin.destinations.create'))->assertOk();
});

test('an admin can create a destination', function () {
    $this->post(route('admin.destinations.store'), [
        'city' => 'Kaffrine',
        'region' => 'Kaffrine',
        'latitude' => 14.1059,
        'longitude' => -15.5508,
    ])->assertRedirect(route('admin.destinations.index'));

    $this->assertDatabaseHas('destinations', [
        'city' => 'Kaffrine',
        'region' => 'Kaffrine',
        'status' => 'active',
    ]);
});

test('an admin can update a destination', function () {
    $destination = Destination::factory()->create();

    $this->patch(route('admin.destinations.update', $destination), [
        'city' => 'Sédhiou',
        'region' => 'Sédhiou',
        'status' => 'inactive',
    ])->assertRedirect(route('admin.destinations.index'));

    expect($destination->fresh())
        ->city->toBe('Sédhiou')
        ->status->value->toBe('inactive');
});

test('an admin can delete an unused destination', function () {
    $destination = Destination::factory()->create();

    $this->delete(route('admin.destinations.destroy', $destination))
        ->assertRedirect(route('admin.destinations.index'));

    $this->assertDatabaseMissing('destinations', ['id' => $destination->id]);
});

test('a destination used by a trip cannot be deleted', function () {
    $destination = Destination::factory()->create();
    Trip::factory()->create(['departure_destination_id' => $destination->id]);

    $this->delete(route('admin.destinations.destroy', $destination))
        ->assertSessionHasErrors(['destination']);

    $this->assertDatabaseHas('destinations', ['id' => $destination->id]);
});

test('destination creation is validated', function () {
    $this->post(route('admin.destinations.store'), [])
        ->assertSessionHasErrors(['city', 'region']);
});

test('a destination city must be unique within its region', function () {
    Destination::factory()->create(['city' => 'Dakar', 'region' => 'Dakar']);

    $this->post(route('admin.destinations.store'), [
        'city' => 'Dakar',
        'region' => 'Dakar',
    ])->assertSessionHasErrors(['city']);
});

test('destination coordinates are validated', function () {
    $this->post(route('admin.destinations.store'), [
        'city' => 'Nowhere',
        'region' => 'Nowhere',
        'latitude' => 999,
    ])->assertSessionHasErrors(['latitude']);
});

test('a transporter cannot access the destination administration', function () {
    $transporter = Transporter::factory()->create();

    $this->actingAs($transporter->user)
        ->get(route('admin.destinations.index'))
        ->assertForbidden();
});

test('guests are redirected to login', function () {
    auth()->logout();

    $this->get(route('admin.destinations.index'))->assertRedirect(route('login'));
});
