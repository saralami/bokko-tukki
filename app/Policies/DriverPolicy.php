<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Driver;
use App\Models\User;

class DriverPolicy
{
    /**
     * Determine whether the user can view a list of drivers.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasUserRole(UserRole::Transporter);
    }

    /**
     * Determine whether the user can view the driver.
     */
    public function view(User $user, Driver $driver): bool
    {
        return $this->owns($user, $driver);
    }

    /**
     * Determine whether the user can create drivers.
     */
    public function create(User $user): bool
    {
        return $user->hasUserRole(UserRole::Transporter);
    }

    /**
     * Determine whether the user can update the driver.
     */
    public function update(User $user, Driver $driver): bool
    {
        return $this->owns($user, $driver);
    }

    /**
     * Determine whether the user can delete the driver.
     */
    public function delete(User $user, Driver $driver): bool
    {
        return $this->owns($user, $driver);
    }

    /**
     * Determine whether the driver belongs to the user's transporter company.
     */
    private function owns(User $user, Driver $driver): bool
    {
        return $user->transporter !== null
            && $user->transporter->id === $driver->transporter_id;
    }
}
