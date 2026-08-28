<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Trip;
use App\Models\User;

class TripPolicy
{
    /**
     * Determine whether the user can view a list of trips.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasUserRole(UserRole::Transporter);
    }

    /**
     * Determine whether the user can view the trip.
     */
    public function view(User $user, Trip $trip): bool
    {
        return $this->owns($user, $trip);
    }

    /**
     * Determine whether the user can create trips.
     */
    public function create(User $user): bool
    {
        return $user->hasUserRole(UserRole::Transporter);
    }

    /**
     * Determine whether the user can update the trip.
     */
    public function update(User $user, Trip $trip): bool
    {
        return $this->owns($user, $trip);
    }

    /**
     * Determine whether the user can delete the trip.
     */
    public function delete(User $user, Trip $trip): bool
    {
        return $this->owns($user, $trip);
    }

    /**
     * Determine whether the trip belongs to the user's transporter company.
     */
    private function owns(User $user, Trip $trip): bool
    {
        return $user->transporter !== null
            && $user->transporter->id === $trip->transporter_id;
    }
}
