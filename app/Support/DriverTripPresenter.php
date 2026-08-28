<?php

namespace App\Support;

use App\Models\Booking;
use App\Models\Trip;

class DriverTripPresenter
{
    /**
     * Shape a trip for a driver list card.
     *
     * Expects the query to have loaded `departureDestination`, `arrivalDestination`,
     * `vehicle` and the `reservations_count` / `booked_seats` aggregates.
     *
     * @return array<string, mixed>
     */
    public static function summary(Trip $trip): array
    {
        return [
            'id' => $trip->id,
            'departure' => $trip->departureDestination?->city,
            'arrival' => $trip->arrivalDestination?->city,
            'route' => $trip->departureDestination?->city.' → '.$trip->arrivalDestination?->city,
            'date' => $trip->departure_date->toDateString(),
            'time' => substr($trip->departure_time, 0, 5),
            'vehicle' => $trip->vehicle ? $trip->vehicle->brand.' '.$trip->vehicle->model : null,
            'capacity' => $trip->capacity,
            'available_seats' => $trip->available_seats,
            'passengers' => (int) ($trip->booked_seats ?? 0),
            'reservations' => (int) ($trip->reservations_count ?? 0),
            'status' => $trip->status->value,
            'status_label' => $trip->status->label(),
        ];
    }

    /**
     * Shape a booking as a passenger row for the driver trip detail.
     *
     * @return array<string, mixed>
     */
    public static function passenger(Booking $booking): array
    {
        return [
            'id' => $booking->id,
            'reference' => $booking->reference,
            'name' => $booking->passenger->name,
            'seats' => $booking->seats,
            'status' => $booking->status->value,
            'status_label' => $booking->status->label(),
            'payment_method' => $booking->payment_method->value,
            'payment_method_label' => $booking->payment_method->label(),
            'boarded' => $booking->boarded_at !== null,
        ];
    }
}
