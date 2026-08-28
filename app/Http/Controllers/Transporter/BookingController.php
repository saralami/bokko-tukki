<?php

namespace App\Http\Controllers\Transporter;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class BookingController extends Controller
{
    /**
     * List every booking made on the connected transporter's trips.
     */
    public function index(Request $request): Response
    {
        $transporter = $request->user()->transporter ?? abort(403);

        $bookings = Booking::query()
            ->whereHas('trip', fn (Builder $query) => $query->where('transporter_id', $transporter->id))
            ->with([
                'passenger:id,name',
                'trip.departureDestination:id,city',
                'trip.arrivalDestination:id,city',
            ])
            ->latest()
            ->get()
            ->map(fn (Booking $booking): array => [
                'id' => $booking->id,
                'reference' => $booking->reference,
                'passenger' => $booking->passenger->name,
                'route' => $booking->trip->departureDestination->city.' → '.$booking->trip->arrivalDestination->city,
                'date' => $booking->trip->departure_date->toDateString(),
                'seats' => $booking->seats,
                'amount' => $booking->total_amount,
                'payment_method' => $booking->payment_method->value,
                'status' => $booking->status->value,
            ]);

        return Inertia::render('transporter/bookings/Index', [
            'bookings' => $bookings,
        ]);
    }
}
