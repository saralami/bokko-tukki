<?php

namespace App\Http\Controllers\Driver;

use App\Enums\BookingStatus;
use App\Enums\TripStatus;
use App\Http\Controllers\Controller;
use App\Models\Driver;
use App\Models\Trip;
use App\Support\DriverTripPresenter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    /**
     * Display the driver home screen: next departure and upcoming trips.
     */
    public function index(Request $request): Response
    {
        $driver = $request->user()->driver;

        if ($driver === null) {
            return Inertia::render('driver/Dashboard', [
                'hasProfile' => false,
                'driverName' => $request->user()->name,
                'next' => null,
                'upcoming' => [],
                'stats' => ['upcoming' => 0, 'openIncidents' => 0],
            ]);
        }

        $trips = $this->upcomingTripsQuery($driver)->get();

        $next = $trips->first();

        return Inertia::render('driver/Dashboard', [
            'hasProfile' => true,
            'driverName' => $driver->full_name,
            'next' => $next !== null ? DriverTripPresenter::summary($next) : null,
            'upcoming' => $trips->skip(1)->take(5)->map(DriverTripPresenter::summary(...))->values()->all(),
            'stats' => [
                'upcoming' => $trips->count(),
                'openIncidents' => $driver->incidents()->where('status', 'open')->count(),
            ],
        ]);
    }

    /**
     * Build the base query for a driver's upcoming, operable trips ordered by departure.
     *
     * @return Builder<Trip>
     */
    private function upcomingTripsQuery(Driver $driver): Builder
    {
        return Trip::query()
            ->where('driver_id', $driver->id)
            ->whereIn('status', [TripStatus::Published, TripStatus::Boarding])
            ->whereDate('departure_date', '>=', now()->toDateString())
            ->with([
                'departureDestination:id,city',
                'arrivalDestination:id,city',
                'vehicle:id,brand,model',
            ])
            ->withCount(['bookings as reservations_count' => fn (Builder $query) => $query
                ->whereIn('status', [BookingStatus::Pending, BookingStatus::Confirmed, BookingStatus::Completed])])
            ->withSum(['bookings as booked_seats' => fn (Builder $query) => $query
                ->whereIn('status', [BookingStatus::Pending, BookingStatus::Confirmed, BookingStatus::Completed])], 'seats')
            ->orderBy('departure_date')
            ->orderBy('departure_time');
    }
}
