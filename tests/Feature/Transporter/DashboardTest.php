<?php

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\Payment;
use App\Models\Transporter;
use App\Models\Trip;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function () {
    app(PermissionRegistrar::class)->forgetCachedPermissions();
});

test('the dashboard shows statistics strictly scoped to the connected transporter', function () {
    $transporter = Transporter::factory()->create();
    $transporter->walletOrCreate()->update(['available_balance' => 3000, 'outstanding_debt' => 1000]);

    $trip = Trip::factory()->for($transporter)->published()->create([
        'available_seats' => 10,
        'price_per_seat' => 5000,
    ]);
    Booking::factory()->count(2)->create([
        'trip_id' => $trip->id,
        'seats' => 2,
        'status' => BookingStatus::Confirmed,
    ]);
    Payment::factory()->create([
        'transporter_id' => $transporter->id,
        'amount' => 5000,
        'commission_amount' => 250,
    ]);

    // Another transporter's data must never leak in.
    $other = Transporter::factory()->create();
    Trip::factory()->for($other)->published()->create(['available_seats' => 40]);
    Payment::factory()->create(['transporter_id' => $other->id, 'amount' => 99999]);

    $this->actingAs($transporter->user)
        ->get(route('transporter.dashboard'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('transporter/Dashboard')
            ->where('hasCompany', true)
            ->where('stats.revenue', 5000)
            ->where('stats.commissions', 250)
            ->where('stats.trips', 1)
            ->where('stats.reservations', 2)
            ->where('stats.seats_sold', 4)
            ->where('stats.seats_remaining', 10)
            ->where('stats.debt', 1000)
            ->where('stats.available_balance', 3000)
            ->where('stats.withdrawals', 0)
            ->has('recentPayments', 1)
        );
});

test('a transporter without a company sees an empty dashboard instead of a 403', function () {
    $user = User::factory()->transporter()->create();

    $this->actingAs($user)
        ->get(route('transporter.dashboard'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('hasCompany', false)
            ->where('stats.revenue', 0)
        );
});

test('a passenger cannot access the transporter dashboard', function () {
    $passenger = User::factory()->passenger()->create();

    $this->actingAs($passenger)->get(route('transporter.dashboard'))->assertForbidden();
});

test('guests are redirected to login', function () {
    $this->get(route('transporter.dashboard'))->assertRedirect(route('login'));
});
