<?php

namespace App\Actions\Payments;

use App\Enums\BookingStatus;
use App\Enums\LedgerEntryType;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Booking;
use App\Models\Payment;
use App\Models\Wallet;
use App\Notifications\BookingConfirmedNotification;
use App\Notifications\PaymentConfirmedNotification;
use App\Notifications\TransporterCommissionNotification;
use App\Notifications\TransporterPaymentReceivedNotification;
use App\Support\Settings;
use Illuminate\Support\Facades\DB;

class ProcessMobileMoneyPayment
{
    /**
     * Process a confirmed Mobile Money payment applying the strict priority waterfall:
     * 1. current booking commission, 2. previous debts, 3. transporter available balance.
     *
     * Idempotent per provider reference so a repeated webhook is never processed twice.
     */
    public function __invoke(Booking $booking, string $providerReference, int $amount, ?string $provider = null): Payment
    {
        $existing = Payment::query()->where('provider_reference', $providerReference)->first();
        if ($existing !== null) {
            return $existing;
        }

        $booking->loadMissing('trip.transporter');
        $transporter = $booking->trip->transporter;
        $wallet = $transporter->walletOrCreate();

        $commission = (int) round($amount * (float) Settings::get('commission.rate'));

        $payment = DB::transaction(function () use ($booking, $transporter, $wallet, $providerReference, $provider, $amount, $commission): Payment {
            // Re-check idempotency inside the locked section to defeat concurrent webhook retries.
            $existing = Payment::query()->where('provider_reference', $providerReference)->lockForUpdate()->first();
            if ($existing !== null) {
                return $existing;
            }

            $wallet = Wallet::query()->whereKey($wallet->id)->lockForUpdate()->firstOrFail();

            $proceeds = $amount - $commission;
            $debtSettled = min($proceeds, $wallet->outstanding_debt);

            $payment = Payment::create([
                'booking_id' => $booking->id,
                'transporter_id' => $transporter->id,
                'method' => PaymentMethod::MobileMoney,
                'amount' => $amount,
                'commission_amount' => $commission,
                'status' => PaymentStatus::Completed,
                'provider' => $provider,
                'provider_reference' => $providerReference,
                'processed_at' => now(),
            ]);

            // 1. Commission of the current booking (platform revenue).
            $wallet->postEntry(LedgerEntryType::Commission, $commission, 0, 0, $payment, null, "Commission — {$booking->reference}");

            // Transporter proceeds (amount minus commission) credited to the balance.
            $wallet->postEntry(LedgerEntryType::MobileMoneyProceeds, $proceeds, $proceeds, 0, $payment, null, "Recette Mobile Money — {$booking->reference}");

            // 2. Previous debts are cleared out of those proceeds.
            if ($debtSettled > 0) {
                $wallet->postEntry(LedgerEntryType::DebtSettlement, $debtSettled, -$debtSettled, -$debtSettled, $payment, null, 'Apurement des dettes antérieures');
            }

            // 3. The remaining amount stays as available balance (net effect of the entries above).
            $booking->update(['status' => BookingStatus::Confirmed]);

            return $payment;
        });

        if ($payment->wasRecentlyCreated) {
            $booking->loadMissing('passenger');
            $transporter->loadMissing('user');

            $booking->passenger->notify(new BookingConfirmedNotification($booking));
            $booking->passenger->notify(new PaymentConfirmedNotification($booking));
            $transporter->user->notify(new TransporterPaymentReceivedNotification($payment));
            $transporter->user->notify(new TransporterCommissionNotification($payment));
        }

        return $payment;
    }
}
