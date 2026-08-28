<?php

namespace App\Policies;

use App\Models\Transporter;
use App\Models\User;

class TransporterPolicy
{
    /**
     * Determine whether the user can view the transporter.
     */
    public function view(User $user, Transporter $transporter): bool
    {
        return $user->id === $transporter->user_id;
    }

    /**
     * Determine whether the user can update the transporter.
     */
    public function update(User $user, Transporter $transporter): bool
    {
        return $user->id === $transporter->user_id;
    }

    /**
     * Determine whether the user can delete the transporter.
     */
    public function delete(User $user, Transporter $transporter): bool
    {
        return $user->id === $transporter->user_id;
    }
}
