<?php

namespace App\Support;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Request;

class AuditLogger
{
    /**
     * Record a sensitive administrative operation in the append-only audit trail.
     *
     * @param  array<string, mixed>  $meta
     */
    public static function log(string $action, string $description, ?Model $subject = null, array $meta = [], ?User $actor = null): AuditLog
    {
        $actor ??= Request::user();

        return AuditLog::create([
            'user_id' => $actor?->id,
            'action' => $action,
            'auditable_type' => $subject !== null ? $subject::class : null,
            'auditable_id' => $subject?->getKey(),
            'description' => $description,
            'meta' => $meta,
            'ip_address' => Request::ip(),
        ]);
    }
}
