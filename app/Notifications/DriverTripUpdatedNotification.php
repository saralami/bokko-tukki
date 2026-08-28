<?php

namespace App\Notifications;

use App\Models\Trip;

class DriverTripUpdatedNotification extends BaseNotification
{
    public function __construct(private Trip $trip) {}

    protected function subject(): string
    {
        return 'Trajet modifié';
    }

    protected function content(): string
    {
        $date = $this->trip->departure_date->toDateString();
        $time = substr($this->trip->departure_time, 0, 5);

        return "Votre trajet a été modifié : départ le {$date} à {$time}.";
    }

    /**
     * @return array<string, mixed>
     */
    protected function payload(): array
    {
        return ['trip_id' => $this->trip->id];
    }
}
