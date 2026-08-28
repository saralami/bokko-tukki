<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Vehicle;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class VehicleController extends Controller
{
    /**
     * List vehicles across every transporter (read-only oversight).
     */
    public function index(Request $request): Response
    {
        $filters = $request->only(['search', 'status']);

        $vehicles = Vehicle::query()
            ->when($filters['search'] ?? null, fn ($query, $search) => $query
                ->where(fn ($q) => $q->where('registration', 'like', "%{$search}%")
                    ->orWhere('brand', 'like', "%{$search}%")
                    ->orWhere('model', 'like', "%{$search}%")))
            ->when($filters['status'] ?? null, fn ($query, $status) => $query->where('status', $status))
            ->with('transporter:id,company_name')
            ->latest()
            ->paginate(20)
            ->withQueryString()
            ->through(fn (Vehicle $vehicle): array => [
                'id' => $vehicle->id,
                'registration' => $vehicle->registration,
                'name' => $vehicle->brand.' '.$vehicle->model,
                'capacity' => $vehicle->capacity,
                'transporter' => $vehicle->transporter?->company_name,
                'status' => $vehicle->status->value,
                'status_label' => $vehicle->status->label(),
            ]);

        return Inertia::render('admin/vehicles/Index', [
            'vehicles' => $vehicles,
            'filters' => $filters,
        ]);
    }
}
