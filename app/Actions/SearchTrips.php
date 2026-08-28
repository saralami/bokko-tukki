<?php

namespace App\Actions;

use App\Enums\TripStatus;
use App\Models\Trip;
use App\Support\Coordinates;

class SearchTrips
{
    /**
     * Search available published trips matching the given criteria.
     *
     * @param  array<string, mixed>  $criteria
     * @return array<int, array<string, mixed>>
     */
    public function __invoke(array $criteria): array
    {
        $seats = max(1, (int) ($criteria['seats'] ?? 1));

        $trips = Trip::query()
            ->where('status', TripStatus::Published)
            ->whereDate('departure_date', '>=', now()->toDateString())
            ->where('available_seats', '>=', $seats)
            ->when(! empty($criteria['departure_destination_id']), fn ($query) => $query->where('departure_destination_id', $criteria['departure_destination_id']))
            ->when(! empty($criteria['arrival_destination_id']), fn ($query) => $query->where('arrival_destination_id', $criteria['arrival_destination_id']))
            ->when(! empty($criteria['date']), fn ($query) => $query->whereDate('departure_date', $criteria['date']))
            ->with([
                'departureDestination:id,city,region,latitude,longitude',
                'arrivalDestination:id,city,region',
                'transporter:id,company_name',
                'vehicle:id,brand,model',
            ])
            ->orderBy('departure_date')
            ->orderBy('departure_time')
            ->get();

        $origin = Coordinates::tryFrom($criteria['latitude'] ?? null, $criteria['longitude'] ?? null);

        $results = $trips->map(fn (Trip $trip) => $this->present($trip, $origin));

        $results = match ((string) ($criteria['sort'] ?? 'relevance')) {
            'price' => $results->sortBy(fn ($result) => $result['price_per_seat']),
            'date' => $results->sortBy(fn ($result) => $result['date'].' '.$result['time']),
            default => $origin !== null
                ? $results->sortBy(fn ($result) => $result['distance_km'] ?? INF)
                : $results->sortBy(fn ($result) => $result['date'].' '.$result['time']),
        };

        if ($origin !== null && isset($criteria['radius']) && $criteria['radius'] !== '') {
            $radius = (float) $criteria['radius'];
            $results = $results->filter(
                fn ($result) => $result['distance_km'] !== null && $result['distance_km'] <= $radius,
            );
        }

        return $results->values()->all();
    }

    /**
     * Shape a trip into a public, privacy-safe search result.
     *
     * @return array{
     *     id: int,
     *     departure: array{city: string, region: string},
     *     arrival: array{city: string, region: string},
     *     date: string,
     *     time: string,
     *     price_per_seat: int,
     *     available_seats: int,
     *     transporter: array{company_name: string},
     *     vehicle: array{brand: string, model: string}|null,
     *     distance_km: float|null,
     * }
     */
    public function present(Trip $trip, ?Coordinates $origin): array
    {
        $departure = $trip->departureDestination;
        $arrival = $trip->arrivalDestination;

        $distance = null;
        if ($origin !== null && $departure->latitude !== null && $departure->longitude !== null) {
            $distance = round(
                $origin->distanceInKilometersTo(new Coordinates($departure->latitude, $departure->longitude)),
                1,
            );
        }

        return [
            'id' => $trip->id,
            'departure' => ['city' => $departure->city, 'region' => $departure->region],
            'arrival' => ['city' => $arrival->city, 'region' => $arrival->region],
            'date' => $trip->departure_date->toDateString(),
            'time' => substr($trip->departure_time, 0, 5),
            'price_per_seat' => $trip->price_per_seat,
            'available_seats' => $trip->available_seats,
            'transporter' => ['company_name' => $trip->transporter->company_name],
            'vehicle' => $trip->vehicle ? ['brand' => $trip->vehicle->brand, 'model' => $trip->vehicle->model] : null,
            'distance_km' => $distance,
        ];
    }
}
