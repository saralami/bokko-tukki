<?php

use App\Actions\Payments\ProcessCashPayment;
use App\Actions\Payments\ProcessMobileMoneyPayment;
use App\Actions\Withdrawals\RequestWithdrawal;
use App\Models\Booking;
use App\Models\LedgerEntry;
use App\Models\Transporter;
use App\Models\Trip;
use App\Models\Wallet;

beforeEach(function () {
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

test('a ledger entry cannot be updated', function () {
    app(ProcessCashPayment::class)(($this->makeBooking)('cash'));
    $entry = LedgerEntry::firstOrFail();

    expect(fn () => $entry->update(['amount' => 1]))->toThrow(RuntimeException::class);
    expect($entry->fresh()->amount)->toBe(250);
});

test('a ledger entry cannot be deleted', function () {
    app(ProcessCashPayment::class)(($this->makeBooking)('cash'));
    $entry = LedgerEntry::firstOrFail();

    expect(fn () => $entry->delete())->toThrow(RuntimeException::class);
    $this->assertDatabaseHas('ledger_entries', ['id' => $entry->id]);
});

test('wallet balances always equal the immutable ledger sum', function () {
    app(ProcessCashPayment::class)(($this->makeBooking)('cash'));
    app(ProcessCashPayment::class)(($this->makeBooking)('cash'));
    app(ProcessMobileMoneyPayment::class)(($this->makeBooking)('mobile_money'), 'MOMO-A', 5000);
    app(RequestWithdrawal::class)($this->transporter, 1000);

    $wallet = ($this->wallet)();

    expect($wallet->available_balance)
        ->toBe((int) LedgerEntry::query()->where('wallet_id', $wallet->id)->sum('balance_delta'))
        ->toBe(3250)
        ->and($wallet->outstanding_debt)
        ->toBe((int) LedgerEntry::query()->where('wallet_id', $wallet->id)->sum('debt_delta'))
        ->toBe(0);
});
