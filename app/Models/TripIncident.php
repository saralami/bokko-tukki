<?php

namespace App\Models;

use App\Enums\IncidentCategory;
use App\Enums\IncidentStatus;
use Database\Factories\TripIncidentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $trip_id
 * @property int $driver_id
 * @property IncidentCategory $category
 * @property string $message
 * @property IncidentStatus $status
 * @property Carbon|null $resolved_at
 */
#[Fillable(['trip_id', 'driver_id', 'category', 'message', 'status', 'resolved_at'])]
class TripIncident extends Model
{
    /** @use HasFactory<TripIncidentFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'category' => IncidentCategory::class,
            'status' => IncidentStatus::class,
            'resolved_at' => 'datetime',
        ];
    }

    /**
     * Get the trip the incident was reported on.
     *
     * @return BelongsTo<Trip, $this>
     */
    public function trip(): BelongsTo
    {
        return $this->belongsTo(Trip::class);
    }

    /**
     * Get the driver who reported the incident.
     *
     * @return BelongsTo<Driver, $this>
     */
    public function driver(): BelongsTo
    {
        return $this->belongsTo(Driver::class);
    }
}
