<?php

namespace App\Http\Controllers\Admin;

use App\Enums\BookingStatus;
use App\Enums\TripStatus;
use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Trip;
use App\Support\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class TripController extends Controller
{
    /**
     * List every trip on the platform with filters.
     */
    public function index(Request $request): Response
    {
        $filters = $request->only(['search', 'status']);

        $trips = Trip::query()
            ->when($filters['status'] ?? null, fn ($query, $status) => $query->where('status', $status))
            ->when($filters['search'] ?? null, fn ($query, $search) => $query
                ->whereHas('departureDestination', fn ($q) => $q->where('city', 'like', "%{$search}%"))
                ->orWhereHas('arrivalDestination', fn ($q) => $q->where('city', 'like', "%{$search}%")))
            ->with([
                'departureDestination:id,city',
                'arrivalDestination:id,city',
                'transporter:id,company_name',
            ])
            ->withCount(['bookings as reservations_count' => fn ($query) => $query->where('status', '!=', BookingStatus::Cancelled)])
            ->latest()
            ->paginate(20)
            ->withQueryString()
            ->through(fn (Trip $trip): array => $this->summary($trip));

        return Inertia::render('admin/trips/Index', [
            'trips' => $trips,
            'filters' => $filters,
            'statuses' => TripStatus::values(),
        ]);
    }

    /**
     * Show a single trip with its bookings.
     */
    public function show(Trip $trip): Response
    {
        $trip->load([
            'departureDestination:id,city,region',
            'arrivalDestination:id,city,region',
            'transporter:id,company_name',
            'vehicle:id,brand,model',
            'driver:id,first_name,last_name',
            'bookings' => fn ($query) => $query->with('passenger:id,name')->latest(),
        ]);

        return Inertia::render('admin/trips/Show', [
            'trip' => [
                ...$this->summary($trip),
                'region_from' => $trip->departureDestination?->region,
                'region_to' => $trip->arrivalDestination?->region,
                'vehicle' => $trip->vehicle ? $trip->vehicle->brand.' '.$trip->vehicle->model : null,
                'driver' => $trip->driver?->full_name,
                'price_per_seat' => $trip->price_per_seat,
            ],
            'bookings' => $trip->bookings->map(fn (Booking $booking): array => [
                'id' => $booking->id,
                'reference' => $booking->reference,
                'passenger' => $booking->passenger->name,
                'seats' => $booking->seats,
                'amount' => $booking->total_amount,
                'status' => $booking->status->value,
                'status_label' => $booking->status->label(),
            ])->all(),
        ]);
    }

    /**
     * Administratively cancel a trip.
     */
    public function cancel(Trip $trip): RedirectResponse
    {
        if (in_array($trip->status, [TripStatus::Departed, TripStatus::Completed, TripStatus::Cancelled], true)) {
            throw ValidationException::withMessages(['status' => 'Ce trajet ne peut plus être annulé.']);
        }

        $trip->update(['status' => TripStatus::Cancelled]);

        AuditLogger::log('trip.cancelled', "Annulation administrative du trajet #{$trip->id}.", $trip);

        return back();
    }

    /**
     * Shape a trip for the list / detail view.
     *
     * @return array<string, mixed>
     */
    private function summary(Trip $trip): array
    {
        return [
            'id' => $trip->id,
            'route' => $trip->departureDestination?->city.' → '.$trip->arrivalDestination?->city,
            'transporter' => $trip->transporter?->company_name,
            'date' => $trip->departure_date->toDateString(),
            'time' => substr($trip->departure_time, 0, 5),
            'capacity' => $trip->capacity,
            'available_seats' => $trip->available_seats,
            'reservations' => $trip->reservations_count ?? $trip->bookings()->where('status', '!=', BookingStatus::Cancelled)->count(),
            'status' => $trip->status->value,
            'status_label' => $trip->status->label(),
        ];
    }
}
