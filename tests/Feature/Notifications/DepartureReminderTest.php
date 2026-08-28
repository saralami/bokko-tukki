<?php

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\Transporter;
use App\Models\Trip;
use App\Models\User;
use App\Notifications\DepartureReminderNotification;
use Illuminate\Support\Facades\Notification;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function () {
    app(PermissionRegistrar::class)->forgetCachedPermissions();
    Notification::fake();

    $this->transporter = Transporter::factory()->create();
    $this->passenger = User::factory()->passenger()->create();

    $this->tripDepartingIn = fn (string $date, string $time, array $overrides = []): Trip => Trip::factory()
        ->for($this->transporter)
        ->published()
        ->create(array_merge([
            'departure_date' => $date,
            'departure_time' => $time,
            'available_seats' => 18,
        ], $overrides));

    $this->bookOn = fn (Trip $trip): Booking => Booking::factory()->create([
        'trip_id' => $trip->id,
        'passenger_id' => $this->passenger->id,
        'status' => BookingStatus::Confirmed,
    ]);
});

test('passengers are reminded of an upcoming departure', function () {
    $trip = ($this->tripDepartingIn)(now()->addHours(3)->format('Y-m-d'), now()->addHours(3)->format('H:i'));
    ($this->bookOn)($trip);

    $this->artisan('trips:send-departure-reminders')->assertSuccessful();

    Notification::assertSentTo($this->passenger, DepartureReminderNotification::class);
    expect($trip->fresh()->departure_reminded_at)->not->toBeNull();
});

test('trips outside the reminder window are not reminded', function () {
    $trip = ($this->tripDepartingIn)(now()->addDays(3)->format('Y-m-d'), '08:30');
    ($this->bookOn)($trip);

    $this->artisan('trips:send-departure-reminders')->assertSuccessful();

    Notification::assertNothingSent();
    expect($trip->fresh()->departure_reminded_at)->toBeNull();
});

test('a trip is never reminded twice', function () {
    $trip = ($this->tripDepartingIn)(
        now()->addHours(3)->format('Y-m-d'),
        now()->addHours(3)->format('H:i'),
        ['departure_reminded_at' => now()],
    );
    ($this->bookOn)($trip);

    $this->artisan('trips:send-departure-reminders')->assertSuccessful();

    Notification::assertNothingSent();
});
