<?php

namespace App\Http\Controllers\Transporter;

use App\Actions\PublishTrip;
use App\Enums\BookingStatus;
use App\Enums\TripStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTripRequest;
use App\Http\Requests\UpdateTripRequest;
use App\Models\Booking;
use App\Models\Destination;
use App\Models\Transporter;
use App\Models\Trip;
use App\Notifications\DriverTripUpdatedNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class TripController extends Controller
{
    /**
     * Display the authenticated transporter's trips.
     */
    public function index(Request $request): Response
    {
        $transporter = $this->transporterFor($request);

        return Inertia::render('transporter/trips/Index', [
            'trips' => $transporter->trips()
                ->with(['departureDestination', 'arrivalDestination', 'vehicle', 'driver'])
                ->orderByDesc('departure_date')
                ->get(),
        ]);
    }

    /**
     * Show the form for creating a new trip.
     */
    public function create(Request $request): Response
    {
        return Inertia::render('transporter/trips/Create', $this->formOptions($request));
    }

    /**
     * Store a newly created draft trip.
     */
    public function store(StoreTripRequest $request): RedirectResponse
    {
        $transporter = $this->transporterFor($request);
        $data = $request->validated();

        $vehicle = $transporter->vehicles()->whereKey($data['vehicle_id'])->firstOrFail();
        $data['capacity'] = $vehicle->capacity;
        $data['available_seats'] = $vehicle->capacity;
        $data['status'] = TripStatus::Draft->value;

        $trip = $transporter->trips()->create($data);

        return to_route('transporter.trips.show', $trip);
    }

    /**
     * Display the trip details and its passenger list.
     */
    public function show(Request $request, Trip $trip): Response
    {
        Gate::authorize('view', $trip);

        $trip->load([
            'departureDestination', 'arrivalDestination', 'vehicle', 'driver',
            'bookings' => fn ($query) => $query
                ->where('status', '!=', BookingStatus::Cancelled)
                ->with('passenger:id,name'),
        ]);

        $passengers = $trip->bookings->map(fn (Booking $booking): array => [
            'id' => $booking->id,
            'reference' => $booking->reference,
            'name' => $booking->passenger->name,
            'seats' => $booking->seats,
            'status' => $booking->status->value,
            'boarded' => $booking->boarded_at !== null,
        ]);

        return Inertia::render('transporter/trips/Show', [
            'trip' => $trip,
            'passengers' => $passengers,
        ]);
    }

    /**
     * Show the form for editing the trip.
     */
    public function edit(Request $request, Trip $trip): Response
    {
        Gate::authorize('view', $trip);

        return Inertia::render('transporter/trips/Edit', [
            'trip' => $trip,
            ...$this->formOptions($request),
        ]);
    }

    /**
     * Update the given trip.
     */
    public function update(UpdateTripRequest $request, Trip $trip): RedirectResponse
    {
        $transporter = $this->transporterFor($request);
        $data = $request->validated();

        $vehicle = $transporter->vehicles()->whereKey($data['vehicle_id'])->firstOrFail();
        $data['capacity'] = $vehicle->capacity;
        $data['available_seats'] = $trip->status === TripStatus::Draft
            ? $vehicle->capacity
            : min($trip->available_seats, $vehicle->capacity);

        $trip->update($data);

        $trip->loadMissing('driver');
        if ($trip->driver !== null) {
            $trip->driver->notify(new DriverTripUpdatedNotification($trip));
        }

        return to_route('transporter.trips.show', $trip);
    }

    /**
     * Publish the given trip after validating the business rules.
     */
    public function publish(Request $request, Trip $trip, PublishTrip $publishTrip): RedirectResponse
    {
        Gate::authorize('update', $trip);

        $publishTrip($trip);

        return to_route('transporter.trips.show', $trip);
    }

    /**
     * Cancel the given trip.
     */
    public function cancel(Request $request, Trip $trip): RedirectResponse
    {
        Gate::authorize('update', $trip);

        if (in_array($trip->status, [TripStatus::Departed, TripStatus::Completed], true)) {
            throw ValidationException::withMessages([
                'status' => 'Ce trajet ne peut plus être annulé.',
            ]);
        }

        $trip->update(['status' => TripStatus::Cancelled]);

        return to_route('transporter.trips.show', $trip);
    }

    /**
     * Resolve the transporter company owned by the authenticated user.
     */
    private function transporterFor(Request $request): Transporter
    {
        return $request->user()->transporter ?? abort(403);
    }

    /**
     * Build the shared option lists used by the create and edit forms.
     *
     * @return array<string, mixed>
     */
    private function formOptions(Request $request): array
    {
        $transporter = $this->transporterFor($request);

        return [
            'vehicles' => $transporter->vehicles()->get(['id', 'registration', 'brand', 'model', 'capacity', 'status']),
            'drivers' => $transporter->drivers()->get(['id', 'first_name', 'last_name', 'status']),
            'destinations' => Destination::query()->active()->orderBy('city')->get(['id', 'city', 'region']),
            'statuses' => TripStatus::values(),
        ];
    }
}
