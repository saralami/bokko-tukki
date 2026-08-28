<?php

namespace App\Events;

use App\Models\Booking;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * Fired once a passenger boarding has been confirmed: the reservation has
 * genuinely been executed (seat consumed, trip honoured). This is the domain
 * signal other parts of the system can react to (analytics, ratings, etc.).
 */
class BookingBoarded
{
    use Dispatchable;

    public function __construct(public Booking $booking) {}
}
