<?php

namespace App\Actions\Payments;

use App\Enums\LedgerEntryType;
use App\Enums\PaymentStatus;
use App\Models\LedgerEntry;
use App\Models\Payment;
use App\Models\Wallet;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RefundPayment
{
    /**
     * Refund a completed payment using a compensating ledger entry.
     *
     * The original entries are never modified; a new entry reverses their net effect.
     *
     * @throws ValidationException
     */
    public function __invoke(Payment $payment, ?string $reason = null): Payment
    {
        if ($payment->status !== PaymentStatus::Completed) {
            throw ValidationException::withMessages([
                'payment' => 'Seul un paiement réglé peut être remboursé.',
            ]);
        }

        $wallet = $payment->transporter->walletOrCreate();

        return DB::transaction(function () use ($payment, $wallet, $reason): Payment {
            // Re-check the payment under a row lock so two concurrent refunds can
            // never post a second compensating entry for the same payment.
            $payment = Payment::query()->whereKey($payment->id)->lockForUpdate()->firstOrFail();

            if ($payment->status !== PaymentStatus::Completed) {
                return $payment;
            }

            $wallet = Wallet::query()->whereKey($wallet->id)->lockForUpdate()->firstOrFail();

            $netBalance = (int) LedgerEntry::query()->where('payment_id', $payment->id)->sum('balance_delta');
            $netDebt = (int) LedgerEntry::query()->where('payment_id', $payment->id)->sum('debt_delta');

            $reversalBalance = -$netBalance;
            $reversalDebt = -$netDebt;

            if ($wallet->available_balance + $reversalBalance < 0 || $wallet->outstanding_debt + $reversalDebt < 0) {
                throw ValidationException::withMessages([
                    'payment' => 'Remboursement impossible : les fonds ont déjà été utilisés ou retirés.',
                ]);
            }

            $description = "Remboursement paiement #{$payment->id}";

            if ($reason !== null && $reason !== '') {
                $description .= " — {$reason}";
            }

            $wallet->postEntry(
                LedgerEntryType::Refund,
                $payment->amount,
                $reversalBalance,
                $reversalDebt,
                $payment,
                null,
                $description,
            );

            $payment->update(['status' => PaymentStatus::Refunded]);

            return $payment;
        });
    }
}
