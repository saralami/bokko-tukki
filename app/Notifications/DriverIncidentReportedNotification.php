<?php

namespace App\Notifications;

use App\Models\TripIncident;

class DriverIncidentReportedNotification extends BaseNotification
{
    public function __construct(private TripIncident $incident) {}

    protected function subject(): string
    {
        return 'Incident signalé par un chauffeur';
    }

    protected function content(): string
    {
        return "{$this->incident->driver->full_name} a signalé un problème ({$this->incident->category->label()}) sur un de vos trajets.";
    }

    /**
     * @return array<string, mixed>
     */
    protected function payload(): array
    {
        return [
            'incident_id' => $this->incident->id,
            'trip_id' => $this->incident->trip_id,
            'category' => $this->incident->category->value,
        ];
    }
}
