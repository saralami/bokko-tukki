<?php

use App\Actions\CreateBooking;
use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\Transporter;
use App\Models\Trip;
use App\Models\User;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function () {
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    $this->passenger = User::factory()->passenger()->create();
    $this->actingAs($this->passenger);

    $this->transporter = Transporter::factory()->create();
    $this->trip = Trip::factory()->for($this->transporter)->published()->create([
        'capacity' => 18,
        'available_seats' => 18,
        'price_per_seat' => 5000,
        'departure_date' => now()->addDays(5)->format('Y-m-d'),
        'departure_time' => '08:30',
    ]);

    $this->book = fn (array $overrides = []) => $this->post(route('passenger.bookings.store'), array_merge([
        'trip_id' => $this->trip->id,
        'seats' => 1,
        'payment_method' => 'cash',
    ], $overrides));
});

test('a passenger can make a normal booking', function () {
    ($this->book)()->assertRedirect();

    $booking = Booking::firstOrFail();

    expect($booking)
        ->passenger_id->toBe($this->passenger->id)
        ->seats->toBe(1)
        ->unit_price->toBe(5000)
        ->total_amount->toBe(5000)
        ->and($booking->reference)->toStartWith('AD-')
        ->and($booking->status)->toBe(BookingStatus::Pending)
        ->and($this->trip->fresh()->available_seats)->toBe(17);
});

test('a passenger can book several seats', function () {
    ($this->book)(['seats' => 3])->assertRedirect();

    expect(Booking::firstOrFail()->total_amount)->toBe(15000)
        ->and($this->trip->fresh()->available_seats)->toBe(15);
});

test('a passenger can book the last seat', function () {
    $this->trip->update(['available_seats' => 1]);

    ($this->book)(['seats' => 1])->assertRedirect();

    expect($this->trip->fresh()->available_seats)->toBe(0);
});

test('booking is refused when it would overbook the trip', function () {
    // 18-seat vehicle, 17 already reserved -> only 1 seat left.
    $this->trip->update(['available_seats' => 1]);

    ($this->book)(['seats' => 2])->assertSessionHasErrors(['seats']);

    expect($this->trip->fresh()->available_seats)->toBe(1);
    $this->assertDatabaseCount('bookings', 0);
});

test('concurrent bookings can never exceed the capacity', function () {
    $trip = Trip::factory()->published()->create(['capacity' => 3, 'available_seats' => 3, 'price_per_seat' => 1000]);
    $createBooking = app(CreateBooking::class);

    $confirmed = 0;
    foreach (User::factory()->count(6)->passenger()->create() as $passenger) {
        try {
            $createBooking($passenger, ['trip_id' => $trip->id, 'seats' => 1, 'payment_method' => 'cash']);
            $confirmed++;
        } catch (ValidationException) {
            // seat no longer available
        }
    }

    expect($confirmed)->toBe(3)
        ->and($trip->fresh()->available_seats)->toBe(0)
        ->and(Booking::where('trip_id', $trip->id)->count())->toBe(3);
});

test('a passenger cannot book the same trip twice', function () {
    ($this->book)()->assertRedirect();
    ($this->book)()->assertSessionHasErrors(['trip_id']);

    $this->assertDatabaseCount('bookings', 1);
});

test('booking is idempotent with an idempotency key', function () {
    ($this->book)(['seats' => 2, 'idempotency_key' => 'idem-123'])->assertRedirect();
    ($this->book)(['seats' => 2, 'idempotency_key' => 'idem-123'])->assertRedirect();

    $this->assertDatabaseCount('bookings', 1);
    expect($this->trip->fresh()->available_seats)->toBe(16);
});

test('a draft trip cannot be booked', function () {
    $draft = Trip::factory()->create(['available_seats' => 10]);

    ($this->book)(['trip_id' => $draft->id])->assertSessionHasErrors(['trip_id']);
});

test('booking is validated', function () {
    ($this->book)(['seats' => 0])->assertSessionHasErrors(['seats']);
    ($this->book)(['payment_method' => 'bitcoin'])->assertSessionHasErrors(['payment_method']);
});

test('a passenger can cancel a booking and the seats are released', function () {
    ($this->book)(['seats' => 2])->assertRedirect();
    $booking = Booking::firstOrFail();

    expect($this->trip->fresh()->available_seats)->toBe(16);

    $this->patch(route('passenger.bookings.cancel', $booking))->assertRedirect();

    expect($booking->fresh()->status)->toBe(BookingStatus::Cancelled)
        ->and($booking->fresh()->cancelled_at)->not->toBeNull()
        ->and($this->trip->fresh()->available_seats)->toBe(18);

    // The booking is never physically deleted.
    $this->assertDatabaseHas('bookings', ['id' => $booking->id, 'status' => 'cancelled']);
});

test('a booking cannot be cancelled past the deadline', function () {
    config(['allodakar.cancellation.deadline_hours' => 2]);

    $trip = Trip::factory()->published()->create([
        'available_seats' => 10,
        'departure_date' => now()->format('Y-m-d'),
        'departure_time' => now()->addHour()->format('H:i'),
    ]);
    $booking = Booking::factory()->create([
        'passenger_id' => $this->passenger->id,
        'trip_id' => $trip->id,
    ]);

    $this->patch(route('passenger.bookings.cancel', $booking))->assertSessionHasErrors(['booking']);

    expect($booking->fresh()->status)->toBe(BookingStatus::Pending);
});

test('a passenger cannot view or cancel another passenger booking', function () {
    $booking = Booking::factory()->create();

    $this->get(route('passenger.bookings.show', $booking))->assertForbidden();
    $this->patch(route('passenger.bookings.cancel', $booking))->assertForbidden();
});

test('a transporter cannot book a trip', function () {
    $transporter = Transporter::factory()->create();

    $this->actingAs($transporter->user)
        ->post(route('passenger.bookings.store'), [
            'trip_id' => $this->trip->id,
            'seats' => 1,
            'payment_method' => 'cash',
        ])->assertForbidden();
});

test('guests are redirected to login', function () {
    auth()->logout();

    $this->get(route('passenger.bookings.index'))->assertRedirect(route('login'));
});
