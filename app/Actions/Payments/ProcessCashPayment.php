<?php

namespace App\Actions\Payments;

use App\Enums\LedgerEntryType;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Booking;
use App\Models\Payment;
use App\Models\Wallet;
use App\Notifications\PaymentConfirmedNotification;
use App\Notifications\TransporterCommissionNotification;
use App\Notifications\TransporterDebtNotification;
use App\Notifications\TransporterDebtThresholdNotification;
use App\Support\Settings;
use Illuminate\Support\Facades\DB;

class ProcessCashPayment
{
    /**
     * Record a cash payment (passenger -> driver): the commission becomes a debt of the transporter.
     */
    public function __invoke(Booking $booking): Payment
    {
        // One payment per booking guarantees the commission is never charged twice.
        $existing = Payment::query()->where('booking_id', $booking->id)->first();
        if ($existing !== null) {
            return $existing;
        }

        $booking->loadMissing('trip.transporter');
        $transporter = $booking->trip->transporter;
        $wallet = $transporter->walletOrCreate();

        $commission = (int) round($booking->total_amount * (float) Settings::get('commission.rate'));

        $payment = DB::transaction(function () use ($booking, $transporter, $wallet, $commission): Payment {
            $wallet = Wallet::query()->whereKey($wallet->id)->lockForUpdate()->firstOrFail();

            $payment = Payment::create([
                'booking_id' => $booking->id,
                'transporter_id' => $transporter->id,
                'method' => PaymentMethod::Cash,
                'amount' => $booking->total_amount,
                'commission_amount' => $commission,
                'status' => PaymentStatus::Completed,
                'processed_at' => now(),
            ]);

            $wallet->postEntry(
                LedgerEntryType::CashCommissionDebt,
                $commission,
                0,
                $commission,
                $payment,
                null,
                "Commission cash — réservation {$booking->reference}",
            );

            return $payment;
        });

        if ($payment->wasRecentlyCreated) {
            $booking->loadMissing('passenger');
            $transporter->loadMissing('user');

            $booking->passenger->notify(new PaymentConfirmedNotification($booking));
            $transporter->user->notify(new TransporterCommissionNotification($payment));
            $transporter->user->notify(new TransporterDebtNotification($payment));

            $maximum = (int) Settings::get('debt.maximum');
            $debt = (int) Wallet::query()->where('transporter_id', $transporter->id)->value('outstanding_debt');

            if ($debt > $maximum) {
                $transporter->user->notify(new TransporterDebtThresholdNotification($debt, $maximum));
            }
        }

        return $payment;
    }
}
