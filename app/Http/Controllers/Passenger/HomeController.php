<?php

namespace App\Http\Controllers\Passenger;

use App\Enums\BookingStatus;
use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Destination;
use App\Support\BookingPresenter;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class HomeController extends Controller
{
    /**
     * Display the passenger home screen: quick search and next trips.
     */
    public function index(Request $request): Response
    {
        $upcoming = Booking::query()
            ->where('passenger_id', $request->user()->id)
            ->whereIn('status', [BookingStatus::Pending, BookingStatus::Confirmed])
            ->with([
                'trip.departureDestination:id,city',
                'trip.arrivalDestination:id,city',
                'payment',
            ])
            ->latest()
            ->take(3)
            ->get()
            ->map(fn (Booking $booking): array => BookingPresenter::summary($booking))
            ->all();

        return Inertia::render('passenger/Home', [
            'destinations' => Destination::query()->active()->orderBy('city')->get(['id', 'city', 'region']),
            'upcoming' => $upcoming,
        ]);
    }
}
