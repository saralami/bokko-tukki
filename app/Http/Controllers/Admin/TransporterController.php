<?php

namespace App\Http\Controllers\Admin;

use App\Enums\TransporterStatus;
use App\Http\Controllers\Controller;
use App\Models\Transporter;
use App\Support\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class TransporterController extends Controller
{
    /**
     * List transporters with wallet and activity indicators.
     */
    public function index(Request $request): Response
    {
        $filters = $request->only(['search', 'status']);

        $transporters = Transporter::query()
            ->when($filters['search'] ?? null, fn ($query, $search) => $query->where('company_name', 'like', "%{$search}%"))
            ->when($filters['status'] ?? null, fn ($query, $status) => $query->where('status', $status))
            ->with('wallet:id,transporter_id,available_balance,outstanding_debt')
            ->withCount(['drivers', 'vehicles', 'trips'])
            ->latest()
            ->paginate(20)
            ->withQueryString()
            ->through(fn (Transporter $transporter): array => $this->summary($transporter));

        return Inertia::render('admin/transporters/Index', [
            'transporters' => $transporters,
            'filters' => $filters,
            'statuses' => TransporterStatus::values(),
        ]);
    }

    /**
     * Show a single transporter with its wallet and fleet.
     */
    public function show(Transporter $transporter): Response
    {
        $transporter->load('user:id,name,email,phone', 'wallet');
        $transporter->loadCount(['drivers', 'vehicles', 'trips']);

        return Inertia::render('admin/transporters/Show', [
            'transporter' => [
                ...$this->summary($transporter),
                'owner' => $transporter->user ? ['name' => $transporter->user->name, 'email' => $transporter->user->email, 'phone' => $transporter->user->phone] : null,
            ],
            'statuses' => TransporterStatus::values(),
        ]);
    }

    /**
     * Change a transporter's activation status.
     */
    public function updateStatus(Request $request, Transporter $transporter): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', Rule::enum(TransporterStatus::class)],
        ]);

        $previous = $transporter->status->value;
        $transporter->update(['status' => $validated['status']]);

        AuditLogger::log(
            'transporter.status_changed',
            "Statut du transporteur {$transporter->company_name} : {$previous} → {$validated['status']}.",
            $transporter,
            ['from' => $previous, 'to' => $validated['status']],
        );

        return back();
    }

    /**
     * Shape a transporter for the list / detail view.
     *
     * @return array<string, mixed>
     */
    private function summary(Transporter $transporter): array
    {
        return [
            'id' => $transporter->id,
            'company_name' => $transporter->company_name,
            'status' => $transporter->status->value,
            'status_label' => $transporter->status->label(),
            'available_balance' => $transporter->wallet->available_balance,
            'outstanding_debt' => $transporter->wallet->outstanding_debt,
            'drivers' => $transporter->drivers_count ?? 0,
            'vehicles' => $transporter->vehicles_count ?? 0,
            'trips' => $transporter->trips_count ?? 0,
        ];
    }
}
