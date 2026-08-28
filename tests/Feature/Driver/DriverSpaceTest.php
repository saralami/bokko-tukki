<?php

use App\Enums\BookingStatus;
use App\Enums\TripStatus;
use App\Events\BookingBoarded;
use App\Models\Booking;
use App\Models\Destination;
use App\Models\Driver;
use App\Models\Payment;
use App\Models\Transporter;
use App\Models\Trip;
use App\Models\TripIncident;
use App\Models\User;
use App\Notifications\BoardingConfirmedNotification;
use App\Notifications\DriverIncidentReportedNotification;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function () {
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    $this->transporter = Transporter::factory()->create();
    $this->driverUser = User::factory()->driver()->create();
    $this->driver = Driver::factory()->for($this->transporter)->create(['user_id' => $this->driverUser->id]);

    $this->dakar = Destination::factory()->create(['city' => 'Dakar']);
    $this->thies = Destination::factory()->create(['city' => 'Thiès']);

    $this->trip = Trip::factory()->for($this->transporter)->published()->create([
        'driver_id' => $this->driver->id,
        'departure_destination_id' => $this->dakar->id,
        'arrival_destination_id' => $this->thies->id,
        'departure_date' => now()->addDay()->format('Y-m-d'),
        'departure_time' => '08:30',
        'capacity' => 10,
        'available_seats' => 8,
    ]);

    $this->actingAs($this->driverUser);
});

test('the driver dashboard shows the next departure', function () {
    $this->get(route('driver.dashboard'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('driver/Dashboard')
            ->where('next.id', $this->trip->id)
            ->where('next.status', 'published'));
});

test('the driver only sees trips assigned to them', function () {
    $otherTrip = Trip::factory()->for($this->transporter)->published()->create([
        'departure_date' => now()->addDays(2)->format('Y-m-d'),
    ]);

    $this->get(route('driver.trips.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('driver/trips/Index')
            ->has('trips', 1)
            ->where('trips.0.id', $this->trip->id));

    expect($otherTrip->driver_id)->toBeNull();
});

test('the driver trip detail lists passengers and reservations', function () {
    $booking = Booking::factory()->create(['trip_id' => $this->trip->id, 'seats' => 2]);

    $this->get(route('driver.trips.show', $this->trip))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('driver/trips/Show')
            ->where('trip.capacity', 10)
            ->where('trip.available_seats', 8)
            ->has('reservations', 1)
            ->where('reservations.0.reference', $booking->reference));
});

test('a driver cannot view a trip that is not theirs', function () {
    $otherTrip = Trip::factory()->for(Transporter::factory())->published()->create();

    $this->get(route('driver.trips.show', $otherTrip))->assertForbidden();
});

test('boarding by reference confirms the boarding and triggers business events', function () {
    Notification::fake();
    Event::fake([BookingBoarded::class]);

    $booking = Booking::factory()->create([
        'trip_id' => $this->trip->id,
        'payment_method' => 'cash',
        'total_amount' => 6000,
    ]);

    $this->post(route('driver.boarding.store'), ['reference' => $booking->reference])
        ->assertRedirect();

    expect($booking->fresh())
        ->status->toBe(BookingStatus::Completed)
        ->and($booking->fresh()->boarded_at)->not->toBeNull();

    // Cash payment recorded => the reservation was really executed.
    $this->assertDatabaseHas('payments', ['booking_id' => $booking->id, 'amount' => 6000]);

    Event::assertDispatched(BookingBoarded::class);
    Notification::assertSentTo($booking->passenger, BoardingConfirmedNotification::class);
});

test('boarding accepts a reference scanned from a QR payload', function () {
    $booking = Booking::factory()->create(['trip_id' => $this->trip->id]);

    $this->post(route('driver.boarding.store'), ['reference' => "https://allodakar.test/t/{$booking->reference}"])
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    expect($booking->fresh()->status)->toBe(BookingStatus::Completed);
});

test('a driver cannot board a passenger of another driver trip', function () {
    $otherTrip = Trip::factory()->for(Transporter::factory())->published()->create();
    $booking = Booking::factory()->create(['trip_id' => $otherTrip->id]);

    $this->post(route('driver.boarding.store'), ['reference' => $booking->reference])
        ->assertSessionHasErrors(['reference']);

    expect($booking->fresh()->status)->toBe(BookingStatus::Pending);
});

test('boarding an unknown reference fails', function () {
    $this->post(route('driver.boarding.store'), ['reference' => 'AD-UNKNOWN0'])
        ->assertSessionHasErrors(['reference']);
});

test('a cancelled booking cannot be boarded by reference', function () {
    $booking = Booking::factory()->cancelled()->create(['trip_id' => $this->trip->id]);

    $this->post(route('driver.boarding.store'), ['reference' => $booking->reference])
        ->assertSessionHasErrors(['reference']);
});

test('a driver can report a problem which alerts the transporter', function () {
    Notification::fake();

    $this->post(route('driver.incidents.store'), [
        'trip_id' => $this->trip->id,
        'category' => 'breakdown',
        'message' => 'Pneu crevé sur la route de Thiès.',
    ])->assertRedirect();

    $this->assertDatabaseHas('trip_incidents', [
        'trip_id' => $this->trip->id,
        'driver_id' => $this->driver->id,
        'category' => 'breakdown',
        'status' => 'open',
    ]);

    Notification::assertSentTo($this->transporter->user, DriverIncidentReportedNotification::class);
});

test('a driver cannot report a problem on a trip that is not theirs', function () {
    $otherTrip = Trip::factory()->for(Transporter::factory())->published()->create();

    $this->post(route('driver.incidents.store'), [
        'trip_id' => $otherTrip->id,
        'category' => 'delay',
        'message' => 'Message de test.',
    ])->assertForbidden();

    $this->assertDatabaseCount('trip_incidents', 0);
});

test('the driver history lists past trips', function () {
    $this->trip->update(['status' => TripStatus::Completed, 'departure_date' => now()->subDay()->format('Y-m-d')]);

    $this->get(route('driver.history'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('driver/trips/History')->has('trips', 1));
});

test('the driver profile renders', function () {
    $this->get(route('driver.profile'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('driver/Profile'));
});

test('a passenger cannot access the driver space', function () {
    $this->actingAs(User::factory()->passenger()->create());

    $this->get(route('driver.dashboard'))->assertForbidden();
    $this->post(route('driver.boarding.store'), ['reference' => 'AD-XXXXXXXX'])->assertForbidden();
});

test('a driver without an open incident reports it in the trip detail incidents', function () {
    TripIncident::factory()->for($this->trip)->for($this->driver)->create();

    $this->get(route('driver.trips.show', $this->trip))
        ->assertInertia(fn (Assert $page) => $page->has('incidents', 1));
});
