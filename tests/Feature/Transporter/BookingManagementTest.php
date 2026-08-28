<?php

use App\Models\Booking;
use App\Models\Transporter;
use App\Models\Trip;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function () {
    app(PermissionRegistrar::class)->forgetCachedPermissions();
});

test('a transporter sees only the bookings of their own trips', function () {
    $transporter = Transporter::factory()->create();
    $trip = Trip::factory()->for($transporter)->published()->create();
    Booking::factory()->count(3)->create(['trip_id' => $trip->id]);

    $other = Transporter::factory()->create();
    $otherTrip = Trip::factory()->for($other)->published()->create();
    Booking::factory()->count(2)->create(['trip_id' => $otherTrip->id]);

    $this->actingAs($transporter->user)
        ->get(route('transporter.bookings.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('transporter/bookings/Index')
            ->has('bookings', 3)
        );
});

test('a passenger cannot access the transporter bookings', function () {
    $passenger = User::factory()->passenger()->create();

    $this->actingAs($passenger)->get(route('transporter.bookings.index'))->assertForbidden();
});

test('guests are redirected to login', function () {
    $this->get(route('transporter.bookings.index'))->assertRedirect(route('login'));
});
