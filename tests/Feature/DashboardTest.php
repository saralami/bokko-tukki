<?php

use App\Models\User;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function () {
    app(PermissionRegistrar::class)->forgetCachedPermissions();
});

test('guests are redirected to the login page', function () {
    $response = $this->get(route('dashboard'));
    $response->assertRedirect(route('login'));
});

test('a passenger is dispatched to their own space', function () {
    $this->actingAs(User::factory()->passenger()->create());

    $this->get(route('dashboard'))->assertRedirect(route('passenger.dashboard'));
});

test('an admin is dispatched to the admin backoffice', function () {
    $this->actingAs(User::factory()->admin()->create());

    $this->get(route('dashboard'))->assertRedirect(route('admin.dashboard'));
});

test('a driver is dispatched to their space', function () {
    $this->actingAs(User::factory()->driver()->create());

    $this->get(route('dashboard'))->assertRedirect(route('driver.dashboard'));
});
