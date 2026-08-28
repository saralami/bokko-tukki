<?php

namespace App\Actions;

use App\Actions\Payments\ProcessCashPayment;
use App\Enums\BookingStatus;
use App\Enums\PaymentMethod;
use App\Events\BookingBoarded;
use App\Models\Booking;
use App\Notifications\BoardingConfirmedNotification;
use Illuminate\Validation\ValidationException;

class ConfirmBoarding
{
    public function __construct(private ProcessCashPayment $processCashPayment) {}

    /**
     * Confirm that the passenger has actually boarded and trigger the business
     * consequences that prove the reservation was executed:
     *  - the booking is marked completed with a boarding timestamp,
     *  - a cash payment is recorded (commission -> transporter debt) when due,
     *  - the passenger is notified and the BookingBoarded domain event fires.
     *
     * @throws ValidationException
     */
    public function __invoke(Booking $booking): Booking
    {
        if (! $booking->status->isActive()) {
            throw ValidationException::withMessages([
                'status' => 'Seule une réservation active peut être embarquée.',
            ]);
        }

        $booking->update([
            'status' => BookingStatus::Completed,
            'boarded_at' => now(),
        ]);

        if ($booking->payment_method === PaymentMethod::Cash) {
            ($this->processCashPayment)($booking);
        }

        $booking->loadMissing('passenger');
        $booking->passenger->notify(new BoardingConfirmedNotification($booking));

        BookingBoarded::dispatch($booking);

        return $booking;
    }
}
