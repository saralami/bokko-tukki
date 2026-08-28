<?php

use App\Actions\Payments\ProcessMobileMoneyPayment;
use App\Enums\BookingStatus;
use App\Enums\PaymentStatus;
use App\Enums\TransporterStatus;
use App\Models\AuditLog;
use App\Models\Booking;
use App\Models\Payment;
use App\Models\Transporter;
use App\Models\Trip;
use App\Models\User;
use App\Support\Settings;
use Illuminate\Support\Facades\Route;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function () {
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    $this->admin = User::factory()->admin()->create();
    $this->actingAs($this->admin);
});

test('the admin dashboard exposes platform KPIs', function () {
    $this->get(route('admin.dashboard'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/Dashboard')
            ->has('stats.users')
            ->has('stats.commissions')
            ->has('stats.pendingWithdrawals'));
});

test('a non-admin cannot reach the admin backoffice', function () {
    $this->actingAs(User::factory()->passenger()->create());

    $this->get(route('admin.dashboard'))->assertForbidden();
    $this->get(route('admin.finance.ledger'))->assertForbidden();
    $this->get(route('admin.users.index'))->assertForbidden();
});

test('a suspended user is blocked from the application', function () {
    $user = User::factory()->passenger()->create(['suspended_at' => now()]);

    $this->actingAs($user)->get(route('passenger.dashboard'))->assertRedirect(route('login'));
});

test('an admin can suspend and reinstate a user, and it is audited', function () {
    $user = User::factory()->passenger()->create();

    $this->patch(route('admin.users.suspension', $user))->assertRedirect();
    expect($user->fresh()->suspended_at)->not->toBeNull();
    $this->assertDatabaseHas('audit_logs', ['action' => 'user.suspended', 'auditable_id' => $user->id]);

    $this->patch(route('admin.users.suspension', $user))->assertRedirect();
    expect($user->fresh()->suspended_at)->toBeNull();
    $this->assertDatabaseHas('audit_logs', ['action' => 'user.reinstated']);
});

test('an admin cannot suspend their own account', function () {
    $this->patch(route('admin.users.suspension', $this->admin))->assertSessionHasErrors(['user']);

    expect($this->admin->fresh()->suspended_at)->toBeNull();
});

test('an admin can change a user role and it is audited', function () {
    // Ensure the target role exists (roles are created lazily by the factory states).
    User::factory()->driver()->create();
    $user = User::factory()->passenger()->create();

    $this->patch(route('admin.users.role', $user), ['role' => 'driver'])->assertRedirect();

    expect($user->fresh()->hasRole('driver'))->toBeTrue();
    $this->assertDatabaseHas('audit_logs', ['action' => 'user.role_changed', 'auditable_id' => $user->id]);
});

test('an admin can change a transporter status with an audit trail', function () {
    $transporter = Transporter::factory()->create(['status' => TransporterStatus::Pending]);

    $this->patch(route('admin.transporters.status', $transporter), ['status' => 'active'])->assertRedirect();

    expect($transporter->fresh()->status)->toBe(TransporterStatus::Active);
    $this->assertDatabaseHas('audit_logs', ['action' => 'transporter.status_changed']);
});

test('an admin can cancel a trip with an audit trail', function () {
    $trip = Trip::factory()->published()->create();

    $this->patch(route('admin.trips.cancel', $trip))->assertRedirect();

    expect($trip->fresh()->status->value)->toBe('cancelled');
    $this->assertDatabaseHas('audit_logs', ['action' => 'trip.cancelled', 'auditable_id' => $trip->id]);
});

test('the finance transactions view lists payments and totals', function () {
    $this->get(route('admin.finance.transactions'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/finance/Transactions')
            ->has('totals.volume')
            ->has('payments.data'));
});

test('a refund requires a justification', function () {
    $payment = makeCompletedPayment();

    $this->post(route('admin.finance.payments.refund', $payment))->assertSessionHasErrors(['reason']);

    expect($payment->fresh()->status)->toBe(PaymentStatus::Completed);
});

test('a refund posts a compensating entry and never edits the transaction', function () {
    $payment = makeCompletedPayment();
    $entriesBefore = $payment->transporter->walletOrCreate()->ledgerEntries()->count();

    $this->post(route('admin.finance.payments.refund', $payment), ['reason' => 'Trajet annulé par le transporteur.'])
        ->assertRedirect();

    expect($payment->fresh()->status)->toBe(PaymentStatus::Refunded);

    // A new compensating ledger entry is added; nothing is edited or removed.
    $this->assertDatabaseHas('ledger_entries', ['payment_id' => $payment->id, 'type' => 'refund']);
    expect($payment->transporter->walletOrCreate()->ledgerEntries()->count())->toBeGreaterThan($entriesBefore);
    $this->assertDatabaseHas('audit_logs', ['action' => 'payment.refunded', 'auditable_id' => $payment->id]);
});

test('the immutable ledger cannot be edited through any admin route', function () {
    // The admin ledger is read-only: there is no update/delete route registered.
    expect(fn () => Route::has('admin.finance.ledger.update'))->not->toThrow(Exception::class);

    expect(Route::has('admin.finance.ledger.update'))->toBeFalse();
    expect(Route::has('admin.finance.ledger.destroy'))->toBeFalse();
});

test('an admin can update business settings which are persisted and audited', function () {
    $this->patch(route('admin.settings.update'), [
        'settings' => [
            'commission.rate' => 0.1,
            'debt.maximum' => 75000,
            'cancellation.deadline_hours' => 3,
            'reminder.lead_hours' => 12,
        ],
    ])->assertRedirect();

    expect(Settings::get('commission.rate'))->toBe(0.1)
        ->and(Settings::get('debt.maximum'))->toBe(75000);

    $this->assertDatabaseHas('settings', ['key' => 'commission.rate', 'value' => '0.1']);
    $this->assertDatabaseHas('audit_logs', ['action' => 'setting.updated']);
});

test('the audit log view lists sensitive operations', function () {
    AuditLog::factory()->create(['action' => 'user.suspended', 'user_id' => $this->admin->id]);

    $this->get(route('admin.audit-logs.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('admin/AuditLogs')->has('logs.data', 1));
});

/**
 * Build a completed Mobile Money payment (with its ledger entries) so refunds
 * have a real net effect to compensate.
 */
function makeCompletedPayment(): Payment
{
    $transporter = Transporter::factory()->create();
    $trip = Trip::factory()->for($transporter)->published()->create(['price_per_seat' => 5000, 'available_seats' => 10]);
    $booking = Booking::factory()->create([
        'trip_id' => $trip->id,
        'payment_method' => 'mobile_money',
        'total_amount' => 10000,
        'seats' => 2,
        'status' => BookingStatus::Confirmed,
    ]);

    return app(ProcessMobileMoneyPayment::class)($booking, 'PROV-'.uniqid(), 10000);
}
