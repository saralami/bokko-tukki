<?php

namespace App\Http\Controllers\Admin;

use App\Enums\DestinationStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreDestinationRequest;
use App\Http\Requests\UpdateDestinationRequest;
use App\Models\Destination;
use App\Models\Trip;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class DestinationController extends Controller
{
    /**
     * Display the destination catalogue.
     */
    public function index(): Response
    {
        return Inertia::render('admin/destinations/Index', [
            'destinations' => Destination::query()->orderBy('region')->orderBy('city')->get(),
        ]);
    }

    /**
     * Show the form for creating a new destination.
     */
    public function create(): Response
    {
        return Inertia::render('admin/destinations/Create', [
            'statuses' => DestinationStatus::values(),
        ]);
    }

    /**
     * Store a newly created destination.
     */
    public function store(StoreDestinationRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['status'] ??= DestinationStatus::Active->value;

        Destination::create($data);

        return to_route('admin.destinations.index');
    }

    /**
     * Show the form for editing the destination.
     */
    public function edit(Destination $destination): Response
    {
        return Inertia::render('admin/destinations/Edit', [
            'destination' => $destination,
            'statuses' => DestinationStatus::values(),
        ]);
    }

    /**
     * Update the given destination.
     */
    public function update(UpdateDestinationRequest $request, Destination $destination): RedirectResponse
    {
        $destination->update($request->validated());

        return to_route('admin.destinations.index');
    }

    /**
     * Delete the given destination when it is not used by any trip.
     */
    public function destroy(Destination $destination): RedirectResponse
    {
        $inUse = Trip::query()
            ->where('departure_destination_id', $destination->id)
            ->orWhere('arrival_destination_id', $destination->id)
            ->exists();

        if ($inUse) {
            throw ValidationException::withMessages([
                'destination' => 'Cette destination est utilisée par des trajets et ne peut pas être supprimée.',
            ]);
        }

        $destination->delete();

        return to_route('admin.destinations.index');
    }
}
