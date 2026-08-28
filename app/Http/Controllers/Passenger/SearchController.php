<?php

namespace App\Http\Controllers\Passenger;

use App\Actions\SearchTrips;
use App\Enums\TripStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\SearchTripsRequest;
use App\Models\Destination;
use App\Models\Trip;
use Inertia\Inertia;
use Inertia\Response;

class SearchController extends Controller
{
    /**
     * Display the passenger trip search page and its results.
     */
    public function index(SearchTripsRequest $request, SearchTrips $searchTrips): Response
    {
        $criteria = $request->criteria();

        return Inertia::render('passenger/Search', [
            'destinations' => Destination::query()->active()->orderBy('city')->get(['id', 'city', 'region']),
            'filters' => $criteria,
            'results' => $searchTrips($criteria),
        ]);
    }

    /**
     * Display the public details of a published trip.
     */
    public function show(Trip $trip, SearchTrips $searchTrips): Response
    {
        abort_unless($trip->status === TripStatus::Published, 404);

        $trip->load([
            'departureDestination:id,city,region,latitude,longitude',
            'arrivalDestination:id,city,region',
            'transporter:id,company_name',
            'vehicle:id,brand,model',
        ]);

        return Inertia::render('passenger/TripDetails', [
            'trip' => $searchTrips->present($trip, null),
        ]);
    }
}
