<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Driver;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DriverController extends Controller
{
    /**
     * List drivers across every transporter (read-only oversight).
     */
    public function index(Request $request): Response
    {
        $filters = $request->only(['search', 'status']);

        $drivers = Driver::query()
            ->when($filters['search'] ?? null, fn ($query, $search) => $query
                ->where(fn ($q) => $q->where('first_name', 'like', "%{$search}%")->orWhere('last_name', 'like', "%{$search}%")))
            ->when($filters['status'] ?? null, fn ($query, $status) => $query->where('status', $status))
            ->with('transporter:id,company_name')
            ->withCount('trips')
            ->latest()
            ->paginate(20)
            ->withQueryString()
            ->through(fn (Driver $driver): array => [
                'id' => $driver->id,
                'name' => $driver->full_name,
                'phone' => $driver->phone,
                'transporter' => $driver->transporter?->company_name,
                'status' => $driver->status->value,
                'status_label' => $driver->status->label(),
                'trips' => $driver->trips_count ?? 0,
                'linked' => $driver->user_id !== null,
            ]);

        return Inertia::render('admin/drivers/Index', [
            'drivers' => $drivers,
            'filters' => $filters,
        ]);
    }
}
