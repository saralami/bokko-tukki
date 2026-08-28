<?php

namespace App\Actions\Withdrawals;

use App\Enums\LedgerEntryType;
use App\Enums\WithdrawalStatus;
use App\Models\Transporter;
use App\Models\Wallet;
use App\Models\Withdrawal;
use App\Notifications\TransporterWithdrawalNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RequestWithdrawal
{
    /**
     * Request a withdrawal. Only the available balance can be withdrawn, never the debt.
     *
     * The amount is held (debited) immediately under a row lock so two concurrent
     * requests can never withdraw more than the available balance.
     *
     * @throws ValidationException
     */
    public function __invoke(Transporter $transporter, int $amount): Withdrawal
    {
        if ($amount < 1) {
            throw ValidationException::withMessages([
                'amount' => 'Le montant du retrait doit être positif.',
            ]);
        }

        $wallet = $transporter->walletOrCreate();

        $withdrawal = DB::transaction(function () use ($transporter, $wallet, $amount): Withdrawal {
            $wallet = Wallet::query()->whereKey($wallet->id)->lockForUpdate()->firstOrFail();

            if ($amount > $wallet->available_balance) {
                throw ValidationException::withMessages([
                    'amount' => 'Montant supérieur au solde disponible.',
                ]);
            }

            $withdrawal = Withdrawal::create([
                'transporter_id' => $transporter->id,
                'amount' => $amount,
                'status' => WithdrawalStatus::Requested,
            ]);

            $wallet->postEntry(
                LedgerEntryType::Withdrawal,
                $amount,
                -$amount,
                0,
                null,
                $withdrawal,
                "Demande de retrait #{$withdrawal->id}",
            );

            return $withdrawal;
        });

        $transporter->loadMissing('user');
        $transporter->user->notify(new TransporterWithdrawalNotification($withdrawal));

        return $withdrawal;
    }
}
