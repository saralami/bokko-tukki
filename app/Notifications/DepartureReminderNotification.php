<?php

namespace App\Notifications;

use App\Models\Booking;
use App\Models\Trip;

class DepartureReminderNotification extends BaseNotification
{
    public function __construct(private Trip $trip, private Booking $booking) {}

    protected function subject(): string
    {
        return 'Rappel de départ';
    }

    protected function content(): string
    {
        $date = $this->trip->departure_date->toDateString();
        $time = substr($this->trip->departure_time, 0, 5);

        return "Rappel : votre trajet (réservation {$this->booking->reference}) part le {$date} à {$time}.";
    }

    /**
     * @return array<string, mixed>
     */
    protected function payload(): array
    {
        return ['trip_id' => $this->trip->id, 'booking_id' => $this->booking->id];
    }
}
