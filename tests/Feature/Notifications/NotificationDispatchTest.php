<?php

use App\Actions\CancelBooking;
use App\Actions\CreateBooking;
use App\Actions\Payments\ProcessCashPayment;
use App\Actions\Payments\ProcessMobileMoneyPayment;
use App\Actions\Withdrawals\RequestWithdrawal;
use App\Enums\DriverStatus;
use App\Enums\VehicleStatus;
use App\Models\Booking;
use App\Models\Driver;
use App\Models\Transporter;
use App\Models\Trip;
use App\Models\User;
use App\Models\Vehicle;
use App\Notifications\BookingCancelledNotification;
use App\Notifications\BookingConfirmedNotification;
use App\Notifications\BookingCreatedNotification;
use App\Notifications\DriverBookingCancelledNotification;
use App\Notifications\DriverNewBookingNotification;
use App\Notifications\DriverTripUpdatedNotification;
use App\Notifications\PaymentConfirmedNotification;
use App\Notifications\TransporterCommissionNotification;
use App\Notifications\TransporterDebtNotification;
use App\Notifications\TransporterDebtThresholdNotification;
use App\Notifications\TransporterNewBookingNotification;
use App\Notifications\TransporterPaymentReceivedNotification;
use App\Notifications\TransporterWithdrawalNotification;
use Illuminate\Support\Facades\Notification;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function () {
    app(PermissionRegistrar::class)->forgetCachedPermissions();
    Notification::fake();

    $this->transporter = Transporter::factory()->create();
    $this->driver = Driver::factory()->for($this->transporter)->create(['status' => DriverStatus::Active]);
    $this->trip = Trip::factory()->for($this->transporter)->published()->create([
        'driver_id' => $this->driver->id,
        'price_per_seat' => 5000,
        'available_seats' => 18,
        'departure_date' => now()->addDays(5)->format('Y-m-d'),
        'departure_time' => '08:30',
    ]);
    $this->passenger = User::factory()->passenger()->create();

    $this->book = fn (string $method): Booking => app(CreateBooking::class)($this->passenger, [
        'trip_id' => $this->trip->id,
        'seats' => 1,
        'payment_method' => $method,
    ]);
});

test('creating a booking notifies the passenger, transporter and driver', function () {
    ($this->book)('cash');

    Notification::assertSentTo($this->passenger, BookingCreatedNotification::class);
    Notification::assertSentTo($this->transporter->user, TransporterNewBookingNotification::class);
    Notification::assertSentTo($this->driver, DriverNewBookingNotification::class);
});

test('a mobile money payment notifies the passenger and transporter', function () {
    $booking = ($this->book)('mobile_money');

    app(ProcessMobileMoneyPayment::class)($booking->fresh(), 'MOMO-1', 5000);

    Notification::assertSentTo($this->passenger, BookingConfirmedNotification::class);
    Notification::assertSentTo($this->passenger, PaymentConfirmedNotification::class);
    Notification::assertSentTo($this->transporter->user, TransporterPaymentReceivedNotification::class);
    Notification::assertSentTo($this->transporter->user, TransporterCommissionNotification::class);
});

test('a cash payment notifies the transporter of commission and debt', function () {
    $booking = ($this->book)('cash');

    app(ProcessCashPayment::class)($booking->fresh());

    Notification::assertSentTo($this->passenger, PaymentConfirmedNotification::class);
    Notification::assertSentTo($this->transporter->user, TransporterCommissionNotification::class);
    Notification::assertSentTo($this->transporter->user, TransporterDebtNotification::class);
});

test('exceeding the debt ceiling notifies the transporter', function () {
    config(['allodakar.debt.maximum' => 200]);
    $booking = ($this->book)('cash');

    app(ProcessCashPayment::class)($booking->fresh());

    Notification::assertSentTo($this->transporter->user, TransporterDebtThresholdNotification::class);
});

test('cancelling a booking notifies the passenger and driver', function () {
    $booking = ($this->book)('cash');

    app(CancelBooking::class)($booking->fresh());

    Notification::assertSentTo($this->passenger, BookingCancelledNotification::class);
    Notification::assertSentTo($this->driver, DriverBookingCancelledNotification::class);
});

test('updating a trip notifies the driver', function () {
    $vehicle = Vehicle::factory()->for($this->transporter)->create(['capacity' => 20, 'status' => VehicleStatus::Active]);

    $this->actingAs($this->transporter->user)
        ->patch(route('transporter.trips.update', $this->trip), [
            'vehicle_id' => $vehicle->id,
            'driver_id' => $this->driver->id,
            'departure_destination_id' => $this->trip->departure_destination_id,
            'arrival_destination_id' => $this->trip->arrival_destination_id,
            'departure_date' => now()->addDays(6)->format('Y-m-d'),
            'departure_time' => '09:00',
            'price_per_seat' => 6000,
        ])->assertRedirect();

    Notification::assertSentTo($this->driver, DriverTripUpdatedNotification::class);
});

test('a withdrawal request notifies the transporter', function () {
    $this->transporter->walletOrCreate()->update(['available_balance' => 10000]);

    app(RequestWithdrawal::class)($this->transporter, 3000);

    Notification::assertSentTo($this->transporter->user, TransporterWithdrawalNotification::class);
});
