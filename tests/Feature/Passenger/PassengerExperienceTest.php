<?php

use App\Enums\TripStatus;
use App\Models\Booking;
use App\Models\Destination;
use App\Models\Transporter;
use App\Models\Trip;
use App\Models\User;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function () {
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    $this->passenger = User::factory()->passenger()->create();
    $this->actingAs($this->passenger);

    $this->dakar = Destination::factory()->create(['city' => 'Dakar', 'region' => 'Dakar']);
    $this->thies = Destination::factory()->create(['city' => 'Thiès', 'region' => 'Thiès']);

    $this->trip = Trip::factory()->for(Transporter::factory())->published()->create([
        'departure_destination_id' => $this->dakar->id,
        'arrival_destination_id' => $this->thies->id,
        'departure_date' => now()->addDays(5)->format('Y-m-d'),
        'departure_time' => '08:30',
        'capacity' => 10,
        'available_seats' => 10,
        'price_per_seat' => 5000,
    ]);
});

test('the passenger home screen renders with quick search data', function () {
    $this->get(route('passenger.dashboard'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('passenger/Home')
            ->has('destinations')
            ->has('upcoming'));
});

test('the reservation step renders for a published trip', function () {
    $this->get(route('passenger.bookings.create', $this->trip))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('passenger/bookings/Create')
            ->where('trip.id', $this->trip->id)
            ->where('trip.price_per_seat', 5000));
});

test('the reservation step is not available for a draft trip', function () {
    $draft = Trip::factory()->create(['status' => TripStatus::Draft]);

    $this->get(route('passenger.bookings.create', $draft))->assertNotFound();
});

test('storing a booking redirects to the payment step', function () {
    $response = $this->post(route('passenger.bookings.store'), [
        'trip_id' => $this->trip->id,
        'seats' => 2,
        'payment_method' => 'mobile_money',
    ]);

    $booking = Booking::firstOrFail();

    $response->assertRedirect(route('passenger.bookings.payment', $booking));
});

test('the payment step renders the booking payment state', function () {
    $booking = Booking::factory()->create([
        'passenger_id' => $this->passenger->id,
        'trip_id' => $this->trip->id,
        'payment_method' => 'mobile_money',
    ]);

    $this->get(route('passenger.bookings.payment', $booking))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('passenger/Payment')
            ->where('booking.reference', $booking->reference)
            ->where('booking.payment.state', 'pending'));
});

test('a cash booking shows the pay-at-boarding payment state', function () {
    $booking = Booking::factory()->create([
        'passenger_id' => $this->passenger->id,
        'trip_id' => $this->trip->id,
        'payment_method' => 'cash',
    ]);

    $this->get(route('passenger.bookings.payment', $booking))
        ->assertInertia(fn (Assert $page) => $page->where('booking.payment.state', 'cash_on_boarding'));
});

test('a passenger cannot see the payment of another passenger booking', function () {
    $booking = Booking::factory()->create();

    $this->get(route('passenger.bookings.payment', $booking))->assertForbidden();
});

test('the bookings index only lists active bookings', function () {
    $active = Booking::factory()->create([
        'passenger_id' => $this->passenger->id,
        'trip_id' => $this->trip->id,
        'status' => 'confirmed',
    ]);
    Booking::factory()->create([
        'passenger_id' => $this->passenger->id,
        'trip_id' => $this->trip->id,
        'status' => 'completed',
    ]);

    $this->get(route('passenger.bookings.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('passenger/bookings/Index')
            ->has('bookings', 1)
            ->where('bookings.0.id', $active->id));
});

test('the history only lists closed bookings', function () {
    Booking::factory()->create([
        'passenger_id' => $this->passenger->id,
        'trip_id' => $this->trip->id,
        'status' => 'confirmed',
    ]);
    Booking::factory()->create([
        'passenger_id' => $this->passenger->id,
        'trip_id' => $this->trip->id,
        'status' => 'cancelled',
    ]);

    $this->get(route('passenger.history'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('passenger/bookings/History')
            ->has('bookings', 1));
});

test('the notifications page lists in-app notifications', function () {
    $this->passenger->notifications()->create([
        'id' => (string) Str::uuid(),
        'type' => 'test',
        'data' => ['title' => 'Bienvenue', 'body' => 'Votre compte est prêt.'],
        'read_at' => null,
    ]);

    $this->get(route('passenger.notifications.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('passenger/Notifications')
            ->has('notifications', 1)
            ->where('notifications.0.title', 'Bienvenue'));
});

test('a passenger can mark all notifications as read', function () {
    $this->passenger->notifications()->create([
        'id' => (string) Str::uuid(),
        'type' => 'test',
        'data' => ['title' => 'Alerte', 'body' => 'Message.'],
        'read_at' => null,
    ]);

    $this->patch(route('passenger.notifications.read-all'))->assertRedirect();

    expect($this->passenger->unreadNotifications()->count())->toBe(0);
});

test('the profile page renders', function () {
    $this->get(route('passenger.profile'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('passenger/Profile'));
});
