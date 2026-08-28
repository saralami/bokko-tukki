<?php

namespace App\Http\Controllers\Admin;

use App\Enums\DriverStatus;
use App\Enums\PaymentStatus;
use App\Enums\TransporterStatus;
use App\Enums\WithdrawalStatus;
use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Booking;
use App\Models\Driver;
use App\Models\Payment;
use App\Models\Transporter;
use App\Models\Trip;
use App\Models\User;
use App\Models\Wallet;
use App\Models\Withdrawal;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    /**
     * Display the platform-wide administration dashboard.
     */
    public function index(): Response
    {
        $completed = fn () => Payment::query()->where('status', PaymentStatus::Completed);

        return Inertia::render('admin/Dashboard', [
            'stats' => [
                'users' => User::query()->count(),
                'activeTransporters' => Transporter::query()->where('status', TransporterStatus::Active)->count(),
                'activeDrivers' => Driver::query()->where('status', DriverStatus::Active)->count(),
                'trips' => Trip::query()->count(),
                'bookings' => Booking::query()->count(),
                'financialVolume' => (int) $completed()->sum('amount'),
                'commissions' => (int) $completed()->sum('commission_amount'),
                'transporterDebt' => (int) Wallet::query()->sum('outstanding_debt'),
                'pendingWithdrawals' => Withdrawal::query()
                    ->whereIn('status', [WithdrawalStatus::Requested, WithdrawalStatus::Approved])
                    ->count(),
                'pendingWithdrawalsAmount' => (int) Withdrawal::query()
                    ->whereIn('status', [WithdrawalStatus::Requested, WithdrawalStatus::Approved])
                    ->sum('amount'),
            ],
            'recentAudit' => AuditLog::query()
                ->with('user:id,name')
                ->latest()
                ->take(6)
                ->get()
                ->map(fn (AuditLog $log): array => [
                    'id' => $log->id,
                    'action' => $log->action,
                    'description' => $log->description,
                    'user' => $log->user?->name,
                    'date' => $log->created_at?->toIso8601String(),
                ])
                ->all(),
        ]);
    }
}
