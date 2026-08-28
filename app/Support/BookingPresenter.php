<?php

namespace App\Support;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Booking;

class BookingPresenter
{
    /**
     * Shape a booking for the history / list view.
     *
     * @return array<string, mixed>
     */
    public static function summary(Booking $booking): array
    {
        return [
            'id' => $booking->id,
            'reference' => $booking->reference,
            'status' => $booking->status->value,
            'status_label' => $booking->status->label(),
            'seats' => $booking->seats,
            'total_amount' => $booking->total_amount,
            'date' => $booking->trip->departure_date->toDateString(),
            'time' => substr($booking->trip->departure_time, 0, 5),
            'route' => $booking->trip->departureDestination->city.' → '.$booking->trip->arrivalDestination->city,
            'payment' => self::paymentState($booking),
        ];
    }

    /**
     * Shape a booking for the detail / confirmation view.
     *
     * @return array<string, mixed>
     */
    public static function detail(Booking $booking): array
    {
        $deadlineHours = (int) Settings::get('cancellation.deadline_hours');
        $cancellable = $booking->status->isActive()
            && now()->lessThanOrEqualTo($booking->trip->departureAt()->subHours($deadlineHours));

        return [
            ...self::summary($booking),
            'unit_price' => $booking->unit_price,
            'payment_method' => $booking->payment_method->value,
            'payment_method_label' => $booking->payment_method->label(),
            'departure' => $booking->trip->departureDestination->city,
            'arrival' => $booking->trip->arrivalDestination->city,
            'boarded' => $booking->boarded_at !== null,
            'cancellable' => $cancellable,
        ];
    }

    /**
     * Resolve the passenger-facing payment state for a booking.
     *
     * The financial engine records a Payment only once money moves (Mobile Money
     * webhook, or cash collected at boarding). Before that the booking is unpaid.
     *
     * @return array{state: string, label: string, method: string, reference: string|null, paid_at: string|null}
     */
    public static function paymentState(Booking $booking): array
    {
        $payment = $booking->relationLoaded('payment') ? $booking->payment : $booking->payment()->first();

        $state = 'pending';

        if ($payment !== null && $payment->status === PaymentStatus::Completed) {
            $state = 'paid';
        } elseif ($payment !== null && $payment->status === PaymentStatus::Refunded) {
            $state = 'refunded';
        } elseif ($booking->payment_method === PaymentMethod::Cash) {
            $state = 'cash_on_boarding';
        }

        return [
            'state' => $state,
            'label' => match ($state) {
                'paid' => 'Payé',
                'refunded' => 'Remboursé',
                'cash_on_boarding' => 'À régler en espèces à l’embarquement',
                default => 'En attente de paiement',
            },
            'method' => $booking->payment_method->value,
            'reference' => $payment?->provider_reference,
            'paid_at' => $payment?->processed_at?->toIso8601String(),
        ];
    }
}
