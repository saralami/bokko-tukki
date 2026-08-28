<?php

use App\Actions\Payments\ProcessCashPayment;
use App\Actions\Payments\ProcessMobileMoneyPayment;
use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\Payment;
use App\Models\Transporter;
use App\Models\Trip;
use App\Models\Wallet;

beforeEach(function () {
    $this->transporter = Transporter::factory()->create();
    $this->trip = Trip::factory()->for($this->transporter)->published()->create([
        'price_per_seat' => 5000,
        'capacity' => 18,
        'available_seats' => 18,
        'departure_date' => now()->addDays(5)->format('Y-m-d'),
        'departure_time' => '08:30',
    ]);

    $this->makeBooking = fn (string $method, int $total = 5000): Booking => Booking::factory()->create([
        'trip_id' => $this->trip->id,
        'seats' => 1,
        'unit_price' => $total,
        'total_amount' => $total,
        'payment_method' => $method,
    ]);

    $this->wallet = fn (): ?Wallet => Wallet::query()->where('transporter_id', $this->transporter->id)->first();
});

test('a cash payment turns the commission into transporter debt', function () {
    $booking = ($this->makeBooking)('cash', 5000);

    app(ProcessCashPayment::class)($booking);

    expect(($this->wallet)())
        ->outstanding_debt->toBe(250)
        ->available_balance->toBe(0);

    $this->assertDatabaseHas('payments', [
        'booking_id' => $booking->id,
        'method' => 'cash',
        'commission_amount' => 250,
        'status' => 'completed',
    ]);
});

test('the commission uses the configured rate', function () {
    config(['allodakar.commission.rate' => 0.10]);

    app(ProcessCashPayment::class)(($this->makeBooking)('cash', 5000));

    expect(($this->wallet)()->outstanding_debt)->toBe(500);
});

test('mobile money applies commission, then old debts, then the balance', function () {
    // Four cash bookings build a 1 000 FCFA debt (4 x 250).
    for ($i = 0; $i < 4; $i++) {
        app(ProcessCashPayment::class)(($this->makeBooking)('cash', 5000));
    }
    expect(($this->wallet)()->outstanding_debt)->toBe(1000);

    $booking = ($this->makeBooking)('mobile_money', 5000);
    app(ProcessMobileMoneyPayment::class)($booking, 'MOMO-1', 5000);

    // 250 -> commission, 1 000 -> old debt, 3 750 -> available balance.
    expect(($this->wallet)())
        ->available_balance->toBe(3750)
        ->outstanding_debt->toBe(0);

    expect($booking->fresh()->status)->toBe(BookingStatus::Confirmed);
    $this->assertDatabaseHas('payments', [
        'booking_id' => $booking->id,
        'method' => 'mobile_money',
        'commission_amount' => 250,
        'provider_reference' => 'MOMO-1',
        'status' => 'completed',
    ]);
});

test('a mobile money payment is never processed twice', function () {
    $booking = ($this->makeBooking)('mobile_money', 5000);

    app(ProcessMobileMoneyPayment::class)($booking, 'MOMO-1', 5000);
    app(ProcessMobileMoneyPayment::class)($booking, 'MOMO-1', 5000);

    expect(Payment::count())->toBe(1)
        ->and(($this->wallet)()->available_balance)->toBe(4750);
});

test('a cash payment is idempotent per booking', function () {
    $booking = ($this->makeBooking)('cash', 5000);

    app(ProcessCashPayment::class)($booking);
    app(ProcessCashPayment::class)($booking);

    expect(Payment::count())->toBe(1)
        ->and(($this->wallet)()->outstanding_debt)->toBe(250);
});

test('a repeated webhook never credits the wallet twice', function () {
    $booking = ($this->makeBooking)('mobile_money', 5000);
    $payload = [
        'booking_reference' => $booking->reference,
        'provider_reference' => 'MOMO-9',
        'amount' => 5000,
    ];
    $headers = ['X-Webhook-Secret' => config('allodakar.mobile_money.webhook_secret')];

    $this->withHeaders($headers)->postJson(route('webhooks.mobile-money'), $payload)->assertOk();
    $this->withHeaders($headers)->postJson(route('webhooks.mobile-money'), $payload)->assertOk();

    expect(Payment::count())->toBe(1)
        ->and(($this->wallet)()->available_balance)->toBe(4750);
});

test('the webhook rejects an invalid secret', function () {
    $booking = ($this->makeBooking)('mobile_money', 5000);

    $this->withHeaders(['X-Webhook-Secret' => 'wrong'])
        ->postJson(route('webhooks.mobile-money'), [
            'booking_reference' => $booking->reference,
            'provider_reference' => 'MOMO-1',
            'amount' => 5000,
        ])->assertStatus(401);

    expect(Payment::count())->toBe(0);
});

test('the webhook rejects an amount mismatch', function () {
    $booking = ($this->makeBooking)('mobile_money', 5000);

    $this->withHeaders(['X-Webhook-Secret' => config('allodakar.mobile_money.webhook_secret')])
        ->postJson(route('webhooks.mobile-money'), [
            'booking_reference' => $booking->reference,
            'provider_reference' => 'MOMO-1',
            'amount' => 4000,
        ])->assertStatus(422);

    expect(Payment::count())->toBe(0);
});
