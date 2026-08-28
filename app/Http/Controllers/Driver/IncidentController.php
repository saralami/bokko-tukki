<?php

namespace App\Http\Controllers\Driver;

use App\Enums\IncidentStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Driver\StoreIncidentRequest;
use App\Models\Trip;
use App\Models\TripIncident;
use App\Notifications\DriverIncidentReportedNotification;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

class IncidentController extends Controller
{
    /**
     * Report a problem on one of the driver's trips and alert the transporter.
     */
    public function store(StoreIncidentRequest $request): RedirectResponse
    {
        $driver = $request->user()->driver;

        abort_if($driver === null, 403);

        $data = $request->validated();

        /** @var Trip $trip */
        $trip = Trip::query()->with('transporter.user')->findOrFail($data['trip_id']);

        // A driver can only report incidents on trips assigned to them.
        abort_unless($trip->driver_id === $driver->id, 403);

        $incident = TripIncident::create([
            'trip_id' => $trip->id,
            'driver_id' => $driver->id,
            'category' => $data['category'],
            'message' => $data['message'],
            'status' => IncidentStatus::Open,
        ]);

        $incident->setRelation('driver', $driver);

        $trip->transporter->user->notify(new DriverIncidentReportedNotification($incident));

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Problème signalé au transporteur.']);

        return back();
    }
}
