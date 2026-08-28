<?php

namespace App\Http\Controllers\Driver;

use App\Enums\BookingStatus;
use App\Enums\IncidentCategory;
use App\Enums\TripStatus;
use App\Http\Controllers\Controller;
use App\Models\Driver;
use App\Models\Trip;
use App\Models\TripIncident;
use App\Support\DriverTripPresenter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TripController extends Controller
{
    /**
     * List the driver's upcoming / operable trips.
     */
    public function index(Request $request): Response
    {
        $driver = $this->driver($request);

        $trips = $this->tripsQuery($driver)
            ->whereIn('status', [TripStatus::Published, TripStatus::Boarding])
            ->whereDate('departure_date', '>=', now()->toDateString())
            ->orderBy('departure_date')
            ->orderBy('departure_time')
            ->get()
            ->map(DriverTripPresenter::summary(...))
            ->all();

        return Inertia::render('driver/trips/Index', [
            'trips' => $trips,
        ]);
    }

    /**
     * List the driver's past trips.
     */
    public function history(Request $request): Response
    {
        $driver = $this->driver($request);

        $trips = $this->tripsQuery($driver)
            ->where(fn (Builder $query) => $query
                ->whereIn('status', [TripStatus::Departed, TripStatus::Completed, TripStatus::Cancelled])
                ->orWhereDate('departure_date', '<', now()->toDateString()))
            ->orderByDesc('departure_date')
            ->orderByDesc('departure_time')
            ->get()
            ->map(DriverTripPresenter::summary(...))
            ->all();

        return Inertia::render('driver/trips/History', [
            'trips' => $trips,
        ]);
    }

    /**
     * Show a trip with its passenger and reservation list.
     */
    public function show(Request $request, Trip $trip): Response
    {
        $driver = $this->driver($request);

        abort_unless($trip->driver_id === $driver->id, 403);

        $trip->load([
            'departureDestination:id,city,region',
            'arrivalDestination:id,city,region',
            'vehicle:id,brand,model',
            'bookings' => fn ($query) => $query
                ->where('status', '!=', BookingStatus::Cancelled)
                ->with('passenger:id,name')
                ->latest(),
            'incidents' => fn ($query) => $query->latest(),
        ]);

        $reservations = $trip->bookings->map(DriverTripPresenter::passenger(...))->all();

        return Inertia::render('driver/trips/Show', [
            'trip' => [
                'id' => $trip->id,
                'departure' => $trip->departureDestination?->city,
                'arrival' => $trip->arrivalDestination?->city,
                'route' => $trip->departureDestination?->city.' → '.$trip->arrivalDestination?->city,
                'date' => $trip->departure_date->toDateString(),
                'time' => substr($trip->departure_time, 0, 5),
                'vehicle' => $trip->vehicle ? $trip->vehicle->brand.' '.$trip->vehicle->model : null,
                'capacity' => $trip->capacity,
                'available_seats' => $trip->available_seats,
                'passengers' => (int) $trip->bookings->whereNotIn('status', [BookingStatus::NoShow])->sum('seats'),
                'reservations' => $trip->bookings->count(),
                'status' => $trip->status->value,
                'status_label' => $trip->status->label(),
            ],
            'reservations' => $reservations,
            'incidents' => $trip->incidents->map(fn (TripIncident $incident): array => [
                'id' => $incident->id,
                'category' => $incident->category->value,
                'category_label' => $incident->category->label(),
                'message' => $incident->message,
                'status' => $incident->status->value,
                'status_label' => $incident->status->label(),
                'created_at' => $incident->created_at?->toIso8601String(),
            ])->all(),
            'incidentCategories' => collect(IncidentCategory::cases())
                ->map(fn (IncidentCategory $category): array => ['value' => $category->value, 'label' => $category->label()])
                ->all(),
        ]);
    }

    /**
     * Resolve the authenticated driver or deny access.
     */
    private function driver(Request $request): Driver
    {
        $driver = $request->user()->driver;

        abort_if($driver === null, 403);

        return $driver;
    }

    /**
     * Base query for the driver's trips with list aggregates loaded.
     *
     * @return Builder<Trip>
     */
    private function tripsQuery(Driver $driver): Builder
    {
        return Trip::query()
            ->where('driver_id', $driver->id)
            ->with([
                'departureDestination:id,city',
                'arrivalDestination:id,city',
                'vehicle:id,brand,model',
            ])
            ->withCount(['bookings as reservations_count' => fn (Builder $query) => $query
                ->whereIn('status', [BookingStatus::Pending, BookingStatus::Confirmed, BookingStatus::Completed])])
            ->withSum(['bookings as booked_seats' => fn (Builder $query) => $query
                ->whereIn('status', [BookingStatus::Pending, BookingStatus::Confirmed, BookingStatus::Completed])], 'seats');
    }
}
