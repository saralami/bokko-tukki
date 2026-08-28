<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Withdrawals\RejectWithdrawal;
use App\Enums\WithdrawalStatus;
use App\Http\Controllers\Controller;
use App\Models\Withdrawal;
use App\Notifications\TransporterWithdrawalNotification;
use App\Support\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class WithdrawalController extends Controller
{
    /**
     * List every withdrawal request.
     */
    public function index(): Response
    {
        $withdrawals = Withdrawal::query()
            ->with('transporter:id,company_name')
            ->latest()
            ->get()
            ->map(fn (Withdrawal $withdrawal): array => [
                'id' => $withdrawal->id,
                'transporter' => $withdrawal->transporter->company_name,
                'amount' => $withdrawal->amount,
                'status' => $withdrawal->status->value,
                'date' => $withdrawal->created_at?->toDateTimeString(),
            ]);

        return Inertia::render('admin/withdrawals/Index', [
            'withdrawals' => $withdrawals,
        ]);
    }

    /**
     * Approve a requested withdrawal.
     */
    public function approve(Withdrawal $withdrawal): RedirectResponse
    {
        if ($withdrawal->status !== WithdrawalStatus::Requested) {
            throw ValidationException::withMessages(['status' => 'Ce retrait ne peut pas être approuvé.']);
        }

        $withdrawal->update(['status' => WithdrawalStatus::Approved]);

        AuditLogger::log('withdrawal.approved', "Approbation du retrait #{$withdrawal->id} ({$withdrawal->amount} FCFA).", $withdrawal);

        return back();
    }

    /**
     * Mark a withdrawal as paid.
     */
    public function pay(Request $request, Withdrawal $withdrawal): RedirectResponse
    {
        // Lock the row and re-check the status so a concurrent reject can never
        // both refund the held amount and mark the withdrawal as paid.
        DB::transaction(function () use ($request, $withdrawal): void {
            $locked = Withdrawal::query()->whereKey($withdrawal->id)->lockForUpdate()->firstOrFail();

            if (! in_array($locked->status, [WithdrawalStatus::Requested, WithdrawalStatus::Approved], true)) {
                throw ValidationException::withMessages(['status' => 'Ce retrait ne peut pas être payé.']);
            }

            $withdrawal->update([
                'status' => WithdrawalStatus::Paid,
                'processed_by' => $request->user()->id,
                'processed_at' => now(),
            ]);
        });

        $withdrawal->loadMissing('transporter.user');
        $withdrawal->transporter->user->notify(new TransporterWithdrawalNotification($withdrawal));

        AuditLogger::log('withdrawal.paid', "Paiement du retrait #{$withdrawal->id} ({$withdrawal->amount} FCFA).", $withdrawal);

        return back();
    }

    /**
     * Reject a withdrawal and refund the held amount.
     */
    public function reject(Request $request, Withdrawal $withdrawal, RejectWithdrawal $rejectWithdrawal): RedirectResponse
    {
        $rejectWithdrawal($withdrawal, $request->user());

        AuditLogger::log('withdrawal.rejected', "Rejet du retrait #{$withdrawal->id} ({$withdrawal->amount} FCFA).", $withdrawal);

        return back();
    }
}
