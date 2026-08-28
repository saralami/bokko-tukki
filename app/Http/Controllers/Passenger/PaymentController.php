<?php

namespace App\Http\Controllers\Passenger;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Support\BookingPresenter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class PaymentController extends Controller
{
    /**
     * Display the payment step and live status for a booking.
     *
     * Mobile Money is confirmed asynchronously by the provider webhook, so the
     * page reflects the real Payment state and polls until it settles. Cash is
     * collected in person at boarding.
     */
    public function show(Request $request, Booking $booking): Response
    {
        Gate::authorize('view', $booking);

        $booking->load([
            'trip.departureDestination:id,city,region',
            'trip.arrivalDestination:id,city,region',
            'payment',
        ]);

        return Inertia::render('passenger/Payment', [
            'booking' => BookingPresenter::detail($booking),
        ]);
    }
}
