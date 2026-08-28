<?php

namespace App\Actions;

use App\Actions\Payments\RefundPayment;
use App\Enums\BookingStatus;
use App\Enums\PaymentStatus;
use App\Models\Booking;
use App\Models\Payment;
use App\Models\Trip;
use App\Notifications\BookingCancelledNotification;
use App\Notifications\DriverBookingCancelledNotification;
use App\Support\Settings;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CancelBooking
{
    /**
     * Cancel an active booking following the configurable cancellation rules.
     *
     * The booking is never deleted; only its status changes and the held seats are released.
     *
     * @throws ValidationException
     */
    public function __invoke(Booking $booking): Booking
    {
        if (! $booking->status->isActive()) {
            throw ValidationException::withMessages([
                'status' => 'Cette réservation ne peut pas être annulée.',
            ]);
        }

        $deadlineHours = (int) Settings::get('cancellation.deadline_hours');
        $booking->loadMissing('trip');
        $cancellationDeadline = $booking->trip->departureAt()->subHours($deadlineHours);

        if (now()->greaterThan($cancellationDeadline)) {
            throw ValidationException::withMessages([
                'booking' => "L’annulation n’est plus possible à moins de {$deadlineHours} h du départ.",
            ]);
        }

        DB::transaction(function () use ($booking): void {
            // Re-check the booking under a row lock so two concurrent cancellations
            // can never release the seats or refund the payment twice.
            $locked = Booking::query()->whereKey($booking->id)->lockForUpdate()->first();

            if ($locked === null || ! $locked->status->isActive()) {
                return;
            }

            $trip = Trip::query()->whereKey($booking->trip_id)->lockForUpdate()->first();
            $trip?->releaseSeats($booking->seats);

            $booking->update([
                'status' => BookingStatus::Cancelled,
                'cancelled_at' => now(),
            ]);

            // Refund any settled payment with a compensating ledger entry.
            $payment = Payment::query()
                ->where('booking_id', $booking->id)
                ->where('status', PaymentStatus::Completed)
                ->first();

            if ($payment !== null) {
                app(RefundPayment::class)($payment);
            }
        });

        $booking->loadMissing(['passenger', 'trip.driver']);
        $booking->passenger->notify(new BookingCancelledNotification($booking));

        if ($booking->trip->driver !== null) {
            $booking->trip->driver->notify(new DriverBookingCancelledNotification($booking));
        }

        return $booking;
    }
}
