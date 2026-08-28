<?php

use App\Http\Controllers\Admin\AuditLogController;
use App\Http\Controllers\Admin\BookingController as AdminBookingController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\DestinationController;
use App\Http\Controllers\Admin\DriverController as AdminDriverController;
use App\Http\Controllers\Admin\FinanceController;
use App\Http\Controllers\Admin\SettingController;
use App\Http\Controllers\Admin\TransporterController as AdminTransporterController;
use App\Http\Controllers\Admin\TripController as AdminTripController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\Admin\VehicleController as AdminVehicleController;
use App\Http\Controllers\Admin\WithdrawalController;
use App\Http\Controllers\Auth\TransporterRegisterController;
use App\Http\Controllers\BoardingController;
use App\Http\Controllers\DashboardController as HomeDashboardController;
use App\Http\Controllers\Driver\BoardingController as DriverBoardingController;
use App\Http\Controllers\Driver\DashboardController as DriverDashboardController;
use App\Http\Controllers\Driver\IncidentController as DriverIncidentController;
use App\Http\Controllers\Driver\TripController as DriverTripController;
use App\Http\Controllers\Passenger\BookingController;
use App\Http\Controllers\Passenger\HomeController;
use App\Http\Controllers\Passenger\NotificationController;
use App\Http\Controllers\Passenger\PaymentController;
use App\Http\Controllers\Passenger\SearchController;
use App\Http\Controllers\Transporter\BookingController as TransporterBookingController;
use App\Http\Controllers\Transporter\DashboardController;
use App\Http\Controllers\Transporter\DriverController;
use App\Http\Controllers\Transporter\TransporterController;
use App\Http\Controllers\Transporter\TripController;
use App\Http\Controllers\Transporter\VehicleController;
use App\Http\Controllers\Transporter\WalletController;
use App\Http\Controllers\Webhooks\MobileMoneyWebhookController;
use App\Http\Middleware\EnsureUserIsNotSuspended;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'Welcome')->name('home');

// Public transport-company registration (pending admin validation).
Route::middleware('guest')->group(function () {
    Route::get('transporter/register', [TransporterRegisterController::class, 'create'])->name('transporter.register');
    Route::post('transporter/register', [TransporterRegisterController::class, 'store'])->name('transporter.register.store');
});

// Public, secret-authenticated and idempotent Mobile Money payment webhook.
Route::post('webhooks/mobile-money', [MobileMoneyWebhookController::class, 'handle'])->name('webhooks.mobile-money');

Route::middleware(['auth', 'verified', EnsureUserIsNotSuspended::class])->group(function () {
    Route::get('dashboard', [HomeDashboardController::class, 'index'])->name('dashboard');

    // Boarding is authorized by policy for the trip's transporter owner or assigned driver.
    Route::patch('bookings/{booking}/board', [BoardingController::class, 'board'])->name('bookings.board');
    Route::patch('bookings/{booking}/no-show', [BoardingController::class, 'noShow'])->name('bookings.no-show');

    Route::middleware('role:passenger')->prefix('passenger')->name('passenger.')->group(function () {
        Route::get('dashboard', [HomeController::class, 'index'])->name('dashboard');

        Route::get('search', [SearchController::class, 'index'])->name('search');
        Route::get('trips/{trip}', [SearchController::class, 'show'])->name('trips.show');
        Route::get('trips/{trip}/book', [BookingController::class, 'create'])->name('bookings.create');

        Route::get('bookings', [BookingController::class, 'index'])->name('bookings.index');
        Route::get('history', [BookingController::class, 'history'])->name('history');
        Route::post('bookings', [BookingController::class, 'store'])->name('bookings.store');
        Route::get('bookings/{booking}', [BookingController::class, 'show'])->name('bookings.show');
        Route::get('bookings/{booking}/payment', [PaymentController::class, 'show'])->name('bookings.payment');
        Route::patch('bookings/{booking}/cancel', [BookingController::class, 'cancel'])->name('bookings.cancel');

        Route::get('notifications', [NotificationController::class, 'index'])->name('notifications.index');
        Route::patch('notifications/read-all', [NotificationController::class, 'markAllAsRead'])->name('notifications.read-all');
        Route::patch('notifications/{notification}/read', [NotificationController::class, 'markAsRead'])->name('notifications.read');

        Route::inertia('profile', 'passenger/Profile')->name('profile');
    });

    Route::middleware('role:driver')->prefix('driver')->name('driver.')->group(function () {
        Route::get('dashboard', [DriverDashboardController::class, 'index'])->name('dashboard');

        Route::get('trips', [DriverTripController::class, 'index'])->name('trips.index');
        Route::get('history', [DriverTripController::class, 'history'])->name('history');
        Route::get('trips/{trip}', [DriverTripController::class, 'show'])->name('trips.show');

        Route::get('boarding', [DriverBoardingController::class, 'create'])->name('boarding.create');
        Route::post('boarding', [DriverBoardingController::class, 'store'])->name('boarding.store');

        Route::post('incidents', [DriverIncidentController::class, 'store'])->name('incidents.store');

        Route::inertia('profile', 'driver/Profile')->name('profile');
    });

    Route::middleware('role:transporter')->prefix('transporter')->name('transporter.')->group(function () {
        Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');
        Route::get('bookings', [TransporterBookingController::class, 'index'])->name('bookings.index');

        Route::get('company', [TransporterController::class, 'edit'])->name('company.edit');
        Route::patch('transporters/{transporter}', [TransporterController::class, 'update'])->name('transporters.update');

        Route::patch('vehicles/{vehicle}/status', [VehicleController::class, 'updateStatus'])->name('vehicles.status');
        Route::resource('vehicles', VehicleController::class)->except(['show']);

        Route::patch('drivers/{driver}/status', [DriverController::class, 'updateStatus'])->name('drivers.status');
        Route::resource('drivers', DriverController::class)->except(['show']);

        Route::patch('trips/{trip}/publish', [TripController::class, 'publish'])->name('trips.publish');
        Route::patch('trips/{trip}/cancel', [TripController::class, 'cancel'])->name('trips.cancel');
        Route::resource('trips', TripController::class)->except(['destroy']);

        Route::get('wallet', [WalletController::class, 'index'])->name('wallet.index');
        Route::post('wallet/withdrawals', [WalletController::class, 'requestWithdrawal'])->name('wallet.withdrawals.store');
    });

    Route::middleware('role:admin')->prefix('admin')->name('admin.')->group(function () {
        Route::get('dashboard', [AdminDashboardController::class, 'index'])->name('dashboard');

        // Users
        Route::get('users', [AdminUserController::class, 'index'])->name('users.index');
        Route::get('users/{user}', [AdminUserController::class, 'show'])->name('users.show');
        Route::patch('users/{user}/suspension', [AdminUserController::class, 'toggleSuspension'])->name('users.suspension');
        Route::patch('users/{user}/role', [AdminUserController::class, 'updateRole'])->name('users.role');

        // Transporters
        Route::get('transporters', [AdminTransporterController::class, 'index'])->name('transporters.index');
        Route::get('transporters/{transporter}', [AdminTransporterController::class, 'show'])->name('transporters.show');
        Route::patch('transporters/{transporter}/status', [AdminTransporterController::class, 'updateStatus'])->name('transporters.status');

        // Fleet oversight
        Route::get('drivers', [AdminDriverController::class, 'index'])->name('drivers.index');
        Route::get('vehicles', [AdminVehicleController::class, 'index'])->name('vehicles.index');

        Route::resource('destinations', DestinationController::class)->except(['show']);

        // Trips
        Route::get('trips', [AdminTripController::class, 'index'])->name('trips.index');
        Route::get('trips/{trip}', [AdminTripController::class, 'show'])->name('trips.show');
        Route::patch('trips/{trip}/cancel', [AdminTripController::class, 'cancel'])->name('trips.cancel');

        // Bookings
        Route::get('bookings', [AdminBookingController::class, 'index'])->name('bookings.index');
        Route::get('bookings/{booking}', [AdminBookingController::class, 'show'])->name('bookings.show');

        // Finance (auditable, read-only + compensating refunds)
        Route::get('finance/transactions', [FinanceController::class, 'transactions'])->name('finance.transactions');
        Route::get('finance/commissions', [FinanceController::class, 'commissions'])->name('finance.commissions');
        Route::get('finance/debts', [FinanceController::class, 'debts'])->name('finance.debts');
        Route::get('finance/refunds', [FinanceController::class, 'refunds'])->name('finance.refunds');
        Route::get('finance/ledger', [FinanceController::class, 'ledger'])->name('finance.ledger');
        Route::post('finance/payments/{payment}/refund', [FinanceController::class, 'refund'])->name('finance.payments.refund');

        // Withdrawals
        Route::get('withdrawals', [WithdrawalController::class, 'index'])->name('withdrawals.index');
        Route::patch('withdrawals/{withdrawal}/approve', [WithdrawalController::class, 'approve'])->name('withdrawals.approve');
        Route::patch('withdrawals/{withdrawal}/pay', [WithdrawalController::class, 'pay'])->name('withdrawals.pay');
        Route::patch('withdrawals/{withdrawal}/reject', [WithdrawalController::class, 'reject'])->name('withdrawals.reject');

        // Settings & audit
        Route::get('settings', [SettingController::class, 'edit'])->name('settings.edit');
        Route::patch('settings', [SettingController::class, 'update'])->name('settings.update');
        Route::get('audit-logs', [AuditLogController::class, 'index'])->name('audit-logs.index');
    });
});

require __DIR__.'/settings.php';
