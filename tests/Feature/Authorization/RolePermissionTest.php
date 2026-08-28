<?php

use App\Enums\UserRole;
use App\Models\Transporter;
use App\Models\User;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function () {
    app(PermissionRegistrar::class)->forgetCachedPermissions();
});

test('a newly registered user receives the passenger role', function () {
    $this->post(route('register.store'), [
        'name' => 'Awa Diop',
        'email' => 'awa@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $user = User::where('email', 'awa@example.com')->firstOrFail();

    expect($user->hasUserRole(UserRole::Passenger))->toBeTrue()
        ->and($user->isAdmin())->toBeFalse();
});

test('each role can reach its own dashboard', function (string $state, string $route) {
    $user = User::factory()->{$state}()->create();

    $this->actingAs($user)->get(route($route))->assertOk();
})->with([
    'passenger' => ['passenger', 'passenger.dashboard'],
    'driver' => ['driver', 'driver.dashboard'],
    'transporter' => ['transporter', 'transporter.dashboard'],
    'admin' => ['admin', 'admin.dashboard'],
]);

test('a passenger cannot access transporter or admin areas', function () {
    $user = User::factory()->passenger()->create();

    $this->actingAs($user)->get(route('transporter.dashboard'))->assertForbidden();
    $this->actingAs($user)->get(route('admin.dashboard'))->assertForbidden();
});

test('a driver cannot access the admin area', function () {
    $user = User::factory()->driver()->create();

    $this->actingAs($user)->get(route('admin.dashboard'))->assertForbidden();
});

test('guests are redirected to login from protected role areas', function () {
    $this->get(route('transporter.dashboard'))->assertRedirect(route('login'));
    $this->get(route('admin.dashboard'))->assertRedirect(route('login'));
});

test('a transporter cannot update another transporter', function () {
    $transporterA = Transporter::factory()->create();
    $transporterB = Transporter::factory()->create();

    $this->actingAs($transporterB->user)
        ->patch(route('transporter.transporters.update', $transporterA), [
            'company_name' => 'Compagnie Piratée',
        ])
        ->assertForbidden();

    expect($transporterA->fresh()->company_name)->not->toBe('Compagnie Piratée');
});

test('a transporter can update their own company', function () {
    $transporter = Transporter::factory()->create();

    $this->actingAs($transporter->user)
        ->patch(route('transporter.transporters.update', $transporter), [
            'company_name' => 'Dakar Express',
            'phone' => '+221771234567',
        ])
        ->assertRedirect(route('transporter.dashboard'));

    expect($transporter->fresh()->company_name)->toBe('Dakar Express');
});

test('an administrator bypasses the transporter policy', function () {
    $admin = User::factory()->admin()->create();
    $transporter = Transporter::factory()->create();

    expect($admin->can('update', $transporter))->toBeTrue();
});
