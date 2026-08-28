<?php

namespace App\Http\Controllers\Transporter;

use App\Enums\DriverStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreDriverRequest;
use App\Http\Requests\UpdateDriverRequest;
use App\Models\Driver;
use App\Models\Transporter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class DriverController extends Controller
{
    /**
     * Display the authenticated transporter's drivers.
     */
    public function index(Request $request): Response
    {
        $transporter = $this->transporterFor($request);

        return Inertia::render('transporter/drivers/Index', [
            'drivers' => $transporter->drivers()
                ->with('vehicles:id,driver_id,registration')
                ->latest()
                ->get(),
        ]);
    }

    /**
     * Show the form for creating a new driver.
     */
    public function create(): Response
    {
        return Inertia::render('transporter/drivers/Create', [
            'statuses' => DriverStatus::values(),
        ]);
    }

    /**
     * Store a newly created driver for the transporter.
     */
    public function store(StoreDriverRequest $request): RedirectResponse
    {
        $transporter = $this->transporterFor($request);

        $data = $request->validated();
        $data['status'] ??= DriverStatus::Active->value;

        $transporter->drivers()->create($data);

        return to_route('transporter.drivers.index');
    }

    /**
     * Show the form for editing the driver.
     */
    public function edit(Request $request, Driver $driver): Response
    {
        Gate::authorize('view', $driver);

        return Inertia::render('transporter/drivers/Edit', [
            'driver' => $driver,
            'statuses' => DriverStatus::values(),
        ]);
    }

    /**
     * Update the given driver.
     */
    public function update(UpdateDriverRequest $request, Driver $driver): RedirectResponse
    {
        $driver->update($request->validated());

        return to_route('transporter.drivers.index');
    }

    /**
     * Set the driver status (active / inactive / suspended).
     */
    public function updateStatus(Request $request, Driver $driver): RedirectResponse
    {
        Gate::authorize('update', $driver);

        $validated = $request->validate([
            'status' => ['required', Rule::enum(DriverStatus::class)],
        ]);

        $driver->update($validated);

        return back();
    }

    /**
     * Delete the given driver.
     */
    public function destroy(Request $request, Driver $driver): RedirectResponse
    {
        Gate::authorize('delete', $driver);

        $driver->delete();

        return to_route('transporter.drivers.index');
    }

    /**
     * Resolve the transporter company owned by the authenticated user.
     */
    private function transporterFor(Request $request): Transporter
    {
        return $request->user()->transporter ?? abort(403);
    }
}
