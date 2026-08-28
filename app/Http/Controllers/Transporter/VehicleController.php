<?php

namespace App\Http\Controllers\Transporter;

use App\Enums\VehicleStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreVehicleRequest;
use App\Http\Requests\UpdateVehicleRequest;
use App\Models\Driver;
use App\Models\Transporter;
use App\Models\Vehicle;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class VehicleController extends Controller
{
    /**
     * Display the authenticated transporter's fleet.
     */
    public function index(Request $request): Response
    {
        $transporter = $this->transporterFor($request);

        return Inertia::render('transporter/vehicles/Index', [
            'vehicles' => $transporter->vehicles()->with('driver')->latest()->get(),
        ]);
    }

    /**
     * Show the form for creating a new vehicle.
     */
    public function create(Request $request): Response
    {
        return Inertia::render('transporter/vehicles/Create', [
            'drivers' => $this->driverOptions($request),
            'statuses' => VehicleStatus::values(),
        ]);
    }

    /**
     * Store a newly created vehicle for the transporter.
     */
    public function store(StoreVehicleRequest $request): RedirectResponse
    {
        $transporter = $this->transporterFor($request);

        $data = $request->validated();
        $data['status'] ??= VehicleStatus::Active->value;

        $transporter->vehicles()->create($data);

        return to_route('transporter.vehicles.index');
    }

    /**
     * Show the form for editing the vehicle.
     */
    public function edit(Request $request, Vehicle $vehicle): Response
    {
        Gate::authorize('view', $vehicle);

        return Inertia::render('transporter/vehicles/Edit', [
            'vehicle' => $vehicle,
            'drivers' => $this->driverOptions($request),
            'statuses' => VehicleStatus::values(),
        ]);
    }

    /**
     * Update the given vehicle.
     */
    public function update(UpdateVehicleRequest $request, Vehicle $vehicle): RedirectResponse
    {
        $vehicle->update($request->validated());

        return to_route('transporter.vehicles.index');
    }

    /**
     * Toggle or set the vehicle status (activation / deactivation).
     */
    public function updateStatus(Request $request, Vehicle $vehicle): RedirectResponse
    {
        Gate::authorize('update', $vehicle);

        $validated = $request->validate([
            'status' => ['required', Rule::enum(VehicleStatus::class)],
        ]);

        $vehicle->update($validated);

        return back();
    }

    /**
     * Soft delete the given vehicle.
     */
    public function destroy(Request $request, Vehicle $vehicle): RedirectResponse
    {
        Gate::authorize('delete', $vehicle);

        $vehicle->delete();

        return to_route('transporter.vehicles.index');
    }

    /**
     * Resolve the transporter company owned by the authenticated user.
     */
    private function transporterFor(Request $request): Transporter
    {
        return $request->user()->transporter ?? abort(403);
    }

    /**
     * Get the transporter's drivers usable for vehicle assignment.
     *
     * @return Collection<int, Driver>
     */
    private function driverOptions(Request $request): Collection
    {
        return $this->transporterFor($request)
            ->drivers()
            ->get(['id', 'first_name', 'last_name']);
    }
}
