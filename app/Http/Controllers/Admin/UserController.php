<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    /**
     * List platform users with role and status filters.
     */
    public function index(Request $request): Response
    {
        $filters = $request->only(['search', 'role', 'status']);

        $users = User::query()
            ->when($filters['search'] ?? null, fn ($query, $search) => $query
                ->where(fn ($q) => $q->where('name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%")))
            ->when($filters['role'] ?? null, fn ($query, $role) => $query
                ->whereHas('roles', fn ($q) => $q->where('name', $role)))
            ->when(($filters['status'] ?? null) === 'suspended', fn ($query) => $query->whereNotNull('suspended_at'))
            ->when(($filters['status'] ?? null) === 'active', fn ($query) => $query->whereNull('suspended_at'))
            ->with('roles:id,name')
            ->latest()
            ->paginate(20)
            ->withQueryString()
            ->through(fn (User $user): array => $this->summary($user));

        return Inertia::render('admin/users/Index', [
            'users' => $users,
            'filters' => $filters,
            'roles' => UserRole::values(),
        ]);
    }

    /**
     * Show a single user with their platform footprint.
     */
    public function show(User $user): Response
    {
        $user->load('roles:id,name', 'transporter:id,user_id,company_name,status', 'driver:id,user_id,first_name,last_name,status');

        return Inertia::render('admin/users/Show', [
            'user' => [
                ...$this->summary($user),
                'phone' => $user->phone,
                'email_verified' => $user->email_verified_at !== null,
                'bookings' => $user->bookings()->count(),
                'transporter' => $user->transporter ? ['id' => $user->transporter->id, 'company_name' => $user->transporter->company_name] : null,
                'driver' => $user->driver ? ['id' => $user->driver->id, 'name' => $user->driver->full_name] : null,
            ],
            'roles' => UserRole::values(),
        ]);
    }

    /**
     * Suspend or reinstate a user account.
     */
    public function toggleSuspension(Request $request, User $user): RedirectResponse
    {
        if ($user->id === $request->user()->id) {
            throw ValidationException::withMessages(['user' => 'Vous ne pouvez pas suspendre votre propre compte.']);
        }

        $suspend = $user->suspended_at === null;
        $user->forceFill(['suspended_at' => $suspend ? now() : null])->save();

        AuditLogger::log(
            $suspend ? 'user.suspended' : 'user.reinstated',
            ($suspend ? 'Suspension' : 'Réactivation')." du compte {$user->email}.",
            $user,
        );

        return back();
    }

    /**
     * Change the application role of a user.
     */
    public function updateRole(Request $request, User $user): RedirectResponse
    {
        if ($user->id === $request->user()->id) {
            throw ValidationException::withMessages(['role' => 'Vous ne pouvez pas modifier votre propre rôle.']);
        }

        $validated = $request->validate([
            'role' => ['required', Rule::enum(UserRole::class)],
        ]);

        $previous = $user->getRoleNames()->first();
        $user->syncRoles([$validated['role']]);

        AuditLogger::log(
            'user.role_changed',
            "Rôle de {$user->email} : {$previous} → {$validated['role']}.",
            $user,
            ['from' => $previous, 'to' => $validated['role']],
        );

        return back();
    }

    /**
     * Shape a user for the list / detail view.
     *
     * @return array<string, mixed>
     */
    private function summary(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'roles' => $user->getRoleNames()->all(),
            'suspended' => $user->suspended_at !== null,
            'created_at' => $user->created_at?->toIso8601String(),
        ];
    }
}
