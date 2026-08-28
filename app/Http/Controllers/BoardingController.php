<?php

namespace App\Http\Controllers;

use App\Actions\ConfirmBoarding;
use App\Enums\BookingStatus;
use App\Models\Booking;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class BoardingController extends Controller
{
    /**
     * Confirm that a passenger has boarded.
     *
     * The cash payment, passenger notification and BookingBoarded event are all
     * handled by the ConfirmBoarding action so every entry point stays consistent.
     */
    public function board(Booking $booking, ConfirmBoarding $confirmBoarding): RedirectResponse
    {
        Gate::authorize('confirmBoarding', $booking);

        $confirmBoarding($booking);

        return back();
    }

    /**
     * Mark a booking as a no-show.
     */
    public function noShow(Booking $booking): RedirectResponse
    {
        Gate::authorize('confirmBoarding', $booking);

        if (! $booking->status->isActive()) {
            throw ValidationException::withMessages([
                'status' => 'Seule une réservation active peut être marquée comme absente.',
            ]);
        }

        $booking->update(['status' => BookingStatus::NoShow]);

        return back();
    }
}
