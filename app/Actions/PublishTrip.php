<?php

namespace App\Actions;

use App\Enums\DestinationStatus;
use App\Enums\DriverStatus;
use App\Enums\TransporterStatus;
use App\Enums\TripStatus;
use App\Enums\VehicleStatus;
use App\Models\Trip;
use Illuminate\Validation\ValidationException;

class PublishTrip
{
    /**
     * Publish the given draft trip after validating every business rule.
     *
     * @throws ValidationException
     */
    public function __invoke(Trip $trip): Trip
    {
        if ($trip->status !== TripStatus::Draft) {
            throw ValidationException::withMessages([
                'status' => 'Seul un trajet en brouillon peut être publié.',
            ]);
        }

        if ($trip->transporter->status !== TransporterStatus::Active) {
            throw ValidationException::withMessages([
                'status' => 'Publication bloquée : votre compte transporteur est en attente de validation par un administrateur.',
            ]);
        }

        if ($trip->transporter->hasExceededDebtCeiling()) {
            throw ValidationException::withMessages([
                'status' => 'Publication bloquée : la dette de commission dépasse le seuil autorisé.',
            ]);
        }

        $trip->loadMissing(['vehicle', 'driver', 'departureDestination', 'arrivalDestination']);

        $errors = [];

        if ($trip->vehicle === null) {
            $errors['vehicle_id'] = 'Un véhicule est requis pour publier le trajet.';
        } elseif ($trip->vehicle->status !== VehicleStatus::Active) {
            $errors['vehicle_id'] = 'Le véhicule doit être actif.';
        }

        if ($trip->driver === null) {
            $errors['driver_id'] = 'Un chauffeur est requis pour publier le trajet.';
        } elseif ($trip->driver->status !== DriverStatus::Active) {
            $errors['driver_id'] = 'Le chauffeur doit être actif.';
        }

        if ($trip->departureDestination === null || $trip->departureDestination->status !== DestinationStatus::Active) {
            $errors['departure_destination_id'] = 'Le point de départ doit être une destination active.';
        }

        if ($trip->arrivalDestination === null || $trip->arrivalDestination->status !== DestinationStatus::Active) {
            $errors['arrival_destination_id'] = 'La destination doit être active.';
        }

        if ($trip->price_per_seat < 1) {
            $errors['price_per_seat'] = 'Le prix par place est obligatoire.';
        }

        if ($trip->vehicle !== null && $trip->capacity > $trip->vehicle->capacity) {
            $errors['capacity'] = 'La capacité du trajet dépasse celle du véhicule.';
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }

        $trip->update([
            'status' => TripStatus::Published,
            'capacity' => $trip->vehicle->capacity,
            'available_seats' => $trip->vehicle->capacity,
        ]);

        return $trip;
    }
}
