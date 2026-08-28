<?php

namespace App\Http\Controllers\Transporter;

use App\Enums\BookingStatus;
use App\Enums\PaymentStatus;
use App\Enums\TripStatus;
use App\Enums\WithdrawalStatus;
use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Payment;
use App\Models\Transporter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    /**
     * Display the transporter dashboard with data strictly scoped to the connected transporter.
     */
    public function index(Request $request): Response
    {
        $transporter = $request->user()->transporter;

        if ($transporter === null) {
            return Inertia::render('transporter/Dashboard', [
                'hasCompany' => false,
                'status' => null,
                'stats' => $this->emptyStats(),
                'recentPayments' => [],
            ]);
        }

        return Inertia::render('transporter/Dashboard', [
            'hasCompany' => true,
            'status' => $transporter->status->value,
            'stats' => $this->stats($transporter),
            'recentPayments' => $this->recentPayments($transporter),
        ]);
    }

    /**
     * Compute the KPI statistics for the given transporter.
     *
     * @return array<string, int>
     */
    private function stats(Transporter $transporter): array
    {
        $wallet = $transporter->walletOrCreate();

        $bookings = fn (): Builder => Booking::query()
            ->whereHas('trip', fn (Builder $query) => $query->where('transporter_id', $transporter->id));

        $completedPayments = fn (): Builder => Payment::query()
            ->where('transporter_id', $transporter->id)
            ->where('status', PaymentStatus::Completed);

        return [
            'revenue' => (int) $completedPayments()->sum('amount'),
            'commissions' => (int) $completedPayments()->sum('commission_amount'),
            'trips' => $transporter->trips()->count(),
            'reservations' => $bookings()->count(),
            'seats_sold' => (int) $bookings()->where('status', '!=', BookingStatus::Cancelled->value)->sum('seats'),
            'seats_remaining' => (int) $transporter->trips()->where('status', TripStatus::Published)->sum('available_seats'),
            'debt' => $wallet->outstanding_debt,
            'available_balance' => $wallet->available_balance,
            'withdrawals' => (int) $transporter->withdrawals()->where('status', WithdrawalStatus::Paid)->sum('amount'),
        ];
    }

    /**
     * @return array<string, int>
     */
    private function emptyStats(): array
    {
        return [
            'revenue' => 0,
            'commissions' => 0,
            'trips' => 0,
            'reservations' => 0,
            'seats_sold' => 0,
            'seats_remaining' => 0,
            'debt' => 0,
            'available_balance' => 0,
            'withdrawals' => 0,
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function recentPayments(Transporter $transporter): array
    {
        return Payment::query()
            ->where('transporter_id', $transporter->id)
            ->with('booking:id,reference')
            ->latest()
            ->limit(5)
            ->get()
            ->map(fn (Payment $payment): array => [
                'id' => $payment->id,
                'reference' => $payment->booking->reference,
                'amount' => $payment->amount,
                'commission' => $payment->commission_amount,
                'method' => $payment->method->value,
                'status' => $payment->status->value,
                'date' => $payment->created_at?->toDateTimeString(),
            ])
            ->all();
    }
}
