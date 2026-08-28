<?php

use App\Actions\CancelBooking;
use App\Actions\Payments\ProcessMobileMoneyPayment;
use App\Actions\Withdrawals\RejectWithdrawal;
use App\Actions\Withdrawals\RequestWithdrawal;
use App\Enums\BookingStatus;
use App\Enums\LedgerEntryType;
use App\Enums\PaymentStatus;
use App\Enums\WithdrawalStatus;
use App\Models\Booking;
use App\Models\LedgerEntry;
use App\Models\Payment;
use App\Models\Transporter;
use App\Models\Trip;
use App\Models\Wallet;
use App\Models\Withdrawal;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function () {
    app(PermissionRegistrar::class)->forgetCachedPermissions();
});

test('two concurrent cancellations release seats and refund only once', function () {
    // Capacity 20 with only 8 seats free (others still hold seats), so releasing
    // our 2 seats must leave 10 — a second release would wrongly inflate it to 12.
    $trip = Trip::factory()->published()->create([
        'capacity' => 20,
        'available_seats' => 8,
        'price_per_seat' => 5000,
        'departure_date' => now()->addDays(3)->format('Y-m-d'),
        'departure_time' => '08:00',
    ]);
    $booking = Booking::factory()->create([
        'trip_id' => $trip->id,
        'seats' => 2,
        'total_amount' => 10000,
        'payment_method' => 'mobile_money',
        'status' => BookingStatus::Confirmed,
    ]);
    app(ProcessMobileMoneyPayment::class)($booking, 'PROV-RACE', 10000);

    // Two independent instances both read the booking as still active.
    $first = Booking::query()->findOrFail($booking->id);
    $second = Booking::query()->findOrFail($booking->id);

    app(CancelBooking::class)($first);

    // The second cancellation (loaded before the first committed) must be a no-op
    // thanks to the locked re-check: seats are not released twice, no double refund.
    try {
        app(CancelBooking::class)($second);
    } catch (ValidationException) {
        // Also acceptable: the guard rejects the duplicate.
    }

    expect($trip->fresh()->available_seats)->toBe(10) // released once, not twice (would be 12)
        ->and($booking->fresh()->status)->toBe(BookingStatus::Cancelled)
        ->and(Payment::where('booking_id', $booking->id)->where('status', PaymentStatus::Refunded)->count())->toBe(1)
        ->and(LedgerEntry::query()->where('type', LedgerEntryType::Refund)->count())->toBe(1);
});

test('two concurrent rejections reverse a withdrawal only once', function () {
    $transporter = Transporter::factory()->create();
    $wallet = $transporter->walletOrCreate();
    Wallet::query()->whereKey($wallet->id)->update(['available_balance' => 20000]);

    $withdrawal = app(RequestWithdrawal::class)($transporter->fresh(), 8000);

    expect($transporter->walletOrCreate()->fresh()->available_balance)->toBe(12000);

    $first = Withdrawal::query()->findOrFail($withdrawal->id);
    $second = Withdrawal::query()->findOrFail($withdrawal->id);

    app(RejectWithdrawal::class)($first);

    try {
        app(RejectWithdrawal::class)($second);
    } catch (ValidationException) {
        // Expected: the duplicate rejection is refused by the locked re-check.
    }

    // The held amount is credited back exactly once.
    expect($transporter->walletOrCreate()->fresh()->available_balance)->toBe(20000)
        ->and($withdrawal->fresh()->status)->toBe(WithdrawalStatus::Rejected)
        ->and(LedgerEntry::query()->where('withdrawal_id', $withdrawal->id)->where('type', LedgerEntryType::WithdrawalReversal)->count())->toBe(1);
});
