<?php

use App\Actions\CreateBooking;
use App\Actions\Payments\ProcessCashPayment;
use App\Actions\Payments\ProcessMobileMoneyPayment;
use App\Actions\Payments\RefundPayment;
use App\Models\Booking;
use App\Models\LedgerEntry;
use App\Models\Payment;
use App\Models\Transporter;
use App\Models\Trip;
use App\Models\User;
use App\Models\Wallet;
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

    $this->makeBooking = fn (string $method): Booking => Booking::factory()->create([
        'trip_id' => $this->trip->id,
        'seats' => 1,
        'unit_price' => 5000,
        'total_amount' => 5000,
        'payment_method' => $method,
    ]);

    $this->wallet = fn (): Wallet => Wallet::query()->where('transporter_id', $this->transporter->id)->firstOrFail();
});

test('refunding a cash payment reverses the commission debt', function () {
    $payment = app(ProcessCashPayment::class)(($this->makeBooking)('cash'));
    expect(($this->wallet)()->outstanding_debt)->toBe(250);

    app(RefundPayment::class)($payment);

    expect(($this->wallet)()->outstanding_debt)->toBe(0)
        ->and($payment->fresh()->status->value)->toBe('refunded');
});

test('refunding a mobile money payment restores debt and reverses the balance', function () {
    for ($i = 0; $i < 4; $i++) {
        app(ProcessCashPayment::class)(($this->makeBooking)('cash'));
    }
    $payment = app(ProcessMobileMoneyPayment::class)(($this->makeBooking)('mobile_money'), 'MOMO-R', 5000);
    expect(($this->wallet)())->available_balance->toBe(3750)->outstanding_debt->toBe(0);

    app(RefundPayment::class)($payment);

    expect(($this->wallet)())->available_balance->toBe(0)->outstanding_debt->toBe(1000)
        ->and($payment->fresh()->status->value)->toBe('refunded');
});

test('a payment cannot be refunded twice', function () {
    $payment = app(ProcessCashPayment::class)(($this->makeBooking)('cash'));

    app(RefundPayment::class)($payment);

    expect(fn () => app(RefundPayment::class)($payment->fresh()))->toThrow(ValidationException::class);
});

test('a refund adds a compensating entry without altering the original', function () {
    $payment = app(ProcessCashPayment::class)(($this->makeBooking)('cash'));
    $original = LedgerEntry::query()->where('payment_id', $payment->id)->firstOrFail();
    $originalAmount = $original->amount;

    app(RefundPayment::class)($payment);

    expect($original->fresh()->amount)->toBe($originalAmount);
    $this->assertDatabaseHas('ledger_entries', ['payment_id' => $payment->id, 'type' => 'refund']);
});

test('cancelling a paid booking refunds the passenger', function () {
    $passenger = User::factory()->passenger()->create();
    $booking = app(CreateBooking::class)($passenger, [
        'trip_id' => $this->trip->id,
        'seats' => 1,
        'payment_method' => 'mobile_money',
    ]);
    app(ProcessMobileMoneyPayment::class)($booking->fresh(), 'MOMO-C', 5000);
    expect(($this->wallet)()->available_balance)->toBe(4750);

    $this->actingAs($passenger)->patch(route('passenger.bookings.cancel', $booking))->assertRedirect();

    expect($booking->fresh()->status->value)->toBe('cancelled')
        ->and(Payment::query()->where('booking_id', $booking->id)->firstOrFail()->status->value)->toBe('refunded')
        ->and(($this->wallet)()->available_balance)->toBe(0);
});
