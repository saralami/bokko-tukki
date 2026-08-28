<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AuditLogController extends Controller
{
    /**
     * List the append-only audit trail of sensitive operations.
     */
    public function index(Request $request): Response
    {
        $filters = $request->only(['action']);

        $logs = AuditLog::query()
            ->when($filters['action'] ?? null, fn ($query, $action) => $query->where('action', $action))
            ->with('user:id,name')
            ->latest()
            ->paginate(30)
            ->withQueryString()
            ->through(fn (AuditLog $log): array => [
                'id' => $log->id,
                'action' => $log->action,
                'description' => $log->description,
                'user' => $log->user?->name,
                'ip' => $log->ip_address,
                'date' => $log->created_at?->toIso8601String(),
            ]);

        return Inertia::render('admin/AuditLogs', [
            'logs' => $logs,
            'filters' => $filters,
            'actions' => AuditLog::query()->distinct()->orderBy('action')->pluck('action')->all(),
        ]);
    }
}
