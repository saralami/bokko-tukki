<?php

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\Driver;
use App\Models\Transporter;
use App\Models\Trip;
use App\Models\User;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function () {
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    $this->transporter = Transporter::factory()->create();
    $this->trip = Trip::factory()->for($this->transporter)->published()->create(['available_seats' => 10]);
    $this->booking = Booking::factory()->create(['trip_id' => $this->trip->id]);
});

test('the transporter owner can confirm boarding', function () {
    $this->actingAs($this->transporter->user)
        ->patch(route('bookings.board', $this->booking))
        ->assertRedirect();

    expect($this->booking->fresh())
        ->status->toBe(BookingStatus::Completed)
        ->and($this->booking->fresh()->boarded_at)->not->toBeNull();
});

test('the assigned driver can confirm boarding', function () {
    $driverUser = User::factory()->driver()->create();
    $driver = Driver::factory()->for($this->transporter)->create(['user_id' => $driverUser->id]);
    $this->trip->update(['driver_id' => $driver->id]);

    $this->actingAs($driverUser)
        ->patch(route('bookings.board', $this->booking))
        ->assertRedirect();

    expect($this->booking->fresh()->status)->toBe(BookingStatus::Completed);
});

test('another transporter cannot confirm boarding', function () {
    $other = Transporter::factory()->create();

    $this->actingAs($other->user)
        ->patch(route('bookings.board', $this->booking))
        ->assertForbidden();
});

test('the passenger cannot confirm their own boarding', function () {
    $this->actingAs($this->booking->passenger)
        ->patch(route('bookings.board', $this->booking))
        ->assertForbidden();
});

test('a passenger can be marked as a no-show', function () {
    $this->actingAs($this->transporter->user)
        ->patch(route('bookings.no-show', $this->booking))
        ->assertRedirect();

    expect($this->booking->fresh()->status)->toBe(BookingStatus::NoShow);
});

test('a cancelled booking cannot be boarded', function () {
    $this->booking->update(['status' => BookingStatus::Cancelled]);

    $this->actingAs($this->transporter->user)
        ->patch(route('bookings.board', $this->booking))
        ->assertSessionHasErrors(['status']);
});

test('guests cannot confirm boarding', function () {
    $this->patch(route('bookings.board', $this->booking))->assertRedirect(route('login'));
});
