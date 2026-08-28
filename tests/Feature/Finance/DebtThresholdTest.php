<?php

use App\Actions\CreateBooking;
use App\Actions\Payments\ProcessCashPayment;
use App\Actions\PublishTrip;
use App\Enums\DriverStatus;
use App\Enums\TripStatus;
use App\Enums\VehicleStatus;
use App\Models\Booking;
use App\Models\Destination;
use App\Models\Driver;
use App\Models\Transporter;
use App\Models\Trip;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function () {
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    $this->transporter = Transporter::factory()->create();
    $this->trip = Trip::factory()->for($this->transporter)->published()->create([
        'price_per_seat' => 5000,
        'available_seats' => 18,
        'departure_date' => now()->addDays(5)->format('Y-m-d'),
        'departure_time' => '08:30',
    ]);

    $this->makeCashDebt = function (): void {
        $booking = Booking::factory()->create([
            'trip_id' => $this->trip->id,
            'seats' => 1,
            'unit_price' => 5000,
            'total_amount' => 5000,
            'payment_method' => 'cash',
        ]);
        app(ProcessCashPayment::class)($booking);
    };
});

test('exceeding the debt ceiling blocks new bookings', function () {
    config(['allodakar.debt.maximum' => 200]);
    ($this->makeCashDebt)(); // debt 250 > 200

    $passenger = User::factory()->passenger()->create();

    expect(fn () => app(CreateBooking::class)($passenger, [
        'trip_id' => $this->trip->id,
        'seats' => 1,
        'payment_method' => 'cash',
    ]))->toThrow(ValidationException::class);
});

test('exceeding the debt ceiling blocks publishing new trips', function () {
    config(['allodakar.debt.maximum' => 200]);
    ($this->makeCashDebt)(); // debt 250 > 200

    $vehicle = Vehicle::factory()->for($this->transporter)->create(['status' => VehicleStatus::Active, 'capacity' => 20]);
    $driver = Driver::factory()->for($this->transporter)->create(['status' => DriverStatus::Active]);
    $draft = Trip::factory()->for($this->transporter)->create([
        'vehicle_id' => $vehicle->id,
        'driver_id' => $driver->id,
        'departure_destination_id' => Destination::factory()->create()->id,
        'arrival_destination_id' => Destination::factory()->create()->id,
        'capacity' => 20,
        'available_seats' => 20,
        'price_per_seat' => 5000,
        'status' => TripStatus::Draft,
    ]);

    expect(fn () => app(PublishTrip::class)($draft))->toThrow(ValidationException::class);
    expect($draft->fresh()->status)->toBe(TripStatus::Draft);
});

test('below the ceiling bookings still work', function () {
    config(['allodakar.debt.maximum' => 50000]);
    ($this->makeCashDebt)(); // debt 250 < 50000

    $passenger = User::factory()->passenger()->create();
    $booking = app(CreateBooking::class)($passenger, [
        'trip_id' => $this->trip->id,
        'seats' => 1,
        'payment_method' => 'cash',
    ]);

    expect($booking->exists)->toBeTrue();
});
