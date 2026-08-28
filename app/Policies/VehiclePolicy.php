<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\User;
use App\Models\Vehicle;

class VehiclePolicy
{
    /**
     * Determine whether the user can view a list of vehicles.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasUserRole(UserRole::Transporter);
    }

    /**
     * Determine whether the user can view the vehicle.
     */
    public function view(User $user, Vehicle $vehicle): bool
    {
        return $this->owns($user, $vehicle);
    }

    /**
     * Determine whether the user can create vehicles.
     */
    public function create(User $user): bool
    {
        return $user->hasUserRole(UserRole::Transporter);
    }

    /**
     * Determine whether the user can update the vehicle.
     */
    public function update(User $user, Vehicle $vehicle): bool
    {
        return $this->owns($user, $vehicle);
    }

    /**
     * Determine whether the user can delete the vehicle.
     */
    public function delete(User $user, Vehicle $vehicle): bool
    {
        return $this->owns($user, $vehicle);
    }

    /**
     * Determine whether the vehicle belongs to the user's transporter company.
     */
    private function owns(User $user, Vehicle $vehicle): bool
    {
        return $user->transporter !== null
            && $user->transporter->id === $vehicle->transporter_id;
    }
}
