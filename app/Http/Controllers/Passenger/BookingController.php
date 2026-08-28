<?php

namespace App\Http\Controllers\Passenger;

use App\Actions\CancelBooking;
use App\Actions\CreateBooking;
use App\Enums\BookingStatus;
use App\Enums\TripStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreBookingRequest;
use App\Models\Booking;
use App\Models\Trip;
use App\Support\BookingPresenter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class BookingController extends Controller
{
    /**
     * Display the passenger's active (upcoming) bookings.
     */
    public function index(Request $request): Response
    {
        return Inertia::render('passenger/bookings/Index', [
            'bookings' => $this->bookingsFor($request, active: true),
        ]);
    }

    /**
     * Display the passenger's past / closed bookings.
     */
    public function history(Request $request): Response
    {
        return Inertia::render('passenger/bookings/History', [
            'bookings' => $this->bookingsFor($request, active: false),
        ]);
    }

    /**
     * Display the reservation step for a bookable trip.
     */
    public function create(Trip $trip): Response
    {
        abort_unless($trip->status === TripStatus::Published, 404);

        $trip->load([
            'departureDestination:id,city,region',
            'arrivalDestination:id,city,region',
            'transporter:id,company_name',
            'vehicle:id,brand,model',
        ]);

        return Inertia::render('passenger/bookings/Create', [
            'trip' => [
                'id' => $trip->id,
                'departure' => $trip->departureDestination?->city,
                'departure_region' => $trip->departureDestination?->region,
                'arrival' => $trip->arrivalDestination?->city,
                'arrival_region' => $trip->arrivalDestination?->region,
                'date' => $trip->departure_date->toDateString(),
                'time' => substr($trip->departure_time, 0, 5),
                'price_per_seat' => $trip->price_per_seat,
                'available_seats' => $trip->available_seats,
                'transporter' => $trip->transporter?->company_name,
                'vehicle' => $trip->vehicle ? $trip->vehicle->brand.' '.$trip->vehicle->model : null,
            ],
        ]);
    }

    /**
     * Display a single booking (detail / confirmation).
     */
    public function show(Request $request, Booking $booking): Response
    {
        Gate::authorize('view', $booking);

        $booking->load([
            'trip.departureDestination:id,city,region',
            'trip.arrivalDestination:id,city,region',
            'payment',
        ]);

        return Inertia::render('passenger/bookings/Show', [
            'booking' => BookingPresenter::detail($booking),
        ]);
    }

    /**
     * Create a booking for the authenticated passenger and continue to payment.
     */
    public function store(StoreBookingRequest $request, CreateBooking $createBooking): RedirectResponse
    {
        $booking = $createBooking($request->user(), $request->validated());

        return to_route('passenger.bookings.payment', $booking);
    }

    /**
     * Cancel the given booking.
     */
    public function cancel(Request $request, Booking $booking, CancelBooking $cancelBooking): RedirectResponse
    {
        Gate::authorize('cancel', $booking);

        $cancelBooking($booking);

        return to_route('passenger.bookings.show', $booking);
    }

    /**
     * Fetch and present the passenger's bookings, split by active vs. closed status.
     *
     * @return array<int, array<string, mixed>>
     */
    private function bookingsFor(Request $request, bool $active): array
    {
        $statuses = $active
            ? [BookingStatus::Pending, BookingStatus::Confirmed]
            : [BookingStatus::Completed, BookingStatus::Cancelled, BookingStatus::NoShow];

        return Booking::query()
            ->where('passenger_id', $request->user()->id)
            ->whereIn('status', $statuses)
            ->with([
                'trip.departureDestination:id,city',
                'trip.arrivalDestination:id,city',
                'payment',
            ])
            ->latest()
            ->get()
            ->map(fn (Booking $booking): array => BookingPresenter::summary($booking))
            ->all();
    }
}
