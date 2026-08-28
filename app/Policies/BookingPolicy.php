<?php

namespace App\Policies;

use App\Models\Booking;
use App\Models\User;

class BookingPolicy
{
    /**
     * Determine whether the user can view the booking.
     */
    public function view(User $user, Booking $booking): bool
    {
        return $user->id === $booking->passenger_id;
    }

    /**
     * Determine whether the user can cancel the booking.
     */
    public function cancel(User $user, Booking $booking): bool
    {
        return $user->id === $booking->passenger_id;
    }

    /**
     * Determine whether the user can confirm the passenger boarding.
     *
     * Allowed for the trip's transporter owner and for the driver assigned to the trip.
     */
    public function confirmBoarding(User $user, Booking $booking): bool
    {
        $trip = $booking->trip;

        if ($user->transporter !== null && $user->transporter->id === $trip->transporter_id) {
            return true;
        }

        $driver = $trip->driver;

        return $driver !== null && $driver->user_id === $user->id;
    }
}
