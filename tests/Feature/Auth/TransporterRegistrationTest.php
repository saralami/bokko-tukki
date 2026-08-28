<?php

use App\Actions\PublishTrip;
use App\Enums\DestinationStatus;
use App\Enums\DriverStatus;
use App\Enums\TransporterStatus;
use App\Enums\TripStatus;
use App\Enums\UserRole;
use App\Enums\VehicleStatus;
use App\Models\Destination;
use App\Models\Driver;
use App\Models\Transporter;
use App\Models\Trip;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Validation\ValidationException;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function () {
    app(PermissionRegistrar::class)->forgetCachedPermissions();
});

test('a guest can view the transporter registration page', function () {
    $this->get(route('transporter.register'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('auth/RegisterTransporter'));
});

test('a company can register and lands pending validation', function () {
    $response = $this->post(route('transporter.register.store'), [
        'company_name' => 'Dakar Express',
        'name' => 'Amadou Ba',
        'phone' => '+221771234567',
        'email' => 'contact@dakarexpress.sn',
        'password' => 'AlloDakar2026!',
        'password_confirmation' => 'AlloDakar2026!',
    ]);

    $response->assertRedirect(route('transporter.dashboard'));
    $this->assertAuthenticated();

    $user = User::firstWhere('email', 'contact@dakarexpress.sn');

    expect($user)->not->toBeNull()
        ->and($user->hasUserRole(UserRole::Transporter))->toBeTrue()
        ->and($user->transporter)->not->toBeNull()
        ->and($user->transporter->company_name)->toBe('Dakar Express')
        ->and($user->transporter->status)->toBe(TransporterStatus::Pending);
});

test('transporter registration is validated', function () {
    $this->post(route('transporter.register.store'), [
        'company_name' => '',
        'name' => '',
        'email' => 'not-an-email',
        'password' => 'x',
        'password_confirmation' => 'y',
    ])->assertSessionHasErrors(['company_name', 'name', 'email', 'password']);

    $this->assertGuest();
});

test('an authenticated user cannot open the transporter registration page', function () {
    $this->actingAs(User::factory()->passenger()->create());

    $this->get(route('transporter.register'))->assertRedirect();
});

test('a pending transporter cannot publish a trip', function () {
    $transporter = Transporter::factory()->create(['status' => TransporterStatus::Pending]);
    $trip = publishableDraft($transporter);

    expect(fn () => app(PublishTrip::class)($trip))->toThrow(ValidationException::class);
    expect($trip->fresh()->status)->toBe(TripStatus::Draft);
});

test('an activated transporter can publish a trip', function () {
    $transporter = Transporter::factory()->create(['status' => TransporterStatus::Pending]);
    $trip = publishableDraft($transporter);

    // Admin validates the company.
    $transporter->update(['status' => TransporterStatus::Active]);

    app(PublishTrip::class)($trip->fresh());

    expect($trip->fresh()->status)->toBe(TripStatus::Published);
});

test('the public landing page renders for guests', function () {
    $this->get('/')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('Welcome'));
});

/**
 * Build a fully publishable draft trip for the given transporter.
 */
function publishableDraft(Transporter $transporter): Trip
{
    $vehicle = Vehicle::factory()->for($transporter)->create(['capacity' => 30, 'status' => VehicleStatus::Active]);
    $driver = Driver::factory()->for($transporter)->create(['status' => DriverStatus::Active]);
    $from = Destination::factory()->create(['status' => DestinationStatus::Active]);
    $to = Destination::factory()->create(['status' => DestinationStatus::Active]);

    return Trip::factory()->for($transporter)->create([
        'vehicle_id' => $vehicle->id,
        'driver_id' => $driver->id,
        'departure_destination_id' => $from->id,
        'arrival_destination_id' => $to->id,
        'capacity' => 30,
        'available_seats' => 30,
        'price_per_seat' => 5000,
        'status' => TripStatus::Draft,
    ]);
}
