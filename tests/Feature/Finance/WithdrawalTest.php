<?php

use App\Actions\Withdrawals\RequestWithdrawal;
use App\Models\Transporter;
use App\Models\User;
use App\Models\Wallet;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function () {
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    $this->transporter = Transporter::factory()->create();
    $this->transporter->walletOrCreate()->update([
        'available_balance' => 10000,
        'outstanding_debt' => 2000,
    ]);
    $this->actingAs($this->transporter->user);

    $this->wallet = fn (): Wallet => Wallet::query()->where('transporter_id', $this->transporter->id)->firstOrFail();
});

test('a transporter can request a withdrawal within the available balance', function () {
    $this->post(route('transporter.wallet.withdrawals.store'), ['amount' => 4000])->assertRedirect();

    expect(($this->wallet)()->available_balance)->toBe(6000);
    $this->assertDatabaseHas('withdrawals', [
        'transporter_id' => $this->transporter->id,
        'amount' => 4000,
        'status' => 'requested',
    ]);
});

test('a transporter cannot withdraw more than the available balance', function () {
    $this->post(route('transporter.wallet.withdrawals.store'), ['amount' => 15000])->assertSessionHasErrors(['amount']);

    expect(($this->wallet)()->available_balance)->toBe(10000);
    $this->assertDatabaseCount('withdrawals', 0);
});

test('a transporter can never withdraw the debt', function () {
    ($this->wallet)()->update(['available_balance' => 0, 'outstanding_debt' => 5000]);

    $this->post(route('transporter.wallet.withdrawals.store'), ['amount' => 1000])->assertSessionHasErrors(['amount']);
    $this->assertDatabaseCount('withdrawals', 0);
});

test('two successive withdrawals can never exceed the balance', function () {
    $this->post(route('transporter.wallet.withdrawals.store'), ['amount' => 7000])->assertRedirect();
    $this->post(route('transporter.wallet.withdrawals.store'), ['amount' => 5000])->assertSessionHasErrors(['amount']);

    expect(($this->wallet)()->available_balance)->toBe(3000);
    $this->assertDatabaseCount('withdrawals', 1);
});

test('an admin can approve and pay a withdrawal', function () {
    $withdrawal = app(RequestWithdrawal::class)($this->transporter, 3000);
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)->patch(route('admin.withdrawals.approve', $withdrawal))->assertRedirect();
    expect($withdrawal->fresh()->status->value)->toBe('approved');

    $this->actingAs($admin)->patch(route('admin.withdrawals.pay', $withdrawal))->assertRedirect();
    expect($withdrawal->fresh()->status->value)->toBe('paid');
});

test('an admin can reject a withdrawal and the held amount is refunded', function () {
    $withdrawal = app(RequestWithdrawal::class)($this->transporter, 4000);
    expect(($this->wallet)()->available_balance)->toBe(6000);

    $admin = User::factory()->admin()->create();
    $this->actingAs($admin)->patch(route('admin.withdrawals.reject', $withdrawal))->assertRedirect();

    expect($withdrawal->fresh()->status->value)->toBe('rejected')
        ->and(($this->wallet)()->available_balance)->toBe(10000);
});

test('a transporter cannot access the admin withdrawals', function () {
    $this->get(route('admin.withdrawals.index'))->assertForbidden();
});

test('a passenger cannot request a withdrawal', function () {
    $passenger = User::factory()->passenger()->create();

    $this->actingAs($passenger)
        ->post(route('transporter.wallet.withdrawals.store'), ['amount' => 1000])
        ->assertForbidden();
});
