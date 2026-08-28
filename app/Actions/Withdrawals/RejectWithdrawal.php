<?php

namespace App\Actions\Withdrawals;

use App\Enums\LedgerEntryType;
use App\Enums\WithdrawalStatus;
use App\Models\User;
use App\Models\Wallet;
use App\Models\Withdrawal;
use App\Notifications\TransporterWithdrawalNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RejectWithdrawal
{
    /**
     * Reject a withdrawal and refund the held amount back to the available balance
     * through a compensating ledger entry.
     *
     * @throws ValidationException
     */
    public function __invoke(Withdrawal $withdrawal, ?User $admin = null): Withdrawal
    {
        if (! in_array($withdrawal->status, [WithdrawalStatus::Requested, WithdrawalStatus::Approved], true)) {
            throw ValidationException::withMessages([
                'status' => 'Ce retrait ne peut plus être rejeté.',
            ]);
        }

        $wallet = $withdrawal->transporter->walletOrCreate();

        $withdrawal = DB::transaction(function () use ($withdrawal, $wallet, $admin): Withdrawal {
            $wallet = Wallet::query()->whereKey($wallet->id)->lockForUpdate()->firstOrFail();

            // Re-check the withdrawal under a row lock: a concurrent reject/pay must
            // never post the reversal twice nor reverse an already paid withdrawal.
            $locked = Withdrawal::query()->whereKey($withdrawal->id)->lockForUpdate()->firstOrFail();

            if (! in_array($locked->status, [WithdrawalStatus::Requested, WithdrawalStatus::Approved], true)) {
                throw ValidationException::withMessages([
                    'status' => 'Ce retrait ne peut plus être rejeté.',
                ]);
            }

            $wallet->postEntry(
                LedgerEntryType::WithdrawalReversal,
                $withdrawal->amount,
                $withdrawal->amount,
                0,
                null,
                $withdrawal,
                "Rejet du retrait #{$withdrawal->id}",
            );

            $withdrawal->update([
                'status' => WithdrawalStatus::Rejected,
                'processed_by' => $admin?->id,
                'processed_at' => now(),
            ]);

            return $withdrawal;
        });

        $withdrawal->loadMissing('transporter.user');
        $withdrawal->transporter->user->notify(new TransporterWithdrawalNotification($withdrawal));

        return $withdrawal;
    }
}
